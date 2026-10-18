<?php
/**
 * WordWise Editorial Authentication - Login View & Controller
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (auth_check()) {
    $dest = auth_can('access_admin') ? '/admin/index.php' : '/profile.php';
    header("Location: " . $dest);
    exit;
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $remember = !empty($_POST['remember']);

        if ($username === '' || $password === '') {
            $error = "Please provide both your username/email and password.";
        } else {
            $result = auth_login($username, $password, $remember);
            if ($result['success']) {
                $rawTarget = $_SESSION['redirect_after_login'] ?? '';
                unset($_SESSION['redirect_after_login']);

                $defaultTarget = auth_can('access_admin') ? '/admin/index.php' : '/profile.php';
                $target = safe_redirect($rawTarget, $defaultTarget);

                header("Location: " . $target);
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}

$customTitle = "Sign In &bull; Editorial CMS";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-20 flex items-center justify-center">
  <div class="w-full max-w-md mx-auto px-4">
    
    <div class="editorial-card p-8 sm:p-9 rounded-sm space-y-6">
      
      <!-- Card Header -->
      <div class="text-center space-y-2 border-b border-paper-200 dark:border-paper-800 pb-5">
        <div class="font-mono text-xs uppercase tracking-widest text-accent font-semibold">
          [ Secured Terminal ]
        </div>
        <h1 class="text-2xl sm:text-3xl font-mono font-bold text-ink-950 dark:text-ink-50">
          Sign In to WordWise
        </h1>
        <p class="text-xs font-mono text-ink-500">
          Editorial &amp; CMS Administration
        </p>
      </div>

      <?php if (!empty($success)): ?>
        <div class="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-mono rounded">
          <?php echo e($success); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div class="p-3 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 text-xs font-mono rounded">
          <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <!-- Google OAuth Sign-in Button -->
      <div>
        <a 
          href="/auth/google/redirect.php" 
          class="w-full flex items-center justify-center space-x-3 px-4 py-2.5 border border-paper-300 dark:border-paper-700 hover:border-accent dark:hover:border-accent bg-paper-50 dark:bg-paper-950 text-ink-900 dark:text-ink-100 text-xs font-mono rounded transition-colors"
        >
          <svg class="w-4 h-4" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
          </svg>
          <span>Sign In with Google</span>
        </a>
      </div>

      <div class="relative flex items-center justify-center">
        <div class="border-t border-paper-200 dark:border-paper-800 w-full"></div>
        <span class="bg-white dark:bg-paper-900 px-3 text-[10px] font-mono text-ink-400 uppercase tracking-wider relative">
          Or Enter Credentials
        </span>
      </div>

      <form method="POST" action="/login.php" class="space-y-4">
        <?php echo csrf_field(); ?>

        <!-- Username or Email -->
        <div class="space-y-1">
          <label for="username" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
            Username or Email
          </label>
          <input 
            type="text" 
            name="username" 
            id="username" 
            required 
            autocomplete="username" 
            class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
            placeholder="admin or user@domain.com"
          >
        </div>

        <!-- Password with Toggle -->
        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <label for="password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              Password
            </label>
            <a href="/forgot-password.php" class="font-mono text-[11px] text-accent hover:underline">
              Forgot password?
            </a>
          </div>
          <div class="relative">
            <input 
              type="password" 
              name="password" 
              id="password" 
              required 
              autocomplete="current-password" 
              class="w-full px-3.5 py-2 pr-10 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
              placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
            <button 
              type="button" 
              onclick="toggleLoginPassword(this)"
              class="absolute right-2.5 top-2 text-ink-400 hover:text-accent font-mono text-xs"
              title="Toggle password visibility"
            >
              SHOW
            </button>
          </div>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="pt-1 flex items-center justify-between">
          <label class="flex items-center space-x-2 cursor-pointer">
            <input 
              type="checkbox" 
              name="remember" 
              id="remember" 
              class="rounded border-paper-300 dark:border-paper-700 text-accent focus:ring-accent"
            >
            <span class="font-mono text-xs text-ink-600 dark:text-ink-400">
              Remember me on this typewriter
            </span>
          </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
          <button 
            type="submit" 
            class="w-full py-2.5 px-4 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
          >
            Authenticate &rarr;
          </button>
        </div>

      </form>

      <div class="border-t border-paper-200 dark:border-paper-800 pt-4 text-center font-mono text-xs text-ink-500">
        New contributor? 
        <a href="/register.php" class="text-accent hover:underline font-semibold ml-1">
          Enlist account &rarr;
        </a>
      </div>

    </div>

  </div>
</main>

<script>
function toggleLoginPassword(btn) {
  const input = document.getElementById('password');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'HIDE';
  } else {
    input.type = 'password';
    btn.textContent = 'SHOW';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
