<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Scientist;

class ScientistSeeder extends Seeder
{
    public function run()
    {
        $scientists = [

            [
                'name' => 'Avicenna (Ibn Sina)',
                'nationality' => 'Persian',
                'birth_year' => 980,
                'death_year' => 1037,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/7d/Ibn_Sina.jpg/220px-Ibn_Sina.jpg',
                'field' => 'Medicine, Pharmacology',
                'bio' => 'Ibn Sina, known as Avicenna, was a Persian polymath who wrote the famous medical encyclopedia "The Canon of Medicine", which was used in Europe for 600 years.',
                'impact' => 'Laid the foundation for clinical pharmacology and herbal-based treatments, influencing modern drug discovery concepts.',
            ],

            [
                'name' => 'Alexander Fleming',
                'nationality' => 'British',
                'birth_year' => 1881,
                'death_year' => 1955,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/6e/Sir_Alexander_Fleming.jpg',
                'field' => 'Microbiology',
                'bio' => 'Fleming was a biologist and pharmacologist best known for discovering penicillin in 1928, the world’s first antibiotic.',
                'impact' => 'Revolutionized medicine by introducing antibiotics, saving millions of lives and shaping drug development against bacterial infections.',
            ],

            [
                'name' => 'Paul Ehrlich',
                'nationality' => 'German',
                'birth_year' => 1854,
                'death_year' => 1915,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/09/Paul_Ehrlich.jpg',
                'field' => 'Immunology, Chemotherapy',
                'bio' => 'Paul Ehrlich was a physician and scientist who developed the first targeted drug therapy and introduced the concept of “magic bullets”.',
                'impact' => 'Founder of chemotherapy and target-based drug discovery approaches widely used today.',
            ],

            [
                'name' => 'Gertrude Elion',
                'nationality' => 'American',
                'birth_year' => 1918,
                'death_year' => 1999,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/43/Gertrude_Elion.jpg',
                'field' => 'Biochemistry, Pharmacology',
                'bio' => 'Nobel Prize–winning scientist who developed revolutionary drugs for leukemia, AIDS, and prevention of organ transplant rejection.',
                'impact' => 'Pioneer of rational drug design methods and biochemical targeting.',
            ],

            [
                'name' => 'Tu Youyou',
                'nationality' => 'Chinese',
                'birth_year' => 1930,
                'death_year' => null,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/Tu_Youyou.jpg/220px-Tu_Youyou.jpg',
                'field' => 'Pharmaceutical Chemistry',
                'bio' => 'Chinese scientist who discovered artemisinin, a breakthrough treatment for malaria, saving millions of lives.',
                'impact' => 'Opened a new era of natural-product-based drug discovery and modernized traditional medicine approaches.',
            ],

            [
                'name' => 'Louis Pasteur',
                'nationality' => 'French',
                'birth_year' => 1822,
                'death_year' => 1895,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/0e/Louis_Pasteur%2C_foto_av_F%C3%A9lix_Nadar_1820-1910.jpg/220px-Louis_Pasteur%2C_foto_av_F%C3%A9lix_Nadar_1820-1910.jpg',
                'field' => 'Microbiology, Immunology',
                'bio' => 'Founder of modern microbiology and creator of vaccines for rabies and anthrax.',
                'impact' => 'Established immunology principles essential for vaccine development and infectious disease treatment.',
            ],
            [
                'name' => 'Kary Mullis',
                'nationality' => 'American',
                'birth_year' => 1944,
                'death_year' => 2019,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b7/Kary_Mullis_Ted_Talk.jpg',
                'field' => 'Biochemistry, Molecular Biology',
                'bio' => 'Kary Mullis was an American biochemist who invented the Polymerase Chain Reaction (PCR), transforming genetic analysis and diagnostics worldwide.',
                'impact' => 'Enabled rapid DNA amplification which became essential in drug discovery, genomics, cancer research, and personalized medicine.',
            ],
            [
                'name' => 'Jonas Salk',
                'nationality' => 'American',
                'birth_year' => 1914,
                'death_year' => 1995,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/5/57/Jonas_Salk_candid.jpg',
                'field' => 'Virology, Immunology',
                'bio' => 'Jonas Salk was an American medical researcher who developed the first successful polio vaccine.',
                'impact' => 'His work laid the foundation for modern vaccine development and antiviral drug strategies.',
            ],

            [
                'name' => 'Selman Waksman',
                'nationality' => 'American',
                'birth_year' => 1888,
                'death_year' => 1973,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/82/S_Waksman.jpg',
                'field' => 'Microbiology, Biochemistry',
                'bio' => 'Selman Waksman discovered streptomycin, the first effective treatment for tuberculosis, and developed methods for screening antibiotics.',
                'impact' => 'Established the systematic discovery of antibiotics, saving millions of lives.',
            ],
            [
                'name' => 'Emil Fischer',
                'nationality' => 'German',
                'birth_year' => 1852,
                'death_year' => 1919,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/7/7a/Emil_Fischer.jpg',
                'field' => 'Organic Chemistry',
                'bio' => 'Emil Fischer was a pioneering chemist whose studies on enzyme–substrate binding and molecular structures reshaped drug chemistry.',
                'impact' => 'His lock-and-key model became the basis for modern drug-target interaction understanding.',
            ],
            [
                'name' => 'Frederick Banting',
                'nationality' => 'Canadian',
                'birth_year' => 1891,
                'death_year' => 1941,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/2/2f/Frederick_Banting_1923.jpg',
                'field' => 'Medicine, Physiology',
                'bio' => 'Banting co-discovered insulin, one of the most important medical breakthroughs of the 20th century.',
                'impact' => 'Revolutionized treatment of diabetes and shaped modern endocrinology and drug therapy.',
            ],
            [
                'name' => 'Linus Pauling',
                'nationality' => 'American',
                'birth_year' => 1901,
                'death_year' => 1994,
                'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/09/Linus_Pauling.jpg',
                'field' => 'Chemistry, Molecular Biology',
                'bio' => 'Linus Pauling was a chemist who contributed to molecular bonding theories and structural biology, influencing drug design.',
                'impact' => 'Helped establish molecular medicine and understanding protein structures critical for drug development.',
            ],

        ];

        Scientist::insert($scientists);
    }
}
