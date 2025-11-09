<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Update last_login_at jika user authenticated dan ini adalah request setelah login successful
        if (auth()->check() && $this->shouldUpdateLastLogin($request)) {
            auth()->user()->update(['last_login_at' => now()]);
        }
        
        return $response;
    }

    private function shouldUpdateLastLogin(Request $request): bool
    {
        // Only update on specific paths (dashboard pages)
        $paths = ['admin', 'portal'];
        
        foreach ($paths as $path) {
            if (str_contains($request->path(), $path) && !str_contains($request->path(), 'login')) {
                return true;
            }
        }
        
        return false;
    }
}