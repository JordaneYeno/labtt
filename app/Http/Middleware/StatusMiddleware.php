<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StatusMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->statusActivate()) {
            return $next($request);
        }
    
        return response()->json([
            'status' => 'error',
            'message' => 'activer votre compte pour avoir accès à cette option'
        ]);
    }
}
