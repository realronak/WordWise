<?php
/**
 * WordWise Email Verification Endpoint
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? '');
$status = 'pending';
$message = '';
$username = '';

if ($token === '') {
    $status = 'error';
    $message = "Verification token is missing. Please check your verification link.";
} else {
    $res = auth_verify_email($token);
    if ($res['success']) {
        $status = 'success';
        $username = $res['username'];
        $message = "Your email address has been verified successfully. Your account is now fully active.";
    } else {
        $status = 'error';
        $message = $res['error'] ?? "This verification token is invalid or has already been used.";
    }
}

$customTitle = "Email Verification";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-20 flex items-center justify-center">
  <div class="w-full max-w-md mx-auto px-4">
    
    <div class="editorial-card p-8 rounded-sm space-y-6 text-center">
      
      <div class="space-y-2 border-b border-paper-200 dark:border-paper-800 pb-5">
        <div class="font-mono text-xs uppercase tracking-widest text-accent font-semibold">
          [ Identity Verification ]
        </div>
        <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
          <?php echo $status === 'success' ? 'Email Confirmed' : 'Verification Issue'; ?>
        </h1>
      </div>

      <?php if ($status === 'success'): ?>
        <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
        </div>
        <p class="font-mono text-xs text-ink-600 dark:text-ink-300 leading-relaxed">
          <?php echo e($message); ?>
        </p>
        <div class="pt-4">
          <a href="/login.php" class="inline-block px-6 py-2.5 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded">
            Sign In Now &rarr;
          </a>
        </div>
      <?php else: ?>
        <div class="w-12 h-12 mx-auto rounded-full bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </div>
        <p class="font-mono text-xs text-ink-600 dark:text-ink-300 leading-relaxed">
          <?php echo e($message); ?>
        </p>
        <div class="pt-4 flex items-center justify-center space-x-3 font-mono text-xs">
          <a href="/register.php" class="typewriter-badge hover:border-accent hover:text-accent">Try Registering</a>
          <a href="/login.php" class="typewriter-badge hover:border-accent hover:text-accent">Sign In</a>
        </div>
      <?php endif; ?>

    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
