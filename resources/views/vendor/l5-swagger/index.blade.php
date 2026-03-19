<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentationTitle }}</title>
    <link rel="stylesheet" type="text/css" href="{{ l5_swagger_asset($documentation, 'swagger-ui.css') }}">
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-32x32.png') }}" sizes="32x32"/>
    <link rel="icon" type="image/png" href="{{ l5_swagger_asset($documentation, 'favicon-16x16.png') }}" sizes="16x16"/>
    <style>
    html
    {
        box-sizing: border-box;
        overflow: -moz-scrollbars-vertical;
        overflow-y: scroll;
    }
    *,
    *:before,
    *:after
    {
        box-sizing: inherit;
    }

    body {
      margin:0;
      background: #fafafa;
    }
    <style>
body#dark-mode {
    background: #0f0f0f;
    color: #ffffff;
}

/* container */
#dark-mode .swagger-ui {
    background: #0f0f0f;
}

/* endpoint cards */
#dark-mode .opblock {
    border-radius: 12px;
    margin-bottom: 12px;
    border: none;
}

/* GET */
#dark-mode .opblock.opblock-get {
    background: #0d1b2a;
    border-left: 5px solid #3b82f6;
}

/* POST */
#dark-mode .opblock.opblock-post {
    background: #0d2a1b;
    border-left: 5px solid #22c55e;
}

/* PUT */
#dark-mode .opblock.opblock-put {
    background: #2a1f0d;
    border-left: 5px solid #f59e0b;
}

/* DELETE */
#dark-mode .opblock.opblock-delete {
    background: #2a0d0d;
    border-left: 5px solid #ef4444;
}

/* headers */
#dark-mode .opblock-summary {
    color: #ffffff;
}

/* input fields */
#dark-mode input,
#dark-mode textarea {
    background: #1e1e1e;
    color: #ffffff;
    border: 1px solid #333;
}

/* buttons */
#dark-mode .btn {
    border-radius: 8px;
}

/* tables */
#dark-mode table {
    background: #141414;
}

/* models */
#dark-mode .model-box {
    background: #181818;
}
</style>
    @endif
</head>

<body @if(config('l5-swagger.defaults.ui.display.dark_mode')) id="dark-mode" @endif>
<div id="swagger-ui"></div>

<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-bundle.js') }}"></script>
<script src="{{ l5_swagger_asset($documentation, 'swagger-ui-standalone-preset.js') }}"></script>
<script>
    window.onload = function() {
        const urls = [];

        @foreach($urlsToDocs as $title => $url)
            urls.push({name: "{{ $title }}", url: "{{ $url }}"});
        @endforeach

        // Build a system
        const ui = SwaggerUIBundle({
            dom_id: '#swagger-ui',
            urls: urls,
            "urls.primaryName": "{{ $documentationTitle }}",
            operationsSorter: {!! isset($operationsSorter) ? '"' . $operationsSorter . '"' : 'null' !!},
            configUrl: {!! isset($configUrl) ? '"' . $configUrl . '"' : 'null' !!},
            validatorUrl: {!! isset($validatorUrl) ? '"' . $validatorUrl . '"' : 'null' !!},
            oauth2RedirectUrl: "{{ route('l5-swagger.'.$documentation.'.oauth2_callback', [], $useAbsolutePath) }}",

            requestInterceptor: function(request) {
                request.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
                return request;
            },

            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],

            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],

            layout: "StandaloneLayout",
            docExpansion : "{!! config('l5-swagger.defaults.ui.display.doc_expansion', 'none') !!}",
            deepLinking: true,
            filter: {!! config('l5-swagger.defaults.ui.display.filter') ? 'true' : 'false' !!},
            persistAuthorization: "{!! config('l5-swagger.defaults.ui.authorization.persist_authorization') ? 'true' : 'false' !!}",

        })

        window.ui = ui

        @if(in_array('oauth2', array_column(config('l5-swagger.defaults.securityDefinitions.securitySchemes'), 'type')))
        ui.initOAuth({
            usePkceWithAuthorizationCodeGrant: "{!! (bool)config('l5-swagger.defaults.ui.authorization.oauth2.use_pkce_with_authorization_code_grant') !!}"
        })
        @endif
    }
</script>
</body>
</html>
