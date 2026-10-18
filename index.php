<?php
/**
 * WordWise Public Homepage
 * Minimal Editorial / Typewriter Layout with Featured Article, Latest Essays, and Category Sections.
 */
require_once __DIR__ . '/includes/db.php';

// Fetch Featured Post
$featuredPost = db_fetch_one(
    "SELECT p.*, c.cat_title, c.cat_slug 
     FROM posts p 
     LEFT JOIN categories c ON p.post_category_id = c.cat_id 
     WHERE p.post_status = 'published' AND p.is_featured = 1 
     ORDER BY p.post_date DESC LIMIT 1"
);

// Fallback to latest post if no explicitly featured post
if (!$featuredPost) {
    $featuredPost = db_fetch_one(
        "SELECT p.*, c.cat_title, c.cat_slug 
         FROM posts p 
         LEFT JOIN categories c ON p.post_category_id = c.cat_id 
         WHERE p.post_status = 'published' 
         ORDER BY p.post_date DESC LIMIT 1"
    );
}

// Fetch Latest Published Posts (excluding featured post from the top spot if desired)
$excludeId = $featuredPost['post_id'] ?? 0;
$latestPosts = db_fetch_all(
    "SELECT p.*, c.cat_title, c.cat_slug 
     FROM posts p 
     LEFT JOIN categories c ON p.post_category_id = c.cat_id 
     WHERE p.post_status = 'published' AND p.post_id != ? 
     ORDER BY p.post_date DESC LIMIT 6",
    [$excludeId]
);

// Fetch Popular Posts based on genuine view data
$popularPosts = db_fetch_all(
    "SELECT p.*, c.cat_title, c.cat_slug 
     FROM posts p 
     LEFT JOIN categories c ON p.post_category_id = c.cat_id 
     WHERE p.post_status = 'published' AND p.post_views > 0 
     ORDER BY p.post_views DESC, p.post_date DESC LIMIT 4"
);

// Fetch Categories for Quick Navigation
$allCategories = db_fetch_all(
    "SELECT c.*, COUNT(p.post_id) as post_count 
     FROM categories c 
     LEFT JOIN posts p ON c.cat_id = p.post_category_id AND p.post_status = 'published' 
     GROUP BY c.cat_id 
     ORDER BY post_count DESC, c.cat_title ASC"
);

$siteTagline = get_setting('site_tagline', 'A minimal editorial journal for tech, ideas, and craft.');
$authorName = get_setting('author_name', 'Ronak Rathod');
$authorBio = get_setting('author_bio', 'Engineer & writer exploring operating systems, agentic AI, and software design.');

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow">
  
  <!-- Hero Section with Typewriter Effect -->
  <section class="border-b border-paper-200 dark:border-paper-800 bg-white/50 dark:bg-paper-900/20 py-16 sm:py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="max-w-3xl">
        <div class="flex items-center space-x-2 text-accent font-mono text-xs uppercase tracking-widest mb-4">
          <span class="inline-block w-2 h-2 rounded-full bg-accent animate-pulse"></span>
          <span>Vol. I &mdash; <?php echo date('Y'); ?></span>
        </div>
        
        <!-- Headline with Typewriter text reveal -->
        <h1 class="text-3xl sm:text-5xl font-mono font-bold tracking-tight text-ink-950 dark:text-ink-50 leading-[1.2] mb-5">
          <span id="hero-typewriter">Essays on software craft, system architecture, and modern technology.</span>
        </h1>
        
        <p class="text-base sm:text-lg text-ink-500 dark:text-ink-400 font-sans leading-relaxed mb-8">
          <?php echo e($siteTagline); ?>
        </p>

        <!-- Category Filter Pills -->
        <div class="flex flex-wrap items-center gap-2 pt-2">
          <span class="font-mono text-xs text-ink-400 uppercase tracking-wider mr-1">Topics:</span>
          <?php foreach ($allCategories as $cat): ?>
            <a href="/articles.php?category=<?php echo urlencode($cat['cat_slug']); ?>" class="typewriter-badge hover:border-accent hover:text-accent transition-colors">
              <?php echo e($cat['cat_title']); ?> <span class="text-ink-400 font-sans ml-1"><?php echo $cat['post_count']; ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div>
  </section>

  <!-- Featured Article Section -->
  <?php if ($featuredPost): ?>
    <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 border-b border-paper-200 dark:border-paper-800">
      <div class="flex items-center justify-between mb-6">
        <h2 class="font-mono text-xs uppercase tracking-wider text-accent font-medium">Featured Story</h2>
        <span class="font-mono text-xs text-ink-400">Editor's Selection</span>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center editorial-card p-6 sm:p-8 rounded-sm">
        
        <?php if (!empty($featuredPost['post_image'])): ?>
          <div class="lg:col-span-6 overflow-hidden rounded border border-paper-200 dark:border-paper-800">
            <a href="/post.php?slug=<?php echo urlencode($featuredPost['post_slug']); ?>" class="block group">
              <img src="/images/<?php echo e($featuredPost['post_image']); ?>" alt="<?php echo e($featuredPost['post_title']); ?>" class="w-full h-64 sm:h-80 object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
            </a>
          </div>
        <?php endif; ?>

        <div class="<?php echo !empty($featuredPost['post_image']) ? 'lg:col-span-6' : 'lg:col-span-12'; ?> space-y-4">
          <div class="flex items-center space-x-3 text-xs font-mono text-ink-500 dark:text-ink-400">
            <span class="text-accent font-semibold"><?php echo e($featuredPost['cat_title'] ?? 'General'); ?></span>
            <span>&bull;</span>
            <time datetime="<?php echo e($featuredPost['post_date']); ?>"><?php echo date('M j, Y', strtotime($featuredPost['post_date'])); ?></time>
            <span>&bull;</span>
            <span><?php echo reading_time($featuredPost['post_content']); ?> min read</span>
          </div>

          <h3 class="text-2xl sm:text-3xl font-mono font-bold text-ink-950 dark:text-ink-50 hover:text-accent transition-colors leading-snug">
            <a href="/post.php?slug=<?php echo urlencode($featuredPost['post_slug']); ?>">
              <?php echo e($featuredPost['post_title']); ?>
            </a>
          </h3>

          <p class="text-sm sm:text-base text-ink-600 dark:text-ink-300 font-sans leading-relaxed line-clamp-3">
            <?php echo e(mb_strimwidth(strip_tags($featuredPost['post_content']), 0, 220, '...')); ?>
          </p>

          <div class="pt-2">
            <a href="/post.php?slug=<?php echo urlencode($featuredPost['post_slug']); ?>" class="inline-flex items-center space-x-2 font-mono text-xs uppercase tracking-wider text-accent font-semibold hover:underline">
              <span>Read Essay</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </a>
          </div>
        </div>

      </div>
    </section>
  <?php endif; ?>

  <!-- Latest Essays & Sidebar Layout -->
  <section class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
      
      <!-- Primary Column: Latest Articles -->
      <div class="lg:col-span-8 space-y-10">
        <div class="flex items-center justify-between pb-4 border-b border-paper-200 dark:border-paper-800">
          <h2 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-semibold">Latest Dispatches</h2>
          <a href="/articles.php" class="font-mono text-xs text-accent hover:underline">View All &rarr;</a>
        </div>

        <?php if (empty($latestPosts)): ?>
          <div class="text-center py-12 border border-dashed border-paper-300 dark:border-paper-800 rounded p-8">
            <p class="font-mono text-xs text-ink-500 italic">No additional dispatches published yet.</p>
          </div>
        <?php else: ?>
          <div class="space-y-8 divide-y divide-paper-200 dark:divide-paper-800">
            <?php foreach ($latestPosts as $index => $post): ?>
              <article class="<?php echo $index > 0 ? 'pt-8' : ''; ?> group">
                <div class="flex items-center space-x-3 text-xs font-mono text-ink-500 dark:text-ink-400 mb-2">
                  <a href="/articles.php?category=<?php echo urlencode($post['cat_slug']); ?>" class="text-accent hover:underline">
                    <?php echo e($post['cat_title'] ?? 'General'); ?>
                  </a>
                  <span>&bull;</span>
                  <time datetime="<?php echo e($post['post_date']); ?>"><?php echo date('M j, Y', strtotime($post['post_date'])); ?></time>
                  <span>&bull;</span>
                  <span><?php echo reading_time($post['post_content']); ?> min read</span>
                </div>

                <h3 class="text-xl sm:text-2xl font-mono font-bold text-ink-950 dark:text-ink-50 group-hover:text-accent transition-colors leading-snug mb-3">
                  <a href="/post.php?slug=<?php echo urlencode($post['post_slug']); ?>">
                    <?php echo e($post['post_title']); ?>
                  </a>
                </h3>

                <p class="text-sm text-ink-600 dark:text-ink-300 font-sans leading-relaxed line-clamp-2 mb-4">
                  <?php echo e(mb_strimwidth(strip_tags($post['post_content']), 0, 180, '...')); ?>
                </p>

                <div class="flex items-center justify-between pt-1">
                  <a href="/post.php?slug=<?php echo urlencode($post['post_slug']); ?>" class="font-mono text-xs text-accent hover:underline inline-flex items-center space-x-1">
                    <span>Continue Reading</span>
                    <span>&rarr;</span>
                  </a>
                  <span class="font-mono text-xs text-ink-400">By <?php echo e($post['post_author']); ?></span>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>

      <!-- Secondary Sidebar Column -->
      <aside class="lg:col-span-4 space-y-10">
        
        <!-- Author Note Card -->
        <div class="editorial-card p-6 rounded-sm space-y-4">
          <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center font-mono font-bold text-accent">
              W
            </div>
            <div>
              <h3 class="text-sm font-mono font-bold text-ink-900 dark:text-ink-100"><?php echo e($authorName); ?></h3>
              <p class="text-xs font-mono text-ink-400">Editor & Maintainer</p>
            </div>
          </div>
          <p class="text-xs text-ink-600 dark:text-ink-300 font-sans leading-relaxed">
            <?php echo e($authorBio); ?>
          </p>
          <div class="pt-1">
            <a href="/about.php" class="font-mono text-xs text-accent hover:underline">Learn more about the publication &rarr;</a>
          </div>
        </div>

        <!-- Popular Essays (Real View Data) -->
        <?php if (!empty($popularPosts)): ?>
          <div class="space-y-4">
            <h3 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-semibold pb-2 border-b border-paper-200 dark:border-paper-800">
              Most Read
            </h3>
            <div class="space-y-4">
              <?php foreach ($popularPosts as $popIdx => $pop): ?>
                <div class="flex items-start space-x-3 group">
                  <span class="font-mono text-sm font-semibold text-accent/60">0<?php echo $popIdx + 1; ?></span>
                  <div class="space-y-1">
                    <h4 class="text-sm font-mono font-bold text-ink-900 dark:text-ink-100 group-hover:text-accent leading-snug">
                      <a href="/post.php?slug=<?php echo urlencode($pop['post_slug']); ?>">
                        <?php echo e($pop['post_title']); ?>
                      </a>
                    </h4>
                    <p class="font-mono text-[11px] text-ink-400">
                      <?php echo $pop['post_views']; ?> views &bull; <?php echo date('M Y', strtotime($pop['post_date'])); ?>
                    </p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Topic Index -->
        <div class="space-y-4">
          <h3 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-semibold pb-2 border-b border-paper-200 dark:border-paper-800">
            Directory
          </h3>
          <ul class="space-y-2 text-xs font-mono">
            <?php foreach ($allCategories as $c): ?>
              <li class="flex items-center justify-between py-1">
                <a href="/articles.php?category=<?php echo urlencode($c['cat_slug']); ?>" class="text-ink-700 dark:text-ink-300 hover:text-accent">
                  <?php echo e($c['cat_title']); ?>
                </a>
                <span class="text-ink-400">[<?php echo $c['post_count']; ?>]</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

      </aside>

    </div>
  </section>

</main>

<script>
  // Initialize typewriter reveal for hero headline
  document.addEventListener('DOMContentLoaded', () => {
    const text = "Essays on software craft, system architecture, and modern technology.";
    if (window.initTypewriter) {
      window.initTypewriter('hero-typewriter', text, 35);
    }
  });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>