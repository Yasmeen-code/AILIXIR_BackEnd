<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class NewsController extends Controller
{
    private array $feeds = [
        ['source' => 'Nature - Drug Discovery', 'url' => 'https://www.nature.com/subjects/drug-discovery.rss'],
        ['source' => 'Drug Discovery News', 'url' => 'https://www.drugdiscoverynews.com/rss'],
        ['source' => 'Labiotech', 'url' => 'https://www.labiotech.eu/feed/'],
        ['source' => 'BioWorld', 'url' => 'https://www.bioworld.com/rss'],
        ['source' => 'BioPharma Dive', 'url' => 'https://www.biopharmadive.com/rss/'],
        ['source' => 'Science Daily - Drug Discovery', 'url' => 'https://www.sciencedaily.com/rss/mind_brain/drug_discovery.xml'],
        ['source' => 'PharmaTimes', 'url' => 'https://www.pharmatimes.com/rss/news'],
        ['source' => 'Fierce Biotech', 'url' => 'https://www.fiercebiotech.com/rss/full/news'],
        ['source' => 'Genetic Engineering & Biotechnology News', 'url' => 'https://www.genengnews.com/feed/'],
        ['source' => 'Endpoints News', 'url' => 'https://endpts.com/feed/'],
    ];

    public function refresh()
    {
        foreach ($this->feeds as $feed) {
            $this->fetchFeed($feed, 10);
        }

        return response()->json([
            'success' => true,
            'message' => 'News updated successfully',
            'data' => News::orderBy('published_at', 'desc')->take(50)->get()
        ]);
    }

    private function fetchFeed(array $feed, int $limit = 10)
    {
        try {
            $response = Http::timeout(10)->get($feed['url']);
            if (!$response->ok()) return;

            $body = str_replace('&', '&amp;', $response->body());
            $body = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\x80-\xFF]/', '', $body);
            $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (!$xml) return;

            $items = $xml->channel->item ?? $xml->entry ?? [];
            $count = 0;

            foreach ($items as $item) {
                if ($count >= $limit) break;

                $title = (string) ($item->title ?? 'No title');
                $summary = (string) ($item->description ?? $item->summary ?? 'No summary available...');
                $url = (string) ($item->link ?? ($item->id ?? '#'));
                $publishedRaw = (string) ($item->pubDate ?? $item->published ?? now());

                try {
                    $published = Carbon::parse($publishedRaw)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $published = now()->format('Y-m-d H:i:s');
                }

                News::updateOrCreate(
                    ['url' => $url],
                    [
                        'title' => $title,
                        'summary' => $this->cleanSummary($summary, 200),
                        'source' => $feed['source'],
                        'url' => $url,
                        'image' => null,
                        'published_at' => $published,
                    ]
                );

                $count++;
            }
        } catch (\Exception $e) {
        }
    }

    private function cleanSummary(string $text, int $limit = 200): string
    {
        $text = preg_replace('#<figure.*?>.*?</figure>#is', '', $text);
        $text = preg_replace('#<img.*?>#is', '', $text);
        $text = preg_replace('#<script.*?>.*?</script>#is', '', $text);
        $text = preg_replace('#<style.*?>.*?</style>#is', '', $text);
        $text = preg_replace('#<iframe.*?>.*?</iframe>#is', '', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > $limit) {
            $text = substr($text, 0, $limit);
            $text = preg_replace('/\s+\S*$/', '', $text);
            $text .= '...';
        }

        return $text;
    }

    public function list()
    {
        $news = News::orderBy('published_at', 'desc')->take(50)->get();
        return response()->json(['success' => true, 'data' => $news]);
    }
}
