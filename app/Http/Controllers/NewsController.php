<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NewsController extends Controller
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

    public function clear()
    {
        News::truncate();
        return response()->json(['success' => true, 'message' => 'All news deleted']);
    }

    public function refresh()
    {
        $totalAdded = 0;
        $feedCount = count($this->feeds);

        foreach ($this->feeds as $index => $feed) {
            try {
                Log::info("[" . ($index + 1) . "/{$feedCount}] {$feed['source']}");
                $added = $this->fetchFromFeed($feed, $this->articlesPerFetch);
                $totalAdded += $added;
            } catch (\Exception $e) {
                Log::error("{$feed['source']}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Fetched {$totalAdded} new articles",
            'total_in_db' => News::count(),
            'sources_count' => $feedCount
        ]);
    }

    private function fetchFromFeed(array $feed, int $limit = 4): int
    {
        $added = 0;

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

            foreach ($items as $item) {
                if ($added >= $limit) break;

                $article = $this->parseItem($item, $feed['source'], $existingUrls);
                if (!$article) continue;

                try {
                    News::create($article);
                    $added++;
                    $existingUrls[$article['url']] = true;
                } catch (\Exception $e) {
                    Log::error("Insert failed: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("{$feed['source']}: " . $e->getMessage());
        }

        return $added;
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

        $categories = $this->categorizeArticle($title, $cleanContent);
        $categoryString = !empty($categories) ? implode(', ', $categories) : $this->checkGeneral($title, $cleanContent);

        return [
            'title' => $title,
            'summary' => $this->cleanText($cleanContent, 250),
            'source' => $source,
            'url' => $url,
            'published_at' => $published,
            'category' => $categoryString,
        ];
    }

    private function categorizeArticle(string $title, string $content): array
    {
        $text = strtolower($title . ' ' . $content);
        $scores = [];

        foreach ($this->categories as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $keywordLower = strtolower(trim($keyword));
                if (empty($keywordLower)) continue;

                if (str_contains(strtolower($title), $keywordLower)) {
                    $score += 3;
                } elseif (str_contains($text, $keywordLower)) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $scores[$category] = $score;
            }
        }

        arsort($scores);
        return array_slice(array_keys($scores), 0, 2);
    }

    private function checkGeneral(string $title, string $content): string
    {
        $text = strtolower($title . ' ' . $content);

        foreach ($this->generalKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return 'General Biotech & Pharma';
            }
        }

        return 'Other';
    }

    private function cleanText(string $text, int $limit): string
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        if (strlen($text) > $limit) {
            $text = substr($text, 0, $limit) . '...';
        }

        return $text;
    }

    public function list()
    {
        $perPage = min(100, max(1, (int) request()->get('per_page', 10)));
        $page = max(1, (int) request()->get('page', 1));
        $category = request()->get('category');

        $skip = ($page - 1) * $perPage;
        $total = News::count();

        if ($skip >= $total) {
            Log::info("Page {$page} requested but only have {$total}. Fetching more...");

            $beforeCount = $total;
            $attempts = 0;

            while ($attempts < $this->maxFetchAttempts) {
                $attempts++;
                $newAdded = 0;

                foreach ($this->feeds as $feed) {
                    $added = $this->fetchFromFeed($feed, $this->articlesPerFetch);
                    $newAdded += $added;
                }

                $afterCount = News::count();
                Log::info("Attempt {$attempts}: Added {$newAdded} new articles (total: {$afterCount})");

                if ($afterCount > $beforeCount) {
                    break;
                }

                if ($attempts < $this->maxFetchAttempts) {
                    sleep(2);
                }
            }

            $total = News::count();
        }

        $query = News::orderBy('published_at', 'desc');

        if ($category) {
            $query->where('category', 'like', '%' . $category . '%');
        }

        $news = $query->skip($skip)->take($perPage)->get();

        return response()->json([
            'success' => true,
            'data' => $news,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'has_more' => ($skip + $perPage) < $total,
                'filter_category' => $category
            ]
        ]);
    }

    public function getCategories()
    {
        $categories = array_merge(array_keys($this->categories), ['General Biotech & Pharma', 'Other']);

        $counts = [];
        foreach ($categories as $cat) {
            $counts[$cat] = News::where('category', 'like', '%' . $cat . '%')->count();
        }

        return response()->json([
            'success' => true,
            'categories' => $categories,
            'article_counts' => $counts
        ]);
    }
}
