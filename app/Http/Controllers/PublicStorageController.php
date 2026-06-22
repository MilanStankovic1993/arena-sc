<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicStorageController extends Controller
{
    private const IMAGE_DIRECTORIES = [
        'campaigns/',
        'courts/',
        'equipment/',
        'events/',
        'sports/',
    ];

    private const IMAGE_MIME_TYPES = [
        'image/avif',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function __invoke(string $path): StreamedResponse
    {
        abort_if(Str::contains($path, ['..', '\\']), 404);
        abort_unless(Str::startsWith($path, self::IMAGE_DIRECTORIES), 404);

        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        $mimeType = $disk->mimeType($path);

        abort_unless(in_array($mimeType, self::IMAGE_MIME_TYPES, true), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=604800',
            'Content-Type' => $mimeType,
        ]);
    }
}
