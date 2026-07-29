<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterOtpFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_then_verify_otp_creates_account_and_logs_in(): void
    {
        Mail::fake();

        $response = $this->postJson('/register', [
            'full_name' => 'Nguyen Van A',
            'email' => 'newcustomer@gmail.com',
            'password' => 'Leminhvu9124@',
            'password_confirmation' => 'Leminhvu9124@',
        ]);

        $response->assertOk()->assertJson(['success' => true, 'otp_required' => true]);

        $otp = session('verify_otp');
        $this->assertNotNull($otp, 'verify_otp should be in session after register');

        $digits = str_split((string) $otp);

        $verify = $this->postJson('/verify-otp', ['otp' => $digits]);

        $verify->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('users', ['email' => 'newcustomer@gmail.com']);
        $this->assertAuthenticated();
    }

    public function test_otp_is_rejected_after_expiry_window(): void
    {
        Mail::fake();

        $this->postJson('/register', [
            'full_name' => 'Nguyen Van B',
            'email' => 'expired@gmail.com',
            'password' => 'Leminhvu9124@',
            'password_confirmation' => 'Leminhvu9124@',
        ])->assertOk();

        $otp = session('verify_otp');
        $digits = str_split((string) $otp);

        // Rewind the stored issue time well past the allowed window.
        session(['verify_otp_time' => now()->subMinutes(30)]);

        $this->postJson('/verify-otp', ['otp' => $digits])->assertStatus(422);
        $this->assertDatabaseMissing('users', ['email' => 'expired@gmail.com']);
        $this->assertGuest();
    }

    public function test_verify_otp_handles_email_taken_between_register_and_verify_gracefully(): void
    {
        Mail::fake();

        $this->postJson('/register', [
            'full_name' => 'Nguyen Van C',
            'email' => 'racecondition@gmail.com',
            'password' => 'Leminhvu9124@',
            'password_confirmation' => 'Leminhvu9124@',
        ])->assertOk();

        $otp = session('verify_otp');
        $digits = str_split((string) $otp);

        // Simulate the account having been created in the gap between register and verify
        // (e.g. a duplicate submit in another tab) so User::create() below hits the unique
        // constraint on email instead of succeeding.
        User::factory()->create(['email' => 'racecondition@gmail.com']);

        $response = $this->postJson('/verify-otp', ['otp' => $digits]);

        $response->assertStatus(422);
        $this->assertGuest();
    }
}
