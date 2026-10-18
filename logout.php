<?php
/**
 * WordWise Logout Action
 */
require_once __DIR__ . '/includes/auth.php';

auth_logout();
header("Location: /?logged_out=1");
exit;
