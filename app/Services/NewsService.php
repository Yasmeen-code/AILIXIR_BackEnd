<?php

namespace App\Services;

use App\Models\News;
use App\Models\NewsArticle;
use App\Models\SavedArticle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NewsService
{
    private array $feeds = [
        ['source' => 'STAT News - Biotech', 'url' => 'https://www.statnews.com/category/biotech/feed/'],
        ['source' => 'Drug Discovery News', 'url' => 'https://www.drugdiscoverynews.com/rss'],
        ['source' => 'Labiotech', 'url' => 'https://www.labiotech.eu/feed/'],
        ['source' => 'BioWorld', 'url' => 'https://www.bioworld.com/rss'],
        ['source' => 'BioPharma Dive', 'url' => 'https://www.biopharmadive.com/rss/'],
        ['source' => 'Medical Xpress - Drug Discovery', 'url' => 'https://medicalxpress.com/rss-feed/drug-discovery-news/'],
        ['source' => 'Drug Target Review', 'url' => 'https://www.drugtargetreview.com/feed/'],
        ['source' => 'European Pharmaceutical Review', 'url' => 'https://www.europeanpharmaceuticalreview.com/feed/'],
        ['source' => 'Genetic Engineering & Biotechnology News', 'url' => 'https://www.genengnews.com/feed/'],
        ['source' => 'Endpoints News', 'url' => 'https://endpts.com/feed/'],
        ['source' => 'Fierce Biotech', 'url' => 'https://www.fiercebiotech.com/rss/biotech'],
        ['source' => 'Bioengineer.org', 'url' => 'https://bioengineer.org/feed/'],
        ['source' => 'ScienceDaily - Biotech', 'url' => 'https://www.sciencedaily.com/rss/plants_animals/biotechnology.xml'],
        ['source' => 'Phys.org - Biotech', 'url' => 'https://phys.org/rss-feed/biology-news/biotechnology/'],
        ['source' => 'MIT Tech Review - Biotech', 'url' => 'https://www.technologyreview.com/topic/biotechnology/rss/'],
        ['source' => 'Bio.News', 'url' => 'https://www.bio.news/feed/'],
        ['source' => 'BioCentury', 'url' => 'https://www.biocentury.com/rss/BioCentury.rss'],
        ['source' => 'Nature - Biotechnology', 'url' => 'https://www.nature.com/subjects/biotechnology.rss'],
        ['source' => 'Pharmaceutical Technology', 'url' => 'https://www.pharmaceutical-technology.com/feed/'],
        ['source' => 'Fierce Pharma', 'url' => 'https://www.fiercepharma.com/rss/pharma'],
        ['source' => 'NYT - Pharmaceuticals', 'url' => 'https://rss.nytimes.com/services/xml/rss/nyt/Drugs.xml'],
        ['source' => 'Economic Times - Pharma', 'url' => 'https://economictimes.indiatimes.com/industry/healthcare/biotech/rssfeeds/13358259.cms'],
        ['source' => 'Clinical Trials Arena', 'url' => 'https://www.clinicaltrialsarena.com/feed/'],
        ['source' => 'TrialSiteNews', 'url' => 'https://trialsitenews.com/feed/'],
        ['source' => 'GenomeWeb', 'url' => 'https://www.genomeweb.com/section/rss/news'],
        ['source' => 'CDC - Genomics', 'url' => 'https://blogs.cdc.gov/genomics/feed/'],
        ['source' => 'Drugs.com - Clinical Trials', 'url' => 'https://www.drugs.com/rss/clinical_trials.xml'],
        ['source' => 'Drugs.com - New Drugs', 'url' => 'https://www.drugs.com/rss/new_drug_approvals.xml'],
        ['source' => 'Cancer.gov - Trials', 'url' => 'https://www.cancer.gov/syndication/rss/nci-clinical-trials.rss'],
        ['source' => 'Outsourcing-Pharma', 'url' => 'https://www.outsourcing-pharma.com/feed/'],
        ['source' => 'BioPharma Reporter', 'url' => 'https://www.biopharma-reporter.com/feed/'],
        ['source' => 'Life Sciences Connect', 'url' => 'https://www.lifesciencesconnect.com/feed/'],
        ['source' => 'GEN - News', 'url' => 'https://www.genengnews.com/feed/'],
        ['source' => 'MedCity News', 'url' => 'https://medcitynews.com/feed/'],
        ['source' => 'Healthcare IT News', 'url' => 'https://www.healthcareitnews.com/rss.xml'],
        ['source' => 'MobiHealthNews', 'url' => 'https://www.mobihealthnews.com/feed/'],
        ['source' => 'HIMSS', 'url' => 'https://www.himss.org/news/rss.xml'],
        ['source' => 'Health Affairs', 'url' => 'https://www.healthaffairs.org/rss.xml'],
        ['source' => 'NEJM - Research', 'url' => 'https://www.nejm.org/rss/medical-articles.xml'],
        ['source' => 'The Lancet', 'url' => 'https://www.thelancet.com/rssfeed/lancet_current_issue.xml'],
        ['source' => 'JAMA Network', 'url' => 'https://jamanetwork.com/rss/site_3/67.xml'],
        ['source' => 'BMJ', 'url' => 'https://www.bmj.com/rss.xml'],
        ['source' => 'PLOS Medicine', 'url' => 'https://journals.plos.org/plosmedicine/feed/atom'],
        ['source' => 'PubMed - Latest', 'url' => 'https://pubmed.ncbi.nlm.nih.gov/rss/search/1nH-_ZcrhcXRArTxM9h_UEAOS8gzeGr9cPXpj9naNj5L6bp4/?limit=15&utm_campaign=pubmed-2&fc=20250101000000'],
        ['source' => 'Science Magazine', 'url' => 'https://www.science.org/rss/news_current.xml'],
        ['source' => 'Cell Press', 'url' => 'https://www.cell.com/rss/Current.xml'],
        ['source' => 'PNAS', 'url' => 'https://www.pnas.org/rss/News.xml'],
        ['source' => 'BioRxiv', 'url' => 'https://www.biorxiv.org/rss/recent.xml'],
        ['source' => 'MedRxiv', 'url' => 'https://www.medrxiv.org/rss/recent.xml'],
        ['source' => 'FDA News', 'url' => 'https://www.fdanews.com/feed'],
    ];

    private int $articlesPerFetch = 4;
    private int $maxFetchAttempts = 3;

    private array $categories = [
        'AI & Digital Health' => [
            'artificial intelligence',
            'ai ',
            'machine learning',
            'deep learning',
            'neural network',
            'digital health',
            'digital therapeutics',
            'health tech',
            'digital medicine',
            'algorithm',
            'computational',
            'in silico',
            'bioinformatics',
            'data science',
            'predictive model',
            'virtual screening',
            'computer-aided drug design',
        ],
        'Clinical Trials' => [
            'clinical trial',
            'phase i',
            'phase ii',
            'phase iii',
            'phase iv',
            'phase 1',
            'phase 2',
            'phase 3',
            'phase 4',
            'patient enrollment',
            'trial results',
            'study results',
            'clinical study',
            'randomized controlled',
            'double-blind',
            'placebo-controlled',
            'endpoint',
            'fda approval',
            'regulatory approval',
            'pdufa',
            'breakthrough therapy',
            'fast track',
            'priority review',
            'orphan drug designation',
        ],
        'Genomics & Gene Therapy' => [
            'genomics',
            'genomic',
            'genome',
            'gene therapy',
            'gene editing',
            'crispr',
            'dna',
            'rna',
            'mrna',
            'genetic',
            'genetics',
            'sequencing',
            'whole genome',
            'car-t',
            'cell therapy',
            'stem cell',
            'regenerative medicine',
            'tissue engineering',
            'personalized medicine',
            'precision medicine',
            'biomarker',
            'companion diagnostic',
        ],
        'Oncology' => [
            'cancer',
            'oncology',
            'oncologist',
            'tumor',
            'tumour',
            'malignant',
            'metastasis',
            'chemotherapy',
            'radiotherapy',
            'immunotherapy',
            'checkpoint inhibitor',
            'car-t cell',
            'antibody-drug conjugate',
            'adc ',
            'targeted therapy',
            'solid tumor',
            'hematology',
            'leukemia',
            'lymphoma',
            'melanoma',
            'carcinoma',
        ],
        'Neurology & CNS' => [
            'neurology',
            'neuroscience',
            'brain',
            'cns ',
            'central nervous system',
            'alzheimer',
            'parkinson',
            'epilepsy',
            'multiple sclerosis',
            'ms ',
            'stroke',
            'neurodegenerative',
            'neuroprotection',
            'cognitive',
            'dementia',
            'amyloid',
            'psychiatry',
            'depression',
            'anxiety',
            'schizophrenia',
            'bipolar',
            'mental health',
        ],
        'Infectious Diseases' => [
            'infectious disease',
            'antibiotic',
            'antiviral',
            'antimicrobial',
            'antifungal',
            'vaccine',
            'vaccination',
            'immunization',
            'pandemic',
            'epidemic',
            'outbreak',
            'covid',
            'coronavirus',
            'influenza',
            'flu ',
            'hiv ',
            'aids',
            'hepatitis',
            'resistance',
            'superbug',
            'pathogen',
            'bacteria',
            'virus',
            'fungal',
        ],
        'Cardiovascular & Metabolic' => [
            'cardiovascular',
            'heart',
            'cardiac',
            'hypertension',
            'blood pressure',
            'diabetes',
            'diabetic',
            'insulin',
            'glucose',
            'metabolic',
            'obesity',
            'weight loss',
            'cholesterol',
            'lipid',
            'atherosclerosis',
            'stroke',
            'myocardial',
            'heart failure',
            'arrhythmia',
            'anticoagulant',
            'blood thinner',
        ],
        'Rare Diseases' => [
            'rare disease',
            'orphan drug',
            'genetic disorder',
            'inherited disease',
            'sickle cell',
            'hemophilia',
            'cystic fibrosis',
            'muscular dystrophy',
            'lysosomal',
            ' enzyme replacement',
            'gene therapy rare',
        ],
        'Pharma Industry' => [
            'pharma',
            'pharmaceutical',
            'biotech',
            'biopharma',
            'merger',
            'acquisition',
            'partnership',
            'collaboration',
            'licensing deal',
            'drug pricing',
            'market access',
            'blockbuster',
            'patent',
            'generic',
            'biosimilar',
            'cmo ',
            'cro ',
            'manufacturing',
            'supply chain',
            'drug shortage',
            'recall',
            'fda warning',
            'complete response letter',
        ],
        'Drug Discovery' => [
            'drug discovery',
            'lead compound',
            'hit identification',
            'target identification',
            'high-throughput screening',
            'hts ',
            'medicinal chemistry',
            'structure-based',
            'fragment-based',
            'protein-protein interaction',
            'kinase inhibitor',
            'enzyme inhibitor',
            'gpcr',
            'antibody',
            'monoclonal',
            'bispecific',
            'adc ',
            'small molecule',
            'biologic',
        ],
        'Regulatory & Policy' => [
            'regulatory',
            'fda ',
            'ema ',
            'ich ',
            'guidance',
            'compliance',
            'gxp ',
            'gmp ',
            'validation',
            'quality control',
            'inspection',
            'warning letter',
            '483 ',
            'health policy',
            'reimbursement',
            'payer',
            'healthcare reform',
            'price regulation',
        ],
    ];

    private array $generalKeywords = [
        'drug',
        'drugs',
        'medication',
        'medicine',
        'medicines',
        'therapeutic',
        'therapeutics',
        'therapy',
        'treatment',
        'treatments',
        'pharma',
        'pharmaceutical',
        'pharmaceuticals',
        'biotech',
        'biotechnology',
        'medical',
        'health',
        'healthcare',
        'patient',
        'patients',
        'disease',
        'diseases',
        'disorder',
        'condition',
        'diagnosis',
        'symptom',
        'symptoms',
        'research',
        'study',
        'studies',
        'finding',
        'findings',
        'breakthrough',
        'innovation',
        'development',
        'pipeline',
        'product',
        'products',
        'market',
        'launch',
        'commercial',
    ];

    /**
     * Fetch news from all RSS feeds
     */
    public function fetchNews(): Collection
    {
        $articles = collect();

        foreach ($this->feeds as $feed) {
            try {
                $fetched = $this->fetchFromFeed($feed, $this->articlesPerFetch);
                $articles = $articles->merge($fetched);
            } catch (\Exception $e) {
                Log::error("Error fetching {$feed['source']}: " . $e->getMessage());
            }
        }

        return $articles;
    }

    /**
     * Fetch more news if needed (for pagination)
     */
    public function fetchMoreIfNeeded(int $neededCount): int
    {
        $beforeCount = News::count();
        $attempts = 0;

        while ($attempts < $this->maxFetchAttempts && News::count() < $neededCount) {
            $attempts++;

            foreach ($this->feeds as $feed) {
                $this->fetchFromFeed($feed, $this->articlesPerFetch);
            }

            $afterCount = News::count();
            if ($afterCount > $beforeCount) {
                break;
            }

            if ($attempts < $this->maxFetchAttempts) {
                sleep(2);
            }
        }

        return News::count() - $beforeCount;
    }

    /**
     * Save article for user
     */
    public function saveArticle(int $userId, int $articleId): SavedArticle
    {
        return SavedArticle::firstOrCreate([
            'user_id' => $userId,
            'news_id' => $articleId,
        ]);
    }

    /**
     * Share article (increment share count)
     */
    public function shareArticle(int $articleId): void
    {
        $article = News::findOrFail($articleId);
        $article->increment('share_count');
    }

    /**
     * Get articles with pagination and optional category filter
     */
    public function getArticles(int $page = 1, int $perPage = 10, ?string $category = null): array
    {
        $skip = ($page - 1) * $perPage;
        $total = News::count();

        if ($skip >= $total) {
            $this->fetchMoreIfNeeded($skip + $perPage);
            $total = News::count();
        }

        $query = News::orderBy('published_at', 'desc');

        if ($category) {
            $query->where(function ($q) use ($category) {
                $keywords = $this->categories[$category] ?? [];
                foreach ($keywords as $keyword) {
                    $q->orWhere('title', 'like', '%' . $keyword . '%')
                        ->orWhere('summary', 'like', '%' . $keyword . '%');
                }
            });
        }

        $news = $query->skip($skip)->take($perPage)->get();

        return [
            'articles' => $news->map(fn($item) => NewsArticle::fromModel($item)),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($skip + $perPage) < $total,
            ]
        ];
    }

    /**
     * Get all available categories
     */
    public function getCategories(): array
    {
        return array_merge(array_keys($this->categories), ['General Biotech & Pharma', 'Other']);
    }

    private function fetchFromFeed(array $feed, int $limit = 4): Collection
    {
        $articles = collect();

        try {
            $response = Http::timeout(30)->withOptions(['verify' => false])->get($feed['url']);

            if (!$response->ok()) {
                throw new \Exception("HTTP " . $response->status());
            }

            $body = $response->body();
            if (empty($body)) {
                throw new \Exception("Empty response");
            }

            $body = $this->cleanXml($body);

            $xml = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
            if (!$xml) {
                throw new \Exception("XML parse failed");
            }

            $items = $this->getItems($xml);
            if (empty($items)) {
                throw new \Exception("No items found");
            }

            $existingUrls = News::pluck('url')->toArray();
            $existingUrls = array_flip($existingUrls);

            $count = 0;
            foreach ($items as $item) {
                if ($count >= $limit) break;

                $article = $this->parseItem($item, $feed['source'], $existingUrls);
                if (!$article) continue;

                try {
                    $news = News::create($article);
                    $articles->push(NewsArticle::fromModel($news));
                    $existingUrls[$article['url']] = true;
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Insert failed: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("{$feed['source']}: " . $e->getMessage());
        }

        return $articles;
    }

    private function cleanXml(string $body): string
    {
        $body = preg_replace('/&(?!#?[a-z0-9]+;)/i', '&amp;', $body);
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body);
        return $body;
    }

    private function getItems($xml): array
    {
        $items = [];
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) $items[] = $item;
        } elseif (isset($xml->item)) {
            foreach ($xml->item as $item) $items[] = $item;
        } elseif (isset($xml->entry)) {
            foreach ($xml->entry as $item) $items[] = $item;
        }
        return $items;
    }

    private function parseItem($item, string $source, array $existingUrls): ?array
    {
        $url = trim((string) ($item->link ?? $item->id ?? ''));
        if (empty($url) || isset($existingUrls[$url])) {
            return null;
        }

        $title = trim((string) ($item->title ?? ''));
        if (empty($title)) {
            return null;
        }

        $content = (string) ($item->description ?? '');
        if (empty($content)) {
            $content = (string) ($item->children('content', true)->encoded ?? '');
        }
        if (empty($content)) {
            $content = (string) ($item->summary ?? '');
        }

        $cleanContent = strip_tags($content);
        if (empty(trim($cleanContent))) {
            return null;
        }

        $dateStr = (string) ($item->pubDate ?? $item->published ?? now());
        try {
            $published = Carbon::parse($dateStr);
        } catch (\Exception $e) {
            $published = now();
        }

        return [
            'title' => $title,
            'summary' => $this->cleanText($cleanContent, 250),
            'source' => $source,
            'url' => $url,
            'published_at' => $published,
        ];
    }

    private function cleanText(string $text, int $limit): string
    {
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
}
