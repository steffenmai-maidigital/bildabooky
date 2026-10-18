<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookLog;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/** Seiten — wird vom Editor per fetch() aufgerufen / Pages — called by the editor via fetch() */
class PageController extends Controller
{
    public function store(Request $request, Book $book)
    {
        abort_unless($book->editableBy($request->user()), 403);
        abort_if($book->pages()->count() >= 200, 422, __('Ein Buch kann höchstens 200 Seiten haben.'));

        $data = $request->validate([
            'after' => ['nullable', 'integer'],   // nach dieser Seite einfügen / insert after this page
            'copy'  => ['nullable', 'integer'],   // diese Seite kopieren / copy this page
        ]);

        $page = DB::transaction(function () use ($book, $data) {
            $pages = $book->pages()->get();
            $index = $pages->search(fn ($p) => $p->id == ($data['after'] ?? 0));
            $index = $index === false ? $pages->count() : $index + 1;

            $source = isset($data['copy']) ? $pages->firstWhere('id', $data['copy']) : null;
            $elements = $source ? array_map(function ($e) {
                $e['id'] = 'e' . bin2hex(random_bytes(5));
                return $e;
            }, $source->elementList()) : [];

            $page = $book->pages()->create([
                'position'   => $index,
                'background' => $source->background ?? '#ffffff',
                'elements'   => json_encode($elements),
            ]);
            $pages->splice($index, 0, [$page]);
            $this->renumber($pages);
            return $page;
        });
        $book->touch();
        BookLog::record($book, $request->user(), 'page_added', [], $page);

        $index = $book->pages()->pluck('id')->search($page->id);
        return response()->json(['page' => $page->fresh()->toEditorArray(), 'index' => $index]);
    }

    /**
     * Seite speichern. Mit „version“ wird geprüft, ob inzwischen jemand anderes
     * gespeichert hat (Klassenbuch): dann 409 mit dem aktuellen Stand — der
     * Editor führt beide Fassungen zusammen und speichert erneut.
     *
     * Save a page. With “version” it checks whether someone else has saved
     * in the meantime (class book): then 409 with the current state — the
     * editor merges both versions and saves again.
     */
    public function update(Request $request, Book $book, Page $page)
    {
        abort_unless($page->book_id == $book->id && $book->editableBy($request->user()), 403);

        $data = $request->validate([
            'background' => ['nullable', 'string', 'max:20'],
            'elements'   => ['present', 'array', 'max:300'],
            'version'    => ['nullable', 'integer'],
        ]);

        $elements = array_values(array_filter(array_map([Page::class, 'sanitizeElement'], $data['elements'])));
        $json = json_encode($elements, JSON_UNESCAPED_UNICODE);
        abort_if(strlen($json) > 1_000_000, 413, __('Die Seite ist zu groß.'));
        $background = Page::color($data['background'] ?? null, '#ffffff');

        $result = DB::transaction(function () use ($page, $data, $json, $background, $elements) {
            $current = Page::lockForUpdate()->find($page->id);
            if (isset($data['version']) && (int) $data['version'] !== (int) $current->version) {
                return ['conflict' => $current];
            }
            $old = ['elements' => $current->elementList(), 'background' => $current->background];
            $current->update([
                'background' => $background,
                'elements'   => $json,
                'version'    => $current->version + 1,
            ]);
            return ['page' => $current, 'old' => $old];
        });

        if (isset($result['conflict'])) {
            return response()->json(['conflict' => true, 'page' => $result['conflict']->toEditorArray()], 409);
        }

        $book->touch();
        $diff = BookLog::diff($result['old']['elements'], $elements);
        if ($result['old']['background'] !== $background) {
            $diff['background'] = $background;
        }
        if (! BookLog::diffIsEmpty($diff)) {
            BookLog::record($book, $request->user(), 'page', $diff, $page, [BookLog::class, 'diffSum']);
        }

        return response()->json(['ok' => true, 'version' => (int) $result['page']->version]);
    }

    public function destroy(Request $request, Book $book, Page $page)
    {
        abort_unless($page->book_id == $book->id && $book->manageableBy($request->user()), 403);
        abort_if($book->pages()->count() <= 1, 422, __('Ein Buch braucht mindestens eine Seite.'));
        BookLog::record($book, $request->user(), 'page_removed', [], $page);
        $page->delete();
        $this->renumber($book->pages()->get());
        $book->touch();
        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, Book $book)
    {
        abort_unless($book->manageableBy($request->user()), 403);
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        $pages = $book->pages()->get()->keyBy('id');
        abort_unless(count($ids) === $pages->count() && collect($ids)->every(fn ($id) => $pages->has($id)), 422);
        foreach ($ids as $pos => $id) {
            $pages[$id]->update(['position' => $pos]);
        }
        $book->touch();
        BookLog::record($book, $request->user(), 'order', [], null,
            fn ($old, $new) => $old);   // mehrfaches Verschieben = ein Eintrag / several moves = one entry
        return response()->json(['ok' => true]);
    }

    /**
     * Abgleich im Klassenbuch (alle paar Sekunden vom Editor):
     *  - Reihenfolge und Versionen aller Seiten
     *  - vollständiger Inhalt nur der Seiten, die der Editor noch nicht kennt
     *  - wer gerade auf welcher Seite arbeitet
     *
     * Sync in the class book (every few seconds from the editor):
     *  - order and versions of all pages
     *  - full content only of the pages the editor does not yet know
     *  - who is currently working on which page
     */
    public function state(Request $request, Book $book)
    {
        $user = $request->user();
        abort_unless($book->editableBy($user), 403);

        // bekannt: "id:version,id:version"
        // known: "id:version,id:version"
        $known = [];
        foreach (array_filter(explode(',', (string) $request->query('v'))) as $pair) {
            [$id, $v] = array_pad(explode(':', $pair), 2, 0);
            $known[(int) $id] = (int) $v;
        }

        // Anwesenheit: kurz im Cache merken
        // Presence: remember briefly in the cache
        $key = 'presence:' . $book->id;
        $now = time();
        $present = array_filter(Cache::get($key, []), fn ($p) => $p['t'] > $now - 15);
        $present[$user->id] = ['name' => $user->first_name, 'page' => (int) ($request->query('page') ?? $request->query('seite')), 't' => $now];
        Cache::put($key, $present, 60);

        $pages = $book->pages()->get();
        return response()->json([
            'pages'   => $pages->map(fn ($p) => ['id' => $p->id, 'version' => (int) $p->version])->values(),
            'changed' => $pages->filter(fn ($p) => ($known[$p->id] ?? -1) !== (int) $p->version)
                ->map->toEditorArray()->values(),
            'others'   => collect($present)->except($user->id)
                ->map(fn ($p) => ['name' => $p['name'], 'page' => $p['page']])->values(),
            'title'    => $book->title,
        ]);
    }

    private function renumber($pages): void
    {
        foreach ($pages->values() as $i => $p) {
            if ($p->position !== $i) {
                $p->update(['position' => $i]);
            }
        }
    }
}
