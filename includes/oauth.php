<?php
/**
 * WordWise Extensible OAuth 2.0 & OpenID Connect Subsystem
 * Implements Google OAuth 2.0 with PKCE, state token verification, 
 * nonce verification, and secure account linking.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

interface OAuthProviderInterface {
    public function isConfigured(): bool;
    public function getAuthUrl(string $state, string $codeChallenge, string $nonce): string;
    public function exchangeCode(string $code, string $codeVerifier): array;
    public function verifyAndGetUser(array $tokenResponse, string $nonce): array;
}

/**
 * Google OpenID Connect Provider Implementation
 */
class GoogleOAuthProvider implements OAuthProviderInterface {
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct() {
        $this->clientId = trim(env('GOOGLE_CLIENT_ID', ''));
        $this->clientSecret = trim(env('GOOGLE_CLIENT_SECRET', ''));
        
        $configuredUri = env('GOOGLE_REDIRECT_URI', '');
        if ($configuredUri !== '') {
            $this->redirectUri = $configuredUri;
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $this->redirectUri = "{$scheme}://{$host}/auth/google/callback.php";
        }
    }

    public function isConfigured(): bool {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function getClientId(): string {
        return $this->clientId;
    }

    public function getRedirectUri(): string {
        return $this->redirectUri;
    }

    /**
     * Generate Google Authorization URL with OpenID Connect scopes & PKCE
     */
    public function getAuthUrl(string $state, string $codeChallenge, string $nonce): string {
        $params = [
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUri,
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
            'nonce'                 => $nonce,
            'access_type'           => 'online',
            'prompt'                => 'select_account',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    /**
     * Exchange authorization code for tokens
     */
    public function exchangeCode(string $code, string $codeVerifier): array {
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        $postData = [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
            'code_verifier' => $codeVerifier,
        ];

        $ch = curl_init($tokenUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error !== '') {
            return ['success' => false, 'error' => "Network error during Google token exchange: {$error}"];
        }

        $data = json_decode($response, true);
        if ($httpCode !== 200 || empty($data['id_token']) || empty($data['access_token'])) {
            $msg = $data['error_description'] ?? $data['error'] ?? 'Unknown token exchange error';
            return ['success' => false, 'error' => "Google token response error ({$httpCode}): {$msg}"];
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Validate ID token / Userinfo and return verified identity
     */
    public function verifyAndGetUser(array $tokenResponse, string $nonce): array {
        $idToken = $tokenResponse['id_token'] ?? '';
        $accessToken = $tokenResponse['access_token'] ?? '';

        // 1. Parse ID Token payload
        $tokenParts = explode('.', $idToken);
        if (count($tokenParts) < 2) {
            return ['success' => false, 'error' => 'Malformed Google ID token format.'];
        }

        $payloadJson = self::base64UrlDecode($tokenParts[1]);
        $payload = json_decode($payloadJson, true);
        if (!$payload || empty($payload['sub'])) {
            return ['success' => false, 'error' => 'Unable to decode Google identity claims.'];
        }

        // 2. Validate Issuer & Audience
        $validIssuers = ['accounts.google.com', 'https://accounts.google.com'];
        if (!in_array($payload['iss'] ?? '', $validIssuers, true)) {
            return ['success' => false, 'error' => 'Invalid Google token issuer.'];
        }

        if (($payload['aud'] ?? '') !== $this->clientId) {
            return ['success' => false, 'error' => 'Google identity token audience does not match Client ID.'];
        }

        // 3. Validate Token Expiry
        if (empty($payload['exp']) || $payload['exp'] < (time() - 300)) {
            return ['success' => false, 'error' => 'Google identity token has expired.'];
        }

        // 4. Validate Nonce if present in payload
        if (!empty($payload['nonce']) && !hash_equals($nonce, $payload['nonce'])) {
            return ['success' => false, 'error' => 'Google OpenID nonce mismatch.'];
        }

        // 5. Enforce verified email
        if (empty($payload['email']) || empty($payload['email_verified'])) {
            return ['success' => false, 'error' => 'Google account must have a verified email address to proceed.'];
        }

        return [
            'success'          => true,
            'provider'         => 'google',
            'provider_user_id' => (string)$payload['sub'],
            'email'            => strtolower(trim($payload['email'])),
            'full_name'        => trim($payload['name'] ?? $payload['given_name'] ?? 'Google Contributor'),
            'avatar_url'       => $payload['picture'] ?? null,
        ];
    }

    public static function base64UrlDecode(string $data): string {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

/**
 * Helper: Generate PKCE Code Verifier (43-128 random chars)
 */
function oauth_generate_code_verifier(): string {
    return bin2hex(random_bytes(32)); // 64 chars
}

/**
 * Helper: Generate PKCE Code Challenge (Base64URL SHA-256)
 */
function oauth_generate_code_challenge(string $verifier): string {
    $hash = hash('sha256', $verifier, true);
    return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
}

/**
 * Get Provider Instance by Name
 */
function oauth_get_provider(string $name): ?OAuthProviderInterface {
    switch (strtolower($name)) {
        case 'google':
            return new GoogleOAuthProvider();
        default:
            return null;
    }
}

/**
 * Core Callback Handler for all OAuth Providers
 */
function oauth_handle_callback(string $providerName, array $queryParams): array {
    // 1. Check for provider error callback (e.g. user canceled consent)
    if (!empty($queryParams['error'])) {
        $errDesc = $queryParams['error_description'] ?? $queryParams['error'];
        return ['success' => false, 'error' => 'Authentication canceled or denied: ' . $errDesc];
    }

    $provider = oauth_get_provider($providerName);
    if (!$provider) {
        return ['success' => false, 'error' => 'Unsupported authentication provider.'];
    }

    if (!$provider->isConfigured()) {
        return ['success' => false, 'error' => ucfirst($providerName) . ' authentication is not configured on this server.'];
    }

    $code = trim($queryParams['code'] ?? '');
    $state = trim($queryParams['state'] ?? '');

    if ($code === '' || $state === '') {
        return ['success' => false, 'error' => 'Authorization code or state parameter missing from provider callback.'];
    }

    // 2. Validate State Token against Session
    $sessionState = $_SESSION['oauth_state'] ?? '';
    $sessionVerifier = $_SESSION['oauth_verifier'] ?? '';
    $sessionNonce = $_SESSION['oauth_nonce'] ?? '';

    unset($_SESSION['oauth_state'], $_SESSION['oauth_verifier'], $_SESSION['oauth_nonce']);

    if ($sessionState === '' || !hash_equals($sessionState, $state)) {
        return ['success' => false, 'error' => 'Invalid or expired OAuth state parameter. Request may have timed out or been intercepted.'];
    }

    // 3. Exchange Code for Tokens
    $exchangeRes = $provider->exchangeCode($code, $sessionVerifier);
    if (!$exchangeRes['success']) {
        return $exchangeRes;
    }

    // 4. Validate Identity Claims
    $identityRes = $provider->verifyAndGetUser($exchangeRes['data'], $sessionNonce);
    if (!$identityRes['success']) {
        return $identityRes;
    }

    $providerUserId = $identityRes['provider_user_id'];
    $email = $identityRes['email'];
    $fullName = $identityRes['full_name'];
    $avatarUrl = $identityRes['avatar_url'];

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    // 5. Account Resolution & Safe Linking
    // Case A: Identity already linked to an existing account
    $existingLink = db_fetch_one(
        "SELECT user_id FROM user_providers WHERE provider = ? AND provider_user_id = ? LIMIT 1",
        [$providerName, $providerUserId]
    );

    if ($existingLink) {
        $userId = (int)$existingLink['user_id'];
        $user = db_fetch_one("SELECT * FROM users WHERE user_id = ? AND status = 'active' LIMIT 1", [$userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'The local account associated with this Google identity is currently deactivated.'];
        }

        // Login user
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        unset($user['password']);
        $_SESSION['user'] = $user;

        db_execute(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'oauth_login', ?, ?)",
            [$userId, "Logged in via {$providerName} OAuth", $ip]
        );

        return ['success' => true, 'action' => 'login', 'user' => $user];
    }

    // Case B: Currently logged-in user connecting this identity from profile
    if (auth_check()) {
        $currentUserId = auth_id();

        // Check if provider is already linked to another account
        $otherLink = db_fetch_one(
            "SELECT user_id FROM user_providers WHERE provider = ? AND provider_user_id = ?",
            [$providerName, $providerUserId]
        );
        if ($otherLink) {
            return ['success' => false, 'error' => 'This Google identity is already connected to another WordWise account.'];
        }

        db_execute(
            "INSERT INTO user_providers (user_id, provider, provider_user_id, provider_email, avatar_url) VALUES (?, ?, ?, ?, ?)",
            [$currentUserId, $providerName, $providerUserId, $email, $avatarUrl]
        );

        db_execute(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'oauth_link', ?, ?)",
            [$currentUserId, "Connected {$providerName} identity ({$email})", $ip]
        );

        return ['success' => true, 'action' => 'linked'];
    }

    // Case C: User is not logged in. Check if email matches an existing local account
    $existingLocalUser = db_fetch_one("SELECT user_id, username, email FROM users WHERE email = ? LIMIT 1", [$email]);
    if ($existingLocalUser) {
        // Safe account linking protocol: Require password authentication first to prevent account takeover
        $_SESSION['flash_error'] = "An account with the email '{$email}' already exists. Please sign in with your credentials to link your Google account safely.";
        return [
            'success' => false, 
            'requires_login_link' => true,
            'error'   => "An account with the email '{$email}' already exists. Please sign in with your password first to connect your Google account."
        ];
    }

    // Case D: New User Registration via Google OAuth
    $allowRegistration = get_setting('allow_registration', '0');
    if ($allowRegistration !== '1') {
        return [
            'success' => false,
            'error'   => 'Public registration is currently disabled by the publication owner. Contact the administrator for an invitation.'
        ];
    }

    // Generate clean unique username from email handle or name
    $baseUsername = preg_replace('/[^a-z0-9_]/', '', strtolower(explode('@', $email)[0]));
    if (strlen($baseUsername) < 3) $baseUsername = 'contributor_' . bin2hex(random_bytes(2));
    $username = $baseUsername;

    $usernameTaken = db_fetch_one("SELECT user_id FROM users WHERE username = ?", [$username]);
    if ($usernameTaken) {
        $username = $baseUsername . '_' . rand(100, 999);
    }

    // Generate random secure password for accounts created via OAuth
    $randomPassword = bin2hex(random_bytes(24));
    $passwordHash = password_hash($randomPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    $defaultRole = get_setting('default_user_role', 'reader');

    db_execute(
        "INSERT INTO users (
            username, email, password, full_name, user_role, 
            user_image, status, email_verified_at
         ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())",
        [$username, $email, $passwordHash, $fullName, $defaultRole, $avatarUrl ?: 'images/favicon.png']
    );

    $newUserId = (int)db_last_id();

    db_execute(
        "INSERT INTO user_providers (user_id, provider, provider_user_id, provider_email, avatar_url) VALUES (?, ?, ?, ?, ?)",
        [$newUserId, $providerName, $providerUserId, $email, $avatarUrl]
    );

    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'oauth_register', ?, ?)",
        [$newUserId, "Created new account via {$providerName} OAuth", $ip]
    );

    $newUser = db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$newUserId]);
    unset($newUser['password']);
    
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user'] = $newUser;

    return ['success' => true, 'action' => 'registered', 'user' => $newUser];
}

/**
 * Safely Unlink an OAuth Provider from User Account
 */
function oauth_unlink_provider(int $userId, string $providerName, string $confirmPassword): array {
    if (!auth_check() || (auth_id() !== $userId && auth_role() !== 'admin')) {
        return ['success' => false, 'error' => 'Unauthorized operation.'];
    }

    $user = db_fetch_one("SELECT password FROM users WHERE user_id = ?", [$userId]);
    if (!$user || !password_verify($confirmPassword, $user['password'])) {
        return ['success' => false, 'error' => 'Current password verification failed. You must re-authenticate to unlink your identity.'];
    }

    db_execute(
        "DELETE FROM user_providers WHERE user_id = ? AND provider = ?",
        [$userId, $providerName]
    );

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'oauth_unlink', ?, ?)",
        [$userId, "Unlinked {$providerName} identity", $ip]
    );

    return ['success' => true];
}
