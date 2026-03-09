<!DOCTYPE html>
<html>
<head>
    <title>AILIXIR API Endpoints</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
            font-size: 20px;
        }
        .endpoint {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .endpoint:last-child {
            border-bottom: none;
        }
        .method {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            color: white;
            margin-right: 15px;
            min-width: 60px;
            text-align: center;
        }
        .method.GET { background: #61affe; }
        .method.POST { background: #49cc90; }
        .method.PUT { background: #fca130; }
        .method.DELETE { background: #f93e3e; }
        .method.PATCH { background: #50e3c2; }
        .path {
            color: #333;
            font-size: 14px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>AILIXIR API Endpoints</h1>
        
        @foreach($routes as $route)
            <div class="endpoint">
                <span class="method {{ $route['method'] }}">{{ $route['method'] }}</span>
                <span class="path">{{ $route['path'] }}</span>
            </div>
        @endforeach
    </div>
</body>
</html>