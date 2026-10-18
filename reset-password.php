<?php
/**
 * WordWise Password Reset Page
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$success = false;

// Pre-verify token
$hashedToken = hash('sha256', $token);
$user = null;
if ($token !== '') {
    $user = db_fetch_one(
        "SELECT user_id, username, email FROM users WHERE password_reset_token = ? AND password_reset_expires_at > NOW() AND status = 'active' LIMIT 1",
        [$hashedToken]
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch. Please try again.";
    } elseif (!$user) {
        $errors[] = "This password reset link is invalid or has expired.";
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($newPassword !== $confirmPassword) {
            $errors[] = "Password confirmation does not match.";
        } else {
            $res = auth_reset_password($token, $newPassword);
            if ($res['success']) {
                $success = true;
            } else {
                $errors[] = $res['error'] ?? "Failed to reset password.";
            }
        }
    }
}

$customTitle = "Choose New Password";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-20 flex items-center justify-center">
  <div class="w-full max-w-md mx-auto px-4">
    
    <div class="editorial-card p-8 rounded-sm space-y-6">
      
      <div class="text-center space-y-2 border-b border-paper-200 dark:border-paper-800 pb-5">
        <div class="font-mono text-xs uppercase tracking-widest text-accent font-semibold">
          [ Access Re-establishment ]
        </div>
        <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
          Set New Password
        </h1>
        <p class="text-xs font-mono text-ink-500">
          <?php echo $user ? "Resetting account for @" . e($user['username']) : "WordWise Security Protocol"; ?>
        </p>
      </div>

      <?php if ($success): ?>
        <div class="space-y-4 py-4 text-center">
          <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          </div>
          <h2 class="font-mono text-base font-bold text-ink-900 dark:text-ink-100">Password Updated</h2>
          <p class="text-xs font-mono text-ink-600 dark:text-ink-400 leading-relaxed">
            Your password has been changed securely and any prior active sessions have been invalidated.
          </p>
          <div class="pt-4">
            <a href="/login.php" class="inline-block px-6 py-2.5 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded">
              Sign In with New Password &rarr;
            </a>
          </div>
        </div>
      <?php elseif (!$user): ?>
        <div class="space-y-4 py-4 text-center">
          <div class="w-12 h-12 mx-auto rounded-full bg-red-500/10 text-red-600 dark:text-red-400 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          </div>
          <h2 class="font-mono text-base font-bold text-ink-900 dark:text-ink-100">Expired or Invalid Link</h2>
          <p class="text-xs font-mono text-ink-600 dark:text-ink-400 leading-relaxed">
            This password reset token has expired or is invalid. Please request a new recovery link.
          </p>
          <div class="pt-4">
            <a href="/forgot-password.php" class="inline-block px-5 py-2 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded">
              Request New Link &rarr;
            </a>
          </div>
        </div>
      <?php else: ?>

        <?php if (!empty($errors)): ?>
          <div class="p-3 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 text-xs font-mono rounded space-y-1">
            <?php foreach ($errors as $e): ?><p>&bull; <?php echo e($e); ?></p><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/reset-password.php" class="space-y-4">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="token" value="<?php echo e($token); ?>">

          <!-- New Password -->
          <div class="space-y-1">
            <label for="new_password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              New Password *
            </label>
            <div class="relative">
              <input 
                type="password" 
                name="new_password" 
                id="new_password" 
                required 
                minlength="8"
                class="w-full px-3.5 py-2 pr-10 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
                oninput="checkStrength(this.value)"
              >
              <button 
                type="button" 
                onclick="toggleField('new_password', this)"
                class="absolute right-2.5 top-2 text-ink-400 hover:text-accent font-mono text-xs"
              >
                SHOW
              </button>
            </div>

            <!-- Strength Bar -->
            <div class="pt-1.5 space-y-1">
              <div class="w-full bg-paper-200 dark:bg-paper-800 h-1 rounded-full overflow-hidden">
                <div id="meter" class="h-full bg-red-500 w-0 transition-all duration-300"></div>
              </div>
              <div class="flex items-center justify-between text-[10px] font-mono text-ink-400">
                <span id="meter-text">Min 8 chars</span>
                <span>Uppercase, lowercase, number</span>
              </div>
            </div>
          </div>

          <!-- Confirm New Password -->
          <div class="space-y-1">
            <label for="confirm_password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              Confirm New Password *
            </label>
            <div class="relative">
              <input 
                type="password" 
                name="confirm_password" 
                id="confirm_password" 
                required 
                minlength="8"
                class="w-full px-3.5 py-2 pr-10 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
              >
              <button 
                type="button" 
                onclick="toggleField('confirm_password', this)"
                class="absolute right-2.5 top-2 text-ink-400 hover:text-accent font-mono text-xs"
              >
                SHOW
              </button>
            </div>
          </div>

          <div class="pt-2">
            <button 
              type="submit" 
              class="w-full py-2.5 px-4 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
            >
              Update Password &rarr;
            </button>
          </div>
        </form>

      <?php endif; ?>

    </div>

  </div>
</main>

<script>
function toggleField(id, btn) {
  const el = document.getElementById(id);
  if (el.type === 'password') {
    el.type = 'text';
    btn.textContent = 'HIDE';
  } else {
    el.type = 'password';
    btn.textContent = 'SHOW';
  }
}

function checkStrength(val) {
  const meter = document.getElementById('meter');
  const text = document.getElementById('meter-text');
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;

  if (score < 2) {
    meter.style.width = '25%';
    meter.className = 'h-full bg-red-500';
    text.textContent = 'Weak';
  } else if (score < 4) {
    meter.style.width = '65%';
    meter.className = 'h-full bg-amber-500';
    text.textContent = 'Moderate';
  } else {
    meter.style.width = '100%';
    meter.className = 'h-full bg-emerald-500';
    text.textContent = 'Strong';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
