<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Award;
use Cloudinary\Cloudinary;

class AwardCompleteSeeder extends Seeder
{
    public function run(): void
    {
        // التأكد من إن Cloudinary شغال
        if (!env('CLOUDINARY_API_SECRET')) {
            $this->command->error('❌ CLOUDINARY_API_SECRET missing!');
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

        $awards = [ /* ... نفس الـ Array اللي عندك ... */];

        foreach ($awards as $awardData) {
            try {
                // 1. إنشاء أو تحديث الـ Award (لو موجود)
                $award = Award::updateOrCreate(
                    ['name' => $awardData['name']], // البحث بالاسم
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

                // 2. البحث عن الصور المحلية
                $baseName = strtolower(Str::slug(explode(' ', $awardData['name'])[0]));
                $localImages = [];
                $index = 1;

                while (true) {
                    $fileName = $index === 1 ? "{$baseName}.jpg" : "{$baseName}_{$index}.jpg";
                    $fullPath = public_path("imgs/awards/{$fileName}");

                    if (!file_exists($fullPath)) {
                        break;
                    }
                    $localImages[] = $fullPath;
                    $index++;
                }

                // 3. لو مفيش صور محلية، نبحث في Cloudinary مباشرة (أو نتخطى)
                if (empty($localImages)) {
                    $this->command->warn("⚠️ No local images for: {$awardData['name']}");
                    continue;
                }

                // 4. رفع الصور
                $cloudinaryUrls = [];
                foreach ($localImages as $filePath) {
                    try {
                        $result = $cloudinary->uploadApi()->upload($filePath, [
                            'resource_type' => 'auto',
                            'public_id' => "awards/{$award->id}_{$baseName}_" . pathinfo($filePath, PATHINFO_FILENAME),
                            'overwrite' => true
                        ]);
                        $cloudinaryUrls[] = $result['secure_url'];
                        $this->command->info("✅ Uploaded: " . basename($filePath));
                    } catch (\Exception $e) {
                        $this->command->error("❌ Upload failed: " . $e->getMessage());
                    }
                }

                // 5. حفظ الـ URLs
                if (!empty($cloudinaryUrls)) {
                    $award->images = $cloudinaryUrls;
                    $award->save();
                    $this->command->info("✅ Done: {$award->name}");
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Failed for {$awardData['name']}: " . $e->getMessage());
            }
        }

        $this->command->info('🎉 Seeding completed!');
    }
}
