<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            if ($request->routeIs('admin.settings.password.update') ||
                $request->routeIs('admin.password.expired') ||
                $request->routeIs('admin.logout')) {
                return $next($request);
            }

            return redirect()->route('admin.password.expired');
        }

        return $next($request);
    }
}
