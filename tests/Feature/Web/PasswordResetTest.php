<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Warga', 'email' => 'warga@visueco.test',
            'password' => bcrypt('password'), 'role' => 'user',
        ]);
    }

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_request_is_anti_enumeration(): void
    {
        // Email tak terdaftar tetap memberi pesan netral (tak membocorkan).
        $this->post('/forgot-password', ['email' => 'notexist@visueco.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();
        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token'                 => $notification->token,
                'email'                 => $user->email,
                'password'              => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

            $response->assertRedirect('/login')->assertSessionHas('status');

            return true;
        });

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password)
        );
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = $this->makeUser();

        $this->post('/reset-password', [
            'token'                 => 'token-palsu',
            'email'                 => $user->email,
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('email');

        // Password lama tetap berlaku
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('password', $user->fresh()->password)
        );
    }

    public function test_reset_screen_renders_with_token(): void
    {
        $token = Password::createToken($this->makeUser());

        $this->get("/reset-password/{$token}?email=warga@visueco.test")
            ->assertStatus(200);
    }
}
