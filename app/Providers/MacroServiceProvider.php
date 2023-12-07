<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Response::macro('api', function () {
            return new class {
                public function somethingWentWrong(): JsonResponse
                {
                    return Response::json(
                        data: [
                            'message' => 'Something went wrong.'
                        ],
                        status: 500,
                    );
                }

                public function noContent(): JsonResponse
                {
                    return Response::json(
                        status: 204,
                    );
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
