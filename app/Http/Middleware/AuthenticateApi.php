<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticateApi
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
        // verifica si el usuario esta autenticado
        if (Auth::guard('api')->check()) {
            return $next($request);
        }

        //omitir el token por ruta
        $route = $request->route()->uri;

        if ($route === 'api/login') {
            return $next($request);
        }

        // si el usuario no está autenticado, devuelve una respuesta no autorizada
        return response()->json(['error' => 'No autorizado'], 401);
    }
}