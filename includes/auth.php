<?php
/**
 * WordWise Authentication & Role-Based Authorization
 * Secure sessions, bcrypt passwords, rate limiting, and RBAC permissions.
 */

require_once __DIR__ . '/db.php';

/**
 * Get current authenticated user
 */
function auth_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if a user is logged in
 */
function auth_check(): bool {
    if (!empty($_SESSION['user']) && ($_SESSION['user']['status'] ?? '') === 'active') {
        return true;
    }
    return auth_check_remember_token();
}

/**
 * Get current user ID
 */
function auth_id(): ?int {
    return $_SESSION['user']['user_id'] ?? null;
}

/**
 * Get current user role
 */
function auth_role(): ?string {
    return $_SESSION['user']['user_role'] ?? null;
}

/**
 * Check if current user has permission
 */
function auth_can(string $action, ?int $ownerId = null): bool {
    if (!auth_check()) return false;
    
    $role = auth_role();
    if ($role === 'admin') return true; // Administrator has full CMS capabilities

    switch ($action) {
        case 'manage_settings':
        case 'manage_users':
            return $role === 'admin';

        case 'manage_categories':
        case 'manage_tags':
        case 'publish_posts':
        case 'manage_all_posts':
            return in_array($role, ['admin', 'editor'], true);

        case 'upload_media':
        case 'create_posts':
            return in_array($role, ['admin', 'editor', 'author'], true);

        case 'edit_post':
        case 'delete_post':
            if (in_array($role, ['admin', 'editor'], true)) return true;
            if ($role === 'author' && $ownerId !== null && (int)$ownerId === auth_id()) return true;
            return false;

        case 'access_admin':
            return in_array($role, ['admin', 'editor', 'author'], true);

        default:
            return false;
    }
}

/**
 * Require authentication or specific role
 */
function auth_require(?string $minRole = null, string $redirect = 'login.php'): void {
    if (!auth_check()) {
        $_SESSION['flash_error'] = "Please log in to continue.";
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: /" . ltrim($redirect, '/'));
        exit;
    }

    if ($minRole !== null) {
        $roleHierarchy = ['reader' => 1, 'author' => 2, 'editor' => 3, 'admin' => 4];
        $userLevel = $roleHierarchy[auth_role()] ?? 0;
        $requiredLevel = $roleHierarchy[$minRole] ?? 5;

        if ($userLevel < $requiredLevel) {
            http_response_code(403);
            die("Access Denied: You do not have permission to view this page.");
        }
    }
}

/**
 * Check and record failed login rate limiting
 */
function auth_is_rate_limited(string $identifier): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $cacheKey = 'login_attempts_' . md5($ip . '_' . $identifier);
    $attempts = $_SESSION[$cacheKey] ?? ['count' => 0, 'first_attempt' => time()];

    if (time() - $attempts['first_attempt'] > 900) { // 15 minute window
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }

    $_SESSION[$cacheKey] = $attempts;
    return $attempts['count'] >= 5;
}

function auth_record_failed_attempt(string $identifier): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $cacheKey = 'login_attempts_' . md5($ip . '_' . $identifier);
    if (!isset($_SESSION[$cacheKey])) {
        $_SESSION[$cacheKey] = ['count' => 1, 'first_attempt' => time()];
    } else {
        $_SESSION[$cacheKey]['count']++;
    }
}

function auth_clear_failed_attempts(string $identifier): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $cacheKey = 'login_attempts_' . md5($ip . '_' . $identifier);
    unset($_SESSION[$cacheKey]);
}

/**
 * Attempt user authentication
 */
function auth_login(string $usernameOrEmail, string $password, bool $remember = false): array {
    $identifier = trim($usernameOrEmail);

    if (auth_is_rate_limited($identifier)) {
        return [
            'success' => false,
            'error'   => 'Too many failed login attempts. Please wait 15 minutes before trying again.'
        ];
    }

    $user = db_fetch_one(
        "SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1",
        [$identifier, $identifier]
    );

    if (!$user || !password_verify($password, $user['password'])) {
        auth_record_failed_attempt($identifier);
        return [
            'success' => false,
            'error'   => 'Invalid credentials. Please verify your username/email and password.'
        ];
    }

    if ($user['status'] !== 'active') {
        return [
            'success' => false,
            'error'   => 'Your account is deactivated. Please contact an administrator.'
        ];
    }

    // Success: Clear rate limits & regenerate session ID to prevent session fixation
    auth_clear_failed_attempts($identifier);
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    // Remove sensitive hash from session storage
    unset($user['password']);
    $_SESSION['user'] = $user;

    // Handle Persistent Remember-Me
    if ($remember) {
        auth_remember_me((int)$user['user_id']);
    }

    // Log login in audit trail
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'user_login', 'Successful login', ?)",
        [$user['user_id'], $ip]
    );

    return ['success' => true, 'user' => $user];
}

/**
 * Generate and store persistent remember-me token
 */
function auth_remember_me(int $userId): void {
    $rawToken = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $rawToken);

    db_execute(
        "UPDATE users SET remember_token = ? WHERE user_id = ?",
        [$hashedToken, $userId]
    );

    $cookieValue = $userId . ':' . $rawToken;
    $expires = time() + (86400 * 30); // 30 days
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    if (!headers_sent()) {
        setcookie('wordwise_remember', $cookieValue, [
            'expires'  => $expires,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

/**
 * Verify remember-me cookie and restore session
 */
function auth_check_remember_token(): bool {
    if (empty($_COOKIE['wordwise_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['wordwise_remember'], 2);
    if (count($parts) !== 2) {
        auth_clear_remember_token();
        return false;
    }

    list($userIdStr, $rawToken) = $parts;
    $userId = (int)$userIdStr;
    if ($userId <= 0 || empty($rawToken)) {
        auth_clear_remember_token();
        return false;
    }

    $hashedToken = hash('sha256', $rawToken);
    $user = db_fetch_one(
        "SELECT * FROM users WHERE user_id = ? AND remember_token = ? AND status = 'active' LIMIT 1",
        [$userId, $hashedToken]
    );

    if (!$user) {
        auth_clear_remember_token();
        return false;
    }

    // Token valid: restore session and rotate remember token
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    unset($user['password']);
    $_SESSION['user'] = $user;

    auth_remember_me($userId); // Rotate token
    return true;
}

/**
 * Clear remember-me cookie and database token
 */
function auth_clear_remember_token(): void {
    if (auth_check()) {
        db_execute(
            "UPDATE users SET remember_token = NULL WHERE user_id = ?",
            [auth_id()]
        );
    }

    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if (!headers_sent()) {
        setcookie('wordwise_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    unset($_COOKIE['wordwise_remember']);
}

/**
 * Logout current user and invalidate session & remember token
 */
function auth_logout(): void {
    if (auth_check()) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        db_execute(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'user_logout', 'User logged out', ?)",
            [auth_id(), $ip]
        );
        auth_clear_remember_token();
    }

    $_SESSION = [];
    if (ini_get("session.use_cookies") && !headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Sanitize destination URL to prevent Open Redirect attacks
 */
function safe_redirect(?string $target, string $default = '/'): string {
    if (empty($target)) {
        return $default;
    }

    // Must be a relative path starting with single forward slash
    if (!str_starts_with($target, '/') || str_starts_with($target, '//') || str_starts_with($target, '/\\')) {
        return $default;
    }

    $parsed = parse_url($target);
    $path = $parsed['path'] ?? '/';
    $query = !empty($parsed['query']) ? '?' . $parsed['query'] : '';
    return $path . $query;
}

/**
 * Check if a user's email is verified
 */
function auth_is_email_verified(?int $userId = null): bool {
    if ($userId === null && auth_check()) {
        return !empty($_SESSION['user']['email_verified_at']);
    }
    if ($userId !== null) {
        $user = db_fetch_one("SELECT email_verified_at FROM users WHERE user_id = ?", [$userId]);
        return !empty($user['email_verified_at']);
    }
    return false;
}

/**
 * Register a new user
 */
function auth_register(array $data): array {
    // 1. Verify if registration is permitted by the owner
    $allowRegistration = get_setting('allow_registration', '0');
    if ($allowRegistration !== '1') {
        return [
            'success' => false,
            'errors'  => ['Public registration is currently disabled by the publication owner.']
        ];
    }

    $fullName = trim($data['full_name'] ?? '');
    $username = strtolower(trim($data['username'] ?? ''));
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $passwordConfirm = $data['password_confirm'] ?? '';
    $termsAccepted = !empty($data['terms']);

    $errors = [];

    if ($fullName === '' || mb_strlen($fullName) > 100) {
        $errors[] = "Full name is required (maximum 100 characters).";
    }

    if (!preg_match('/^[a-z0-9_.-]{3,30}$/', $username)) {
        $errors[] = "Username must be 3-30 characters long and contain only letters, numbers, hyphens, periods, or underscores.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }

    // Password strength check: min 8 chars, 1 uppercase, 1 lowercase, 1 number
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters in length.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, and one number.";
    }

    if ($password !== $passwordConfirm) {
        $errors[] = "Password confirmation does not match.";
    }

    if (!$termsAccepted) {
        $errors[] = "You must agree to the WordWise Terms of Service and Privacy Policy.";
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }

    // Check duplicate username or email
    $existingUser = db_fetch_one("SELECT user_id, username, email FROM users WHERE username = ? OR email = ? LIMIT 1", [$username, $email]);
    if ($existingUser) {
        if ($existingUser['username'] === $username) {
            $errors[] = "The username '{$username}' is already taken. Please choose another.";
        }
        if ($existingUser['email'] === $email) {
            $errors[] = "An account with this email address already exists. Try signing in instead.";
        }
        return ['success' => false, 'errors' => $errors];
    }

    // Hash password securely with bcrypt
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $verificationToken = bin2hex(random_bytes(32));
    $defaultRole = get_setting('default_user_role', 'reader');
    if (!in_array($defaultRole, ['reader', 'author'], true)) {
        $defaultRole = 'reader';
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    db_execute(
        "INSERT INTO users (
            username, email, password, full_name, user_role, 
            status, verification_token, email_verified_at
         ) VALUES (?, ?, ?, ?, ?, 'active', ?, NULL)",
        [$username, $email, $passwordHash, $fullName, $defaultRole, $verificationToken]
    );

    $newUserId = (int)db_last_id();

    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'user_register', 'New account registered', ?)",
        [$newUserId, $ip]
    );

    return [
        'success' => true,
        'user_id' => $newUserId,
        'token'   => $verificationToken,
        'email'   => $email,
        'name'    => $fullName
    ];
}

/**
 * Verify user email address with verification token
 */
function auth_verify_email(string $token): array {
    $token = trim($token);
    if ($token === '') {
        return ['success' => false, 'error' => 'Verification token is missing.'];
    }

    $user = db_fetch_one("SELECT user_id, username, email, email_verified_at FROM users WHERE verification_token = ? LIMIT 1", [$token]);
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid or expired verification token.'];
    }

    db_execute(
        "UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE user_id = ?",
        [$user['user_id']]
    );

    // If currently logged in, update session state
    if (auth_id() === (int)$user['user_id']) {
        $_SESSION['user']['email_verified_at'] = date('Y-m-d H:i:s');
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'email_verified', 'Email verified successfully', ?)",
        [$user['user_id'], $ip]
    );

    return ['success' => true, 'username' => $user['username']];
}

/**
 * Request password reset token
 */
function auth_request_password_reset(string $email): array {
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }

    $user = db_fetch_one("SELECT user_id, username, email FROM users WHERE email = ? AND status = 'active' LIMIT 1", [$email]);
    $rawToken = bin2hex(random_bytes(32));
    $hashedToken = hash('sha256', $rawToken);

    if ($user) {
        db_execute(
            "UPDATE users SET password_reset_token = ?, password_reset_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE user_id = ?",
            [$hashedToken, $user['user_id']]
        );

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        db_execute(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'password_reset_request', 'Password reset requested', ?)",
            [$user['user_id'], $ip]
        );
    }

    // Always return generic success to avoid email enumeration
    return [
        'success' => true,
        'token'   => $user ? $rawToken : null,
        'email'   => $email
    ];
}

/**
 * Reset password using valid reset token
 */
function auth_reset_password(string $token, string $newPassword): array {
    $token = trim($token);
    if ($token === '') {
        return ['success' => false, 'error' => 'Password reset token is missing.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters long.'];
    }
    if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        return ['success' => false, 'error' => 'Password must contain uppercase, lowercase, and numbers.'];
    }

    $hashedToken = hash('sha256', $token);
    $user = db_fetch_one(
        "SELECT user_id FROM users WHERE password_reset_token = ? AND password_reset_expires_at > NOW() AND status = 'active' LIMIT 1",
        [$hashedToken]
    );

    if (!$user) {
        return ['success' => false, 'error' => 'This password reset link is invalid or has expired. Please request a new one.'];
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    db_execute(
        "UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires_at = NULL, remember_token = NULL WHERE user_id = ?",
        [$passwordHash, $user['user_id']]
    );

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'password_reset_success', 'Password reset completed', ?)",
        [$user['user_id'], $ip]
    );

    return ['success' => true];
}

/**
 * Update user profile details
 */
function auth_update_profile(int $userId, array $data): array {
    if (!auth_check() || (auth_id() !== $userId && auth_role() !== 'admin')) {
        return ['success' => false, 'error' => 'Unauthorized profile update.'];
    }

    $fullName = trim($data['full_name'] ?? '');
    $bio = trim($data['bio'] ?? '');
    $avatar = trim($data['user_image'] ?? '');

    if ($fullName === '' || mb_strlen($fullName) > 100) {
        return ['success' => false, 'error' => 'Display name is required (max 100 characters).'];
    }

    db_execute(
        "UPDATE users SET full_name = ?, bio = ?, user_image = ? WHERE user_id = ?",
        [$fullName, $bio, $avatar ?: 'images/favicon.png', $userId]
    );

    // Sync active session if updating self
    if (auth_id() === $userId) {
        $_SESSION['user']['full_name'] = $fullName;
        $_SESSION['user']['bio'] = $bio;
        $_SESSION['user']['user_image'] = $avatar ?: 'images/favicon.png';
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'profile_update', 'Profile information updated', ?)",
        [$userId, $ip]
    );

    return ['success' => true];
}

/**
 * Change user password from profile
 */
function auth_change_password(int $userId, string $currentPassword, string $newPassword): array {
    if (!auth_check() || (auth_id() !== $userId && auth_role() !== 'admin')) {
        return ['success' => false, 'error' => 'Unauthorized password change.'];
    }

    $user = db_fetch_one("SELECT password FROM users WHERE user_id = ?", [$userId]);
    if (!$user || !password_verify($currentPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Your current password was entered incorrectly.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'New password must be at least 8 characters long.'];
    }
    if (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        return ['success' => false, 'error' => 'New password must contain uppercase, lowercase, and numbers.'];
    }

    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    db_execute(
        "UPDATE users SET password = ?, remember_token = NULL WHERE user_id = ?",
        [$passwordHash, $userId]
    );

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'password_change', 'Password changed via profile', ?)",
        [$userId, $ip]
    );

    return ['success' => true];
}

