<?php
/**
 * WordWise Categories Directory
 */
require_once __DIR__ . '/includes/db.php';

$categories = db_fetch_all(
    "SELECT c.*, COUNT(p.post_id) as post_count, MAX(p.post_date) as latest_post_date 
     FROM categories c 
     LEFT JOIN posts p ON c.cat_id = p.post_category_id AND p.post_status = 'published' 
     GROUP BY c.cat_id 
     ORDER BY post_count DESC, c.cat_title ASC"
);

$customTitle = "Topic Directory";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-14">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <div class="border-b border-paper-200 dark:border-paper-800 pb-8 mb-10">
      <div class="flex items-center space-x-2 font-mono text-xs text-accent uppercase tracking-wider mb-2">
        <a href="/" class="hover:underline">Home</a>
        <span>/</span>
        <span>Directory</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-mono font-bold text-ink-950 dark:text-ink-50">
        Categories &amp; Subject Index
      </h1>
      <p class="font-sans text-sm text-ink-500 mt-2">
        Browse essays and research categorized by subject matter and discipline.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <?php foreach ($categories as $cat): ?>
        <div class="editorial-card p-6 rounded-sm space-y-3 group">
          <div class="flex items-center justify-between">
            <h2 class="font-mono text-2xl font-bold text-ink-950 dark:text-ink-50 group-hover:text-accent transition-colors">
              <a href="/articles.php?category=<?php echo urlencode($cat['cat_slug']); ?>">
                <?php echo e($cat['cat_title']); ?>
              </a>
            </h2>
            <span class="font-mono text-xs typewriter-badge">
              <?php echo (int)($cat['post_count'] ?? 0); ?> <?php echo ((int)($cat['post_count'] ?? 0)) === 1 ? 'dispatch' : 'dispatches'; ?>
            </span>
          </div>

          <p class="text-sm font-sans text-ink-600 dark:text-ink-300 leading-relaxed">
            <?php echo e($cat['cat_description'] ?? 'Curated articles and commentary related to ' . $cat['cat_title'] . '.'); ?>
          </p>

          <div class="pt-2 flex items-center justify-between text-xs font-mono text-ink-400">
            <?php if (!empty($cat['latest_post_date'])): ?>
              <span>Latest: <?php echo date('M Y', strtotime($cat['latest_post_date'])); ?></span>
            <?php else: ?>
              <span>No articles yet</span>
            <?php endif; ?>
            <a href="/articles.php?category=<?php echo urlencode($cat['cat_slug']); ?>" class="text-accent hover:underline">
              Browse Topic &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
