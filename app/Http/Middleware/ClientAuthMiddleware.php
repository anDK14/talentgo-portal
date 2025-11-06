<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class ClientAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Cek jika user memiliki role client
        if (!$user || !$user->role || $user->role->role_name !== 'client') {
            abort(403, 'Unauthorized access. Client portal only.');
        }

        // Cek jika client user aktif
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('client.auth.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}