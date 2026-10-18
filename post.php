<?php
/**
 * WordWise Single Article Reading Experience
 * Distraction-free typography, dynamic table of contents, reading progress bar,
 * view tracking, related articles, share actions, and print styling.
 * Supports canonical Editor.js block rendering, syntax highlighting, and preview mode.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/blocks.php';

$slug = trim($_GET['slug'] ?? '');
$id = (int)($_GET['id'] ?? 0);
$isPreview = (!empty($_GET['preview']) && $_GET['preview'] === 'true') && auth_check() && auth_can('edit_post');

$statusCondition = $isPreview ? "1=1" : "p.post_status = 'published'";

$post = null;
if ($slug !== '') {
    $post = db_fetch_one(
        "SELECT p.*, c.cat_title, c.cat_slug 
         FROM posts p 
         LEFT JOIN categories c ON p.post_category_id = c.cat_id 
         WHERE p.post_slug = ? AND {$statusCondition} LIMIT 1",
        [$slug]
    );
} elseif ($id > 0) {
    $post = db_fetch_one(
        "SELECT p.*, c.cat_title, c.cat_slug 
         FROM posts p 
         LEFT JOIN categories c ON p.post_category_id = c.cat_id 
         WHERE p.post_id = ? AND {$statusCondition} LIMIT 1",
        [$id]
    );
}

// 404 Not Found Handling
if (!$post) {
    http_response_code(404);
    $customTitle = "404 Article Not Found";
    include __DIR__ . '/404.php';
    exit;
}

// Record Unique View only on live public views
if (!$isPreview && $post['post_status'] === 'published') {
    record_post_view((int)$post['post_id']);
}

// Fetch Associated Tags
$tags = db_fetch_all(
    "SELECT t.* FROM tags t 
     JOIN post_tags pt ON t.tag_id = pt.tag_id 
     WHERE pt.post_id = ? ORDER BY t.tag_name ASC",
    [$post['post_id']]
);

// Fetch Previous & Next Articles
$prevPost = db_fetch_one(
    "SELECT post_id, post_title, post_slug FROM posts 
     WHERE post_status = 'published' AND post_date < ? 
     ORDER BY post_date DESC LIMIT 1",
    [$post['post_date']]
);

$nextPost = db_fetch_one(
    "SELECT post_id, post_title, post_slug FROM posts 
     WHERE post_status = 'published' AND post_date > ? 
     ORDER BY post_date ASC LIMIT 1",
    [$post['post_date']]
);

// Fetch Related Articles from same category
$relatedPosts = db_fetch_all(
    "SELECT post_id, post_title, post_slug, post_date, post_image 
     FROM posts 
     WHERE post_status = 'published' AND post_category_id = ? AND post_id != ? 
     ORDER BY post_date DESC LIMIT 3",
    [$post['post_category_id'], $post['post_id']]
);

// Render Canonical Block JSON or Fallback HTML
if (!empty($post['post_content_json'])) {
    $renderedHtml = render_blocks($post['post_content_json'], $post['post_content'] ?? '');
} else {
    $renderedHtml = sanitize_html($post['post_content'] ?? '');
}

// Process Content: Extract Table of Contents from <h2> and <h3> tags
$toc = [];
$processedContent = preg_replace_callback('/<h([23])(?:\s+id="([^"]+)")?>(.*?)<\/h\1>/i', function($matches) use (&$toc) {
    $level = (int)$matches[1];
    $title = strip_tags($matches[3]);
    $anchor = !empty($matches[2]) ? $matches[2] : slugify($title);
    $toc[] = ['level' => $level, 'title' => $title, 'anchor' => $anchor];
    return "<h{$level} id=\"{$anchor}\">{$matches[3]}</h{$level}>";
}, $renderedHtml);

$customTitle = !empty($post['post_seo_title']) ? $post['post_seo_title'] : $post['post_title'];
$metaDescSource = !empty($post['post_seo_desc']) ? $post['post_seo_desc'] : (!empty($post['post_excerpt']) ? $post['post_excerpt'] : strip_tags($renderedHtml));
$customDesc = mb_strimwidth($metaDescSource, 0, 160, '...');
$readingTime = reading_time($renderedHtml);
$canonicalUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/post.php?slug=" . urlencode($post['post_slug']);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<!-- Syntax Highlighting Stylesheet -->
<link rel="stylesheet" href="/css/vendor/github-dark.min.css">

<?php if ($isPreview): ?>
  <!-- Editorial Preview Mode Banner -->
  <div class="bg-amber-500/10 border-b border-amber-500/30 text-amber-800 dark:text-amber-200 py-3 px-4 font-mono text-xs text-center flex flex-wrap items-center justify-center gap-2 sticky top-0 z-40 backdrop-blur">
    <span class="inline-block px-2 py-0.5 bg-amber-500 text-black font-bold uppercase rounded text-[10px] tracking-wider">Preview Mode</span>
    <span>Article status is <strong><?php echo strtoupper(e($post['post_status'])); ?></strong> &mdash; visible only to authenticated editors.</span>
    <a href="/admin/post-edit.php?id=<?php echo (int)$post['post_id']; ?>" class="underline font-bold hover:text-ink-950 dark:hover:text-ink-50">&larr; Return to Editor</a>
  </div>
<?php endif; ?>

<!-- Reading Progress Bar -->
<div id="reading-progress" class="reading-progress-bar"></div>

<main class="flex-grow py-12">
  <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Article Header -->
    <header class="border-b border-paper-200 dark:border-paper-800 pb-10 mb-10 text-center sm:text-left">
      
      <!-- Meta Badges: Category, Date, Read Time, Views -->
      <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs font-mono text-ink-500 dark:text-ink-400 mb-6">
        <?php if (!empty($post['cat_title'])): ?>
          <a href="/articles.php?category=<?php echo urlencode($post['cat_slug']); ?>" class="typewriter-badge text-accent border-accent hover:bg-accent hover:text-white transition-colors">
            <?php echo e($post['cat_title']); ?>
          </a>
        <?php endif; ?>
        <span>&bull;</span>
        <time datetime="<?php echo e($post['post_date']); ?>"><?php echo date('F j, Y', strtotime($post['post_date'])); ?></time>
        <span>&bull;</span>
        <span><?php echo $readingTime; ?> min read</span>
        <?php if ($post['post_views'] > 0): ?>
          <span>&bull;</span>
          <span><?php echo $post['post_views']; ?> views</span>
        <?php endif; ?>
      </div>

      <!-- Main Title -->
      <h1 class="text-3xl sm:text-5xl font-mono font-bold text-ink-950 dark:text-ink-50 leading-[1.2] mb-4">
        <?php echo e($post['post_title']); ?>
      </h1>

      <!-- Subtitle if present -->
      <?php if (!empty($post['post_subtitle'])): ?>
        <p class="text-lg sm:text-xl font-sans text-ink-500 dark:text-ink-400 italic mb-6">
          <?php echo e($post['post_subtitle']); ?>
        </p>
      <?php endif; ?>

      <!-- Author Information -->
      <div class="flex items-center justify-center sm:justify-start space-x-3 pt-2">
        <div class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center font-mono text-xs font-bold">
          <?php echo strtoupper(substr($post['post_author'], 0, 1)); ?>
        </div>
        <div class="text-xs font-mono text-left">
          <p class="text-ink-900 dark:text-ink-100 font-semibold"><?php echo e($post['post_author']); ?></p>
          <p class="text-ink-400">WordWise Contributor</p>
        </div>
      </div>

    </header>

    <!-- Cover Image -->
    <?php if (!empty($post['post_image'])): ?>
      <figure class="mb-12 overflow-hidden rounded border border-paper-200 dark:border-paper-800">
        <img 
          src="/images/<?php echo e($post['post_image']); ?>" 
          alt="<?php echo e($post['post_title']); ?>" 
          class="w-full max-h-[480px] object-cover"
        >
      </figure>
    <?php endif; ?>

    <!-- Two-Column Layout for Content and Dynamic Table of Contents -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
      
      <!-- Table of Contents Sidebar (Desktop) -->
      <?php if (!empty($toc)): ?>
        <aside class="hidden lg:block lg:col-span-4 order-last no-print">
          <div class="sticky top-24 editorial-card p-5 rounded-sm space-y-3">
            <h3 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
              Outline
            </h3>
            <nav class="space-y-1.5 text-xs font-mono">
              <?php foreach ($toc as $item): ?>
                <a 
                  href="#<?php echo e($item['anchor']); ?>" 
                  class="block text-ink-600 dark:text-ink-400 hover:text-accent transition-colors <?php echo $item['level'] === 3 ? 'pl-3 text-[11px]' : ''; ?>"
                >
                  <?php echo e($item['title']); ?>
                </a>
              <?php endforeach; ?>
            </nav>
          </div>
        </aside>
      <?php endif; ?>

      <!-- Article Body -->
      <div class="<?php echo !empty($toc) ? 'lg:col-span-8' : 'lg:col-span-12'; ?>">
        <div class="prose prose-neutral dark:prose-invert max-w-reading font-mono text-base leading-relaxed text-ink-800 dark:text-ink-200 space-y-6">
          <?php echo $processedContent; ?>
        </div>

        <!-- Tags List -->
        <?php if (!empty($tags)): ?>
          <div class="border-t border-paper-200 dark:border-paper-800 mt-12 pt-6 flex flex-wrap items-center gap-2 font-mono text-xs no-print">
            <span class="text-ink-400">Filed under:</span>
            <?php foreach ($tags as $t): ?>
              <a href="/articles.php?tag=<?php echo urlencode($t['tag_slug']); ?>" class="typewriter-badge hover:border-accent hover:text-accent">
                #<?php echo e($t['tag_name']); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Share Actions Toolbar -->
        <div class="border-t border-b border-paper-200 dark:border-paper-800 my-8 py-4 flex flex-col sm:flex-row items-center justify-between gap-4 font-mono text-xs no-print">
          <span class="text-ink-500 font-semibold uppercase tracking-wider">Share this essay</span>
          <div class="flex items-center space-x-3">
            <!-- Twitter/X -->
            <a 
              href="https://twitter.com/intent/tweet?url=<?php echo urlencode($canonicalUrl); ?>&text=<?php echo urlencode($post['post_title']); ?>" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="typewriter-badge hover:border-accent hover:text-accent"
            >
              X / Twitter
            </a>
            <!-- LinkedIn -->
            <a 
              href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($canonicalUrl); ?>" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="typewriter-badge hover:border-accent hover:text-accent"
            >
              LinkedIn
            </a>
            <!-- Copy Link Button -->
            <button 
              type="button" 
              onclick="navigator.clipboard.writeText(window.location.href); this.textContent = 'COPIED!'; setTimeout(() => this.textContent = 'COPY LINK', 2000);" 
              class="typewriter-badge hover:border-accent hover:text-accent"
            >
              COPY LINK
            </button>
          </div>
        </div>

        <!-- Previous / Next Navigation -->
        <nav class="grid grid-cols-1 sm:grid-cols-2 gap-4 font-mono text-xs my-10 no-print">
          <div>
            <?php if ($prevPost): ?>
              <a href="/post.php?slug=<?php echo urlencode($prevPost['post_slug']); ?>" class="block editorial-card p-4 rounded-sm hover:border-accent group">
                <span class="text-ink-400 block mb-1">&larr; Previous Dispatch</span>
                <span class="font-mono font-bold text-sm text-ink-950 dark:text-ink-50 group-hover:text-accent">
                  <?php echo e($prevPost['post_title']); ?>
                </span>
              </a>
            <?php endif; ?>
          </div>
          <div>
            <?php if ($nextPost): ?>
              <a href="/post.php?slug=<?php echo urlencode($nextPost['post_slug']); ?>" class="block editorial-card p-4 rounded-sm hover:border-accent group sm:text-right">
                <span class="text-ink-400 block mb-1">Next Dispatch &rarr;</span>
                <span class="font-mono font-bold text-sm text-ink-950 dark:text-ink-50 group-hover:text-accent">
                  <?php echo e($nextPost['post_title']); ?>
                </span>
              </a>
            <?php endif; ?>
          </div>
        </nav>

      </div>

    </div>

    <!-- Related Articles Section -->
    <?php if (!empty($relatedPosts)): ?>
      <section class="border-t border-paper-200 dark:border-paper-800 mt-14 pt-10 no-print">
        <h3 class="font-mono text-xs uppercase tracking-wider text-accent font-semibold mb-6">
          Further Reading in <?php echo e($post['cat_title'] ?? 'this category'); ?>
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <?php foreach ($relatedPosts as $rel): ?>
            <div class="editorial-card p-4 rounded-sm space-y-2 group">
              <time class="font-mono text-[11px] text-ink-400"><?php echo date('M j, Y', strtotime($rel['post_date'])); ?></time>
              <h4 class="font-mono font-bold text-base text-ink-900 dark:text-ink-100 group-hover:text-accent leading-snug">
                <a href="/post.php?slug=<?php echo urlencode($rel['post_slug']); ?>">
                  <?php echo e($rel['post_title']); ?>
                </a>
              </h4>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

  </article>
</main>

<!-- Code Syntax Highlighting Engine -->
<script src="/js/vendor/highlightjs/highlight.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (window.hljs) {
      document.querySelectorAll('pre code').forEach((block) => {
        hljs.highlightElement(block);
      });
    }
  });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
