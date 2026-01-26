<?php

namespace App\Models;

use Carbon\Carbon;

class NewsArticle
{
    public int $id;
    public string $title;
    public string $summary;
    public string $source;
    public string $url;
    public Carbon $publishedAt;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->title = $data['title'];
        $this->summary = $data['summary'];
        $this->source = $data['source'];
        $this->url = $data['url'];
        $this->publishedAt = $data['published_at'] instanceof Carbon
            ? $data['published_at']
            : Carbon::parse($data['published_at']);
    }

    public static function fromModel(News $news): self
    {
        return new self([
            'id' => $news->id,
            'title' => $news->title,
            'summary' => $news->summary,
            'source' => $news->source,
            'url' => $news->url,
            'published_at' => $news->published_at,
        ]);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'source' => $this->source,
            'url' => $this->url,
            'published_at' => $this->publishedAt->toIso8601String(),
        ];
    }
}
