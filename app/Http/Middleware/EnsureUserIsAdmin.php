<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Cek jika user adalah admin
        if (!$user || !$user->role || $user->role->role_name !== 'admin') {
            // HANYA invalidate session untuk admin panel
            $request->session()->forget('password_hash_web');
            
            return redirect()->route('filament.admin.auth.login')
                ->withErrors(['email' => 'Unauthorized access. Admin panel only.']);
        }

        return $next($request);
    }
}