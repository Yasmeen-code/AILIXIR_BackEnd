<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Research News Feed</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; margin:0; padding:0; }
        .news-container { max-width:800px; margin:auto; padding:20px; }
        .news-card { background:white; padding:15px; margin-bottom:15px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.1); }
        .news-card h3 { margin:0 0 8px; }
        .news-card p { color:#555; }
        .loader { text-align:center; padding:20px; font-size:18px; color:#777; }
    </style>
</head>
<body>

<div class="news-container" id="newsContainer"></div>
<div class="loader" id="loader">Loading more research news...</div>

<script>
let page = 1;
let loading = false;
const container = document.getElementById("newsContainer");
const loader = document.getElementById("loader");

const API_URL = "/news";

async function loadNews() {
    if (loading) return;
    loading = true;

    const res = await fetch(`${API_URL}?page=${page}`);
    const data = await res.json();

    data.data.forEach(news => {
        const div = document.createElement("div");
        div.className = "news-card";
        div.innerHTML = `
            <h3>${news.title}</h3>
            <p>${news.summary}</p>
            <small>${news.source} | ${news.published_at}</small>
        `;
        container.appendChild(div);
    });

    loader.style.display = "none";

    if (data.meta.has_more) {
        page++;
        loading = false;
    } else {
        await refreshNews();
        loading = false;
    }
}

window.addEventListener("scroll", () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 50) {
        loadNews();
    }
});

async function refreshNews() {
    const res = await fetch("/news/refresh");
    const data = await res.json();

    page = 1;
    container.innerHTML = "";

    data.data.forEach(news => {
        const div = document.createElement("div");
        div.className = "news-card";
        div.innerHTML = `
            <h3>${news.title}</h3>
            <p>${news.summary}</p>
            <small>${news.source} | ${news.published_at}</small>
        `;
        container.appendChild(div);
    });

    loader.style.display = "none";
}

refreshNews();
</script>

</body>
</html>
