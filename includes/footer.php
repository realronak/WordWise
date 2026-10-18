<?php
/**
 * WordWise Editorial Footer Template
 */
$footerCategories = db_fetch_all("SELECT * FROM categories ORDER BY cat_title ASC LIMIT 5");
$twitter = get_setting('social_twitter', '#');
$github = get_setting('social_github', 'https://github.com/realronak/WordWise');
$linkedin = get_setting('social_linkedin', '#');
?>
<footer class="border-t border-paper-200 dark:border-paper-800 bg-paper-100/60 dark:bg-paper-900/40 mt-auto transition-colors duration-200 no-print">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      
      <!-- Colophon / Mission -->
      <div class="space-y-3">
        <div class="flex items-center space-x-2">
          <span class="w-2.5 h-2.5 rounded-full bg-accent inline-block"></span>
          <span class="font-mono text-sm font-semibold tracking-wider text-ink-950 dark:text-ink-50 uppercase">WordWise</span>
        </div>
        <p class="text-xs text-ink-500 dark:text-ink-400 font-sans leading-relaxed">
          An independent editorial journal examining software architecture, technology craft, and modern digital ideas.
        </p>
      </div>

      <!-- Quick Directory -->
      <div>
        <h4 class="font-mono text-xs uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-3">Categories</h4>
        <ul class="space-y-2 text-sm">
          <?php foreach ($footerCategories as $cat): ?>
            <li>
              <a href="/articles.php?category=<?php echo urlencode($cat['cat_slug']); ?>" class="text-ink-700 dark:text-ink-300 hover:text-accent dark:hover:text-accent-dark transition-colors">
                <?php echo e($cat['cat_title']); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Information & Socials -->
      <div class="space-y-3">
        <h4 class="font-mono text-xs uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-3">Colophon & Connect</h4>
        <div class="flex space-x-4 text-sm font-mono">
          <a href="<?php echo e($github); ?>" target="_blank" rel="noopener noreferrer" class="text-ink-700 dark:text-ink-300 hover:text-accent">GitHub</a>
          <a href="<?php echo e($twitter); ?>" target="_blank" rel="noopener noreferrer" class="text-ink-700 dark:text-ink-300 hover:text-accent">Twitter/X</a>
          <a href="<?php echo e($linkedin); ?>" target="_blank" rel="noopener noreferrer" class="text-ink-700 dark:text-ink-300 hover:text-accent">LinkedIn</a>
          <a href="/about.php" class="text-ink-700 dark:text-ink-300 hover:text-accent">About</a>
        </div>
        <p class="font-mono text-xs text-ink-400 dark:text-ink-500">
          Set in JetBrains Mono and Inter.
        </p>
      </div>

    </div>

    <!-- Bottom Bar -->
    <div class="border-t border-paper-200 dark:border-paper-800 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs font-mono text-ink-500 dark:text-ink-400">
      <p>&copy; <?php echo date('Y'); ?> WordWise. All rights reserved.</p>
      <div class="flex items-center space-x-4 mt-2 sm:mt-0">
        <a href="/rss.php" class="hover:text-accent">RSS Feed</a>
        <span>&bull;</span>
        <a href="/sitemap.xml" class="hover:text-accent">Sitemap</a>
        <span>&bull;</span>
        <button type="button" onclick="window.WordWiseTheme.cycle()" class="hover:text-accent">
          Theme: <span data-theme-indicator>SYSTEM</span>
        </button>
      </div>
    </div>
  </div>
</footer>

<!-- Core Script -->
<script src="/js/theme.js"></script>
</body>
</html>