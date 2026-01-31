<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Award;
use Cloudinary\Cloudinary;

class AwardSeeder extends Seeder
{
    public function run(): void
    {
        if (!env('CLOUDINARY_API_SECRET')) {
            $this->command->error('CLOUDINARY_API_SECRET missing in Railway!');
            return;
        }

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true]
        ]);

        $awards = [
            [
                "name" => "Nobel Prize in Physiology or Medicine",
                "category" => "Medicine",
                "description" => "The Nobel Prize in Physiology or Medicine is the world's most prestigious award in medical and biological sciences. It honors discoveries that fundamentally change our understanding of human biology, diseases, and therapeutic development.",
                "notable_winners" => "Alexander Fleming, Katalin Karikó & Drew Weissman, James Watson & Francis Crick, Tu Youyou",
                "country" => "Sweden",
                "year_started" => 1901,
                "website" => "https://www.nobelprize.org "
            ],
            [
                "name" => "Lasker Award",
                "category" => "Medical Research",
                "description" => "The Lasker Award is often called the 'American Nobel Prize' and recognizes outstanding achievements in medical science, public health, and clinical research.",
                "notable_winners" => "Anthony Fauci, Emmanuelle Charpentier & Jennifer Doudna, Akira Endo",
                "country" => "United States",
                "year_started" => 1945,
                "website" => "https://laskerfoundation.org "
            ],
            [
                "name" => "Keio Medical Science Prize",
                "category" => "Biomedical / Translational Research",
                "description" => "The Keio Medical Science Prize recognizes outstanding contributions in the field of biomedical and translational research.",
                "notable_winners" => "Shinya Yamanaka, Tasuku Honjo, Yoshinori Ohsumi",
                "country" => "Japan",
                "year_started" => 1996,
                "website" => "https://www.ms-fund.keio.ac.jp/en/prize/ "
            ],
            [
                "name" => "Gairdner International Award",
                "category" => "Biomedical Research",
                "description" => "The Gairdner Award is one of the most respected biomedical prizes worldwide. More than 95 of its recipients have later received the Nobel Prize.",
                "notable_winners" => "David Julius, Mary-Claire King, Pieter Cullis",
                "country" => "Canada",
                "year_started" => 1959,
                "website" => "https://gairdner.org "
            ],
            [
                "name" => "Breakthrough Prize in Life Sciences",
                "category" => "Life Sciences",
                "description" => "The Breakthrough Prize in Life Sciences celebrates transformative achievements in understanding living systems and improving human health.",
                "notable_winners" => "Svante Pääbo, Emmanuelle Charpentier & Jennifer Doudna, Louisa R. Manfredi",
                "country" => "United States",
                "year_started" => 2013,
                "website" => "https://breakthroughprize.org "
            ],
            [
                "name" => "IUPAC-Richter Prize in Medicinal Chemistry",
                "category" => "Medicinal Chemistry",
                "description" => "This prize recognizes outstanding contributions to medicinal chemistry and innovative drug design.",
                "notable_winners" => "Peter J. Tonge, Stephen M. Coats",
                "country" => "International",
                "year_started" => 2006,
                "website" => "https://iupac.org "
            ],
            [
                "name" => "EFMC Award for Scientific Excellence",
                "category" => "Medicinal Chemistry / Chemical Biology",
                "description" => "The EFMC Award honors world-leading scientists in medicinal chemistry.",
                "notable_winners" => "Benjamin Cravatt, Paul Wender",
                "country" => "Europe",
                "year_started" => 2000,
                "website" => "https://efmc.info "
            ],
            [
                "name" => "Lurie Prize in Biomedical Sciences",
                "category" => "Biomedical Sciences",
                "description" => "The Lurie Prize recognizes exceptional early-career scientists who make breakthrough contributions to biomedical science.",
                "notable_winners" => "Feng Zhang, Ruslan Medzhitov, Rachel Wilson",
                "country" => "United States",
                "year_started" => 2013,
                "website" => "https://fnih.org "
            ],
            [
                "name" => "Paul Janssen Award for Biomedical Research",
                "category" => "Biomedical Research",
                "description" => "The Paul Janssen Award honors scientists whose research leads to major advances in biomedical science and drug development.",
                "notable_winners" => "Yoshinori Ohsumi, Brian Druker, Jules Hoffmann",
                "country" => "United States",
                "year_started" => 2004,
                "website" => "https://pauljanssenaward.com "
            ],
            [
                "name" => "Tang Prize in Biopharmaceutical Science",
                "category" => "Biopharmaceutical Science",
                "description" => "The Tang Prize honors groundbreaking achievements in therapeutics, biotechnology, and modern drug discovery.",
                "notable_winners" => "James P. Allison, Emmanuelle Charpentier, Dan Barouch",
                "country" => "Taiwan",
                "year_started" => 2012,
                "website" => "https://www.tang-prize.org "
            ],
        ];

        foreach ($awards as $awardData) {
            try {
                $award = Award::updateOrCreate(
                    ['name' => $awardData['name']],
                    [
                        'category' => $awardData['category'],
                        'description' => $awardData['description'],
                        'notable_winners' => $awardData['notable_winners'],
                        'country' => $awardData['country'],
                        'year_started' => $awardData['year_started'],
                        'website' => trim($awardData['website']),
                        'images' => []
                    ]
                );

                $baseName = strtolower(Str::slug(explode(' ', $awardData['name'])[0]));
                $localImages = [];
                $index = 1;

                while (true) {
                    $fileName = $index === 1
                        ? "{$baseName}.jpg"
                        : "{$baseName}_{$index}.jpg";

                    $fullPath = public_path("imgs/awards/{$fileName}");

                    if (!file_exists($fullPath)) {
                        break;
                    }

                    $localImages[] = $fullPath;
                    $index++;
                }

                $cloudinaryUrls = [];

                foreach ($localImages as $filePath) {
                    try {
                        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

                        $result = $cloudinary->uploadApi()->upload(
                            $filePath,
                            [
                                'resource_type' => 'auto',
                                'public_id' => "awards/{$award->id}_{$fileName}",
                                'overwrite' => true
                            ]
                        );

                        $cloudinaryUrls[] = $result['secure_url'];
                        $this->command->info("✓ Uploaded: {$fileName}");
                    } catch (\Exception $e) {
                        $this->command->error("✗ Failed to upload {$filePath}: " . $e->getMessage());
                    }
                }

                if (!empty($cloudinaryUrls)) {
                    $award->images = $cloudinaryUrls;
                    $award->save();
                    $this->command->info("✓ Created/Updated: {$award->name} with " . count($cloudinaryUrls) . " images");
                } else {
                    $this->command->warn("⚠ No images found for: {$award->name}");
                }
            } catch (\Exception $e) {
                $this->command->error("Failed for {$awardData['name']}: " . $e->getMessage());
            }
        }

        $this->command->info('All awards seeded successfully with Cloudinary images!');
    }
}
