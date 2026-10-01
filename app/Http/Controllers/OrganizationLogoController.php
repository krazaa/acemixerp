<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrganizationLogoController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $org = Organization::current();

        if (! $org->logo_path || ! Storage::disk('local')->exists($org->logo_path)) {
            abort(404);
        }

        // Any authenticated active user may view the logo.
        abort_unless($request->user()?->isActive(), 403);

        $ext = pathinfo($org->logo_path, PATHINFO_EXTENSION) ?: 'png';
        $mime = match (strtolower($ext)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return Storage::disk('local')->response(
            $org->logo_path,
            'logo.'.$ext,
            ['Content-Type' => $mime, 'Cache-Control' => 'private, max-age=300'],
        );
    }
}
