<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\ProfileRepository;
use Cloudinary\Cloudinary;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    protected Cloudinary $cloudinary;
    protected ProfileRepository $repo;

    public function __construct(ProfileRepository $repo)
    {
        $this->repo = $repo;

        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true]
        ]);
    }

    public function updateProfile(User $user, array $validated, $request): User
    {
        $photoUrl = $this->handlePhotoUpload($request, $user);

        $userData = Arr::only($validated, ['name', 'email']);
        if (isset($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }
        if ($photoUrl) {
            $userData['photo'] = $photoUrl;
        }

        $user->fill($userData);

        $researcher = $this->repo->getUserResearcher($user);

        if ($user->role === 'normal' && ($request->filled('specialization') || $request->filled('university'))) {
            $user->role = 'researcher';
            $this->repo->saveUser($user);
            $this->createResearcher($user, $request, $photoUrl);
        } else {
            $this->repo->saveUser($user);
            if ($researcher) {
                $this->updateResearcher($researcher, $request, $photoUrl);
            }
        }

        return $user->fresh()->load('researcher');
    }

    protected function handlePhotoUpload($request, User $user): ?string
    {
        if (!$request->hasFile('photo')) {
            return null;
        }

        $file = $request->file('photo');
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'resource_type' => 'auto',
                'public_id'     => 'users/' . $user->id . '_' . time(),
                'overwrite'     => true,
            ]
        );

        return $result['secure_url'] ?? null;
    }

    protected function createResearcher(User $user, $request, ?string $photoUrl)
    {
        $data = [
            'user_id'            => $user->id,
            'specialization'     => $request->specialization,
            'university'         => $request->university,
            'years_of_experience' => $request->years_of_experience ?? 0,
            'bio'                => $request->bio,
            'photo'              => $photoUrl,
        ];

        $this->repo->createResearcher($data);
    }

    protected function updateResearcher($researcher, $request, ?string $photoUrl)
    {
        $data = [
            'specialization'      => $request->specialization ?? $researcher->specialization,
            'university'          => $request->university ?? $researcher->university,
            'years_of_experience' => $request->years_of_experience ?? $researcher->years_of_experience,
            'bio'                 => $request->bio ?? $researcher->bio,
        ];

        if ($photoUrl) {
            $data['photo'] = $photoUrl;
        }

        $this->repo->updateResearcher($researcher, $data);
    }
}
