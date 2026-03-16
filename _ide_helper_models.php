<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string $job_id
 * @property string $status
 * @property array<array-key, mixed>|null $parameters
 * @property array<array-key, mixed>|null $preview
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob wherePreview($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AiJob whereUserId($value)
 */
	class AiJob extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $category
 * @property array<array-key, mixed>|null $images
 * @property string|null $description
 * @property string|null $notable_winners
 * @property string|null $country
 * @property int|null $year_started
 * @property string|null $website
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scientist> $scientists
 * @property-read int|null $scientists_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereNotableWinners($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Award whereYearStarted($value)
 */
	class Award extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $title
 * @property string|null $summary
 * @property string $source
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSource($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|News whereUrl($value)
 */
	class News extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property string|null $specialization
 * @property string|null $university
 * @property int|null $years_of_experience
 * @property string|null $bio
 * @property string|null $photo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher wherePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereSpecialization($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereUniversity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Researcher whereYearsOfExperience($value)
 */
	class Researcher extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $news_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\News $news
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereNewsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SavedArticle whereUserId($value)
 */
	class SavedArticle extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $nationality
 * @property int|null $birth_year
 * @property int|null $death_year
 * @property array<array-key, mixed>|null $images
 * @property string $bio
 * @property string|null $impact
 * @property string|null $field
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Award> $awards
 * @property-read int|null $awards_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereBirthYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereDeathYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereField($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Scientist whereUpdatedAt($value)
 */
	class Scientist extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Simulation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Simulation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Simulation query()
 */
	class Simulation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $email_verification_otp
 * @property \Illuminate\Support\Carbon|null $email_verification_otp_expires_at
 * @property bool $is_verified
 * @property string|null $password_reset_otp
 * @property \Illuminate\Support\Carbon|null $password_reset_otp_expires_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\Researcher|null $researcher
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerificationOtpExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordResetOtp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePasswordResetOtpExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

