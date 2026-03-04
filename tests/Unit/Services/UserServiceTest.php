<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Mockery;

class UserServiceTest extends TestCase
{
    protected $userRepo;
    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepo = Mockery::mock(UserRepository::class);
        $this->userService = new UserService($this->userRepo);
    }

    public function test_login_user_successfully()
    {
        $password = '123456';

        $user = Mockery::mock(User::class)->makePartial();
        $user->password = Hash::make($password);
        $user->is_verified = true;

        $user->shouldReceive('tokens->delete')->once();
        $user->shouldReceive('createToken')
            ->once()
            ->andReturn((object)['plainTextToken' => 'fake_token']);

        $this->userRepo
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        $result = $this->userService->loginUser([
            'email' => 'hazem@test.com',
            'password' => $password
        ]);

        $this->assertEquals('fake_token', $result['token']);
    }

    public function test_login_fails_with_wrong_password()
    {
        $user = new User();
        $user->password = Hash::make('correct_password');
        $user->is_verified = true;

        $this->userRepo
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        $result = $this->userService->loginUser([
            'email' => 'hazem@test.com',
            'password' => 'wrong_password'
        ]);

        $this->assertEquals(401, $result['code']);
    }

    public function test_google_login_success()
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake_access_token'
            ])
        ]);

        $googleUser = Mockery::mock();
        $googleUser->shouldReceive('getEmail')->andReturn('hazem@test.com');
        $googleUser->shouldReceive('getName')->andReturn('Hazem');
        $googleUser->shouldReceive('getAvatar')->andReturn('avatar.jpg');

        Socialite::shouldReceive('driver->stateless->userFromToken')
            ->andReturn($googleUser);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('createToken')
            ->andReturn((object)['plainTextToken' => 'google_token']);

        $this->userRepo
            ->shouldReceive('firstOrCreate')
            ->andReturn($user);

        $result = $this->userService->loginGoogleUser('fake_code');

        $this->assertEquals('google_token', $result['token']);
    }

    public function test_register_user_creates_user_successfully()
    {
        $data = [
            'name' => 'Hazem',
            'email' => 'hazem@test.com',
            'password' => '123456'
        ];

        $this->userRepo
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($argument) use ($data) {

                return $argument['name'] === $data['name']
                    && $argument['email'] === $data['email']
                    && $argument['role'] === 'normal'
                    && $argument['is_verified'] === false
                    && \Illuminate\Support\Facades\Hash::check(
                        $data['password'],
                        $argument['password']
                    );
            }))
            ->andReturn(new \App\Models\User($data));

        $result = $this->userService->registerUser($data);

        $this->assertInstanceOf(\App\Models\User::class, $result);
    }
}
