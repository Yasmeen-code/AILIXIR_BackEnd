<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;

class ApiDocsController extends Controller
{
    public function index()
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            if (str_starts_with($route->uri(), 'api/')) {
                return [
                    'method' => $route->methods()[0] === 'HEAD' ? 'GET' : $route->methods()[0],
                    'path' => '/' . $route->uri(),
                ];
            }
            return null;
        })->filter()->unique('path')->sortBy('path')->values();

        return view('api-endpoints', compact('routes'));
    }
}
