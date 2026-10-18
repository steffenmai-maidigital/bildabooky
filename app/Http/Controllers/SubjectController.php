<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Fächer anlegen, umbenennen, sortieren, löschen (nur Admin)
 *
 * Create, rename, sort and delete subjects (admin only)
 */
class SubjectController extends Controller
{
    private function rules(?Subject $subject = null): array
    {
        return [
            'name'   => ['required', 'string', 'max:60', Rule::unique('subjects', 'name')->ignore($subject?->id)],
            'symbol' => ['nullable', 'string', 'max:16', 'regex:/^[^<>&"\'\s]+$/u'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        Subject::create([
            'name'     => trim($data['name']),
            'symbol'   => $data['symbol'] ?: '📘',
            'position' => (int) Subject::max('position') + 1,
        ]);
        return back()->with('ok', __('Fach „:name“ angelegt.', ['name' => trim($data['name'])]));
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $request->validate($this->rules($subject));
        $subject->update(['name' => trim($data['name']), 'symbol' => $data['symbol'] ?: $subject->symbol]);
        return back()->with('ok', __('Gespeichert.'));
    }

    /** eine Stelle nach oben (-1) oder unten (+1) / one position up (-1) or down (+1) */
    public function move(Request $request, Subject $subject)
    {
        $direction = $request->integer('direction') < 0 ? -1 : 1;
        $list = Subject::sorted()->get()->values();
        $i = $list->search(fn ($s) => $s->id === $subject->id);
        $j = $i + $direction;
        if ($i !== false && $j >= 0 && $j < $list->count()) {
            $swap = $list[$i];
            $list[$i] = $list[$j];
            $list[$j] = $swap;
        }
        $list->each(fn ($s, $pos) => $s->position !== $pos ? $s->update(['position' => $pos]) : null);
        return back();
    }

    public function destroy(Subject $subject)
    {
        $name = $subject->name;
        // Bücher behalten ihren Inhalt und stehen danach unter „Ohne Fach“
        // books keep their content and then appear under “No subject”
        $subject->delete();
        return back()->with('ok', __('Fach „:name“ gelöscht. Bücher dieses Fachs stehen jetzt unter „Ohne Fach“.', ['name' => $name]));
    }
}
