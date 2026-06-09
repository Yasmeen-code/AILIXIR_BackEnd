<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

    public function showDocs()
    {
        $path = base_path('API.md');
        $markdown = file_get_contents($path);
        $content = Str::markdown($markdown);

        return view('api-docs', compact('content'));
    }
}
