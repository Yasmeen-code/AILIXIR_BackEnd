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

        $tocMap = [];
        preg_match_all('/-\s+\[(.+?)\]\(#(.+?)\)/', $markdown, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $tocMap[trim($m[1])] = $m[2];
        }

        $content = preg_replace_callback(
            '/<h([2-6])>(.*?)<\/h\1>/i',
            function ($m) use ($tocMap) {
                $level = $m[1];
                $inner = $m[2];
                $text = trim(strip_tags(html_entity_decode($inner)));
                $id = $tocMap[$text] ?? Str::slug($text);
                return sprintf('<h%s id="%s">%s</h%s>', $level, $id, $inner, $level);
            },
            $content
        );

        return view('api-docs', compact('content'));
    }
}
