<?php
/**
 * WordWise Admin CMS - Tag Management
 */
$pageTitle = "Tags";
$pageHeader = "Taxonomy Management";
require_once __DIR__ . '/includes/admin_header.php';

if (!auth_can('manage_tags')) {
    http_response_code(403);
    die("Access denied: You do not have permission to manage tags.");
}

$editId = (int)($_GET['edit'] ?? 0);
$editTag = null;
if ($editId > 0) {
    $editTag = db_fetch_one("SELECT * FROM tags WHERE tag_id = ?", [$editId]);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch.";
    } else {
        $action = $_POST['action'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($action === 'save') {
            $tagId = (int)($_POST['tag_id'] ?? 0);
            $name = trim($_POST['tag_name'] ?? '');
            $slug = trim($_POST['tag_slug'] ?? '');

            if ($name === '') {
                $errors[] = "Tag name cannot be empty.";
            } else {
                if ($slug === '') $slug = slugify($name);
                else $slug = slugify($slug);

                $checkSql = "SELECT tag_id FROM tags WHERE (tag_name = ? OR tag_slug = ?)" . ($tagId > 0 ? " AND tag_id != ?" : "");
                $checkParams = $tagId > 0 ? [$name, $slug, $tagId] : [$name, $slug];
                if (db_fetch_one($checkSql, $checkParams)) {
                    $errors[] = "A tag with that name or slug already exists.";
                } else {
                    if ($tagId > 0) {
                        db_execute("UPDATE tags SET tag_name = ?, tag_slug = ? WHERE tag_id = ?", [$name, $slug, $tagId]);
                        $_SESSION['flash_success'] = "Tag updated.";
                    } else {
                        db_execute("INSERT INTO tags (tag_name, tag_slug) VALUES (?, ?)", [$name, $slug]);
                        $_SESSION['flash_success'] = "Tag created.";
                    }
                    header("Location: /admin/tags.php");
                    exit;
                }
            }
        } elseif ($action === 'delete') {
            $tagId = (int)($_POST['tag_id'] ?? 0);
            db_execute("DELETE FROM tags WHERE tag_id = ?", [$tagId]);
            db_execute("DELETE FROM post_tags WHERE tag_id = ?", [$tagId]);
            $_SESSION['flash_success'] = "Tag deleted.";
            header("Location: /admin/tags.php");
            exit;
        }
    }
}

$tags = db_fetch_all(
    "SELECT t.*, COUNT(pt.post_id) as post_count 
     FROM tags t 
     LEFT JOIN post_tags pt ON t.tag_id = pt.tag_id 
     GROUP BY t.tag_id 
     ORDER BY post_count DESC, t.tag_name ASC"
);
?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
  
  <div class="lg:col-span-4">
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <div class="pb-3 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
        <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">
          <?php echo $editTag ? 'Edit Tag' : 'Add New Tag'; ?>
        </h2>
        <?php if ($editTag): ?>
          <a href="/admin/tags.php" class="font-mono text-xs text-ink-400 hover:text-accent">New +</a>
        <?php endif; ?>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="p-3 bg-red-500/10 border border-red-500/30 text-red-600 text-xs font-mono rounded">
          <?php foreach ($errors as $e): ?><p>&bull; <?php echo e($e); ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="/admin/tags.php" class="space-y-4">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="tag_id" value="<?php echo $editTag['tag_id'] ?? 0; ?>">

        <div>
          <label for="tag_name" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Tag Name *
          </label>
          <input 
            type="text" 
            name="tag_name" 
            id="tag_name" 
            required 
            value="<?php echo e($editTag['tag_name'] ?? ''); ?>" 
            placeholder="e.g. Operating Systems" 
            class="w-full px-3 py-2 text-sm bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-950 dark:text-ink-50"
            oninput="if(!document.getElementById('tag_slug').dataset.edited) { document.getElementById('tag_slug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, ''); }"
          >
        </div>

        <div>
          <label for="tag_slug" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Slug
          </label>
          <input 
            type="text" 
            name="tag_slug" 
            id="tag_slug" 
            value="<?php echo e($editTag['tag_slug'] ?? ''); ?>" 
            placeholder="operating-systems" 
            class="w-full px-3 py-1.5 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
            oninput="this.dataset.edited = 'true'"
          >
        </div>

        <button type="submit" class="w-full py-2 px-4 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded transition-colors">
          <?php echo $editTag ? 'Update Tag' : 'Save Tag'; ?>
        </button>
      </form>
    </div>
  </div>

  <div class="lg:col-span-8">
    <div class="editorial-card rounded-sm overflow-hidden">
      <div class="p-5 border-b border-paper-200 dark:border-paper-800">
        <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">Active Tags</h2>
        <p class="font-mono text-xs text-ink-400"><?php echo count($tags); ?> tags in use</p>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs font-mono">
          <thead class="bg-paper-100 dark:bg-paper-950/60 border-b border-paper-200 dark:border-paper-800 text-ink-400 uppercase">
            <tr>
              <th class="p-4">Tag</th>
              <th class="p-4">Slug</th>
              <th class="p-4">Articles</th>
              <th class="p-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-paper-200 dark:divide-paper-800">
            <?php foreach ($tags as $t): ?>
              <tr class="hover:bg-paper-50 dark:hover:bg-paper-800/40 transition-colors">
                <td class="p-4 font-bold text-ink-900 dark:text-ink-100">
                  #<?php echo e($t['tag_name']); ?>
                </td>
                <td class="p-4 text-ink-500">
                  /<?php echo e($t['tag_slug']); ?>
                </td>
                <td class="p-4">
                  <a href="/articles.php?tag=<?php echo urlencode($t['tag_slug']); ?>" target="_blank" class="typewriter-badge hover:border-accent hover:text-accent">
                    <?php echo $t['post_count']; ?> posts
                  </a>
                </td>
                <td class="p-4 text-right space-x-2">
                  <a href="/admin/tags.php?edit=<?php echo $t['tag_id']; ?>" class="text-accent hover:underline font-bold">Edit</a>
                  <span>&bull;</span>
                  <form method="POST" action="/admin/tags.php" class="inline" onsubmit="return confirm('Delete this tag?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="tag_id" value="<?php echo $t['tag_id']; ?>">
                    <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
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
