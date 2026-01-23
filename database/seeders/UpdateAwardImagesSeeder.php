<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Award;
use Cloudinary\Cloudinary;

class UpdateAwardImagesSeeder extends Seeder
{
    public function run(): void
    {
        // أسماء الملفات الموجودة فعليًا في public/imgs/awards
        $awardsImages = [
            'Nobel Prize in Physiology or Medicine' => ['nobel.jpg'],
            'Lasker Award' => ['lasker.jpg', 'lasker_2.jpg'],
            'Keio Medical Science Prize' => ['keio.jpg', 'keio_2.jpg'],
            'Gairdner International Award' => ['gairdner.jpg', 'gairdner_2.jpeg'],
            'Breakthrough Prize in Life Sciences' => ['breakthrough.jpg', 'breakthrough_2.jpg'],
            'IUPAC-Richter Prize in Medicinal Chemistry' => ['IUPAC-Richter.jpg', 'IUPAC-Richter_2.jpg'],
            'EFMC Award for Scientific Excellence' => ['efmc.jpg'],
            'Lurie Prize in Biomedical Sciences' => ['lurie.jpg', 'lurie_2.jpg'],
            'Paul Janssen Award for Biomedical Research' => ['paul.jpg'],
            'Tang Prize in Biopharmaceutical Science' => ['tang.jpg', 'tang_2.jpg'],
        ];

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true]
        ]);

        foreach ($awardsImages as $awardName => $images) {
            $award = Award::where('name', $awardName)->first();
            if (!$award) continue;

            $uploadedUrls = [];

            foreach ($images as $image) {
                $filePath = public_path('imgs/awards/' . $image);

                if (!file_exists($filePath)) {
                    $this->command->warn("File not found: {$filePath}");
                    continue;
                }
                $result = $cloudinary->uploadApi()->upload(
                    $filePath,
                    [
                        'resource_type' => 'auto', // يسمح بأي امتداد
                        'public_id' => 'awards/' . pathinfo($image, PATHINFO_FILENAME), // يرفع الملف داخل مجلد awards
                        'overwrite' => true // لو نفس الاسم موجود، يتم استبداله
                    ]
                );


                $uploadedUrls[] = $result['secure_url'];
            }

            if (!empty($uploadedUrls)) {
                $award->images = $uploadedUrls;
                $award->save();
                $this->command->info("Updated images for {$awardName}");
            }
        }

        $this->command->info('All award images updated successfully!');
    }
}
