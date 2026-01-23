<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Upload Test</title>
</head>
<body>
    <h1>Upload Test</h1>
    <form id="uploadForm" enctype="multipart/form-data">
        @csrf
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>

    <div id="result"></div>

    <script>
        const form = document.getElementById('uploadForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const data = new FormData(form);

            try {
                const response = await fetch('/api/upload-file', {
                    method: 'POST',
                    body: data,
                });

                if (!response.ok) throw new Error('Upload failed');

                const json = await response.json();
                document.getElementById('result').innerHTML = 
                    `<p>File uploaded! Link:</p>
                     <a href="${json.url}" target="_blank">${json.url}</a>`;
            } catch (err) {
                document.getElementById('result').textContent = err;
            }
        });
    </script>
</body>
</html>
