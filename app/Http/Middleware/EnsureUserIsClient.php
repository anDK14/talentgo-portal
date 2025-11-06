<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Role;

class EnsureUserIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        // Cek jika user adalah client
        if (!$user || !$user->role || $user->role->role_name !== 'client') {
            // HANYA invalidate session untuk client panel  
            $request->session()->forget('password_hash_web');
            
            return redirect()->route('filament.client.auth.login')
                ->withErrors(['email' => 'Unauthorized access. Client portal only.']);
        }

        // Cek jika client user aktif
        if (!$user->is_active) {
            $request->session()->forget('password_hash_web');
            
            return redirect()->route('filament.client.auth.login')
                ->withErrors(['email' => 'Your account has been deactivated.']);
        }

        return $next($request);
    }
}