<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ItemImageController extends Controller
{
    public function __invoke(Request $request, Item $item): StreamedResponse
    {
        $this->authorize('view', $item);

        if (! $item->image_path || ! Storage::disk('local')->exists($item->image_path)) {
            abort(404);
        }

        $ext = pathinfo($item->image_path, PATHINFO_EXTENSION) ?: 'jpg';
        $mime = match (strtolower($ext)) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return Storage::disk('local')->response(
            $item->image_path,
            "item-{$item->code}.{$ext}",
            ['Content-Type' => $mime, 'Cache-Control' => 'private, max-age=600'],
        );
    }
}
