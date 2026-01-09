<?php

namespace App\Http\Middleware;

use App\Models\ContentType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckContentAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('slug');

        if (! $slug) {
            return $next($request);
        }

        // Cache the lookup for 60 seconds to avoid slamming the DB on every request
        // Key should be unique per slug
        $contentType = Cache::remember("content_type_{$slug}_access", 60, function () use ($slug) {
            return ContentType::where('slug', $slug)->select('is_public')->first();
        });

        if (! $contentType) {
            // Let the controller handle 404
            return $next($request);
        }

        // 1. If public AND GET request -> Allow
        if ($contentType->is_public && $request->isMethod('get')) {
            return $next($request);
        }

        // 2. Otherwise -> Require Token Auth
        if (! auth('sanctum')->check()) {
            // Use standard Laravel JSON Unauthorized response usually, but explicitly aborting is safe.
            abort(401, 'Unauthenticated.');
        }

        return $next($request);
    }
}
