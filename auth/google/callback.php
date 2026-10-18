<?php
/**
 * WordWise Google OAuth 2.0 Authorization Callback Handler
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/oauth.php';

$res = oauth_handle_callback('google', $_GET);

if ($res['success']) {
    $action = $res['action'] ?? 'login';

    if ($action === 'linked') {
        $_SESSION['flash_success'] = "Your Google identity has been securely linked to your WordWise account.";
        header("Location: /profile.php");
        exit;
    }

    // Login or registration success
    $userRole = $res['user']['user_role'] ?? 'reader';
    $defaultDest = in_array($userRole, ['admin', 'editor', 'author'], true) ? '/admin/index.php' : '/profile.php';
    
    $rawDest = $_SESSION['redirect_after_login'] ?? '';
    unset($_SESSION['redirect_after_login']);
    $destination = safe_redirect($rawDest, $defaultDest);

    if ($action === 'registered') {
        $_SESSION['flash_success'] = "Welcome to WordWise! Your account has been verified via Google.";
    } else {
        $_SESSION['flash_success'] = "Signed in successfully via Google.";
    }

    header("Location: " . $destination);
    exit;
} else {
    $_SESSION['flash_error'] = $res['error'] ?? "Google authentication was unsuccessful.";
    
    // If linking an existing account requires password authentication first, send to login
    header("Location: /login.php");
    exit;
}
