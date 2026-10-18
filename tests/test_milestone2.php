<?php
/**
 * Automated Verification Suite for Milestone 2
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== Running Milestone 2 Verification Suite ===" . PHP_EOL;
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

// 1. Safe Redirect Tests
assert_test(safe_redirect('/admin/posts.php') === '/admin/posts.php', "safe_redirect allows valid relative path");
assert_test(safe_redirect('https://evil.com') === '/', "safe_redirect blocks external URLs");
assert_test(safe_redirect('//evil.com') === '/', "safe_redirect blocks protocol-relative URLs");
assert_test(safe_redirect('/\\evil.com') === '/', "safe_redirect blocks backslash tricks");
assert_test(safe_redirect('/post.php?slug=test') === '/post.php?slug=test', "safe_redirect preserves safe query string");

// 2. Test Registration Disabled by Default
set_setting('allow_registration', '0');
$disabledReg = auth_register([
    'full_name'        => 'Test User',
    'username'         => 'testuser',
    'email'            => 'testuser@example.com',
    'password'         => 'SecurePass123!',
    'password_confirm' => 'SecurePass123!',
    'terms'            => true
]);
assert_test($disabledReg['success'] === false, "Registration is blocked when allow_registration is 0");

// 3. Enable Registration & Test Validation
set_setting('allow_registration', '1');

// Weak password test
$weakPass = auth_register([
    'full_name'        => 'Test User',
    'username'         => 'testuser',
    'email'            => 'testuser@example.com',
    'password'         => 'weak',
    'password_confirm' => 'weak',
    'terms'            => true
]);
assert_test($weakPass['success'] === false, "Registration rejects short password");

// Password mismatch test
$mismatchPass = auth_register([
    'full_name'        => 'Test User',
    'username'         => 'testuser',
    'email'            => 'testuser@example.com',
    'password'         => 'ValidPass123!',
    'password_confirm' => 'Different123!',
    'terms'            => true
]);
assert_test($mismatchPass['success'] === false, "Registration rejects password mismatch");

// Valid Registration
$testUsername = 'test_' . bin2hex(random_bytes(3));
$testEmail = $testUsername . '@wordwise.test';
$validReg = auth_register([
    'full_name'        => 'Editorial Contributor',
    'username'         => $testUsername,
    'email'            => $testEmail,
    'password'         => 'WordWise@2026!',
    'password_confirm' => 'WordWise@2026!',
    'terms'            => true
]);
assert_test($validReg['success'] === true && !empty($validReg['token']), "Registration succeeds with valid input and generates verification token");

$newUserId = $validReg['user_id'] ?? 0;

// Duplicate Check
$dupUser = auth_register([
    'full_name'        => 'Another User',
    'username'         => $testUsername,
    'email'            => 'unique_' . $testEmail,
    'password'         => 'WordWise@2026!',
    'password_confirm' => 'WordWise@2026!',
    'terms'            => true
]);
assert_test($dupUser['success'] === false, "Duplicate username registration prevented");

// 4. Email Verification Test
$verifyRes = auth_verify_email($validReg['token']);
assert_test($verifyRes['success'] === true, "Email verification token successfully validates");

$dbUser = db_fetch_one("SELECT email_verified_at, verification_token FROM users WHERE user_id = ?", [$newUserId]);
assert_test(!empty($dbUser['email_verified_at']) && $dbUser['verification_token'] === null, "Database record updated with verified timestamp and cleared token");

// Re-using same token should fail
$reverify = auth_verify_email($validReg['token']);
assert_test($reverify['success'] === false, "Verification token cannot be re-used (single-use enforcement)");

// 5. Authentication Test
$invalidLogin = auth_login($testUsername, 'WrongPassword123');
assert_test($invalidLogin['success'] === false, "Authentication rejects invalid password");

$validLogin = auth_login($testUsername, 'WordWise@2026!', true);
assert_test($validLogin['success'] === true, "Authentication succeeds with correct credentials and creates session");

// Verify remember token created in db
$userWithRemember = db_fetch_one("SELECT remember_token FROM users WHERE user_id = ?", [$newUserId]);
assert_test(!empty($userWithRemember['remember_token']), "Persistent remember-me token stored securely as hash");

// 6. Password Reset Flow Test
$resetReq = auth_request_password_reset($testEmail);
assert_test($resetReq['success'] === true && !empty($resetReq['token']), "Password reset request creates token with 1-hour expiry");

$resetAction = auth_reset_password($resetReq['token'], 'NewWordWise@2026!');
assert_test($resetAction['success'] === true, "Password reset succeeds with valid token and strong new password");

$oldPassLogin = auth_login($testUsername, 'WordWise@2026!');
assert_test($oldPassLogin['success'] === false, "Old password rejected after password reset");

$newPassLogin = auth_login($testUsername, 'NewWordWise@2026!');
assert_test($newPassLogin['success'] === true, "New password authenticated successfully");

// 7. Profile Update Test
$_SESSION['user'] = db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$newUserId]);
$profileUpdate = auth_update_profile($newUserId, [
    'full_name' => 'Updated Contributor Name',
    'bio'       => 'Writing on modern architecture and systems design.'
]);
assert_test($profileUpdate['success'] === true, "Profile update executes cleanly");

$refreshedUser = db_fetch_one("SELECT full_name, bio FROM users WHERE user_id = ?", [$newUserId]);
assert_test($refreshedUser['full_name'] === 'Updated Contributor Name', "Profile update persisted to MariaDB");

// 8. Clean up test record and restore allow_registration setting
auth_logout();
db_execute("DELETE FROM users WHERE user_id = ?", [$newUserId]);
set_setting('allow_registration', '0'); // Restored to safe default

echo PHP_EOL . "=== Result: {$testsPassed} / {$testsTotal} tests passed! ===" . PHP_EOL;
