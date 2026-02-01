<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NewsController extends BaseController
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

            return $this->successResponse('Articles retrieved successfully', [
                'results' => $result['articles']->map->toArray(),
                'pagination' => $result['meta'],
            ]);
        } catch (\Exception $e) {
            Log::error('Index error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function refresh()
    {
        try {
            $articles = $this->newsService->fetchNews();

            return $this->successResponse("Fetched {$articles->count()} new articles", [
                'total_in_db' => \App\Models\News::count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Refresh error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function saveArticle(Request $request, int $articleId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->errorResponse('Unauthorized. Please login first.', 401);
            }

            $saved = $this->newsService->saveArticle($user->id, $articleId);

            return $this->successResponse('Article saved successfully', [
                'id' => $saved->id,
                'user_id' => $saved->user_id,
                'news_id' => $saved->news_id,
                'created_at' => $saved->created_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Save error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function shareArticle(int $articleId)
    {
        try {
            $this->newsService->shareArticle($articleId);

            return $this->successResponse('Article shared successfully');
        } catch (\Exception $e) {
            Log::error('Share error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getCategories()
    {
        try {
            $categories = $this->newsService->getCategories();

            return $this->successResponse('Categories retrieved successfully', $categories);
        } catch (\Exception $e) {
            Log::error('Categories error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function clear()
    {
        try {
            \App\Models\News::truncate();

            return $this->successResponse('All news deleted');
        } catch (\Exception $e) {
            Log::error('Clear error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getSavedArticles(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->errorResponse('Unauthorized. Please login first.', 401);
            }

            $saved = \App\Models\SavedArticle::with('news')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse('Saved articles retrieved successfully', $saved->map(function ($item) {
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
            }));
        } catch (\Exception $e) {
            Log::error('GetSaved error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function unsaveArticle(Request $request, int $savedArticleId)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->errorResponse('Unauthorized. Please login first.', 401);
            }

            $saved = \App\Models\SavedArticle::where('id', $savedArticleId)
                ->where('user_id', $user->id)
                ->first();

            if (!$saved) {
                return $this->errorResponse('Saved article not found', 404);
            }

            $saved->delete();

            return $this->successResponse('Article removed from saved');
        } catch (\Exception $e) {
            Log::error('Unsave error: ' . $e->getMessage());
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
