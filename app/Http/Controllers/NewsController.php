<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsController extends Controller
{
    private NewsService $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
    }

    public function index(Request $request)
    {
        try {
            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(100, max(1, (int) $request->get('per_page', 10)));
            $category = $request->get('category');

            $result = $this->newsService->getArticles($page, $perPage, $category);

            return response()->json([
                'success' => true,
                'data' => $result['articles']->map->toArray(),
                'meta' => $result['meta'],
            ]);
        } catch (\Exception $e) {
            Log::error('Index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function refresh()
    {
        try {
            $articles = $this->newsService->fetchNews();

            return response()->json([
                'success' => true,
                'message' => "Fetched {$articles->count()} new articles",
                'total_in_db' => \App\Models\News::count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Refresh error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function saveArticle(Request $request, int $articleId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $saved = $this->newsService->saveArticle($user->id, $articleId);

            return response()->json([
                'success' => true,
                'message' => 'Article saved successfully',
                'data' => [
                    'id' => $saved->id,
                    'user_id' => $saved->user_id,
                    'news_id' => $saved->news_id,
                    'created_at' => $saved->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function shareArticle(int $articleId)
    {
        try {
            $this->newsService->shareArticle($articleId);

            return response()->json([
                'success' => true,
                'message' => 'Article shared successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Share error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function getCategories()
    {
        try {
            $categories = $this->newsService->getCategories();

            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error('Categories error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function clear()
    {
        try {
            \App\Models\News::truncate();

            return response()->json([
                'success' => true,
                'message' => 'All news deleted',
            ]);
        } catch (\Exception $e) {
            Log::error('Clear error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function getSavedArticles(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $saved = \App\Models\SavedArticle::with('news')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $saved->map(function ($item) {
                    return [
                        'saved_id' => $item->id,
                        'saved_at' => $item->created_at,
                        'article' => [
                            'id' => $item->news->id,
                            'title' => $item->news->title,
                            'summary' => $item->news->summary,
                            'source' => $item->news->source,
                            'url' => $item->news->url,
                            'published_at' => $item->news->published_at,
                        ],
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            Log::error('GetSaved error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function unsaveArticle(Request $request, int $savedArticleId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                ], 401);
            }

            $saved = \App\Models\SavedArticle::where('id', $savedArticleId)
                ->where('user_id', $user->id)
                ->first();

            if (!$saved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saved article not found',
                ], 404);
            }

            $saved->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article removed from saved',
            ]);
        } catch (\Exception $e) {
            Log::error('Unsave error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
