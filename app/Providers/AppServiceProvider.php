<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Response::macro('apiOk', function ($data = null, int $status = 200) {
            return response()->json([
                'status'  => $status,
                'data'    => $data,
                'success' => true,
            ], $status);
        });

        // errores 4xx/5xx
        Response::macro('apiError', function ($data = null, int $status = 400) {
            return response()->json([
                'status'  => $status,
                'data'    => $data,
                'success' => false,
            ], $status);
        });
    }
}
