<?php
/**
 * WordWise Public Header Template
 * Minimalist editorial design with JetBrains Mono, Inter, and Tailwind CSS.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$siteName = get_setting('site_name', 'WordWise');
$siteTagline = get_setting('site_tagline', 'A minimal editorial journal for tech, ideas, and craft.');
$pageTitle = isset($customTitle) ? e($customTitle) . " — " . e($siteName) : e($siteName) . " — " . e($siteTagline);
$pageDesc = isset($customDesc) ? e($customDesc) : e(get_setting('site_description', ''));
$canonicalUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $pageTitle; ?></title>
  <meta name="description" content="<?php echo $pageDesc; ?>">
  <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
  
  <!-- Open Graph / Social Media -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo $pageTitle; ?>">
  <meta property="og:description" content="<?php echo $pageDesc; ?>">
  <meta property="og:url" content="<?php echo e($canonicalUrl); ?>">
  <meta property="og:site_name" content="<?php echo e($siteName); ?>">
  
  <!-- Favicon -->
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

  <!-- Google Fonts: JetBrains Mono, IBM Plex Mono, Inter, Newsreader -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,500&family=IBM+Plex+Mono:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,600;0,6..72,700;1,6..72,400&display=swap" rel="stylesheet">

  <!-- Tailwind CSS Compiled Stylesheet -->
  <link rel="stylesheet" href="/css/main.css">
</head>
<body class="bg-paper-50 text-ink-900 dark:bg-paper-950 dark:text-ink-100 flex flex-col min-h-screen selection:bg-accent/20">