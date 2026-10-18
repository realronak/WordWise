<?php
/**
 * WordWise Admin CMS - Site Settings
 * Configure publication name, description, themes, social links, and pagination.
 */
$pageTitle = "Site Settings";
$pageHeader = "System Configuration";
require_once __DIR__ . '/includes/admin_header.php';

auth_require('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $siteName = trim($_POST['site_name'] ?? '');
        $siteTagline = trim($_POST['site_tagline'] ?? '');
        $siteDesc = trim($_POST['site_description'] ?? '');
        $defaultTheme = in_array($_POST['default_theme'] ?? '', ['system', 'light', 'dark'], true) ? $_POST['default_theme'] : 'system';
        $perPage = max(2, min(50, (int)($_POST['posts_per_page'] ?? 6)));
        $twitter = trim($_POST['social_twitter'] ?? '');
        $github = trim($_POST['social_github'] ?? '');
        $linkedin = trim($_POST['social_linkedin'] ?? '');
        $authorName = trim($_POST['author_name'] ?? '');
        $authorBio = trim($_POST['author_bio'] ?? '');
        $allowReg = !empty($_POST['allow_registration']) ? '1' : '0';
        $defaultRole = in_array($_POST['default_user_role'] ?? '', ['reader', 'author'], true) ? $_POST['default_user_role'] : 'reader';

        if ($siteName === '') {
            $errors[] = "Site Name cannot be empty.";
        } else {
            set_setting('site_name', $siteName);
            set_setting('site_tagline', $siteTagline);
            set_setting('site_description', $siteDesc);
            set_setting('default_theme', $defaultTheme);
            set_setting('posts_per_page', (string)$perPage);
            set_setting('social_twitter', $twitter);
            set_setting('social_github', $github);
            set_setting('social_linkedin', $linkedin);
            set_setting('author_name', $authorName);
            set_setting('author_bio', $authorBio);
            set_setting('allow_registration', $allowReg);
            set_setting('default_user_role', $defaultRole);

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'update_settings', 'Updated site configuration parameters', ?)", [auth_id(), $ip]);

            $_SESSION['flash_success'] = "Site settings saved successfully.";
            header("Location: /admin/settings.php");
            exit;
        }
    }
}

// Current Settings Values
$siteName = get_setting('site_name', 'WordWise');
$siteTagline = get_setting('site_tagline', 'A minimal editorial journal for tech, ideas, and craft.');
$siteDesc = get_setting('site_description', '');
$defaultTheme = get_setting('default_theme', 'system');
$perPage = get_setting('posts_per_page', '6');
$twitter = get_setting('social_twitter', '');
$github = get_setting('social_github', '');
$linkedin = get_setting('social_linkedin', '');
$authorName = get_setting('author_name', 'Ronak Rathod');
$authorBio = get_setting('author_bio', '');
$allowReg = get_setting('allow_registration', '0') === '1';
$defaultRole = get_setting('default_user_role', 'reader');
?>

<div class="max-w-3xl mx-auto space-y-6">
  
  <div class="pb-4 border-b border-paper-200 dark:border-paper-800">
    <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
      Site &amp; Editorial Settings
    </h1>
    <p class="font-mono text-xs text-ink-500 mt-1">
      Global parameters, theme preferences, and publication metadata.
    </p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-600 text-xs font-mono rounded space-y-1">
      <?php foreach ($errors as $e): ?><p>&bull; <?php echo e($e); ?></p><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="/admin/settings.php" class="space-y-6">
    <?php echo csrf_field(); ?>

    <!-- General Settings -->
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
        General Publication Identity
      </h2>

      <div>
        <label for="site_name" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
          Publication Name *
        </label>
        <input 
          type="text" 
          name="site_name" 
          id="site_name" 
          required 
          value="<?php echo e($siteName); ?>" 
          class="w-full px-3 py-2 text-sm font-mono font-bold bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-950 dark:text-ink-50"
        >
      </div>

      <div>
        <label for="site_tagline" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
          Editorial Tagline
        </label>
        <input 
          type="text" 
          name="site_tagline" 
          id="site_tagline" 
          value="<?php echo e($siteTagline); ?>" 
          class="w-full px-3 py-1.5 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
        >
      </div>

      <div>
        <label for="site_description" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
          SEO Description (Meta Tag)
        </label>
        <textarea 
          name="site_description" 
          id="site_description" 
          rows="3" 
          class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
        ><?php echo e($siteDesc); ?></textarea>
      </div>
    </div>

    <!-- Layout & Reading -->
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
        Reading Experience &amp; Appearance
      </h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label for="default_theme" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Default Theme
          </label>
          <select name="default_theme" id="default_theme" class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
            <option value="system" <?php echo $defaultTheme === 'system' ? 'selected' : ''; ?>>System Preference</option>
            <option value="light" <?php echo $defaultTheme === 'light' ? 'selected' : ''; ?>>Light (Parchment)</option>
            <option value="dark" <?php echo $defaultTheme === 'dark' ? 'selected' : ''; ?>>Dark (Typewriter Ink)</option>
          </select>
        </div>

        <div>
          <label for="posts_per_page" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Articles Per Page (Pagination)
          </label>
          <input 
            type="number" 
            name="posts_per_page" 
            id="posts_per_page" 
            min="2" 
            max="50" 
            value="<?php echo e($perPage); ?>" 
            class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>
      </div>
    </div>

    <!-- Connect & Social Links -->
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
        Social Links &amp; Author
      </h2>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label for="social_github" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            GitHub URL
          </label>
          <input type="url" name="social_github" id="social_github" value="<?php echo e($github); ?>" class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
        </div>

        <div>
          <label for="social_twitter" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Twitter/X URL
          </label>
          <input type="url" name="social_twitter" id="social_twitter" value="<?php echo e($twitter); ?>" class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
        </div>

        <div>
          <label for="social_linkedin" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            LinkedIn URL
          </label>
          <input type="url" name="social_linkedin" id="social_linkedin" value="<?php echo e($linkedin); ?>" class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
        <div>
          <label for="author_name" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Lead Editor Byline
          </label>
          <input type="text" name="author_name" id="author_name" value="<?php echo e($authorName); ?>" class="w-full px-3 py-1.5 text-xs font-sans bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
        </div>

        <div>
          <label for="author_bio" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Lead Editor Bio
          </label>
          <input type="text" name="author_bio" id="author_bio" value="<?php echo e($authorBio); ?>" class="w-full px-3 py-1.5 text-xs font-sans bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
        </div>
      </div>

    </div>

    <!-- Authentication & Registration Controls -->
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
        Authentication &amp; User Enlistment
      </h2>

      <div class="space-y-4">
        <label class="flex items-start space-x-3 cursor-pointer">
          <input 
            type="checkbox" 
            name="allow_registration" 
            id="allow_registration" 
            value="1" 
            <?php echo $allowReg ? 'checked' : ''; ?>
            class="mt-1 rounded border-paper-300 dark:border-paper-700 text-accent focus:ring-accent"
          >
          <div class="text-xs font-mono">
            <span class="font-bold text-ink-900 dark:text-ink-100 block">Enable Public Registration</span>
            <span class="text-ink-500">Allow visitors to register new reader or contributor accounts via /register.php.</span>
          </div>
        </label>

        <div>
          <label for="default_user_role" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Default Role for Newly Enlisted Accounts
          </label>
          <select name="default_user_role" id="default_user_role" class="w-full sm:w-64 px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100">
            <option value="reader" <?php echo $defaultRole === 'reader' ? 'selected' : ''; ?>>Reader (Read and comment)</option>
            <option value="author" <?php echo $defaultRole === 'author' ? 'selected' : ''; ?>>Author (Can draft and write articles)</option>
          </select>
        </div>

        <div class="pt-2 border-t border-paper-200 dark:border-paper-800 text-[11px] font-mono text-ink-500">
          <span>Google OAuth credentials (Client ID and Secret) are managed securely via <code>.env</code> file.</span>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="flex justify-end">
      <button 
        type="submit" 
        class="py-2.5 px-6 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
      >
        Save Settings
      </button>
    </div>

  </form>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
