<?php
/**
 * WordWise Security and Utility Helpers
 * Context-aware escaping, CSRF protection, HTML sanitization, and slug generation.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', '1');
    session_start();
}

/**
 * Escape output for HTML context
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output hidden input field with CSRF token
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token from request
 */
function csrf_verify(): bool {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
    }
    return true;
}

/**
 * Convert string to clean URL slug
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    if (function_exists('iconv')) {
        $converted = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        if ($converted !== false) $text = $converted;
    }
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

/**
 * Estimate reading time in minutes
 */
function reading_time(string $content): int {
    $wordCount = str_word_count(strip_tags($content));
    $minutes = (int)ceil($wordCount / 200);
    return max(1, $minutes);
}

/**
 * Sanitize rich text HTML to allow safe editorial formatting while blocking XSS
 */
function sanitize_html(string $html): string {
    // Strip dangerous tags completely
    $html = preg_replace('#<(script|style|iframe|object|embed|applet|form|svg)\b[^>]*>(.*?)</\1>#is', '', $html);
    $html = preg_replace('#<(script|style|iframe|object|embed|applet|form|svg)\b[^>]*>#is', '', $html);

    // Strip javascript: and data: URIs in href/src
    $html = preg_replace('/(href|src)\s*=\s*["\']\s*(javascript|data):[^"\']*["\']/i', '$1="#"', $html);

    // Strip inline on* event handlers (e.g. onclick, onerror)
    $html = preg_replace('/\s+on[a-z]+\s*=\s*(["\'][^"\']*["\']|[^\s>]+)/i', '', $html);

    // Allowed tags list
    $allowedTags = '<p><br><hr><h1><h2><h3><h4><h5><h6><strong><b><em><i><u><s><del><strike><blockquote><code><pre><ul><ol><li><a><img><table><thead><tbody><tr><th><td>';
    return strip_tags($html, $allowedTags);
}

/**
 * Record a unique post view per visitor per day
 */
function record_post_view(int $postId): void {
    if ($postId <= 0) return;
    
    // Hash IP address with salt to respect privacy
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $salt = date('Y-m-d') . '_wordwise_salt';
    $ipHash = hash('sha256', $ip . $salt);
    $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    // Check if viewed today
    $existing = db_fetch_one(
        "SELECT view_id FROM article_views WHERE post_id = ? AND ip_hash = ? AND viewed_at >= CURDATE()",
        [$postId, $ipHash]
    );

    if (!$existing) {
        db_execute(
            "INSERT INTO article_views (post_id, ip_hash, user_agent) VALUES (?, ?, ?)",
            [$postId, $ipHash, $userAgent]
        );
        db_execute(
            "UPDATE posts SET post_views = post_views + 1 WHERE post_id = ?",
            [$postId]
        );
    }
}
