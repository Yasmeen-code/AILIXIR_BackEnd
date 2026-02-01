<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Award;
use App\Models\Scientist;

class AwardScientistSeeder extends Seeder
{
    public function run(): void
    {
        $relations = [
            [
                'scientist' => 'Alexander Fleming',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1945,
                'contribution' => 'Discovery of penicillin and its curative effect in various infectious diseases'
            ],
            [
                'scientist' => 'Paul Ehrlich',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1908,
                'contribution' => 'Work on immunity, especially the side-chain theory of antibody formation'
            ],
            [
                'scientist' => 'Gertrude Elion',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1988,
                'contribution' => 'Discovery of important principles for drug treatment and development of new drugs'
            ],
            [
                'scientist' => 'Tu Youyou',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 2015,
                'contribution' => 'Discovery of artemisinin and its use in the treatment of malaria'
            ],
            [
                'scientist' => 'Tu Youyou',
                'award' => 'Lasker Award',
                'year' => 2011,
                'contribution' => 'Clinical medical research for the discovery of artemisinin'
            ],
            [
                'scientist' => 'Kary Mullis',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1993,
                'contribution' => 'Invention of the polymerase chain reaction (PCR) method'
            ],
            [
                'scientist' => 'Jonas Salk',
                'award' => 'Lasker Award',
                'year' => 1956,
                'contribution' => 'Development of the poliomyelitis vaccine (euflavac) and its role in the prevention of the disease'
            ],
            [
                'scientist' => 'Selman Waksman',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1952,
                'contribution' => 'Discovery of streptomycin, the first antibiotic effective against tuberculosis'
            ],
            [
                'scientist' => 'Emil Fischer',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1902,
                'contribution' => 'Work on sugar and purine syntheses, establishing the field of biochemistry'
            ],
            [
                'scientist' => 'Frederick Banting',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1923,
                'contribution' => 'Discovery of insulin and its therapeutic value in the treatment of diabetes mellitus'
            ],
            [
                'scientist' => 'Linus Pauling',
                'award' => 'Nobel Prize in Physiology or Medicine',
                'year' => 1954,
                'contribution' => 'Research into the nature of the chemical bond and its application to the structure of complex substances'
            ],
        ];

        $successCount = 0;
        $skipCount = 0;

        foreach ($relations as $rel) {
            try {
                $scientist = Scientist::where('name', $rel['scientist'])->first();
                $award = Award::where('name', $rel['award'])->first();

                if (!$scientist) {
                    $this->command->warn("Scientist not found: {$rel['scientist']}");
                    continue;
                }

                if (!$award) {
                    $this->command->warn("Award not found: {$rel['award']}");
                    continue;
                }

                $exists = $scientist->awards()
                    ->where('award_id', $award->id)
                    ->wherePivot('year_won', $rel['year'])
                    ->exists();

                if ($exists) {
                    $this->command->info("Skipped (exists): {$rel['scientist']} ↔ {$rel['award']}");
                    $skipCount++;
                    continue;
                }

                $scientist->awards()->attach($award->id, [
                    'year_won' => $rel['year'],
                    'contribution' => $rel['contribution'],
                ]);

                $this->command->info("Linked: {$rel['scientist']} ↔ {$rel['award']} ({$rel['year']})");
                $successCount++;
            } catch (\Exception $e) {
                $this->command->error("Error linking {$rel['scientist']}: " . $e->getMessage());
            }
        }

        $this->command->info('');
        $this->command->info("Summary: {$successCount} linked, {$skipCount} skipped");
        $this->command->info('Award-Scientist relationships completed!');
    }
}
