<?php
/**
 * WordWise User Registration Page
 * Minimalist editorial design with real-time validation, password strength meter,
 * password toggle, and terms acceptance.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already authenticated
if (auth_check()) {
    header("Location: /profile.php");
    exit;
}

$allowRegistration = get_setting('allow_registration', '0') === '1';
$errors = [];
$successMessage = '';
$devVerificationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $allowRegistration) {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $res = auth_register([
            'full_name'        => $_POST['full_name'] ?? '',
            'username'         => $_POST['username'] ?? '',
            'email'            => $_POST['email'] ?? '',
            'password'         => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'terms'            => !empty($_POST['terms']),
        ]);

        if ($res['success']) {
            $successMessage = "Your account has been created successfully!";
            $appUrl = env('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $devVerificationLink = $appUrl . "/verify-email.php?token=" . urlencode($res['token']);
        } else {
            $errors = $res['errors'] ?? ['Registration failed.'];
        }
    }
}

$customTitle = "Register Account";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-16 flex items-center justify-center">
  <div class="w-full max-w-lg mx-auto px-4">
    
    <div class="editorial-card p-8 sm:p-10 rounded-sm space-y-6">
      
      <!-- Card Header -->
      <div class="text-center space-y-2 border-b border-paper-200 dark:border-paper-800 pb-5">
        <div class="font-mono text-xs uppercase tracking-widest text-accent font-semibold">
          [ Colophon Enlistment ]
        </div>
        <h1 class="text-2xl sm:text-3xl font-mono font-bold text-ink-950 dark:text-ink-50">
          Join WordWise
        </h1>
        <p class="text-xs font-mono text-ink-500">
          Create your reader or contributor account
        </p>
      </div>

      <?php if (!$allowRegistration): ?>
        <!-- Registration Disabled Notice -->
        <div class="space-y-4 py-4 text-center">
          <div class="w-12 h-12 mx-auto rounded-full bg-paper-200 dark:bg-paper-800 flex items-center justify-center text-ink-400">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
          </div>
          <h2 class="font-mono text-base font-bold text-ink-900 dark:text-ink-100">Public Registration Closed</h2>
          <p class="text-xs font-mono text-ink-600 dark:text-ink-400 leading-relaxed max-w-sm mx-auto">
            New account registration is currently restricted by the publication owner. If you already have an account, please sign in.
          </p>
          <div class="pt-2">
            <a href="/login.php" class="inline-block px-5 py-2 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded">
              Sign In Instead &rarr;
            </a>
          </div>
        </div>
      <?php elseif (!empty($successMessage)): ?>
        <!-- Success Screen with Email Verification Guidance -->
        <div class="space-y-4 py-4 text-center">
          <div class="w-12 h-12 mx-auto rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          </div>
          <h2 class="font-mono text-base font-bold text-ink-900 dark:text-ink-100">Account Enlisted</h2>
          <p class="text-xs font-mono text-ink-600 dark:text-ink-400 leading-relaxed">
            <?php echo e($successMessage); ?> Please verify your email address to unlock publishing privileges.
          </p>

          <!-- Local Development Direct Verification Link -->
          <div class="p-4 bg-paper-100 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded text-left space-y-2">
            <div class="flex items-center justify-between text-[11px] font-mono text-ink-500">
              <span class="font-bold text-accent uppercase tracking-wider">[ Local Mail Dispatch ]</span>
              <span>Verification Token Ready</span>
            </div>
            <p class="text-[11px] font-mono text-ink-600 dark:text-ink-400 break-all">
              Click the link below to confirm your email:
            </p>
            <a href="<?php echo e($devVerificationLink); ?>" class="block font-mono text-xs text-accent hover:underline break-all">
              <?php echo e($devVerificationLink); ?>
            </a>
          </div>

          <div class="pt-4">
            <a href="/login.php" class="inline-block px-6 py-2.5 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded">
              Proceed to Sign In &rarr;
            </a>
          </div>
        </div>
      <?php else: ?>
        <!-- Registration Form -->
        
        <?php if (!empty($errors)): ?>
          <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 text-xs font-mono rounded space-y-1">
            <?php foreach ($errors as $err): ?>
              <p>&bull; <?php echo e($err); ?></p>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Google OAuth Enlistment Button -->
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
            <span>Continue with Google</span>
          </a>
        </div>

        <div class="relative flex items-center justify-center">
          <div class="border-t border-paper-200 dark:border-paper-800 w-full"></div>
          <span class="bg-white dark:bg-paper-900 px-3 text-[10px] font-mono text-ink-400 uppercase tracking-wider relative">
            Or Register with Email
          </span>
        </div>

        <form method="POST" action="/register.php" class="space-y-4" id="register-form">
          <?php echo csrf_field(); ?>

          <!-- Full Name -->
          <div class="space-y-1">
            <label for="full_name" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              Full Name *
            </label>
            <input 
              type="text" 
              name="full_name" 
              id="full_name" 
              required 
              maxlength="100"
              value="<?php echo e($_POST['full_name'] ?? ''); ?>"
              placeholder="e.g. Ada Lovelace" 
              class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100 placeholder:text-ink-400"
            >
          </div>

          <!-- Username & Email Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label for="username" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Username *
              </label>
              <input 
                type="text" 
                name="username" 
                id="username" 
                required 
                pattern="^[a-zA-Z0-9_.-]{3,30}$"
                value="<?php echo e($_POST['username'] ?? ''); ?>"
                placeholder="adalovelace" 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100 placeholder:text-ink-400"
              >
              <p class="text-[10px] font-mono text-ink-400">3-30 letters, numbers, hyphens</p>
            </div>

            <div class="space-y-1">
              <label for="email" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Email Address *
              </label>
              <input 
                type="email" 
                name="email" 
                id="email" 
                required 
                value="<?php echo e($_POST['email'] ?? ''); ?>"
                placeholder="ada@example.com" 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100 placeholder:text-ink-400"
              >
            </div>
          </div>

          <!-- Password with Toggle -->
          <div class="space-y-1">
            <label for="password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              Password *
            </label>
            <div class="relative">
              <input 
                type="password" 
                name="password" 
                id="password" 
                required 
                minlength="8"
                class="w-full px-3.5 py-2 pr-10 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
                oninput="checkPasswordStrength(this.value)"
              >
              <button 
                type="button" 
                onclick="togglePasswordVisibility('password', this)"
                class="absolute right-2.5 top-2 text-ink-400 hover:text-accent font-mono text-xs"
                title="Toggle password visibility"
              >
                SHOW
              </button>
            </div>

            <!-- Real-time Password Strength Indicator -->
            <div class="pt-1.5 space-y-1.5">
              <div class="w-full bg-paper-200 dark:bg-paper-800 h-1 rounded-full overflow-hidden">
                <div id="strength-meter" class="h-full bg-red-500 w-0 transition-all duration-300"></div>
              </div>
              <div class="flex items-center justify-between text-[10px] font-mono">
                <span id="strength-text" class="text-ink-400">Security: Enter password</span>
                <span class="text-ink-400">Min 8 chars (A-Z, a-z, 0-9)</span>
              </div>
            </div>
          </div>

          <!-- Password Confirmation with Toggle -->
          <div class="space-y-1">
            <label for="password_confirm" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
              Confirm Password *
            </label>
            <div class="relative">
              <input 
                type="password" 
                name="password_confirm" 
                id="password_confirm" 
                required 
                minlength="8"
                class="w-full px-3.5 py-2 pr-10 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
                oninput="checkMatch()"
              >
              <button 
                type="button" 
                onclick="togglePasswordVisibility('password_confirm', this)"
                class="absolute right-2.5 top-2 text-ink-400 hover:text-accent font-mono text-xs"
                title="Toggle password visibility"
              >
                SHOW
              </button>
            </div>
            <p id="match-feedback" class="text-[10px] font-mono text-ink-400"></p>
          </div>

          <!-- Terms & Privacy Acceptance -->
          <div class="pt-2">
            <label class="flex items-start space-x-2.5 cursor-pointer">
              <input 
                type="checkbox" 
                name="terms" 
                id="terms" 
                required 
                class="mt-0.5 rounded border-paper-300 dark:border-paper-700 text-accent focus:ring-accent"
              >
              <span class="font-mono text-xs text-ink-600 dark:text-ink-400 leading-snug">
                I agree to the <a href="/about.php" class="text-accent underline hover:text-accent-hover">Editorial Guidelines</a> and Privacy Statement.
              </span>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="pt-3">
            <button 
              type="submit" 
              id="submit-btn"
              class="w-full py-2.5 px-4 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
            >
              Enlist Account &rarr;
            </button>
          </div>
        </form>

        <div class="pt-4 border-t border-paper-200 dark:border-paper-800 text-center">
          <p class="font-mono text-xs text-ink-500">
            Already enlisted? 
            <a href="/login.php" class="text-accent hover:underline font-semibold ml-1">
              Sign in to your account &rarr;
            </a>
          </p>
        </div>

      <?php endif; ?>

    </div>

  </div>
</main>

<script>
function togglePasswordVisibility(fieldId, btn) {
  const input = document.getElementById(fieldId);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = 'HIDE';
  } else {
    input.type = 'password';
    btn.textContent = 'SHOW';
  }
}

function checkPasswordStrength(val) {
  const meter = document.getElementById('strength-meter');
  const text = document.getElementById('strength-text');
  let score = 0;

  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  if (val.length === 0) {
    meter.style.width = '0%';
    meter.className = 'h-full bg-red-500';
    text.textContent = 'Security: Enter password';
    text.className = 'text-ink-400';
  } else if (score < 3) {
    meter.style.width = '25%';
    meter.className = 'h-full bg-red-500';
    text.textContent = 'Security: Weak';
    text.className = 'text-red-500';
  } else if (score < 4) {
    meter.style.width = '60%';
    meter.className = 'h-full bg-amber-500';
    text.textContent = 'Security: Moderate';
    text.className = 'text-amber-500';
  } else {
    meter.style.width = '100%';
    meter.className = 'h-full bg-emerald-500';
    text.textContent = 'Security: Strong';
    text.className = 'text-emerald-500';
  }
  checkMatch();
}

function checkMatch() {
  const p1 = document.getElementById('password').value;
  const p2 = document.getElementById('password_confirm').value;
  const feedback = document.getElementById('match-feedback');
  if (p2.length === 0) {
    feedback.textContent = '';
  } else if (p1 === p2) {
    feedback.textContent = '✓ Passwords match';
    feedback.className = 'text-[10px] font-mono text-emerald-500';
  } else {
    feedback.textContent = '✗ Passwords do not match';
    feedback.className = 'text-[10px] font-mono text-red-500';
  }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
