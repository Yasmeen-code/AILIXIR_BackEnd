<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Award;

class AwardSeeder extends Seeder
{
    public function run(): void
    {
        $awards = [

            [
                "name" => "Nobel Prize in Physiology or Medicine",
                "category" => "Medicine",
                "image_url" => "https://example.com/nobel.jpg",
                "description" => "The Nobel Prize in Physiology or Medicine is the world's most prestigious award in medical and biological sciences. It honors discoveries that fundamentally change our understanding of human biology, diseases, and therapeutic development. Over the decades, the prize has recognized major breakthroughs such as the discovery of insulin, antibiotics like penicillin, mRNA technology, and genetic mechanisms behind cancer and immune diseases.",
                "notable_winners" => "Alexander Fleming, Katalin Karikó & Drew Weissman, James Watson & Francis Crick, Tu Youyou",
                "country" => "Sweden",
                "year_started" => 1901,
                "website" => "https://www.nobelprize.org"
            ],

            [
                "name" => "Lasker Award",
                "category" => "Medical Research",
                "image_url" => "https://example.com/lasker.jpg",
                "description" => "The Lasker Award is often called the 'American Nobel Prize' and recognizes outstanding achievements in medical science, public health, and clinical research. Many discoveries that have reshaped modern medicine were first awarded by Lasker before later receiving the Nobel.",
                "notable_winners" => "Anthony Fauci, Emmanuelle Charpentier & Jennifer Doudna, Akira Endo",
                "country" => "United States",
                "year_started" => 1945,
                "website" => "https://laskerfoundation.org"
            ],

            [
                "name" => "Wolf Prize in Medicine",
                "category" => "Medicine",
                "image_url" => "https://example.com/wolf.jpg",
                "description" => "The Wolf Prize in Medicine honors scientists whose work significantly impacts global medicine and life sciences. It is one of the highest international honors and often predicts future Nobel laureates. The award celebrates contributions that advance disease understanding, therapeutic innovation, and molecular biology.",
                "notable_winners" => "Shinya Yamanaka, Marc Feldmann & Ravinder Maini, Bert Vogelstein",
                "country" => "Israel",
                "year_started" => 1978,
                "website" => "https://wolf-prize.org"
            ],

            [
                "name" => "Gairdner International Award",
                "category" => "Biomedical Research",
                "image_url" => "https://example.com/gairdner.jpg",
                "description" => "The Gairdner Award is one of the most respected biomedical prizes worldwide. More than 95 of its recipients have later received the Nobel Prize. The award recognizes groundbreaking scientific discoveries in molecular biology, drug discovery, and disease mechanisms.",
                "notable_winners" => "David Julius, Mary-Claire King, Pieter Cullis",
                "country" => "Canada",
                "year_started" => 1959,
                "website" => "https://gairdner.org"
            ],

            [
                "name" => "Breakthrough Prize in Life Sciences",
                "category" => "Life Sciences",
                "image_url" => "https://example.com/breakthrough.jpg",
                "description" => "The Breakthrough Prize in Life Sciences celebrates transformative achievements in understanding living systems and improving human health. It recognizes breakthroughs in genetics, neuroscience, cancer biology, and modern therapeutics. It is one of the richest science awards in the world.",
                "notable_winners" => "Svante Pääbo, Emmanuelle Charpentier & Jennifer Doudna, Louisa R. Manfredi",
                "country" => "United States",
                "year_started" => 2013,
                "website" => "https://breakthroughprize.org"
            ],

            [
                "name" => "IUPAC-Richter Prize in Medicinal Chemistry",
                "category" => "Medicinal Chemistry",
                "image_url" => "https://example.com/iupac.jpg",
                "description" => "This prize recognizes outstanding contributions to medicinal chemistry and innovative drug design. It honors researchers whose work led to important therapeutic agents or advanced chemical approaches for drug discovery.",
                "notable_winners" => "Peter J. Tonge, Stephen M. Coats",
                "country" => "International",
                "year_started" => 2006,
                "website" => "https://iupac.org"
            ],

            [
                "name" => "EFMC Award for Scientific Excellence",
                "category" => "Medicinal Chemistry / Chemical Biology",
                "image_url" => "https://example.com/efmc.jpg",
                "description" => "The EFMC Award honors world-leading scientists in medicinal chemistry. It highlights impactful discoveries in molecular design, drug mechanism understanding, and chemical biology.",
                "notable_winners" => "Benjamin Cravatt, Paul Wender",
                "country" => "Europe",
                "year_started" => 2000,
                "website" => "https://efmc.info"
            ],

            [
                "name" => "Lurie Prize in Biomedical Sciences",
                "category" => "Biomedical Sciences",
                "image_url" => "https://example.com/lurie.jpg",
                "description" => "The Lurie Prize recognizes exceptional early-career scientists who make breakthrough contributions to biomedical science. It highlights innovative work that enhances disease understanding and therapeutic possibilities.",
                "notable_winners" => "Feng Zhang, Ruslan Medzhitov, Rachel Wilson",
                "country" => "United States",
                "year_started" => 2013,
                "website" => "https://fnih.org"
            ],

            [
                "name" => "Paul Janssen Award for Biomedical Research",
                "category" => "Biomedical Research",
                "image_url" => "https://example.com/janssen.jpg",
                "description" => "The Paul Janssen Award honors scientists whose research leads to major advances in biomedical science and drug development. It continues the legacy of Dr. Paul Janssen, a pioneer in drug discovery.",
                "notable_winners" => "Yoshinori Ohsumi, Brian Druker, Jules Hoffmann",
                "country" => "United States",
                "year_started" => 2004,
                "website" => "https://pauljanssenaward.com"
            ],

            [
                "name" => "Tang Prize in Biopharmaceutical Science",
                "category" => "Biopharmaceutical Science",
                "image_url" => "https://example.com/tang.jpg",
                "description" => "The Tang Prize honors groundbreaking achievements in therapeutics, biotechnology, and modern drug discovery. It recognizes contributions in gene editing, mRNA vaccines, immunotherapy, and molecular medicine.",
                "notable_winners" => "James P. Allison, Emmanuelle Charpentier, Dan Barouch",
                "country" => "Taiwan",
                "year_started" => 2012,
                "website" => "https://tang-prize.org"
            ],
        ];

        foreach ($awards as $award) {
            Award::create($award);
        }
    }
}
