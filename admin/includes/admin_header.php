<?php
ob_start();
/**
 * WordWise Admin CMS Header & Sidebar Layout
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce minimum role of author to access admin area
auth_require('author');

$currentUser = auth_user();
$currentRole = auth_role();
$currentPage = basename($_SERVER['PHP_SELF']);

$siteName = get_setting('site_name', 'WordWise');
$adminTitle = isset($pageTitle) ? e($pageTitle) . " &mdash; CMS" : "Dashboard &mdash; WordWise CMS";

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $adminTitle; ?></title>
  <link rel="icon" href="/images/favicon.png" type="image/png">

  <!-- Inline critical script to prevent theme flash -->
  <script>
    (function() {
      const theme = localStorage.getItem('wordwise_theme') || 'system';
      const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
      if (isDark) document.documentElement.classList.add('dark');
      else document.documentElement.classList.remove('dark');
    })();
  </script>

  <!-- Google Fonts: JetBrains Mono, IBM Plex Mono & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,500&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Compiled Tailwind CSS -->
  <link rel="stylesheet" href="/css/main.css">
</head>
<body class="bg-paper-100 dark:bg-paper-950 text-ink-900 dark:text-ink-100 font-sans min-h-screen flex flex-col md:flex-row antialiased">

  <!-- Admin Sidebar Navigation -->
  <aside class="w-full md:w-64 bg-white dark:bg-paper-900 border-r border-paper-200 dark:border-paper-800 flex-shrink-0 flex flex-col justify-between">
    <div>
      
      <!-- Brand & Version -->
      <div class="h-16 border-b border-paper-200 dark:border-paper-800 px-6 flex items-center justify-between">
        <a href="/admin/index.php" class="flex items-center space-x-2.5">
          <span class="w-2.5 h-2.5 rounded-full bg-accent inline-block"></span>
          <span class="font-mono font-bold text-sm tracking-wider uppercase text-ink-950 dark:text-ink-50">WordWise</span>
          <span class="font-mono text-[10px] typewriter-badge text-accent border-accent/40">CMS</span>
        </a>
      </div>

      <!-- Navigation Links -->
      <nav class="p-4 space-y-1 text-xs font-mono">
        
        <div class="px-3 py-2 text-[10px] uppercase tracking-wider text-ink-400 font-semibold">Editorial</div>
        
        <a href="/admin/index.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'index.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
          <span>Dashboard</span>
        </a>

        <a href="/admin/posts.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo in_array($currentPage, ['posts.php', 'post-edit.php']) ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
          <span>Articles</span>
        </a>

        <a href="/admin/post-edit.php" class="flex items-center space-x-3 px-3 py-2 text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800 rounded transition-colors pl-8 text-[11px]">
          <span>+ Write New Dispatch</span>
        </a>

        <?php if (auth_can('manage_categories')): ?>
          <a href="/admin/categories.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'categories.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
            <span>Categories</span>
          </a>

          <a href="/admin/tags.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'tags.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
            <span>Tags</span>
          </a>
        <?php endif; ?>

        <a href="/admin/media.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'media.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
          <span>Media Library</span>
        </a>

        <?php if ($currentRole === 'admin'): ?>
          <div class="pt-4 px-3 py-2 text-[10px] uppercase tracking-wider text-ink-400 font-semibold">System</div>

          <a href="/admin/users.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'users.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span>Users &amp; Roles</span>
          </a>

          <a href="/admin/settings.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'settings.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span>Site Settings</span>
          </a>

          <a href="/admin/audit.php" class="flex items-center space-x-3 px-3 py-2 rounded transition-colors <?php echo $currentPage === 'audit.php' ? 'bg-accent/10 text-accent font-semibold border-l-2 border-accent' : 'text-ink-600 dark:text-ink-300 hover:bg-paper-100 dark:hover:bg-paper-800'; ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Audit Trail</span>
          </a>
        <?php endif; ?>

      </nav>
    </div>

    <!-- User Profile & Sign Out Footer -->
    <div class="p-4 border-t border-paper-200 dark:border-paper-800 bg-paper-50 dark:bg-paper-950/50">
      <div class="flex items-center justify-between text-xs font-mono">
        <div>
          <p class="font-bold text-ink-900 dark:text-ink-100 truncate max-w-[120px]"><?php echo e($currentUser['full_name']); ?></p>
          <span class="typewriter-badge text-[10px] uppercase text-accent border-accent/40"><?php echo e($currentRole); ?></span>
        </div>
        <a href="/logout.php" class="text-ink-400 hover:text-red-600" title="Sign out">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- Main Content Container -->
  <div class="flex-grow flex flex-col min-w-0">
    
    <!-- Top Administrative Bar -->
    <header class="h-16 bg-white dark:bg-paper-900 border-b border-paper-200 dark:border-paper-800 px-6 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <span class="font-mono text-xs uppercase tracking-wider text-ink-500">
          <?php echo isset($pageHeader) ? e($pageHeader) : 'CMS Dashboard'; ?>
        </span>
      </div>

      <div class="flex items-center space-x-4">
        <!-- View Public Site Button -->
        <a href="/" target="_blank" class="typewriter-badge hover:border-accent hover:text-accent transition-colors flex items-center space-x-1.5">
          <span>View Site</span>
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
        </a>

        <!-- Theme Toggle -->
        <button type="button" onclick="window.WordWiseTheme.cycle()" class="p-1.5 text-ink-500 hover:text-accent border border-paper-300 dark:border-paper-800 rounded text-xs font-mono" title="Toggle theme">
          <span data-theme-indicator>THEME</span>
        </button>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow p-6 sm:p-8 max-w-6xl w-full mx-auto">
      
      <!-- Flash Notifications -->
      <?php if (!empty($flashSuccess)): ?>
        <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-xs font-mono rounded flex items-center space-x-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          <span><?php echo e($flashSuccess); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($flashError)): ?>
        <div class="mb-6 p-4 bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400 text-xs font-mono rounded flex items-center space-x-2">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          <span><?php echo e($flashError); ?></span>
        </div>
      <?php endif; ?>
