<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scientist;
use App\Models\Award;
use Cloudinary\Cloudinary;

class ScientistSeeder extends Seeder
{
    public function run(): void
    {

        Scientist::query()->delete();
        $this->command->info('Deleted all existing scientists data.');

        $scientistsData = [
            [
                'info' => [
                    'name' => 'Ibn Sina',
                    'nationality' => 'Persian',
                    'birth_year' => 980,
                    'death_year' => 1037,
                    'field' => 'Medicine, Pharmacology',
                    'bio' => 'Ibn Sina, known as Avicenna, was a Persian polymath who wrote the famous medical encyclopedia "The Canon of Medicine", which was used in Europe for 600 years.',
                    'impact' => 'Laid the foundation for clinical pharmacology and herbal-based treatments, influencing modern drug discovery concepts.',
                ],
                'awards' => [] // مافيش جوايز مسجلة تاريخياً
            ],
            [
                'info' => [
                    'name' => 'Alexander Fleming',
                    'nationality' => 'British',
                    'birth_year' => 1881,
                    'death_year' => 1955,
                    'field' => 'Microbiology',
                    'bio' => 'Fleming was a biologist and pharmacologist best known for discovering penicillin in 1928, the world\'s first antibiotic.',
                    'impact' => 'Revolutionized medicine by introducing antibiotics, saving millions of lives and shaping drug development against bacterial infections.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1945, 'contribution' => 'Discovery of penicillin'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Paul Ehrlich',
                    'nationality' => 'German',
                    'birth_year' => 1854,
                    'death_year' => 1915,
                    'field' => 'Immunology, Chemotherapy',
                    'bio' => 'Paul Ehrlich was a physician and scientist who developed the first targeted drug therapy and introduced the concept of "magic bullets".',
                    'impact' => 'Founder of chemotherapy and target-based drug discovery approaches widely used today.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1908, 'contribution' => 'Work on immunity'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Gertrude Elion',
                    'nationality' => 'American',
                    'birth_year' => 1918,
                    'death_year' => 1999,
                    'field' => 'Biochemistry, Pharmacology',
                    'bio' => 'Nobel Prize–winning scientist who developed revolutionary drugs for leukemia, AIDS, and prevention of organ transplant rejection.',
                    'impact' => 'Pioneer of rational drug design methods and biochemical targeting.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1988, 'contribution' => 'Drug development principles'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Tu Youyou',
                    'nationality' => 'Chinese',
                    'birth_year' => 1930,
                    'death_year' => null,
                    'field' => 'Pharmaceutical Chemistry',
                    'bio' => 'Chinese scientist who discovered artemisinin, a breakthrough treatment for malaria, saving millions of lives.',
                    'impact' => 'Opened a new era of natural-product-based drug discovery and modernized traditional medicine approaches.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 2015, 'contribution' => 'Discovery of artemisinin'],
                    ['name' => 'Lasker Award', 'year' => 2011, 'contribution' => 'Clinical medical research'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Louis Pasteur',
                    'nationality' => 'French',
                    'birth_year' => 1822,
                    'death_year' => 1895,
                    'field' => 'Microbiology, Immunology',
                    'bio' => 'Founder of modern microbiology and creator of vaccines for rabies and anthrax.',
                    'impact' => 'Established immunology principles essential for vaccine development and infectious disease treatment.',
                ],
                'awards' => [] // مافيش Nobel Prize لكن له إنجازات كبيرة
            ],
            [
                'info' => [
                    'name' => 'Kary Mullis',
                    'nationality' => 'American',
                    'birth_year' => 1944,
                    'death_year' => 2019,
                    'field' => 'Biochemistry, Molecular Biology',
                    'bio' => 'Kary Mullis was an American biochemist who invented the Polymerase Chain Reaction (PCR), transforming genetic analysis and diagnostics worldwide.',
                    'impact' => 'Enabled rapid DNA amplification which became essential in drug discovery, genomics, cancer research, and personalized medicine.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1993, 'contribution' => 'Invention of PCR'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Jonas Salk',
                    'nationality' => 'American',
                    'birth_year' => 1914,
                    'death_year' => 1995,
                    'field' => 'Virology, Immunology',
                    'bio' => 'Jonas Salk was an American medical researcher who developed the first successful polio vaccine.',
                    'impact' => 'His work laid the foundation for modern vaccine development and antiviral drug strategies.',
                ],
                'awards' => [
                    ['name' => 'Lasker Award', 'year' => 1956, 'contribution' => 'Polio vaccine development'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Selman Waksman',
                    'nationality' => 'American',
                    'birth_year' => 1888,
                    'death_year' => 1973,
                    'field' => 'Microbiology, Biochemistry',
                    'bio' => 'Selman Waksman discovered streptomycin, the first effective treatment for tuberculosis, and developed methods for screening antibiotics.',
                    'impact' => 'Established the systematic discovery of antibiotics, saving millions of lives.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1952, 'contribution' => 'Discovery of streptomycin'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Emil Fischer',
                    'nationality' => 'German',
                    'birth_year' => 1852,
                    'death_year' => 1919,
                    'field' => 'Organic Chemistry',
                    'bio' => 'Emil Fischer was a pioneering chemist whose studies on enzyme–substrate binding and molecular structures reshaped drug chemistry.',
                    'impact' => 'His lock-and-key model became the basis for modern drug-target interaction understanding.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1902, 'contribution' => 'Work on sugar and purine syntheses'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Frederick Banting',
                    'nationality' => 'Canadian',
                    'birth_year' => 1891,
                    'death_year' => 1941,
                    'field' => 'Medicine, Physiology',
                    'bio' => 'Banting co-discovered insulin, one of the most important medical breakthroughs of the 20th century.',
                    'impact' => 'Revolutionized treatment of diabetes and shaped modern endocrinology and drug therapy.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1923, 'contribution' => 'Discovery of insulin'],
                ]
            ],
            [
                'info' => [
                    'name' => 'Linus Pauling',
                    'nationality' => 'American',
                    'birth_year' => 1901,
                    'death_year' => 1994,
                    'field' => 'Chemistry, Molecular Biology',
                    'bio' => 'Linus Pauling was a chemist who contributed to molecular bonding theories and structural biology, influencing drug design.',
                    'impact' => 'Helped establish molecular medicine and understanding protein structures critical for drug development.',
                ],
                'awards' => [
                    ['name' => 'Nobel Prize in Physiology or Medicine', 'year' => 1954, 'contribution' => 'Research into the nature of the chemical bond'],
                ]
            ],
        ];

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true]
        ]);

        foreach ($scientistsData as $data) {
            $scientistInfo = $data['info'];

            // رفع الصور على Cloudinary
            $baseName = strtolower($scientistInfo['name']);
            $baseName = preg_replace('/[^a-z0-9]+/', '_', $baseName);

            $uploadedUrls = [];
            $index = 1;

            while (true) {
                $fileName = $index === 1
                    ? "{$baseName}.jpg"
                    : "{$baseName}_{$index}.jpg";

                $fullPath = public_path("imgs/scientists/{$fileName}");

                if (!file_exists($fullPath)) {
                    break;
                }

                try {
                    $result = $cloudinary->uploadApi()->upload(
                        $fullPath,
                        [
                            'resource_type' => 'auto',
                            'public_id' => 'scientists/' . pathinfo($fileName, PATHINFO_FILENAME),
                            'overwrite' => true
                        ]
                    );

                    $uploadedUrls[] = $result['secure_url'];
                    $this->command->info("Uploaded: {$fileName}");
                } catch (\Exception $e) {
                    $this->command->error("Failed to upload {$fileName}: " . $e->getMessage());
                }

                $index++;
            }

            $scientistInfo['images'] = $uploadedUrls;

            $scientist = Scientist::create($scientistInfo);
            $this->command->info("Created scientist: {$scientistInfo['name']}");

            foreach ($data['awards'] as $awardData) {
                $award = Award::where('name', $awardData['name'])->first();

                if ($award) {
                    $scientist->awards()->attach($award->id, [
                        'year_won' => $awardData['year'],
                        'contribution' => $awardData['contribution'],
                    ]);
                    $this->command->info("  -> Linked with {$awardData['name']} ({$awardData['year']})");
                } else {
                    $this->command->warn("  -> Award not found: {$awardData['name']}");
                }
            }
        }

        $this->command->info('All scientists seeded successfully with awards!');
    }
}
