<?php
/**
 * WordWise Editorial Navigation Bar
 * Typewriter wordmark, theme switcher, live search trigger, responsive mobile menu.
 */
$categories = db_fetch_all("SELECT * FROM categories ORDER BY cat_title ASC");
$user = auth_user();
?>
<header class="border-b border-paper-200 dark:border-paper-800 bg-paper-50/95 dark:bg-paper-950/95 backdrop-blur-sm sticky top-0 z-40 transition-colors duration-200">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      
      <!-- Brand Wordmark -->
      <div class="flex items-center space-x-3">
        <a href="/" class="group flex items-center space-x-2.5">
          <span class="w-3 h-3 rounded-full bg-accent inline-block group-hover:scale-125 transition-transform"></span>
          <span class="font-mono text-lg font-semibold tracking-wider text-ink-950 dark:text-ink-50 uppercase">WordWise</span>
        </a>
        <span class="hidden md:inline-block font-mono text-xs text-ink-500 dark:text-ink-400 border-l border-paper-300 dark:border-paper-800 pl-3">
          Editorial Journal
        </span>
      </div>

      <!-- Desktop Navigation Links -->
      <nav class="hidden md:flex items-center space-x-8 text-sm font-medium">
        <a href="/" class="text-ink-700 dark:text-ink-300 hover:text-accent dark:hover:text-accent-dark transition-colors">Home</a>
        <a href="/articles.php" class="text-ink-700 dark:text-ink-300 hover:text-accent dark:hover:text-accent-dark transition-colors">Articles</a>
        <a href="/categories.php" class="text-ink-700 dark:text-ink-300 hover:text-accent dark:hover:text-accent-dark transition-colors">Categories</a>
        <a href="/about.php" class="text-ink-700 dark:text-ink-300 hover:text-accent dark:hover:text-accent-dark transition-colors">About</a>
      </nav>

      <!-- Action Utilities: Search, Theme, Auth -->
      <div class="flex items-center space-x-3 sm:space-x-4">
        
        <!-- Search Trigger Button -->
        <a href="/articles.php?focus=search" class="p-2 text-ink-500 hover:text-ink-900 dark:text-ink-400 dark:hover:text-ink-100 transition-colors" title="Search articles">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </a>

        <!-- Theme Toggle Switcher -->
        <button type="button" onclick="window.WordWiseTheme.cycle()" class="flex items-center space-x-1.5 px-2.5 py-1 text-xs font-mono rounded border border-paper-300 dark:border-paper-800 hover:border-accent text-ink-700 dark:text-ink-300 bg-white dark:bg-paper-900 transition-colors" title="Toggle theme (Light / Dark / System)">
          <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
          </svg>
          <span data-theme-indicator class="hidden sm:inline">THEME</span>
        </button>

        <!-- Account / Admin Link -->
        <?php if ($user): ?>
          <div class="flex items-center space-x-2">
            <?php if (auth_can('access_admin')): ?>
              <a href="/admin/index.php" class="hidden sm:inline-block typewriter-badge bg-accent text-white border-accent hover:bg-accent-hover transition-colors">
                CMS
              </a>
            <?php endif; ?>
            <a href="/profile.php" class="typewriter-badge hover:border-accent text-ink-700 dark:text-ink-300 transition-colors flex items-center space-x-1" title="Account profile">
              <span>@<?php echo e($user['username']); ?></span>
            </a>
          </div>
        <?php else: ?>
          <div class="flex items-center space-x-2">
            <a href="/login.php" class="typewriter-badge hover:border-accent text-ink-700 dark:text-ink-300 transition-colors">
              Sign In
            </a>
            <a href="/register.php" class="hidden sm:inline-block typewriter-badge bg-accent text-white border-accent hover:bg-accent-hover transition-colors">
              Register
            </a>
          </div>
        <?php endif; ?>

        <!-- Mobile Menu Toggle Button -->
        <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="md:hidden p-2 text-ink-700 dark:text-ink-300" aria-label="Toggle navigation">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>

      </div>
    </div>
  </div>

  <!-- Mobile Collapsible Menu -->
  <div id="mobile-menu" class="hidden md:hidden border-t border-paper-200 dark:border-paper-800 bg-paper-100 dark:bg-paper-900 px-4 pt-3 pb-5 space-y-2">
    <a href="/" class="block py-2 text-sm font-mono text-ink-800 dark:text-ink-200 hover:text-accent">Home</a>
    <a href="/articles.php" class="block py-2 text-sm font-mono text-ink-800 dark:text-ink-200 hover:text-accent">Articles</a>
    <a href="/categories.php" class="block py-2 text-sm font-mono text-ink-800 dark:text-ink-200 hover:text-accent">Categories</a>
    <a href="/about.php" class="block py-2 text-sm font-mono text-ink-800 dark:text-ink-200 hover:text-accent">About WordWise</a>
    <?php if ($user): ?>
      <?php if (auth_can('access_admin')): ?>
        <a href="/admin/index.php" class="block py-2 text-sm font-mono text-accent">CMS Dashboard &rarr;</a>
      <?php endif; ?>
      <a href="/profile.php" class="block py-2 text-sm font-mono text-ink-800 dark:text-ink-200">Account Profile (@<?php echo e($user['username']); ?>)</a>
      <a href="/logout.php" class="block py-2 text-sm font-mono text-red-600">Sign Out</a>
    <?php else: ?>
      <a href="/login.php" class="block py-2 text-sm font-mono text-accent">Sign In</a>
      <a href="/register.php" class="block py-2 text-sm font-mono text-ink-700 dark:text-ink-300">Enlist Account (Register)</a>
    <?php endif; ?>
  </div>
</header>