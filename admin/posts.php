<?php
/**
 * WordWise Admin CMS - Articles Management
 * Filter by status, category, search, and perform actions (publish, draft, delete, feature).
 */
$pageTitle = "Manage Articles";
$pageHeader = "Editorial Archive";
require_once __DIR__ . '/includes/admin_header.php';

// Handle State-Changing POST Actions (Delete, Toggle Status, Feature)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $_SESSION['flash_error'] = "Security validation failed. Please try again.";
        header("Location: /admin/posts.php");
        exit;
    }

    $action = $_POST['action'] ?? '';
    $postId = (int)($_POST['post_id'] ?? 0);
    $targetPost = db_fetch_one("SELECT * FROM posts WHERE post_id = ?", [$postId]);

    if (!$targetPost) {
        $_SESSION['flash_error'] = "Target post not found.";
        header("Location: /admin/posts.php");
        exit;
    }

    // Check permissions
    if ($action === 'delete') {
        if (!auth_can('delete_post')) {
            $_SESSION['flash_error'] = "You do not have permission to delete this dispatch.";
        } else {
            db_execute("DELETE FROM posts WHERE post_id = ?", [$postId]);
            db_execute("DELETE FROM post_tags WHERE post_id = ?", [$postId]);
            
            // Log audit
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            db_execute(
                "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'delete_post', ?, ?)",
                [auth_id(), "Deleted post #{$postId} '{$targetPost['post_title']}'", $ip]
            );

            $_SESSION['flash_success'] = "Article &ldquo;" . e($targetPost['post_title']) . "&rdquo; deleted successfully.";
        }
    } elseif ($action === 'toggle_publish') {
        if (!auth_can('publish_posts')) {
            $_SESSION['flash_error'] = "You do not have permission to publish/unpublish dispatches.";
        } else {
            $newStatus = $targetPost['post_status'] === 'published' ? 'draft' : 'published';
            db_execute("UPDATE posts SET post_status = ? WHERE post_id = ?", [$newStatus, $postId]);
            $_SESSION['flash_success'] = "Article status changed to " . ucfirst($newStatus) . ".";
        }
    } elseif ($action === 'toggle_featured') {
        if (!auth_can('publish_posts')) {
            $_SESSION['flash_error'] = "You do not have permission to modify featured status.";
        } else {
            $newFeatured = $targetPost['is_featured'] ? 0 : 1;
            db_execute("UPDATE posts SET is_featured = ? WHERE post_id = ?", [$newFeatured, $postId]);
            $_SESSION['flash_success'] = $newFeatured ? "Article marked as featured." : "Article unmarked as featured.";
        }
    }

    header("Location: /admin/posts.php");
    exit;
}

// Request Filters
$statusFilter = trim($_GET['status'] ?? 'all');
$categoryFilter = (int)($_GET['category'] ?? 0);
$search = trim($_GET['search'] ?? '');

$conditions = ["1=1"];
$params = [];

if ($statusFilter !== 'all' && in_array($statusFilter, ['published', 'draft', 'scheduled', 'archived'], true)) {
    $conditions[] = "p.post_status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter > 0) {
    $conditions[] = "p.post_category_id = ?";
    $params[] = $categoryFilter;
}

if ($search !== '') {
    $conditions[] = "(p.post_title LIKE ? OR p.post_author LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}

$whereSql = "WHERE " . implode(" AND ", $conditions);

// Fetch Filtered Posts
$posts = db_fetch_all(
    "SELECT p.*, c.cat_title 
     FROM posts p 
     LEFT JOIN categories c ON p.post_category_id = c.cat_id 
     {$whereSql} 
     ORDER BY p.post_date DESC, p.post_id DESC",
    $params
);

// Counts for Tabs
$totalAll = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts")['c'];
$totalPublished = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_status = 'published'")['c'];
$totalDraft = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_status = 'draft'")['c'];
$totalScheduled = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_status = 'scheduled'")['c'];

$categories = db_fetch_all("SELECT * FROM categories ORDER BY cat_title ASC");
?>

<div class="space-y-6">
  
  <!-- Header Title & Action -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-paper-200 dark:border-paper-800">
    <div>
      <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
        Articles &amp; Dispatches
      </h1>
      <p class="font-mono text-xs text-ink-500 mt-1">
        Manage drafts, published essays, scheduled posts, and metadata.
      </p>
    </div>

    <a href="/admin/post-edit.php" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded shadow-sm transition-colors flex items-center space-x-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
      <span>Write New Article</span>
    </a>
  </div>

  <!-- Status Tabs -->
  <div class="flex items-center space-x-2 border-b border-paper-200 dark:border-paper-800 font-mono text-xs overflow-x-auto pb-1">
    <a href="/admin/posts.php?status=all" class="px-3 py-2 border-b-2 font-semibold transition-colors <?php echo $statusFilter === 'all' ? 'border-accent text-accent' : 'border-transparent text-ink-500 hover:text-ink-900'; ?>">
      All (<?php echo $totalAll; ?>)
    </a>
    <a href="/admin/posts.php?status=published" class="px-3 py-2 border-b-2 font-semibold transition-colors <?php echo $statusFilter === 'published' ? 'border-accent text-accent' : 'border-transparent text-ink-500 hover:text-ink-900'; ?>">
      Published (<?php echo $totalPublished; ?>)
    </a>
    <a href="/admin/posts.php?status=draft" class="px-3 py-2 border-b-2 font-semibold transition-colors <?php echo $statusFilter === 'draft' ? 'border-accent text-accent' : 'border-transparent text-ink-500 hover:text-ink-900'; ?>">
      Drafts (<?php echo $totalDraft; ?>)
    </a>
    <a href="/admin/posts.php?status=scheduled" class="px-3 py-2 border-b-2 font-semibold transition-colors <?php echo $statusFilter === 'scheduled' ? 'border-accent text-accent' : 'border-transparent text-ink-500 hover:text-ink-900'; ?>">
      Scheduled (<?php echo $totalScheduled; ?>)
    </a>
  </div>

  <!-- Filter & Search Bar -->
  <form method="GET" action="/admin/posts.php" class="grid grid-cols-1 sm:grid-cols-12 gap-3 editorial-card p-3 rounded-sm">
    <input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">

    <div class="sm:col-span-6">
      <input 
        type="text" 
        name="search" 
        value="<?php echo e($search); ?>" 
        placeholder="Filter by title or author..." 
        class="w-full px-3 py-1.5 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100"
      >
    </div>

    <div class="sm:col-span-4">
      <select name="category" class="w-full px-3 py-1.5 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100">
        <option value="0">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo $cat['cat_id']; ?>" <?php echo $categoryFilter === (int)$cat['cat_id'] ? 'selected' : ''; ?>>
            <?php echo e($cat['cat_title']); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="sm:col-span-2 flex items-center space-x-2">
      <button type="submit" class="w-full py-1.5 px-3 bg-accent hover:bg-accent-hover text-white text-xs font-mono uppercase tracking-wider font-semibold rounded">
        Apply
      </button>
      <?php if ($search !== '' || $categoryFilter > 0): ?>
        <a href="/admin/posts.php?status=<?php echo e($statusFilter); ?>" class="text-ink-400 hover:text-accent font-mono text-xs px-1">
          Reset
        </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Articles Table -->
  <div class="editorial-card rounded-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs font-mono">
        <thead class="bg-paper-100 dark:bg-paper-950/60 border-b border-paper-200 dark:border-paper-800 text-ink-400 uppercase">
          <tr>
            <th class="p-4">Cover</th>
            <th class="p-4">Title &amp; Slug</th>
            <th class="p-4">Category</th>
            <th class="p-4">Author</th>
            <th class="p-4">Status</th>
            <th class="p-4">Views</th>
            <th class="p-4">Date</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-paper-200 dark:divide-paper-800">
          <?php if (empty($posts)): ?>
            <tr>
              <td colspan="8" class="p-10 text-center text-ink-400 italic font-mono">
                No dispatches found matching the filter criteria.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($posts as $p): ?>
              <tr class="hover:bg-paper-50 dark:hover:bg-paper-800/40 transition-colors">
                
                <!-- Thumbnail -->
                <td class="p-4 w-16">
                  <?php if (!empty($p['post_image'])): ?>
                    <img src="/images/<?php echo e($p['post_image']); ?>" alt="" class="w-12 h-12 object-cover rounded border border-paper-200 dark:border-paper-800">
                  <?php else: ?>
                    <div class="w-12 h-12 rounded bg-paper-200 dark:bg-paper-800 flex items-center justify-center text-ink-400 text-[10px]">
                      No Img
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Title & Slug -->
                <td class="p-4 max-w-xs">
                  <a href="/admin/post-edit.php?id=<?php echo $p['post_id']; ?>" class="font-mono font-bold text-sm text-ink-950 dark:text-ink-50 hover:text-accent block leading-snug">
                    <?php echo e($p['post_title']); ?>
                  </a>
                  <span class="text-[11px] text-ink-400 truncate block mt-0.5">/<?php echo e($p['post_slug']); ?></span>
                  <?php if ($p['is_featured']): ?>
                    <span class="typewriter-badge text-[9px] bg-accent/10 border-accent text-accent inline-block mt-1">Featured</span>
                  <?php endif; ?>
                </td>

                <!-- Category -->
                <td class="p-4 text-ink-500">
                  <?php echo e($p['cat_title'] ?? 'Uncategorized'); ?>
                </td>

                <!-- Author -->
                <td class="p-4 text-ink-500">
                  <?php echo e($p['post_author']); ?>
                </td>

                <!-- Status Badge -->
                <td class="p-4">
                  <?php if ($p['post_status'] === 'published'): ?>
                    <span class="typewriter-badge text-emerald-600 border-emerald-500/40 bg-emerald-500/10">Published</span>
                  <?php elseif ($p['post_status'] === 'draft'): ?>
                    <span class="typewriter-badge text-amber-600 border-amber-500/40 bg-amber-500/10">Draft</span>
                  <?php elseif ($p['post_status'] === 'scheduled'): ?>
                    <span class="typewriter-badge text-blue-600 border-blue-500/40 bg-blue-500/10">Scheduled</span>
                  <?php else: ?>
                    <span class="typewriter-badge text-ink-400"><?php echo e($p['post_status']); ?></span>
                  <?php endif; ?>
                </td>

                <!-- Views -->
                <td class="p-4 text-ink-500">
                  <?php echo $p['post_views']; ?>
                </td>

                <!-- Date -->
                <td class="p-4 text-ink-500 whitespace-nowrap">
                  <?php echo date('M j, Y', strtotime($p['post_date'])); ?>
                </td>

                <!-- Actions -->
                <td class="p-4 text-right whitespace-nowrap space-x-2">
                  <a href="/post.php?slug=<?php echo urlencode($p['post_slug']); ?>" target="_blank" class="text-ink-400 hover:text-accent" title="View public post">
                    Preview
                  </a>
                  <span>&bull;</span>
                  <a href="/admin/post-edit.php?id=<?php echo $p['post_id']; ?>" class="text-accent hover:underline font-bold">
                    Edit
                  </a>
                  <span>&bull;</span>
                  <!-- Quick Toggle Publish Form -->
                  <form method="POST" action="/admin/posts.php" class="inline" onsubmit="return confirm('Change status of this article?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="toggle_publish">
                    <input type="hidden" name="post_id" value="<?php echo $p['post_id']; ?>">
                    <button type="submit" class="text-ink-500 hover:text-ink-900 dark:hover:text-ink-100">
                      <?php echo $p['post_status'] === 'published' ? 'Unpublish' : 'Publish'; ?>
                    </button>
                  </form>
                  <span>&bull;</span>
                  <!-- Delete Form -->
                  <form method="POST" action="/admin/posts.php" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this article?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="post_id" value="<?php echo $p['post_id']; ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700">
                      Delete
                    </button>
                  </form>
                </td>

              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
