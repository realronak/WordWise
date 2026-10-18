<?php
/**
 * WordWise Admin CMS - Category Management
 * CRUD operations for publication categories with slug validation and article association checks.
 */
$pageTitle = "Categories";
$pageHeader = "Taxonomy Management";
require_once __DIR__ . '/includes/admin_header.php';

if (!auth_can('manage_categories')) {
    http_response_code(403);
    die("Access denied: You do not have permission to manage categories.");
}

$editId = (int)($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId > 0) {
    $editCategory = db_fetch_one("SELECT * FROM categories WHERE cat_id = ?", [$editId]);
}

$errors = [];

// Handle Form Submissions (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($action === 'save') {
            $catId = (int)($_POST['cat_id'] ?? 0);
            $title = trim($_POST['cat_title'] ?? '');
            $slug = trim($_POST['cat_slug'] ?? '');
            $desc = trim($_POST['cat_description'] ?? '');

            if ($title === '') {
                $errors[] = "Category name cannot be empty.";
            } else {
                if ($slug === '') $slug = slugify($title);
                else $slug = slugify($slug);

                // Duplicate check
                $checkSql = "SELECT cat_id FROM categories WHERE (cat_title = ? OR cat_slug = ?)" . ($catId > 0 ? " AND cat_id != ?" : "");
                $checkParams = $catId > 0 ? [$title, $slug, $catId] : [$title, $slug];
                if (db_fetch_one($checkSql, $checkParams)) {
                    $errors[] = "A category with that name or slug already exists.";
                } else {
                    if ($catId > 0) {
                        db_execute("UPDATE categories SET cat_title = ?, cat_slug = ?, cat_description = ? WHERE cat_id = ?", [$title, $slug, $desc, $catId]);
                        db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'update_category', ?, ?)", [auth_id(), "Updated category #{$catId} '{$title}'", $ip]);
                        $_SESSION['flash_success'] = "Category updated successfully.";
                    } else {
                        db_execute("INSERT INTO categories (cat_title, cat_slug, cat_description) VALUES (?, ?, ?)", [$title, $slug, $desc]);
                        $newId = db_last_id();
                        db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'create_category', ?, ?)", [auth_id(), "Created category #{$newId} '{$title}'", $ip]);
                        $_SESSION['flash_success'] = "Category &ldquo;" . e($title) . "&rdquo; created successfully.";
                    }
                    header("Location: /admin/categories.php");
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $catId = (int)($_POST['cat_id'] ?? 0);
            $linkedPosts = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_category_id = ?", [$catId])['c'];
            
            if ($linkedPosts > 0) {
                $_SESSION['flash_error'] = "Cannot delete category: {$linkedPosts} articles are currently assigned to it. Please reassign those articles first.";
            } else {
                db_execute("DELETE FROM categories WHERE cat_id = ?", [$catId]);
                db_execute("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'delete_category', ?, ?)", [auth_id(), "Deleted category #{$catId}", $ip]);
                $_SESSION['flash_success'] = "Category deleted.";
            }
            header("Location: /admin/categories.php");
            exit;
        }
    }
}

// Fetch all categories with article counts
$categories = db_fetch_all(
    "SELECT c.*, COUNT(p.post_id) as post_count 
     FROM categories c 
     LEFT JOIN posts p ON c.cat_id = p.post_category_id 
     GROUP BY c.cat_id 
     ORDER BY c.cat_title ASC"
);
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
  
  <!-- Category Form (Create or Edit) -->
  <div class="lg:col-span-5">
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <div class="pb-3 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
        <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">
          <?php echo $editCategory ? 'Edit Category' : 'Create New Category'; ?>
        </h2>
        <?php if ($editCategory): ?>
          <a href="/admin/categories.php" class="font-mono text-xs text-ink-400 hover:text-accent">New +</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="p-3 bg-red-500/10 border border-red-500/30 text-red-600 text-xs font-mono rounded">
          <?php foreach ($errors as $e): ?><p>&bull; <?php echo e($e); ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/categories.php" class="space-y-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="cat_id" value="<?php echo $editCategory['cat_id'] ?? 0; ?>">

        <div>
          <label for="cat_title" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Category Name *
          </label>
          <input 
            type="text" 
            name="cat_title" 
            id="cat_title" 
            required 
            value="<?php echo e($editCategory['cat_title'] ?? ''); ?>" 
            placeholder="e.g. Systems Architecture" 
            class="w-full px-3 py-2 text-sm bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-950 dark:text-ink-50"
            oninput="if(!document.getElementById('cat_slug').dataset.edited) { document.getElementById('cat_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); }"
          >
        </div>

        <div>
          <label for="cat_slug" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Slug
          </label>
          <input 
            type="text" 
            name="cat_slug" 
            id="cat_slug" 
            value="<?php echo e($editCategory['cat_slug'] ?? ''); ?>" 
            placeholder="systems-architecture" 
            class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
            oninput="this.dataset.edited = 'true'"
          >
        </div>

        <div>
          <label for="cat_description" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Editorial Description
          </label>
          <textarea 
            name="cat_description" 
            id="cat_description" 
            rows="3" 
            placeholder="Optional summary describing what dispatches fall under this topic..."
            class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          ><?php echo e($editCategory['cat_description'] ?? ''); ?></textarea>
        </div>

        <button 
          type="submit" 
          class="w-full py-2 px-4 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded transition-colors"
        >
          <?php echo $editCategory ? 'Update Category' : 'Save Category'; ?>
        </button>
      </form>
    </div>
  </div>

  <!-- Categories Table List -->
  <div class="lg:col-span-7">
    <div class="editorial-card rounded-sm overflow-hidden">
      <div class="p-5 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
        <div>
          <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">Active Categories</h2>
          <p class="font-mono text-xs text-ink-400"><?php echo count($categories); ?> topics configured</p>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-paper-100 dark:bg-paper-950/60 border-b border-paper-200 dark:border-paper-800 text-ink-400 uppercase">
            <tr>
              <th class="p-4">Name</th>
              <th class="p-4">Slug</th>
              <th class="p-4">Dispatches</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-paper-200 dark:divide-paper-800">
            <?php foreach ($categories as $cat): ?>
              <tr class="hover:bg-paper-50 dark:hover:bg-paper-800/40 transition-colors">
                <td class="p-4">
                  <a href="/admin/categories.php?edit=<?php echo $cat['cat_id']; ?>" class="font-mono font-bold text-sm text-ink-950 dark:text-ink-50 hover:text-accent">
                    <?php echo e($cat['cat_title']); ?>
                  </a>
                  <?php if (!empty($cat['cat_description'])): ?>
                    <p class="text-[11px] text-ink-400 font-sans truncate max-w-xs mt-0.5"><?php echo e($cat['cat_description']); ?></p>
                  <?php endif; ?>
                </td>
                <td class="p-4 text-ink-500">
                  /<?php echo e($cat['cat_slug']); ?>
                </td>
                <td class="p-4">
                  <a href="/admin/posts.php?category=<?php echo $cat['cat_id']; ?>" class="typewriter-badge hover:border-accent hover:text-accent">
                    <?php echo $cat['post_count']; ?> posts
                  </a>
                </td>
                <td class="p-4 text-right space-x-2">
                  <a href="/admin/categories.php?edit=<?php echo $cat['cat_id']; ?>" class="text-accent hover:underline font-bold">
                    Edit
                  </a>
                  <span>&bull;</span>
                  <form method="POST" action="/admin/categories.php" class="inline" onsubmit="return confirm('Delete this category?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="cat_id" value="<?php echo $cat['cat_id']; ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700" <?php echo $cat['post_count'] > 0 ? 'title="Has linked posts"' : ''; ?>>
                      Delete
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
