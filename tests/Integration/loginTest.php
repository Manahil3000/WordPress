<?php
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase {

    /** @test */
    public function test_wp_login_page_loads() {
        $this->assertFileExists( ABSPATH . 'wp-login.php', 'Login page should exist in root directory' );
    }

    /** @test */
    public function test_user_can_register_and_login() {
        // Create a random username to avoid conflict
        $username = 'testuser_' . rand(1000,9999);
        $email = $username . '@example.com';
        $password = 'password123';

        // Create new user
        $user_id = wp_create_user($username, $password, $email);
        $this->assertIsInt($user_id, 'User should be created successfully');

        // Try to log in
        $creds = [
            'user_login'    => $username,
            'user_password' => $password,
            'remember'      => true
        ];

        $user = wp_signon($creds, false);
        $this->assertInstanceOf(WP_User::class, $user, 'User should log in successfully');
    }

    /** @test */
    public function test_invalid_login_returns_error() {
        $creds = [
            'user_login'    => 'invalid_user',
            'user_password' => 'wrong_pass'
        ];

        $user = wp_signon($creds, false);
        $this->assertInstanceOf(WP_Error::class, $user, 'Invalid credentials should return WP_Error');
    }

    /** @test */
    public function test_password_reset_email_trigger() {
        // Use a dummy email
        $user = get_user_by('login', 'admin');
        if (!$user) {
            $this->markTestSkipped('No admin user found for password reset test');
        }

        $result = retrieve_password($user->user_login);
        $this->assertTrue($result, 'Password reset should send an email (simulated)');
    }

    /** @test */
    public function test_logout_redirects_to_login() {
        // Simulate logout URL creation
        $logout_url = wp_logout_url();
        $this->assertStringContainsString('action=logout', $logout_url, 'Logout URL should contain action=logout');
    }
}
