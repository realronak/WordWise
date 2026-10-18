<?php
/**
 * WordWise Provider Identity Unlinking Handler
 * Requires re-authentication with current password for account protection.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/oauth.php';

auth_require();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /profile.php");
    exit;
}

if (!csrf_verify()) {
    $_SESSION['flash_error'] = "Security token mismatch. Please try again.";
    header("Location: /profile.php");
    exit;
}

$provider = trim($_POST['provider'] ?? '');
$password = $_POST['confirm_password'] ?? '';

if ($provider === '') {
    $_SESSION['flash_error'] = "Missing provider identifier.";
    header("Location: /profile.php");
    exit;
}

$res = oauth_unlink_provider(auth_id(), $provider, $password);

if ($res['success']) {
    $_SESSION['flash_success'] = ucfirst($provider) . " identity unlinked successfully.";
} else {
    $_SESSION['flash_error'] = $res['error'] ?? "Failed to unlink {$provider} identity.";
}

header("Location: /profile.php");
exit;
