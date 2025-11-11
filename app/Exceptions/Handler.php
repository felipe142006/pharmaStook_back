<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Una lista de las excepciones que no deben reportarse.
     */
    protected $dontReport = [];

    /**
     * Inputs que nunca deben mostrarse en los mensajes de validación.
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Reporta o registra una excepción.
     */
    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    /**
     * Renderiza una excepción en una respuesta HTTP.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {

            // Permitir login sin token
            if ($request->is('api/login')) {
                return parent::render($request, $exception);
            }

            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Debe iniciar sesión para acceder a esta ruta.'
            ], 401);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos enviados.',
                'errors'  => $exception->errors()
            ], 422);
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado.'
            ], 404);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor.',
            'error'   => config('app.debug') ? $exception->getMessage() : null
        ], 500);
    }

    /**
     *  Respuesta para excepciones de autenticación.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'success' => false,
            'message' => 'No autorizado. Inicie sesión primero.'
        ], 401);
    }
}
