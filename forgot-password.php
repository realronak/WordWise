<?php
/**
 * WordWise Forgot Password Request Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$success = false;
$devResetLink = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $res = auth_request_password_reset($email);
        $success = true;

        if (!empty($res['token'])) {
            $appUrl = env('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $devResetLink = $appUrl . "/reset-password.php?token=" . urlencode($res['token']);
        }
    }
}

$customTitle = "Reset Password";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-20 flex items-center justify-center">
  <div class="w-full max-w-md mx-auto px-4">
    
    <div class="editorial-card p-8 rounded-sm space-y-6">
      
      <div class="text-center space-y-2 border-b border-paper-200 dark:border-paper-800 pb-5">
        <div class="font-mono text-xs uppercase tracking-widest text-accent font-semibold">
          [ Access Recovery ]
        </div>
        <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
          Recover Password
        </h1>
        <p class="text-xs font-mono text-ink-500">
          Receive a single-use cryptographic reset link
        </p>
      </div>

      <?php if ($success): ?>
        <div class="space-y-4 py-2 text-center">
          <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
          </div>
          <p class="text-xs font-mono text-ink-600 dark:text-ink-400 leading-relaxed">
            If an active account is associated with this email, instructions have been generated. The reset link will remain valid for 1 hour.
          </p>

          <?php if (!empty($devResetLink)): ?>
            <!-- Local Development Simulation Link -->
            <div class="p-4 bg-paper-100 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded text-left space-y-2 mt-4">
              <div class="flex items-center justify-between text-[11px] font-mono text-ink-500">
                <span class="font-bold text-accent uppercase tracking-wider">[ Local Mail Dispatch ]</span>
                <span>Reset Token Active</span>
              </div>
              <p class="text-[11px] font-mono text-ink-600 dark:text-ink-400">
                Click below to set your new password:
              </p>
              <a href="<?php echo e($devResetLink); ?>" class="block font-mono text-xs text-accent hover:underline break-all">
                <?php echo e($devResetLink); ?>
              </a>
            </div>
          <?php endif; ?>

          <div class="pt-4">
            <a href="/login.php" class="inline-block px-5 py-2 typewriter-badge hover:border-accent hover:text-accent font-mono text-xs">
              &larr; Return to Sign In
            </a>
          </div>
        </div>
      <?php else: ?>
        
        <?php if (!empty($error)): ?>
          <div class="p-3 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 text-xs font-mono rounded">
            <?php echo e($error); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/forgot-password.php" class="space-y-4">
          <?php echo csrf_field(); ?>

          <div class="space-y-1">
            <label for="email" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              Your Email Address *
            </label>
            <input 
              type="email" 
              name="email" 
              id="email" 
              required 
              placeholder="e.g. editor@wordwise.local" 
              class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100 placeholder:text-ink-400"
            >
          </div>

          <div class="pt-2">
            <button 
              type="submit" 
              class="w-full py-2.5 px-4 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
            >
              Generate Recovery Link &rarr;
            </button>
          </div>
        </form>

        <div class="pt-4 border-t border-paper-200 dark:border-paper-800 text-center">
          <a href="/login.php" class="font-mono text-xs text-ink-500 hover:text-accent hover:underline">
            &larr; Back to Sign In
          </a>
        </div>

      <?php endif; ?>

    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
