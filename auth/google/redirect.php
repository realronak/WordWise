<?php
/**
 * WordWise Google OAuth 2.0 Authorization Redirect Handler
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/oauth.php';

$provider = oauth_get_provider('google');

// If OAuth credentials are not configured in .env, display a clear, helpful typewriter notice
if (!$provider || !$provider->isConfigured()) {
    $customTitle = "Google OAuth Configuration Required";
    include __DIR__ . '/../../includes/header.php';
    include __DIR__ . '/../../includes/navigation.php';
    ?>
    <main class="flex-grow py-20 flex items-center justify-center">
      <div class="w-full max-w-lg mx-auto px-4">
        <div class="editorial-card p-8 rounded-sm space-y-6 text-center">
          
          <div class="space-y-2 border-b border-paper-200 dark:border-paper-800 pb-5">
            <div class="font-mono text-xs uppercase tracking-widest text-amber-600 dark:text-amber-400 font-semibold">
              [ Provider Integration Notice ]
            </div>
            <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
              Google Sign-In Unconfigured
            </h1>
            <p class="text-xs font-mono text-ink-500">
              Environment credentials required for OAuth 2.0 / OpenID Connect
            </p>
          </div>

          <div class="space-y-4 text-left font-mono text-xs text-ink-600 dark:text-ink-400">
            <p>
              To enable authentic Google OAuth sign-in and registration, register your local application in the <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer" class="text-accent underline">Google Cloud Console</a> and supply your credentials:
            </p>

            <div class="p-3 bg-paper-100 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded space-y-1.5 text-[11px]">
              <div><strong>1. Authorized Redirect URI:</strong></div>
              <code class="text-accent block break-all"><?php echo e($provider ? $provider->getRedirectUri() : 'http://localhost/auth/google/callback.php'); ?></code>
              
              <div class="pt-2"><strong>2. Add to your <code>.env</code> file:</strong></div>
              <pre class="text-ink-800 dark:text-ink-200 bg-white dark:bg-paper-900 p-2 rounded border border-paper-200 dark:border-paper-800">GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-your-secret
GOOGLE_REDIRECT_URI=<?php echo e($provider ? $provider->getRedirectUri() : 'http://localhost/auth/google/callback.php'); ?></pre>
            </div>

            <p class="text-[11px] text-ink-500">
              Per security requirements, WordWise will never simulate a fake or mock OAuth authentication.
            </p>
          </div>

          <div class="pt-2 flex items-center justify-center space-x-4 font-mono text-xs">
            <a href="/login.php" class="typewriter-badge hover:border-accent hover:text-accent">
              &larr; Return to Sign In
            </a>
            <a href="/" class="typewriter-badge hover:border-accent hover:text-accent">
              WordWise Home
            </a>
          </div>

        </div>
      </div>
    </main>
    <?php
    include __DIR__ . '/../../includes/footer.php';
    exit;
}

// Generate PKCE code verifier and challenge
$codeVerifier = oauth_generate_code_verifier();
$codeChallenge = oauth_generate_code_challenge($codeVerifier);

// Generate random State token and OpenID Nonce
$state = bin2hex(random_bytes(24));
$nonce = bin2hex(random_bytes(24));

// Save to session for callback validation
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_verifier'] = $codeVerifier;
$_SESSION['oauth_nonce'] = $nonce;

// Redirect to Google's OpenID Connect Consent Screen
$authUrl = $provider->getAuthUrl($state, $codeChallenge, $nonce);
header("Location: " . $authUrl);
exit;
