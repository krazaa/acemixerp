<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as BaseHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends BaseHandler
{
    public function register(): void
    {
        $this->renderable(function (DomainException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->userMessage(),
                    'type' => class_basename($e),
                ], $e->httpStatus());
            }

            return back()->withErrors(['error' => $e->userMessage()])->withInput();
        });
    }

    public function report(Throwable $e): void
    {
        // Never log stack traces containing secrets; Laravel's default is sufficient.
        parent::report($e);
    }
}
