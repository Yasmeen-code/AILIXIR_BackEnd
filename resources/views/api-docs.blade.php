<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AILIXIR API Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 960px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 40px;
        }
        h1 { font-size: 28px; margin-bottom: 20px; color: #1a1a1a; }
        h2 {
            font-size: 22px; margin-top: 35px; margin-bottom: 15px;
            color: #1a1a1a; border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }
        h3 { font-size: 18px; margin-top: 28px; margin-bottom: 12px; color: #333; }
        h4 { font-size: 16px; margin-top: 22px; margin-bottom: 10px; color: #444; }
        p { margin-bottom: 15px; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
        pre {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 18px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 18px;
            border-left: 4px solid #2563eb;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            font-family: 'SF Mono', 'Fira Code', 'Consolas', monospace;
        }
        pre code { background: none; padding: 0; border-radius: 0; font-size: 1em; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px 14px;
            text-align: left;
        }
        th { background: #f5f7fa; font-weight: 600; color: #444; }
        tr:nth-child(even) td { background: #fafafa; }
        ul, ol { margin-bottom: 15px; padding-left: 28px; }
        li { margin-bottom: 5px; }
        blockquote {
            border-left: 4px solid #2563eb;
            padding: 12px 18px;
            margin-bottom: 18px;
            background: #f0f5ff;
            color: #555;
        }
        hr {
            border: none;
            border-top: 2px solid #e0e0e0;
            margin: 35px 0;
        }
        img { max-width: 100%; height: auto; border-radius: 4px; }
        .toc { margin-bottom: 30px; }
        .toc ul { list-style: none; padding-left: 0; }
        .toc li { margin-bottom: 4px; }
        .toc a { color: #2563eb; font-weight: 500; }
    </style>
</head>
<body>
    <div class="container">
        {!! $content !!}
    </div>
</body>
</html>
