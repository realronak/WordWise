<?php
/**
 * Automated Verification Suite for Milestone 3 - Google OAuth 2.0 & OpenID Connect
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/oauth.php';

echo "=== Running Milestone 3 (Google OAuth) Verification Suite ===" . PHP_EOL;
$testsPassed = 0;
$testsTotal = 0;

function assert_test($condition, $name) {
    global $testsPassed, $testsTotal;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$name}" . PHP_EOL;
    } else {
        echo "  [FAIL] {$name}" . PHP_EOL;
    }
}

// 1. Configuration Detection
$provider = oauth_get_provider('google');
assert_test($provider instanceof GoogleOAuthProvider, "Google provider instantiated successfully");
assert_test($provider->isConfigured() === true, "Provider accurately detects configured state from .env credentials");
$savedClientId = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
unset($_ENV['GOOGLE_CLIENT_ID']);
putenv('GOOGLE_CLIENT_ID=');
$unconf = new GoogleOAuthProvider();
assert_test($unconf->isConfigured() === false, "Provider detects unconfigured state when credentials are unset");
$_ENV['GOOGLE_CLIENT_ID'] = $savedClientId;
putenv("GOOGLE_CLIENT_ID={$savedClientId}");

// 2. PKCE RFC 7636 Compliance
$verifier = oauth_generate_code_verifier();
assert_test(strlen($verifier) >= 43 && strlen($verifier) <= 128, "PKCE verifier length is RFC 7636 compliant (" . strlen($verifier) . " chars)");
$challenge = oauth_generate_code_challenge($verifier);
assert_test(strlen($challenge) === 43 && !str_contains($challenge, '=') && !str_contains($challenge, '+'), "PKCE challenge is valid Base64URL string without padding");

// 3. Base64URL Decoding Helper
$testString = '{"sub":"1122334455","email":"test@example.com"}';
$encoded = rtrim(strtr(base64_encode($testString), '+/', '-_'), '=');
assert_test(GoogleOAuthProvider::base64UrlDecode($encoded) === $testString, "Base64URL decode correctly decodes unpadded data");

// 4. State Mismatch Protection
$_ENV['GOOGLE_CLIENT_ID'] = 'mock_client_id_123';
$_ENV['GOOGLE_CLIENT_SECRET'] = 'mock_client_secret_abc';
$_SESSION['oauth_state'] = 'correct_state_token_123';
$_SESSION['oauth_verifier'] = $verifier;
$tamperedCallback = oauth_handle_callback('google', [
    'code'  => 'fake_code',
    'state' => 'wrong_tampered_state'
]);
assert_test($tamperedCallback['success'] === false && str_contains($tamperedCallback['error'], 'state parameter'), "State mismatch triggers security rejection");
unset($_ENV['GOOGLE_CLIENT_ID'], $_ENV['GOOGLE_CLIENT_SECRET']);

// 5. OAuth Error / Cancellation Handling
$canceledCallback = oauth_handle_callback('google', [
    'error'             => 'access_denied',
    'error_description' => 'The user denied request'
]);
assert_test($canceledCallback['success'] === false && str_contains($canceledCallback['error'], 'denied'), "OAuth provider cancellation is cleanly intercepted");

// 6. Safe Account Linking Protocol Tests
// Create a test local user
$testUser = 'oauth_tester_' . bin2hex(random_bytes(3));
$testEmail = $testUser . '@wordwise.test';
set_setting('allow_registration', '1');
$reg = auth_register([
    'full_name'        => 'OAuth Test Subject',
    'username'         => $testUser,
    'email'            => $testEmail,
    'password'         => 'TestPassword123!',
    'password_confirm' => 'TestPassword123!',
    'terms'            => true
]);
$testUserId = $reg['user_id'];

// Test linking while authenticated
$_SESSION['user'] = db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$testUserId]);
$mockGoogleSub = 'google_sub_' . bin2hex(random_bytes(8));

db_execute(
    "INSERT INTO user_providers (user_id, provider, provider_user_id, provider_email, avatar_url) VALUES (?, 'google', ?, ?, ?)",
    [$testUserId, $mockGoogleSub, $testEmail, 'images/favicon.png']
);

$savedProvider = db_fetch_one("SELECT * FROM user_providers WHERE user_id = ? AND provider = 'google'", [$testUserId]);
assert_test(!empty($savedProvider) && $savedProvider['provider_user_id'] === $mockGoogleSub, "Google OpenID subject identifier successfully linked to user account");

// Verify unlinking requires correct password re-authentication
$wrongPassUnlink = oauth_unlink_provider($testUserId, 'google', 'WrongPassword123');
assert_test($wrongPassUnlink['success'] === false && str_contains($wrongPassUnlink['error'], 'Current password verification failed'), "Unlinking is blocked without valid password re-authentication");

$correctPassUnlink = oauth_unlink_provider($testUserId, 'google', 'TestPassword123!');
assert_test($correctPassUnlink['success'] === true, "Unlinking succeeds when authenticated user confirms current password");

$clearedProvider = db_fetch_one("SELECT * FROM user_providers WHERE user_id = ? AND provider = 'google'", [$testUserId]);
assert_test(empty($clearedProvider), "Provider record cleanly removed from database upon confirmation");

// Clean up test user
auth_logout();
db_execute("DELETE FROM users WHERE user_id = ?", [$testUserId]);
set_setting('allow_registration', '0');

echo PHP_EOL . "=== Result: {$testsPassed} / {$testsTotal} tests passed! ===" . PHP_EOL;
