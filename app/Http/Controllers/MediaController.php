<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fotos, Zeichnungen und Tonaufnahmen. Die Dateien liegen außerhalb von
 * public/ und werden nur an Personen ausgeliefert, die das Buch sehen dürfen.
 *
 * Photos, drawings and sound recordings. The files are stored outside
 * public/ and are only delivered to people who are allowed to see the book.
 */
class MediaController extends Controller
{
    private const IMAGE = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    // Tonaufnahmen: je nach Browser WebM/Opus (Chrome, Firefox) oder MP4/AAC (Safari).
    // finfo erkennt reine Tonspuren in diesen Containern teils als video/*.
    // Sound recordings: depending on the browser WebM/Opus (Chrome, Firefox) or MP4/AAC (Safari).
    // finfo sometimes detects pure audio tracks in these containers as video/*.
    private const AUDIO = ['audio/webm' => 'webm', 'video/webm' => 'webm', 'audio/ogg' => 'ogg',
        'audio/mp4' => 'm4a', 'video/mp4' => 'm4a', 'audio/x-m4a' => 'm4a', 'audio/aac' => 'aac',
        'audio/mpeg' => 'mp3', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav'];

    public function store(Request $request, Book $book)
    {
        abort_unless($book->editableBy($request->user()), 403);
        $request->validate([
            'file' => ['required', 'file', 'max:' . config('bildabooky.upload_kb')],
            'type' => ['required', 'in:image,audio'],
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $type = $request->input('type');
        $table = $type === 'image' ? self::IMAGE : self::AUDIO;
        if (! isset($table[$mime])) {
            return response()->json(['message' => __('Dieser Dateityp wird nicht unterstützt (:typ).', ['typ' => $mime])], 422);
        }
        if ($type === 'audio') {
            $mime = str_replace('video/', 'audio/', $mime);
        }

        $path = $file->storeAs((string) $book->id, Str::uuid() . '.' . $table[$file->getMimeType()], 'media');
        abort_unless($path, 500, __('Datei konnte nicht gespeichert werden.'));

        $media = $book->media()->create([
            'user_id' => $request->user()->id,
            'kind'    => $type,
            'path'    => $path,
            'mime'    => $mime,
            'size'    => $file->getSize(),
        ]);

        return response()->json(['id' => $media->id, 'url' => route('media.show', $media)]);
    }

    public function show(Request $request, Media $media)
    {
        abort_unless($media->book && $media->book->visibleTo($request->user()), 404);
        $disk = Storage::disk('media');
        abort_unless($disk->exists($media->path), 404);

        // BinaryFileResponse beherrscht Range-Anfragen (Safari braucht das für Ton)
        // BinaryFileResponse handles range requests (Safari needs this for sound)
        $response = response()->file($disk->path($media->path), [
            'Content-Type'           => $media->mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
        // Kinderfotos nie in geteilten Zwischenspeichern (Proxys) ablegen
        // Never store children's photos in shared caches (proxies)
        $response->setPrivate();
        $response->setMaxAge(604800);
        $response->headers->addCacheControlDirective('immutable');
        return $response;
    }
}
