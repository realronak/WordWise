<?php
/**
 * WordWise About & Colophon Page
 */
require_once __DIR__ . '/includes/db.php';

$authorName = get_setting('author_name', 'Ronak Rathod');
$authorBio = get_setting('author_bio', 'Engineer & writer interested in operating systems, agentic AI, and thoughtful software design.');
$siteDescription = get_setting('site_description', 'WordWise is an independent, minimalist blogging platform dedicated to thoughtful essays on programming, technology, and design.');

$customTitle = "About &amp; Colophon";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-16">
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="border-b border-paper-200 dark:border-paper-800 pb-8 mb-10">
      <div class="flex items-center space-x-2 font-mono text-xs text-accent uppercase tracking-wider mb-2">
        <a href="/" class="hover:underline">Home</a>
        <span>/</span>
        <span>Colophon</span>
      </div>
      <h1 class="text-4xl sm:text-5xl font-mono font-bold text-ink-950 dark:text-ink-50 mb-3">
        About WordWise
      </h1>
      <p class="font-mono text-xs text-ink-500">
        Publication Principles &bull; Editorial Statement &bull; Technology
      </p>
    </div>

    <div class="prose prose-neutral dark:prose-invert max-w-none font-mono text-base leading-relaxed text-ink-800 dark:text-ink-200 space-y-8">
      
      <p class="text-lg leading-relaxed text-ink-900 dark:text-ink-100 font-mono italic border-l-2 border-accent pl-4">
        &ldquo;Writing is thinking made visible. WordWise was built to provide a focused, distraction-free environment for reading and publishing long-form technical ideas.&rdquo;
      </p>

      <h2>The Editorial Philosophy</h2>
      <p>
        <?php echo e($siteDescription); ?>
      </p>
      <p>
        In an era dominated by algorithmic feeds and visual noise, WordWise favors patience, typographical clarity, and deep exploration. The platform adheres to an editorial typewriter aesthetic: monochromatic ink surfaces, precise monospace accents, and classic editorial typography optimized for long-form comfort.
      </p>

      <h2>The Author</h2>
      <div class="editorial-card p-6 rounded-sm not-prose flex items-start space-x-4 my-6">
        <div class="w-12 h-12 rounded-full bg-accent/20 flex-shrink-0 flex items-center justify-center font-mono font-bold text-lg text-accent">
          <?php echo strtoupper(substr($authorName, 0, 1)); ?>
        </div>
        <div class="space-y-1">
          <h3 class="font-mono font-bold text-base text-ink-950 dark:text-ink-50"><?php echo e($authorName); ?></h3>
          <p class="font-mono text-xs text-accent">Editor &amp; Creator</p>
          <p class="font-mono text-xs text-ink-600 dark:text-ink-300 pt-1 leading-relaxed">
            <?php echo e($authorBio); ?>
          </p>
        </div>
      </div>

      <h2>Colophon &amp; Stack</h2>
      <p>
        WordWise runs on native, modern web fundamentals:
      </p>
      <ul class="font-mono text-xs space-y-1 text-ink-600 dark:text-ink-300">
        <li><strong>Operating System:</strong> Omarchy Linux (Arch Linux rolling distribution)</li>
        <li><strong>Web Server:</strong> Apache 2.4 HTTP Server</li>
        <li><strong>Backend Engine:</strong> Native PHP 8.5 with strict PDO prepared statements</li>
        <li><strong>Database:</strong> MariaDB 12</li>
        <li><strong>Typography:</strong> JetBrains Mono and Inter</li>
        <li><strong>Styles:</strong> Tailwind CSS compiled locally without external frontend frameworks</li>
      </ul>

    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
