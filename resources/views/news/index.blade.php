<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scientific Research & Drug Discovery News</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
        h1 { margin-bottom: 20px; }
        .source-section { margin-bottom: 40px; }
        .news-item { border-bottom: 1px solid #ccc; padding: 10px 0; display: flex; gap: 15px; align-items: flex-start; }
        .news-item img { width: 150px; height: 100px; border-radius: 5px; object-fit: cover; }
        .news-content { flex: 1; }
        .source { font-size: 1.2em; font-weight: bold; color: #333; margin-bottom: 10px; }
        .published { font-size: 0.8em; color: #888; margin-top: 5px; }
        .refresh-btn { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .refresh-btn:hover { background: #218838; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Latest News in Scientific Research & Drug Discovery</h1>

    <a href="{{ route('news.refresh') }}" class="refresh-btn">Refresh News</a>

    @foreach($newsBySource as $source => $newsItems)
        <div class="source-section">
            <h2 class="source">{{ $source }}</h2>

            @foreach($newsItems as $item)
                <div class="news-item">
                    @if($item->image)
                        <img src="{{ $item->image }}" alt="News Image">
                    @endif
                    <div class="news-content">
                        <h3><a href="{{ $item->url }}" target="_blank">{{ $item->title }}</a></h3>
                        @if($item->summary)
                            <p>{{ $item->summary }}</p>
                        @endif
                        @if($item->published_at)
                            <p class="published">Published on: {{ \Carbon\Carbon::parse($item->published_at)->format('d M Y, H:i') }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
</body>
</html>
