<?php
/**
 * WordWise Article Catalogue & Search
 * Server-side parameterized search, category/tag filtering, sorting, and pagination.
 */
require_once __DIR__ . '/includes/db.php';

// Request Parameters
$search = trim($_GET['search'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$tagSlug = trim($_GET['tag'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(2, (int)get_setting('posts_per_page', '6'));

// Base Query Conditions
$conditions = ["p.post_status = 'published'"];
$params = [];

// Search filter
if ($search !== '') {
    $conditions[] = "(p.post_title LIKE ? OR p.post_content LIKE ? OR p.post_tags LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Category filter
$activeCategory = null;
if ($categorySlug !== '') {
    $activeCategory = db_fetch_one("SELECT * FROM categories WHERE cat_slug = ?", [$categorySlug]);
    if ($activeCategory) {
        $conditions[] = "p.post_category_id = ?";
        $params[] = $activeCategory['cat_id'];
    }
}

// Tag filter
$activeTag = null;
if ($tagSlug !== '') {
    $activeTag = db_fetch_one("SELECT * FROM tags WHERE tag_slug = ?", [$tagSlug]);
    if ($activeTag) {
        $conditions[] = "p.post_id IN (SELECT post_id FROM post_tags WHERE tag_id = ?)";
        $params[] = $activeTag['tag_id'];
    }
}

$whereClause = "WHERE " . implode(" AND ", $conditions);

// Sorting
$orderBy = "ORDER BY p.post_date DESC, p.post_id DESC";
if ($sort === 'oldest') {
    $orderBy = "ORDER BY p.post_date ASC, p.post_id ASC";
} elseif ($sort === 'popular') {
    $orderBy = "ORDER BY p.post_views DESC, p.post_date DESC";
} elseif ($sort === 'title') {
    $orderBy = "ORDER BY p.post_title ASC";
}

// Count Total Matches
$countSql = "SELECT COUNT(*) as total FROM posts p {$whereClause}";
$totalRows = (int)(db_fetch_one($countSql, $params)['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch Paginated Articles
$articlesSql = "
    SELECT p.*, c.cat_title, c.cat_slug 
    FROM posts p 
    LEFT JOIN categories c ON p.post_category_id = c.cat_id 
    {$whereClause} 
    {$orderBy} 
    LIMIT {$perPage} OFFSET {$offset}
";
$articles = db_fetch_all($articlesSql, $params);

// Fetch All Categories and Tags for Sidebar / Filters
$allCategories = db_fetch_all(
    "SELECT c.*, COUNT(p.post_id) as post_count 
     FROM categories c 
     LEFT JOIN posts p ON c.cat_id = p.post_category_id AND p.post_status = 'published' 
     GROUP BY c.cat_id 
     ORDER BY c.cat_title ASC"
);

$allTags = db_fetch_all(
    "SELECT t.*, COUNT(pt.post_id) as post_count 
     FROM tags t 
     LEFT JOIN post_tags pt ON t.tag_id = pt.tag_id 
     GROUP BY t.tag_id 
     HAVING post_count > 0 
     ORDER BY post_count DESC"
);

$customTitle = "Articles Archive";
if ($activeCategory) $customTitle = $activeCategory['cat_title'] . " &mdash; Articles";
elseif ($activeTag) $customTitle = "#" . $activeTag['tag_name'] . " &mdash; Articles";
elseif ($search !== '') $customTitle = "Search: " . $search;

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-12">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Breadcrumb & Archive Header -->
    <div class="border-b border-paper-200 dark:border-paper-800 pb-8 mb-10">
      <div class="flex items-center space-x-2 font-mono text-xs text-accent uppercase tracking-wider mb-2">
        <a href="/" class="hover:underline">Home</a>
        <span>/</span>
        <span>Archive</span>
        <?php if ($activeCategory): ?>
          <span>/</span>
          <span class="text-ink-950 dark:text-ink-50 font-bold"><?php echo e($activeCategory['cat_title']); ?></span>
        <?php elseif ($activeTag): ?>
          <span>/</span>
          <span class="text-ink-950 dark:text-ink-50 font-bold">#<?php echo e($activeTag['tag_name']); ?></span>
        <?php endif; ?>
      </div>

      <h1 class="text-3xl sm:text-4xl font-mono font-bold text-ink-950 dark:text-ink-50">
        <?php 
          if ($search !== '') echo 'Results for &ldquo;' . e($search) . '&rdquo;';
          elseif ($activeCategory) echo e($activeCategory['cat_title']);
          elseif ($activeTag) echo '#' . e($activeTag['tag_name']);
          else echo 'All Articles &amp; Essays';
        ?>
      </h1>

      <p class="font-mono text-xs text-ink-500 mt-2">
        Showing <?php echo count($articles); ?> of <?php echo $totalRows; ?> total published <?php echo $totalRows === 1 ? 'dispatch' : 'dispatches'; ?>.
      </p>
    </div>

    <!-- Search & Filter Controls Toolbar -->
    <div class="editorial-card p-4 sm:p-5 rounded-sm mb-10">
      <form method="GET" action="/articles.php" class="grid grid-cols-1 sm:grid-cols-12 gap-4">
        
        <!-- Search Input -->
        <div class="sm:col-span-5 relative">
          <input 
            type="text" 
            name="search" 
            id="search-input"
            value="<?php echo e($search); ?>" 
            placeholder="Search keywords, titles, tags..." 
            class="w-full pl-9 pr-4 py-2 text-sm bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100"
          >
          <svg class="w-4 h-4 text-ink-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>

        <!-- Category Dropdown -->
        <div class="sm:col-span-3">
          <select name="category" class="w-full py-2 px-3 text-sm bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100">
            <option value="">All Categories</option>
            <?php foreach ($allCategories as $c): ?>
              <option value="<?php echo e($c['cat_slug']); ?>" <?php echo $categorySlug === $c['cat_slug'] ? 'selected' : ''; ?>>
                <?php echo e($c['cat_title']); ?> (<?php echo $c['post_count']; ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Sorting Dropdown -->
        <div class="sm:col-span-2">
          <select name="sort" class="w-full py-2 px-3 text-sm bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-mono text-xs focus:border-accent text-ink-900 dark:text-ink-100">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Read</option>
            <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>By Title</option>
          </select>
        </div>

        <!-- Filter Actions -->
        <div class="sm:col-span-2 flex items-center space-x-2">
          <button type="submit" class="w-full py-2 px-4 bg-accent hover:bg-accent-hover text-white text-xs font-mono tracking-wider uppercase rounded transition-colors">
            Filter
          </button>
          <?php if ($search !== '' || $categorySlug !== '' || $tagSlug !== '' || $sort !== 'newest'): ?>
            <a href="/articles.php" class="p-2 text-ink-400 hover:text-accent font-mono text-xs" title="Reset all filters">
              &times;
            </a>
          <?php endif; ?>
        </div>

      </form>
    </div>

    <!-- Active Filter Tags Display -->
    <?php if ($search !== '' || $activeCategory || $activeTag): ?>
      <div class="flex items-center space-x-2 mb-8 font-mono text-xs">
        <span class="text-ink-400">Filtering by:</span>
        <?php if ($search !== ''): ?>
          <span class="typewriter-badge bg-accent/10 border-accent text-accent">Query: &ldquo;<?php echo e($search); ?>&rdquo;</span>
        <?php endif; ?>
        <?php if ($activeCategory): ?>
          <span class="typewriter-badge bg-accent/10 border-accent text-accent">Category: <?php echo e($activeCategory['cat_title']); ?></span>
        <?php endif; ?>
        <?php if ($activeTag): ?>
          <span class="typewriter-badge bg-accent/10 border-accent text-accent">Tag: #<?php echo e($activeTag['tag_name']); ?></span>
        <?php endif; ?>
        <a href="/articles.php" class="text-accent underline hover:text-accent-hover ml-2">Clear all</a>
      </div>
    <?php endif; ?>

    <!-- Article Listing Grid / Rows -->
    <?php if (empty($articles)): ?>
      <div class="editorial-card p-12 text-center rounded-sm space-y-4">
        <svg class="w-12 h-12 mx-auto text-ink-300 dark:text-ink-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <h3 class="font-mono text-xl font-bold text-ink-950 dark:text-ink-50">No articles matched your criteria</h3>
        <p class="text-sm text-ink-500 max-w-md mx-auto">
          Try expanding your search query or selecting a different category from the archive.
        </p>
        <div class="pt-2">
          <a href="/articles.php" class="inline-block typewriter-badge border-accent text-accent hover:bg-accent hover:text-white transition-colors">
            Reset Filters
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="space-y-8 divide-y divide-paper-200 dark:divide-paper-800">
        <?php foreach ($articles as $idx => $post): ?>
          <article class="<?php echo $idx > 0 ? 'pt-8' : ''; ?> grid grid-cols-1 sm:grid-cols-12 gap-6 items-start group">
            
            <?php if (!empty($post['post_image'])): ?>
              <div class="sm:col-span-4 overflow-hidden rounded border border-paper-200 dark:border-paper-800">
                <a href="/post.php?slug=<?php echo urlencode($post['post_slug']); ?>">
                  <img src="/images/<?php echo e($post['post_image']); ?>" alt="<?php echo e($post['post_title']); ?>" class="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                </a>
              </div>
            <?php endif; ?>

            <div class="<?php echo !empty($post['post_image']) ? 'sm:col-span-8' : 'sm:col-span-12'; ?> space-y-2.5">
              <div class="flex items-center space-x-3 text-xs font-mono text-ink-500 dark:text-ink-400">
                <a href="/articles.php?category=<?php echo urlencode($post['cat_slug']); ?>" class="text-accent hover:underline">
                  <?php echo e($post['cat_title'] ?? 'General'); ?>
                </a>
                <span>&bull;</span>
                <time datetime="<?php echo e($post['post_date']); ?>"><?php echo date('M j, Y', strtotime($post['post_date'])); ?></time>
                <span>&bull;</span>
                <span><?php echo reading_time($post['post_content']); ?> min read</span>
                <?php if ($post['post_views'] > 0): ?>
                  <span>&bull;</span>
                  <span><?php echo $post['post_views']; ?> views</span>
                <?php endif; ?>
              </div>

              <h2 class="text-xl sm:text-2xl font-mono font-bold text-ink-950 dark:text-ink-50 group-hover:text-accent transition-colors leading-snug">
                <a href="/post.php?slug=<?php echo urlencode($post['post_slug']); ?>">
                  <?php echo e($post['post_title']); ?>
                </a>
              </h2>

              <p class="text-sm text-ink-600 dark:text-ink-300 font-sans leading-relaxed line-clamp-2">
                <?php echo e(mb_strimwidth(strip_tags($post['post_content']), 0, 200, '...')); ?>
              </p>

              <div class="pt-2 flex items-center justify-between text-xs font-mono">
                <a href="/post.php?slug=<?php echo urlencode($post['post_slug']); ?>" class="text-accent hover:underline inline-flex items-center space-x-1">
                  <span>Read Article</span>
                  <span>&rarr;</span>
                </a>
                <span class="text-ink-400">By <?php echo e($post['post_author']); ?></span>
              </div>
            </div>

          </article>
        <?php endforeach; ?>
      </div>

      <!-- Pagination Navigation -->
      <?php if ($totalPages > 1): ?>
        <nav class="border-t border-paper-200 dark:border-paper-800 mt-14 pt-8 flex items-center justify-between font-mono text-xs">
          <div>
            <?php if ($page > 1): ?>
              <?php 
                $prevParams = $_GET; 
                $prevParams['page'] = $page - 1; 
              ?>
              <a href="/articles.php?<?php echo http_build_query($prevParams); ?>" class="typewriter-badge hover:border-accent hover:text-accent">
                &larr; Previous Page
              </a>
            <?php endif; ?>
          </div>

          <span class="text-ink-400">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
          </span>

          <div>
            <?php if ($page < $totalPages): ?>
              <?php 
                $nextParams = $_GET; 
                $nextParams['page'] = $page + 1; 
              ?>
              <a href="/articles.php?<?php echo http_build_query($nextParams); ?>" class="typewriter-badge hover:border-accent hover:text-accent">
                Next Page &rarr;
              </a>
            <?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

  </div>
</main>

<script>
  // Focus search input if directed from navigation
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('focus') === 'search') {
    const input = document.getElementById('search-input');
    if (input) input.focus();
  }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
