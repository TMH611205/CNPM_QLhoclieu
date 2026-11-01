<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->user()?->status === 'locked') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => 'Tài khoản đã bị khóa, vui lòng liên hệ quản trị viên.']);
        }

        return $next($request);
    }
}
