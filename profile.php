<?php
/**
 * WordWise User Profile & Account Management
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

auth_require(); // Require logged in user

$userId = auth_id();
$user = db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$userId]);
if (!$user) {
    header("Location: /logout.php");
    exit;
}

$error = '';
$success = '';
$devVerifyLink = '';

// Handle Profile Updates or Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = "Security token mismatch. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $bio = trim($_POST['bio'] ?? '');
            $avatar = trim($_POST['user_image'] ?? $user['user_image']);

            // Handle optional avatar file upload
            if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['avatar_file']['tmp_name']);
                finfo_close($finfo);

                $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                if (isset($allowedMimes[$mime])) {
                    $ext = $allowedMimes[$mime];
                    $avatarName = 'avatar_' . $userId . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $targetPath = __DIR__ . '/images/' . $avatarName;
                    if (move_uploaded_file($_FILES['avatar_file']['tmp_name'], $targetPath)) {
                        $avatar = 'images/' . $avatarName;
                    }
                }
            }

            $res = auth_update_profile($userId, [
                'full_name'  => $fullName,
                'bio'        => $bio,
                'user_image' => $avatar
            ]);

            if ($res['success']) {
                $success = "Profile information updated successfully.";
                $user = db_fetch_one("SELECT * FROM users WHERE user_id = ?", [$userId]);
            } else {
                $error = $res['error'] ?? "Failed to update profile.";
            }
        } elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_new_password'] ?? '';

            if ($newPass !== $confirmPass) {
                $error = "New password confirmation does not match.";
            } else {
                $res = auth_change_password($userId, $currentPass, $newPass);
                if ($res['success']) {
                    $success = "Password changed successfully.";
                } else {
                    $error = $res['error'] ?? "Failed to change password.";
                }
            }
        } elseif ($action === 'resend_verification') {
            $newToken = bin2hex(random_bytes(32));
            db_execute("UPDATE users SET verification_token = ? WHERE user_id = ?", [$newToken, $userId]);
            $success = "A fresh email verification link has been generated.";
            $appUrl = env('APP_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            $devVerifyLink = $appUrl . "/verify-email.php?token=" . urlencode($newToken);
        }
    }
}

// Fetch Connected OAuth Providers
$connectedProviders = db_fetch_all("SELECT provider, provider_email, created_at FROM user_providers WHERE user_id = ?", [$userId]);
$isGoogleConnected = false;
foreach ($connectedProviders as $cp) {
    if ($cp['provider'] === 'google') $isGoogleConnected = true;
}

// Fetch User's Articles if Author, Editor, or Admin
$userArticles = [];
if (auth_can('create_posts')) {
    $userArticles = db_fetch_all(
        "SELECT post_id, post_title, post_slug, post_status, post_date, post_views 
         FROM posts 
         WHERE post_author = ? OR post_author = ? 
         ORDER BY post_date DESC LIMIT 10",
        [$user['username'], $user['full_name']]
    );
}

$isVerified = !empty($user['email_verified_at']);

$customTitle = "User Profile & Settings";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-12">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    
    <!-- Profile Header Banner -->
    <div class="editorial-card p-6 sm:p-8 rounded-sm">
      <div class="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6">
        
        <div class="flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-5 text-center sm:text-left">
          <div class="relative">
            <img 
              src="/<?php echo e($user['user_image'] ?: 'images/favicon.png'); ?>" 
              alt="<?php echo e($user['full_name']); ?>" 
              class="w-20 h-20 rounded-full object-cover border-2 border-paper-300 dark:border-paper-700 bg-paper-100"
            >
          </div>

          <div class="space-y-1">
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
                <?php echo e($user['full_name']); ?>
              </h1>
              <span class="typewriter-badge text-[10px] bg-accent/10 border-accent text-accent uppercase">
                <?php echo e($user['user_role']); ?>
              </span>
            </div>

            <p class="font-mono text-xs text-ink-500">
              @<?php echo e($user['username']); ?> &bull; <?php echo e($user['email']); ?>
            </p>

            <div class="pt-2 flex items-center justify-center sm:justify-start space-x-2">
              <?php if ($isVerified): ?>
                <span class="inline-flex items-center space-x-1 text-[11px] font-mono text-emerald-600 dark:text-emerald-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                  <span>Verified Contributor</span>
                </span>
              <?php else: ?>
                <span class="inline-flex items-center space-x-1 text-[11px] font-mono text-amber-600 dark:text-amber-400">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                  <span>Email Unverified</span>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="flex sm:flex-col items-center gap-2">
          <?php if (auth_can('access_admin')): ?>
            <a href="/admin/index.php" class="px-3.5 py-1.5 bg-paper-100 dark:bg-paper-800 hover:border-accent border border-paper-300 dark:border-paper-700 text-xs font-mono text-ink-900 dark:text-ink-100 rounded transition-colors">
              CMS Panel &rarr;
            </a>
          <?php endif; ?>
          <a href="/logout.php" class="px-3.5 py-1.5 text-xs font-mono text-red-600 hover:underline">
            Sign Out
          </a>
        </div>

      </div>
    </div>

    <!-- Notification Messages -->
    <?php if (!empty($success)): ?>
      <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-mono rounded">
        <?php echo e($success); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($devVerifyLink)): ?>
      <div class="p-4 bg-paper-100 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded space-y-2">
        <div class="flex items-center justify-between text-[11px] font-mono text-ink-500">
          <span class="font-bold text-accent uppercase tracking-wider">[ Local Verification Dispatch ]</span>
          <span>Click to verify</span>
        </div>
        <a href="<?php echo e($devVerifyLink); ?>" class="block font-mono text-xs text-accent hover:underline break-all">
          <?php echo e($devVerifyLink); ?>
        </a>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-600 dark:text-red-400 text-xs font-mono rounded">
        <?php echo e($error); ?>
      </div>
    <?php endif; ?>

    <!-- Email Verification Alert for Unverified Users -->
    <?php if (!$isVerified): ?>
      <div class="p-5 bg-amber-500/10 border border-amber-500/30 rounded-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="space-y-1">
          <h3 class="font-mono text-xs uppercase tracking-wider font-bold text-amber-700 dark:text-amber-400">
            Email Verification Pending
          </h3>
          <p class="font-mono text-xs text-ink-600 dark:text-ink-400">
            Please confirm your email address (<?php echo e($user['email']); ?>) to unlock editorial publishing and sensitive account actions.
          </p>
        </div>
        <form method="POST" action="/profile.php" class="flex-shrink-0">
          <?php echo csrf_field(); ?>
          <input type="hidden" name="action" value="resend_verification">
          <button type="submit" class="typewriter-badge hover:border-accent hover:text-accent font-mono text-xs py-1 px-3">
            Resend Verification Link
          </button>
        </form>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Primary Column: Profile Details & Password -->
      <div class="lg:col-span-7 space-y-8">
        
        <!-- Profile Form Card -->
        <div class="editorial-card p-6 rounded-sm space-y-5">
          <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
            Public Contributor Details
          </h2>

          <form method="POST" action="/profile.php" enctype="multipart/form-data" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="space-y-1">
              <label for="full_name" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Display Name *
              </label>
              <input 
                type="text" 
                name="full_name" 
                id="full_name" 
                required 
                maxlength="100"
                value="<?php echo e($user['full_name']); ?>" 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
              >
            </div>

            <div class="space-y-1">
              <label for="username_ro" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Username
              </label>
              <input 
                type="text" 
                id="username_ro" 
                disabled 
                value="@<?php echo e($user['username']); ?>" 
                class="w-full px-3.5 py-2 text-xs bg-paper-200/60 dark:bg-paper-800/40 border border-paper-300 dark:border-paper-800 rounded font-mono text-ink-500 cursor-not-allowed"
              >
              <p class="text-[10px] font-mono text-ink-400">Username handle cannot be modified once enlisted.</p>
            </div>

            <div class="space-y-1">
              <label for="bio" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Biography / Byline Note
              </label>
              <textarea 
                name="bio" 
                id="bio" 
                rows="3" 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
                placeholder="Brief editorial biography..."
              ><?php echo e($user['bio'] ?? ''); ?></textarea>
            </div>

            <div class="space-y-1">
              <label for="avatar_file" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Update Avatar Image
              </label>
              <input 
                type="file" 
                name="avatar_file" 
                id="avatar_file" 
                accept="image/jpeg,image/png,image/webp" 
                class="w-full text-xs font-mono text-ink-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-mono file:bg-paper-200 dark:file:bg-paper-800 file:text-ink-800 dark:file:text-ink-200 hover:file:bg-accent hover:file:text-white cursor-pointer"
              >
            </div>

            <div class="pt-2">
              <button 
                type="submit" 
                class="px-5 py-2 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
              >
                Save Details &rarr;
              </button>
            </div>
          </form>
        </div>

        <!-- Password Change Card -->
        <div class="editorial-card p-6 rounded-sm space-y-5">
          <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
            Change Password
          </h2>

          <form method="POST" action="/profile.php" class="space-y-4">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="change_password">

            <div class="space-y-1">
              <label for="current_password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Current Password *
              </label>
              <input 
                type="password" 
                name="current_password" 
                id="current_password" 
                required 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
              >
            </div>

            <div class="space-y-1">
              <label for="new_password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                New Password *
              </label>
              <input 
                type="password" 
                name="new_password" 
                id="new_password" 
                required 
                minlength="8" 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
              >
              <p class="text-[10px] font-mono text-ink-400">Min 8 characters with uppercase, lowercase, and numbers.</p>
            </div>

            <div class="space-y-1">
              <label for="confirm_new_password" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                Confirm New Password *
              </label>
              <input 
                type="password" 
                name="confirm_new_password" 
                id="confirm_new_password" 
                required 
                minlength="8" 
                class="w-full px-3.5 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono focus:border-accent text-ink-900 dark:text-ink-100"
              >
            </div>

            <div class="pt-2">
              <button 
                type="submit" 
                class="px-5 py-2 bg-ink-900 dark:bg-ink-100 text-white dark:text-ink-950 hover:bg-accent dark:hover:bg-accent text-xs font-mono uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
              >
                Update Password
              </button>
            </div>
          </form>
        </div>

      </div>

      <!-- Secondary Sidebar Column: OAuth & Authored Articles -->
      <div class="lg:col-span-5 space-y-8">
        
        <!-- Connected Sign-in Methods -->
        <div class="editorial-card p-6 rounded-sm space-y-4">
          <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
            Connected Identifiers
          </h2>

          <div class="space-y-3">
            <!-- Google Identity Status -->
            <div class="flex items-center justify-between p-3 border border-paper-200 dark:border-paper-800 rounded bg-paper-50 dark:bg-paper-950">
              <div class="flex items-center space-x-3">
                <svg class="w-4 h-4" viewBox="0 0 24 24">
                  <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                  <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                  <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                  <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                </svg>
                <div class="text-xs font-mono">
                  <p class="font-semibold text-ink-900 dark:text-ink-100">Google Account</p>
                  <p class="text-[10px] text-ink-500">
                    <?php echo $isGoogleConnected ? "Linked &bull; " . e($connectedProviders[0]['provider_email'] ?? '') : "Not Linked"; ?>
                  </p>
                </div>
              </div>

              <div>
                <?php if ($isGoogleConnected): ?>
                  <button 
                    type="button" 
                    onclick="document.getElementById('unlink-modal').classList.toggle('hidden')"
                    class="typewriter-badge text-[10px] text-red-600 border-red-500/30 hover:bg-red-500/10 cursor-pointer"
                  >
                    Unlink
                  </button>
                <?php else: ?>
                  <a href="/auth/google/redirect.php" class="typewriter-badge text-[10px] hover:border-accent hover:text-accent">
                    Connect
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Unlink Re-authentication Modal / Dropdown -->
            <?php if ($isGoogleConnected): ?>
              <div id="unlink-modal" class="hidden p-4 bg-paper-100 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded space-y-3 text-xs font-mono">
                <p class="font-bold text-ink-950 dark:text-ink-50">Confirm Identity Unlinking</p>
                <p class="text-[11px] text-ink-500">
                  Please confirm your current password to unlink this Google account:
                </p>
                <form method="POST" action="/auth/unlink.php" class="space-y-3">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="provider" value="google">
                  <input 
                    type="password" 
                    name="confirm_password" 
                    required 
                    placeholder="Current password" 
                    class="w-full px-3 py-1.5 text-xs bg-white dark:bg-paper-900 border border-paper-300 dark:border-paper-700 rounded font-mono focus:border-accent"
                  >
                  <div class="flex items-center space-x-2">
                    <button type="submit" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded text-[11px] font-mono">
                      Confirm Unlink
                    </button>
                    <button type="button" onclick="document.getElementById('unlink-modal').classList.add('hidden')" class="typewriter-badge text-[11px]">
                      Cancel
                    </button>
                  </div>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Authored Dispatches -->
        <?php if (!empty($userArticles)): ?>
          <div class="editorial-card p-6 rounded-sm space-y-4">
            <div class="flex items-center justify-between border-b border-paper-200 dark:border-paper-800 pb-2">
              <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold">
                Authored Dispatches
              </h2>
              <span class="font-mono text-xs text-ink-400"><?php echo count($userArticles); ?> posts</span>
            </div>

            <div class="divide-y divide-paper-200 dark:divide-paper-800">
              <?php foreach ($userArticles as $art): ?>
                <div class="py-2.5 flex items-center justify-between group">
                  <div class="space-y-0.5 max-w-[200px] truncate">
                    <a href="/post.php?slug=<?php echo urlencode($art['post_slug']); ?>" class="font-mono font-bold text-xs text-ink-900 dark:text-ink-100 group-hover:text-accent truncate block">
                      <?php echo e($art['post_title']); ?>
                    </a>
                    <p class="text-[10px] font-mono text-ink-400">
                      <?php echo date('M j, Y', strtotime($art['post_date'])); ?> &bull; <?php echo $art['post_views']; ?> views
                    </p>
                  </div>
                  <div>
                    <span class="typewriter-badge text-[10px] uppercase <?php echo $art['post_status'] === 'published' ? 'text-emerald-600 border-emerald-500/30' : 'text-amber-600 border-amber-500/30'; ?>">
                      <?php echo e($art['post_status']); ?>
                    </span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if (auth_can('create_posts')): ?>
              <div class="pt-2">
                <a href="/admin/post-edit.php" class="block text-center py-2 bg-paper-100 dark:bg-paper-800 hover:border-accent border border-paper-300 dark:border-paper-700 text-xs font-mono text-accent font-semibold rounded transition-colors">
                  + Compose New Dispatch
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>

    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
