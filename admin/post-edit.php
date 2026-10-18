<?php
/**
 * WordWise Admin CMS - Immersive Article Editor
 * Modern distraction-free block-based composition (Editor.js), live autosave,
 * optimistic concurrency validation, revision checkpoints, media library picker,
 * and comprehensive publication workflow.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/blocks.php';

auth_require('author');
$currentUser = auth_user();

$postId = (int)($_GET['id'] ?? 0);
$post = null;
$existingTags = [];
$revisions = [];
$latestAutosave = null;

if ($postId > 0) {
    $post = db_fetch_one("SELECT * FROM posts WHERE post_id = ?", [$postId]);
    if (!$post) {
        $_SESSION['flash_error'] = "Article not found.";
        header("Location: /admin/posts.php");
        exit;
    }
    // Check permission to edit
    if (!auth_can('edit_post')) {
        $_SESSION['flash_error'] = "You do not have permission to edit this article.";
        header("Location: /admin/posts.php");
        exit;
    }
    if ($currentUser['role'] !== 'admin' && $currentUser['role'] !== 'editor') {
        if ($post['post_author'] !== $currentUser['full_name'] && $post['post_author'] !== $currentUser['username']) {
            $_SESSION['flash_error'] = "You can only edit your own articles.";
            header("Location: /admin/posts.php");
            exit;
        }
    }

    // Fetch existing tag names
    $tagRows = db_fetch_all(
        "SELECT t.tag_name FROM tags t 
         JOIN post_tags pt ON t.tag_id = pt.tag_id 
         WHERE pt.post_id = ?",
        [$postId]
    );
    $existingTags = array_column($tagRows, 'tag_name');

    // Fetch revisions
    $revisions = db_fetch_all(
        "SELECT r.revision_id, r.title, r.created_at, u.username, u.full_name 
         FROM post_revisions r 
         LEFT JOIN users u ON r.user_id = u.user_id 
         WHERE r.post_id = ? 
         ORDER BY r.created_at DESC LIMIT 6",
        [$postId]
    );

    // Fetch latest autosave draft for current user
    $latestAutosave = db_fetch_one(
        "SELECT * FROM post_autosaves WHERE user_id = ? AND post_id = ? ORDER BY updated_at DESC LIMIT 1",
        [auth_id(), $postId]
    );
} else {
    // New unsaved post draft
    $latestAutosave = db_fetch_one(
        "SELECT * FROM post_autosaves WHERE user_id = ? AND (post_id IS NULL OR post_id = 0) ORDER BY updated_at DESC LIMIT 1",
        [auth_id()]
    );
}

$categories = db_fetch_all("SELECT * FROM categories ORDER BY cat_title ASC");
$errors = [];

// Handle Form Submission (Save Draft / Publish)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $title = trim($_POST['post_title'] ?? '');
        $slug = trim($_POST['post_slug'] ?? '');
        $subtitle = trim($_POST['post_subtitle'] ?? '');
        $excerpt = trim($_POST['post_excerpt'] ?? '');
        $categoryId = (int)($_POST['post_category_id'] ?? 0);
        $author = trim($_POST['post_author'] ?? $currentUser['full_name']);
        $postImage = trim($_POST['post_image'] ?? '');
        $status = in_array($_POST['post_status'] ?? '', ['published', 'draft', 'scheduled', 'archived'], true) ? $_POST['post_status'] : 'draft';
        $scheduledAt = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $isPinned = !empty($_POST['is_pinned']) ? 1 : 0;
        $rawTags = trim($_POST['post_tags'] ?? '');
        $seoTitle = trim($_POST['post_seo_title'] ?? '');
        $seoDesc = trim($_POST['post_seo_desc'] ?? '');
        $clientVersion = (int)($_POST['client_version'] ?? 1);

        // Content from Editor.js JSON
        $contentJson = trim($_POST['post_content_json'] ?? '');
        $fallbackHtml = trim($_POST['post_content'] ?? '');

        if ($contentJson !== '') {
            $content = render_blocks($contentJson, $fallbackHtml);
        } else {
            $content = $fallbackHtml;
            $contentJson = html_to_blocks($fallbackHtml);
        }

        // Validation
        if ($title === '') {
            $errors[] = "Article headline is required.";
        }
        if (trim(strip_tags($content)) === '') {
            $errors[] = "Article body cannot be empty.";
        }

        // Generate / clean slug
        if ($slug === '') {
            $slug = slugify($title);
        } else {
            $slug = slugify($slug);
        }

        // Verify slug uniqueness
        $slugCheckSql = "SELECT post_id FROM posts WHERE post_slug = ?" . ($postId > 0 ? " AND post_id != ?" : "");
        $slugCheckParams = $postId > 0 ? [$slug, $postId] : [$slug];
        $slugExists = db_fetch_one($slugCheckSql, $slugCheckParams);
        if ($slugExists) {
            $slug .= '-' . time();
        }

        // Optimistic concurrency check
        if ($postId > 0 && empty($errors)) {
            $currentDbVersion = (int)($post['post_version'] ?? 1);
            if ($currentDbVersion > $clientVersion) {
                $errors[] = "Concurrency collision: This article was updated by another session (Server v{$currentDbVersion} vs Local v{$clientVersion}). Please reload before overwriting.";
            }
        }

        if (empty($errors)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            if ($postId > 0) {
                // Save revision snapshot before overwrite
                db_execute(
                    "INSERT INTO post_revisions (post_id, user_id, title, content_json, content_html, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    [$postId, auth_id(), $post['post_title'], $post['post_content_json'], $post['post_content'] ?? '']
                );

                // Update Existing Post
                db_execute(
                    "UPDATE posts SET 
                        post_title = ?, post_slug = ?, post_subtitle = ?, post_excerpt = ?,
                        post_category_id = ?, post_author = ?, post_image = ?, 
                        post_content = ?, post_content_json = ?, post_tags = ?, post_status = ?, 
                        post_seo_title = ?, post_seo_desc = ?,
                        is_featured = ?, is_pinned = ?, scheduled_at = ?, 
                        post_version = post_version + 1, updated_at = NOW() 
                     WHERE post_id = ?",
                    [
                        $title, $slug, $subtitle, $excerpt,
                        $categoryId, $author, $postImage, 
                        $content, $contentJson, $rawTags, $status, 
                        $seoTitle, $seoDesc,
                        $isFeatured, $isPinned, $scheduledAt, 
                        $postId
                    ]
                );

                // Clear live autosave draft for this post
                db_execute("DELETE FROM post_autosaves WHERE user_id = ? AND post_id = ?", [auth_id(), $postId]);

                db_execute(
                    "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'update_post', ?, ?)",
                    [auth_id(), "Updated post #{$postId} '{$title}'", $ip]
                );

                $targetPostId = $postId;
                $_SESSION['flash_success'] = "Article &ldquo;" . e($title) . "&rdquo; saved and updated successfully.";
            } else {
                // Create New Post
                db_execute(
                    "INSERT INTO posts (
                        post_title, post_slug, post_subtitle, post_excerpt, 
                        post_category_id, post_author, post_date, 
                        post_image, post_content, post_content_json, post_tags, 
                        post_status, post_seo_title, post_seo_desc, 
                        is_featured, is_pinned, scheduled_at, post_version, created_at, updated_at
                     ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
                    [
                        $title, $slug, $subtitle, $excerpt, 
                        $categoryId, $author, 
                        $postImage, $content, $contentJson, $rawTags, 
                        $status, $seoTitle, $seoDesc, 
                        $isFeatured, $isPinned, $scheduledAt
                    ]
                );

                $targetPostId = (int)db_last_id();

                // Clear unpersisted autosave draft
                db_execute("DELETE FROM post_autosaves WHERE user_id = ? AND (post_id IS NULL OR post_id = 0)", [auth_id()]);

                db_execute(
                    "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'create_post', ?, ?)",
                    [auth_id(), "Created post #{$targetPostId} '{$title}'", $ip]
                );

                $_SESSION['flash_success'] = "Article &ldquo;" . e($title) . "&rdquo; published / saved successfully.";
            }

            // Sync Tags in database
            db_execute("DELETE FROM post_tags WHERE post_id = ?", [$targetPostId]);
            if ($rawTags !== '') {
                $tagNames = array_filter(array_map('trim', explode(',', $rawTags)));
                foreach ($tagNames as $tName) {
                    $tSlug = slugify($tName);
                    if ($tSlug === '') continue;

                    db_execute(
                        "INSERT INTO tags (tag_name, tag_slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE tag_name = VALUES(tag_name)",
                        [$tName, $tSlug]
                    );
                    $tagRecord = db_fetch_one("SELECT tag_id FROM tags WHERE tag_slug = ?", [$tSlug]);
                    if ($tagRecord) {
                        db_execute(
                            "INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)",
                            [$targetPostId, $tagRecord['tag_id']]
                        );
                    }
                }
            }

            header("Location: /admin/posts.php");
            exit;
        }
    }
}

// Current field values (post values on failure or db values)
$fTitle = $_POST['post_title'] ?? $post['post_title'] ?? '';
$fSlug = $_POST['post_slug'] ?? $post['post_slug'] ?? '';
$fSubtitle = $_POST['post_subtitle'] ?? $post['post_subtitle'] ?? '';
$fExcerpt = $_POST['post_excerpt'] ?? $post['post_excerpt'] ?? '';
$fCategory = (int)($_POST['post_category_id'] ?? $post['post_category_id'] ?? 1);
$fAuthor = $_POST['post_author'] ?? $post['post_author'] ?? $currentUser['full_name'];
$fContent = $_POST['post_content'] ?? $post['post_content'] ?? '';
$fContentJson = $_POST['post_content_json'] ?? $post['post_content_json'] ?? '';
$fImage = $_POST['post_image'] ?? $post['post_image'] ?? '';
$fStatus = $_POST['post_status'] ?? $post['post_status'] ?? 'published';
$fScheduled = $_POST['scheduled_at'] ?? $post['scheduled_at'] ?? '';
$fFeatured = !empty($_POST) ? (!empty($_POST['is_featured'])) : (!empty($post['is_featured']));
$fPinned = !empty($_POST) ? (!empty($_POST['is_pinned'])) : (!empty($post['is_pinned']));
$fTags = $_POST['post_tags'] ?? implode(', ', $existingTags);
$fSeoTitle = $_POST['post_seo_title'] ?? $post['post_seo_title'] ?? '';
$fSeoDesc = $_POST['post_seo_desc'] ?? $post['post_seo_desc'] ?? '';
$fVersion = (int)($post['post_version'] ?? 1);

// Determine initial Editor.js blocks
if (!empty($fContentJson) && json_decode($fContentJson, true) !== null) {
    $initialBlocks = $fContentJson;
} elseif (!empty($fContent)) {
    $initialBlocks = html_to_blocks($fContent);
} else {
    $initialBlocks = json_encode([
        'time' => time() * 1000,
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => ['text' => '']
            ]
        ],
        'version' => '2.30.0'
    ]);
}

// Draft recovery check
$hasRecoverableDraft = false;
$autosaveTimestamp = '';
$autosaveData = null;
if ($latestAutosave && !empty($latestAutosave['content_json'])) {
    if ($postId > 0 && !empty($post['updated_at'])) {
        if (strtotime($latestAutosave['updated_at']) > strtotime($post['updated_at'])) {
            $hasRecoverableDraft = true;
            $autosaveTimestamp = date('M j, Y H:i:s', strtotime($latestAutosave['updated_at']));
            $autosaveData = $latestAutosave['content_json'];
        }
    } elseif ($postId === 0) {
        $hasRecoverableDraft = true;
        $autosaveTimestamp = date('M j, Y H:i:s', strtotime($latestAutosave['updated_at']));
        $autosaveData = $latestAutosave['content_json'];
    }
}

$pageTitle = $postId > 0 ? "Edit Dispatch" : "New Dispatch";
$pageHeader = "Editorial Composition";
require_once __DIR__ . '/includes/admin_header.php';
?>

<!-- Zen Mode Custom Styling -->
<style>
  body.zen-mode aside,
  body.zen-mode header,
  body.zen-mode footer {
    display: none !important;
  }
  body.zen-mode main {
    max-width: 860px !important;
    margin: 0 auto !important;
    padding: 3rem 1.5rem !important;
  }
  body.zen-mode #editor-meta-panel {
    display: none !important;
  }
  body.zen-mode #zen-exit-pill {
    display: flex !important;
  }
  /* Editor.js Clean Editorial Canvas Tweaks */
  .codex-editor {
    position: relative;
    box-sizing: border-box;
    z-index: 1;
  }
  .ce-block__content, .ce-toolbar__content {
    max-width: 100% !important;
  }
  .cdx-block {
    padding: 0.35rem 0;
  }
  .ce-paragraph {
    line-height: 1.8;
    font-size: 1.05rem;
  }
  .dark .codex-editor svg {
    fill: #e5e7eb;
  }
  .dark .ce-toolbar__plus, .dark .ce-toolbar__settings-btn {
    color: #e5e7eb;
    background-color: #1e293b;
  }
  .dark .ce-inline-toolbar, .dark .ce-popover, .dark .ce-conversion-toolbar {
    background-color: #1e293b;
    border-color: #334155;
    color: #f8fafc;
  }
  .dark .ce-popover-item:hover, .dark .ce-inline-tool:hover {
    background-color: #334155;
  }
</style>

<!-- Floating Exit Zen Mode Button (visible only in Zen Mode) -->
<div id="zen-exit-pill" class="hidden fixed top-4 right-6 z-50 items-center space-x-2 bg-paper-900/90 dark:bg-paper-800/90 text-white px-3.5 py-1.5 rounded-full shadow-lg font-mono text-xs backdrop-blur border border-paper-700">
  <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
  <span>Zen Mode Active</span>
  <button type="button" onclick="toggleZenMode()" class="ml-2 font-bold hover:text-accent underline">
    Exit (Esc) &times;
  </button>
</div>

<div class="max-w-5xl mx-auto space-y-6" id="editor-container">

  <!-- Header & Live Status Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-paper-200 dark:border-paper-800 gap-4">
    <div>
      <div class="flex items-center space-x-3">
        <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
          <?php echo $postId > 0 ? 'Edit Dispatch' : 'Compose New Dispatch'; ?>
        </h1>
        <!-- Concurrency Version Badge -->
        <span class="font-mono text-[10px] typewriter-badge text-ink-500">v<?php echo $fVersion; ?></span>
      </div>

      <!-- Real-Time Metrics & Autosave Status -->
      <div class="flex flex-wrap items-center gap-3 font-mono text-xs text-ink-500 mt-1.5">
        <span id="metric-words">0 words</span>
        <span>&bull;</span>
        <span id="metric-chars">0 characters</span>
        <span>&bull;</span>
        <span id="metric-read">1 min read</span>
        <span>&bull;</span>
        <!-- Autosave Status Badge -->
        <span id="autosave-status" class="inline-flex items-center space-x-1.5 text-ink-400">
          <span class="w-1.5 h-1.5 rounded-full bg-ink-400 inline-block"></span>
          <span>Draft synced</span>
        </span>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center flex-wrap gap-2.5">
      <!-- Zen Mode Button -->
      <button 
        type="button" 
        onclick="toggleZenMode()" 
        class="typewriter-badge hover:border-accent hover:text-accent inline-flex items-center space-x-1.5"
        title="Fullscreen Distraction-Free Writing Mode (Alt+Z)"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
        <span>Zen Mode</span>
      </button>

      <!-- Authenticated Preview Button -->
      <?php if ($postId > 0): ?>
        <a 
          href="/post.php?id=<?php echo $postId; ?>&preview=true" 
          target="_blank" 
          class="typewriter-badge hover:border-accent hover:text-accent inline-flex items-center space-x-1"
          title="Preview this article exactly as published"
        >
          <span>Live Preview</span>
          <span class="text-[10px]">&nearr;</span>
        </a>
      <?php endif; ?>

      <a href="/admin/posts.php" class="font-mono text-xs text-ink-400 hover:text-ink-700 dark:hover:text-ink-200 px-2 py-1">
        Cancel
      </a>
    </div>
  </div>

  <!-- Concurrency Collision Alert (Hidden unless 409 triggered) -->
  <div id="concurrency-warning" class="hidden p-4 bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400 text-xs font-mono rounded flex items-start justify-between">
    <div>
      <p class="font-bold uppercase tracking-wider">&bull; Concurrency Warning</p>
      <p id="concurrency-msg" class="mt-1">This article was modified in another tab or by another user.</p>
    </div>
    <button type="button" onclick="location.reload()" class="underline font-bold hover:text-red-900 ml-4">Reload Article</button>
  </div>

  <!-- Recovered Draft Banner -->
  <?php if ($hasRecoverableDraft): ?>
    <div id="draft-recovery-banner" class="p-4 bg-amber-500/10 border border-amber-500/30 text-amber-800 dark:text-amber-200 text-xs font-mono rounded flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <span class="font-bold uppercase tracking-wider">[Unsaved Draft Found]</span>
        <span class="ml-1">An autosaved draft from <strong><?php echo e($autosaveTimestamp); ?></strong> was found.</span>
      </div>
      <div class="flex items-center space-x-2">
        <button type="button" onclick="restoreDraft()" class="px-3 py-1 bg-amber-500 text-black font-semibold rounded hover:bg-amber-400 transition-colors">
          Restore Draft
        </button>
        <button type="button" onclick="discardDraft()" class="px-3 py-1 border border-amber-500/40 rounded hover:bg-amber-500/20">
          Discard
        </button>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-700 dark:text-red-400 text-xs font-mono rounded space-y-1">
      <?php foreach ($errors as $err): ?>
        <p>&bull; <?php echo e($err); ?></p>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form id="post-form" method="POST" action="/admin/post-edit.php<?php echo $postId > 0 ? '?id=' . $postId : ''; ?>" class="space-y-6">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="client_version" value="<?php echo $fVersion; ?>">
    <input type="hidden" name="post_content_json" id="post_content_json" value="<?php echo htmlspecialchars($fContentJson, ENT_QUOTES, 'UTF-8'); ?>">
    <input type="hidden" name="post_content" id="post_content" value="<?php echo htmlspecialchars($fContent, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Headline and Standfirst Canvas -->
    <div class="editorial-card p-6 sm:p-8 rounded-sm space-y-5">
      
      <!-- Headline Title -->
      <div>
        <label for="post_title" class="block font-mono text-xs uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-2">
          Headline *
        </label>
        <input 
          type="text" 
          name="post_title" 
          id="post_title" 
          value="<?php echo e($fTitle); ?>" 
          required 
          placeholder="Title of your dispatch or essay..." 
          class="w-full px-4 py-3 text-2xl sm:text-3xl font-mono font-bold bg-transparent border-b-2 border-paper-300 dark:border-paper-700 focus:border-accent outline-none text-ink-950 dark:text-ink-50 placeholder:text-ink-300 dark:placeholder:text-ink-700 transition-colors"
          oninput="handleTitleInput(this.value)"
        >
      </div>

      <!-- Subtitle / Standfirst -->
      <div>
        <label for="post_subtitle" class="block font-mono text-xs uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-1">
          Standfirst / Subtitle
        </label>
        <input 
          type="text" 
          name="post_subtitle" 
          id="post_subtitle" 
          value="<?php echo e($fSubtitle); ?>" 
          placeholder="A succinct editorial summary introducing the essay..." 
          class="w-full px-3 py-2 text-base font-sans italic bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-800 dark:text-ink-200"
        >
      </div>

      <!-- Cover Image Strip -->
      <div class="pt-2 border-t border-paper-200 dark:border-paper-800/60">
        <div class="flex items-center justify-between mb-2">
          <label class="font-mono text-xs uppercase tracking-wider text-ink-500 dark:text-ink-400">
            Cover Artwork
          </label>
          <div class="space-x-2">
            <button type="button" onclick="openMediaModal('cover')" class="typewriter-badge hover:border-accent hover:text-accent">
              Choose from Media Library &rarr;
            </button>
            <button type="button" id="btn-remove-cover" onclick="removeCoverImage()" class="<?php echo empty($fImage) ? 'hidden' : ''; ?> typewriter-badge text-red-500 hover:border-red-500">
              Remove
            </button>
          </div>
        </div>

        <input type="hidden" name="post_image" id="post_image" value="<?php echo e($fImage); ?>">

        <!-- Live Cover Image Preview Box -->
        <div id="cover-preview-box" class="<?php echo empty($fImage) ? 'hidden' : ''; ?> relative max-h-56 overflow-hidden rounded border border-paper-200 dark:border-paper-800 bg-paper-100 dark:bg-paper-900">
          <img id="cover-preview-img" src="<?php echo !empty($fImage) ? '/images/' . e($fImage) : ''; ?>" alt="Cover preview" class="w-full h-56 object-cover">
        </div>
      </div>

    </div>

    <!-- Immersive Block-Based Article Editor Canvas -->
    <div class="editorial-card p-6 sm:p-10 rounded-sm space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-paper-200 dark:border-paper-800 text-xs font-mono text-ink-500">
        <span class="uppercase tracking-wider font-bold text-ink-900 dark:text-ink-100">
          Composition Canvas
        </span>
        <span class="text-[11px] text-ink-400">
          Press <strong>Tab</strong> or type <strong>/</strong> to insert blocks
        </span>
      </div>

      <!-- Editor.js Canvas Mounting Container -->
      <div id="editorjs" class="min-h-[420px] font-mono text-ink-900 dark:text-ink-100 focus:outline-none"></div>
    </div>

    <!-- Metadata, Taxonomy, SEO & Settings Drawer -->
    <div id="editor-meta-panel" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Taxonomy & Byline -->
      <div class="editorial-card p-6 rounded-sm space-y-4">
        <h3 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
          Classification &amp; Author
        </h3>

        <div>
          <label for="post_category_id" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Category *
          </label>
          <select name="post_category_id" id="post_category_id" class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100">
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo $cat['cat_id']; ?>" <?php echo $fCategory === (int)$cat['cat_id'] ? 'selected' : ''; ?>>
                <?php echo e($cat['cat_title']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label for="post_tags" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Tags (comma-separated)
          </label>
          <input 
            type="text" 
            name="post_tags" 
            id="post_tags" 
            value="<?php echo e($fTags); ?>" 
            placeholder="php, architecture, minimal, editorial" 
            class="w-full px-3 py-2 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>

        <div>
          <label for="post_slug" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            URL Slug
          </label>
          <input 
            type="text" 
            name="post_slug" 
            id="post_slug" 
            value="<?php echo e($fSlug); ?>" 
            placeholder="auto-generated-slug" 
            class="w-full px-3 py-2 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
            oninput="this.dataset.edited = 'true'"
          >
        </div>

        <div>
          <label for="post_author" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Author Byline
          </label>
          <input 
            type="text" 
            name="post_author" 
            id="post_author" 
            value="<?php echo e($fAuthor); ?>" 
            required 
            class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100"
          >
        </div>
      </div>

      <!-- Publication Workflow & Schedule -->
      <div class="editorial-card p-6 rounded-sm space-y-4">
        <h3 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
          Publishing Lifecycle
        </h3>

        <div>
          <label for="post_status" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Status
          </label>
          <select 
            name="post_status" 
            id="post_status" 
            onchange="toggleScheduleInput(this.value)"
            class="w-full px-3 py-2 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
            <option value="published" <?php echo $fStatus === 'published' ? 'selected' : ''; ?>>Published (Public Live)</option>
            <option value="draft" <?php echo $fStatus === 'draft' ? 'selected' : ''; ?>>Draft (Internal)</option>
            <option value="scheduled" <?php echo $fStatus === 'scheduled' ? 'selected' : ''; ?>>Scheduled (Timed Release)</option>
            <option value="archived" <?php echo $fStatus === 'archived' ? 'selected' : ''; ?>>Archived (Catalogue Only)</option>
          </select>
        </div>

        <!-- Schedule Datetime Picker (visible when status is scheduled) -->
        <div id="schedule-box" class="<?php echo $fStatus === 'scheduled' ? '' : 'hidden'; ?>">
          <label for="scheduled_at" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
            Release Timestamp (Local Time)
          </label>
          <input 
            type="datetime-local" 
            name="scheduled_at" 
            id="scheduled_at" 
            value="<?php echo !empty($fScheduled) ? date('Y-m-d\TH:i', strtotime($fScheduled)) : ''; ?>" 
            class="w-full px-3 py-2 text-xs font-mono bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
          >
          <p class="font-mono text-[10px] text-ink-400 mt-1">Automatically published by the scheduled publishing cron worker.</p>
        </div>

        <div class="space-y-2 pt-2 font-mono text-xs border-t border-paper-200 dark:border-paper-800">
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" name="is_featured" value="1" <?php echo $fFeatured ? 'checked' : ''; ?> class="rounded text-accent focus:ring-accent">
            <span class="text-ink-800 dark:text-ink-200">Feature this article on Homepage Hero</span>
          </label>
          <label class="flex items-center space-x-2 cursor-pointer">
            <input type="checkbox" name="is_pinned" value="1" <?php echo $fPinned ? 'checked' : ''; ?> class="rounded text-accent focus:ring-accent">
            <span class="text-ink-800 dark:text-ink-200">Pin to top of catalogue</span>
          </label>
        </div>

        <!-- Revisions Drawer (if existing post) -->
        <?php if (!empty($revisions)): ?>
          <div class="pt-3 border-t border-paper-200 dark:border-paper-800">
            <h4 class="font-mono text-[11px] uppercase tracking-wider text-ink-500 font-bold mb-2">
              Recent Revision Snapshots
            </h4>
            <div class="space-y-1 font-mono text-[11px] text-ink-500 max-h-32 overflow-y-auto">
              <?php foreach ($revisions as $rev): ?>
                <div class="flex items-center justify-between py-0.5 border-b border-paper-100 dark:border-paper-800/40">
                  <span>#<?php echo $rev['revision_id']; ?> &mdash; <?php echo date('M j, H:i', strtotime($rev['created_at'])); ?></span>
                  <span class="text-ink-400"><?php echo e($rev['username'] ?? 'Author'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

      </div>

    </div>

    <!-- SEO & Search Snippet Metadata -->
    <div class="editorial-card p-6 rounded-sm space-y-4">
      <h3 class="font-mono text-xs uppercase tracking-wider text-ink-900 dark:text-ink-100 font-bold border-b border-paper-200 dark:border-paper-800 pb-2">
        Search Engine &amp; Social Optimization (SEO)
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="post_seo_title" class="font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                SEO Meta Title
              </label>
              <span id="seo-title-count" class="font-mono text-[10px] text-ink-400">0/60</span>
            </div>
            <input 
              type="text" 
              name="post_seo_title" 
              id="post_seo_title" 
              value="<?php echo e($fSeoTitle); ?>" 
              maxlength="70"
              placeholder="Custom title tag for search engines (defaults to headline)..." 
              class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
              oninput="updateSeoPreview()"
            >
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="post_seo_desc" class="font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
                SEO Meta Description
              </label>
              <span id="seo-desc-count" class="font-mono text-[10px] text-ink-400">0/160</span>
            </div>
            <textarea 
              name="post_seo_desc" 
              id="post_seo_desc" 
              rows="3" 
              maxlength="180"
              placeholder="Search snippet summary (defaults to standfirst or excerpt)..." 
              class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
              oninput="updateSeoPreview()"
            ><?php echo e($fSeoDesc); ?></textarea>
          </div>

          <div>
            <label for="post_excerpt" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300 mb-1">
              Article Excerpt (Catalog Summary)
            </label>
            <textarea 
              name="post_excerpt" 
              id="post_excerpt" 
              rows="2" 
              placeholder="Teaser paragraph for homepage cards and RSS feeds..." 
              class="w-full px-3 py-2 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded focus:border-accent text-ink-900 dark:text-ink-100"
            ><?php echo e($fExcerpt); ?></textarea>
          </div>
        </div>

        <!-- Google SERP Live Simulation Preview -->
        <div>
          <label class="block font-mono text-xs uppercase tracking-wider text-ink-500 dark:text-ink-400 mb-2">
            Search Result Preview
          </label>
          <div class="p-4 rounded border border-paper-200 dark:border-paper-800 bg-paper-50 dark:bg-paper-900/60 font-sans space-y-1">
            <div class="font-mono text-[11px] text-ink-400 flex items-center space-x-1.5 truncate">
              <span>https://wordwise.org/post/</span><span id="serp-slug" class="text-accent"><?php echo e($fSlug ?: 'dispatch'); ?></span>
            </div>
            <h4 id="serp-title" class="text-base text-blue-600 dark:text-blue-400 hover:underline font-medium cursor-pointer line-clamp-1">
              <?php echo e($fSeoTitle ?: ($fTitle ?: 'Article Headline — WordWise')); ?>
            </h4>
            <p id="serp-desc" class="text-xs text-ink-600 dark:text-ink-300 line-clamp-2">
              <?php echo e($fSeoDesc ?: ($fSubtitle ?: 'Read the full essay on WordWise publication.')); ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit Bar -->
    <div class="editorial-card p-4 rounded-sm flex items-center justify-between sticky bottom-4 z-30 backdrop-blur bg-white/95 dark:bg-paper-900/95 shadow-md">
      <a href="/admin/posts.php" class="font-mono text-xs text-ink-500 hover:text-ink-900 dark:hover:text-ink-100">
        &larr; Back to Articles
      </a>

      <div class="flex items-center space-x-3">
        <!-- Checkpoint Revision Button -->
        <?php if ($postId > 0): ?>
          <button 
            type="button" 
            onclick="checkpointRevision()" 
            class="px-3.5 py-2 border border-paper-300 dark:border-paper-800 hover:border-accent text-ink-700 dark:text-ink-300 font-mono text-xs uppercase tracking-wider font-semibold rounded transition-colors"
            title="Create an instantaneous snapshot revision in history"
          >
            Checkpoint
          </button>
        <?php endif; ?>

        <!-- Save Draft Button -->
        <button 
          type="button" 
          onclick="submitFormWithStatus('draft')"
          class="px-4 py-2 border border-paper-300 dark:border-paper-800 hover:border-accent text-ink-700 dark:text-ink-300 font-mono text-xs uppercase tracking-wider font-semibold rounded transition-colors"
        >
          Save Draft
        </button>

        <!-- Publish Button -->
        <button 
          type="button" 
          onclick="submitFormWithStatus('published')"
          class="px-6 py-2 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded shadow-sm transition-colors"
        >
          <?php echo $postId > 0 ? 'Save &amp; Update Dispatch' : 'Publish Dispatch'; ?>
        </button>
      </div>
    </div>

  </form>

</div>

<!-- Media Library Picker Modal -->
<div id="media-modal" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white dark:bg-paper-900 border border-paper-200 dark:border-paper-800 w-full max-w-3xl rounded-sm shadow-2xl flex flex-col max-h-[85vh]">
    <!-- Modal Header -->
    <div class="p-4 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
      <div>
        <h3 class="font-mono text-sm font-bold text-ink-950 dark:text-ink-50 uppercase tracking-wider">
          Media Asset Repository
        </h3>
        <p class="font-mono text-[11px] text-ink-400">Select an existing asset or upload a new illustration.</p>
      </div>
      <button type="button" onclick="closeMediaModal()" class="text-ink-400 hover:text-ink-800 text-lg font-mono">&times;</button>
    </div>

    <!-- Search & Quick Upload Toolbar -->
    <div class="p-4 border-b border-paper-100 dark:border-paper-800/60 flex flex-col sm:flex-row items-center justify-between gap-3 bg-paper-50 dark:bg-paper-950">
      <input 
        type="text" 
        id="media-search-input" 
        placeholder="Filter assets by name or alt text..." 
        oninput="searchMediaAssets(this.value)"
        class="w-full sm:w-72 px-3 py-1.5 text-xs font-mono bg-white dark:bg-paper-900 border border-paper-300 dark:border-paper-800 rounded focus:border-accent"
      >
      <label class="typewriter-badge hover:border-accent cursor-pointer text-xs">
        <span>+ Upload File</span>
        <input type="file" id="modal-file-input" class="hidden" accept="image/*,video/mp4" onchange="uploadModalAsset(this.files[0])">
      </label>
    </div>

    <!-- Media Grid -->
    <div id="media-modal-grid" class="p-4 overflow-y-auto grid grid-cols-2 sm:grid-cols-4 gap-3 flex-grow min-h-[240px]">
      <div class="col-span-full py-12 text-center font-mono text-xs text-ink-400">Loading assets...</div>
    </div>

    <!-- Modal Footer -->
    <div class="p-3 border-t border-paper-200 dark:border-paper-800 flex items-center justify-between font-mono text-xs">
      <span id="media-modal-count" class="text-ink-400">0 items</span>
      <button type="button" onclick="closeMediaModal()" class="px-4 py-1.5 border border-paper-300 dark:border-paper-800 rounded hover:border-accent">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Vendor Scripts: Editor.js and Tool Plugins -->
<script src="/js/vendor/editorjs/editorjs.umd.js"></script>
<script src="/js/vendor/editorjs/header.umd.js"></script>
<script src="/js/vendor/editorjs/editorjs-list.umd.js"></script>
<script src="/js/vendor/editorjs/quote.umd.js"></script>
<script src="/js/vendor/editorjs/delimiter.umd.js"></script>
<script src="/js/vendor/editorjs/code.umd.js"></script>
<script src="/js/vendor/editorjs/image.umd.js"></script>
<script src="/js/vendor/editorjs/embed.umd.js"></script>
<script src="/js/vendor/editorjs/warning.umd.js"></script>

<script>
  // Configuration State
  const POST_ID = <?php echo (int)$postId; ?>;
  const CSRF_TOKEN = '<?php echo csrf_token(); ?>';
  let clientVersion = <?php echo (int)$fVersion; ?>;
  let initialBlocksData = <?php echo $initialBlocks; ?>;
  let recoverableAutosaveData = <?php echo $autosaveData ? $autosaveData : 'null'; ?>;
  let isDirty = false;
  let autosaveTimer = null;
  let isSavingAutosave = false;
  let editorInstance = null;

  // Initialize Editor.js Canvas
  document.addEventListener('DOMContentLoaded', () => {
    try {
      editorInstance = new EditorJS({
        holder: 'editorjs',
        placeholder: 'Type \'/\' or press Tab for blocks, or write your dispatch...',
        data: initialBlocksData,
        tools: {
          header: {
            class: Header,
            inlineToolbar: ['link', 'bold', 'italic'],
            config: {
              placeholder: 'Enter a heading...',
              levels: [2, 3, 4],
              defaultLevel: 2
            }
          },
          list: {
            class: EditorjsList,
            inlineToolbar: true,
            config: {
              defaultStyle: 'unordered'
            }
          },
          quote: {
            class: Quote,
            inlineToolbar: true,
            config: {
              quotePlaceholder: 'Enter a quote...',
              captionPlaceholder: 'Quote author or citation'
            }
          },
          delimiter: Delimiter,
          code: {
            class: CodeTool,
            config: {
              placeholder: 'Paste or compose code snippet...'
            }
          },
          warning: {
            class: Warning,
            inlineToolbar: true,
            config: {
              titlePlaceholder: 'Callout Title (Optional)',
              messagePlaceholder: 'Note, tip, or warning message...'
            }
          },
          image: {
            class: ImageTool,
            config: {
              endpoints: {
                byFile: '/admin/api/media.php',
                byUrl: '/admin/api/media.php'
              },
              additionalRequestHeaders: {
                'X-CSRF-Token': CSRF_TOKEN
              },
              field: 'image',
              types: 'image/*'
            }
          },
          embed: {
            class: Embed,
            inlineToolbar: true,
            config: {
              services: {
                youtube: true,
                vimeo: true,
                coub: true
              }
            }
          }
        },
        onChange: () => {
          markDirty();
        },
        onReady: () => {
          updateMetrics();
        }
      });
    } catch (e) {
      console.error('Failed to initialize Editor.js:', e);
    }

    updateSeoPreview();
  });

  // Calculate live metrics (words, chars, reading time)
  async function updateMetrics() {
    if (!editorInstance) return;
    try {
      const data = await editorInstance.save();
      let fullText = document.getElementById('post_title').value + ' ';
      if (data && data.blocks) {
        data.blocks.forEach(b => {
          if (b.data && b.data.text) fullText += ' ' + b.data.text.replace(/<[^>]*>/g, ' ');
          if (b.data && b.data.code) fullText += ' ' + b.data.code;
          if (b.data && b.data.items) {
            b.data.items.forEach(it => {
              const str = typeof it === 'string' ? it : (it.content || '');
              fullText += ' ' + str.replace(/<[^>]*>/g, ' ');
            });
          }
        });
      }

      const trimmed = fullText.trim();
      const words = trimmed ? trimmed.split(/\s+/).length : 0;
      const chars = trimmed.length;
      const readMins = Math.max(1, Math.ceil(words / 200));

      document.getElementById('metric-words').textContent = words + ' words';
      document.getElementById('metric-chars').textContent = chars + ' characters';
      document.getElementById('metric-read').textContent = readMins + ' min read';
    } catch (e) {
      // silently ignore metric calculation errors
    }
  }

  // Handle title input and auto-slug
  function handleTitleInput(val) {
    const slugInput = document.getElementById('post_slug');
    if (!slugInput.dataset.edited) {
      slugInput.value = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }
    updateSeoPreview();
    markDirty();
  }

  // Live SEO search result simulation
  function updateSeoPreview() {
    const titleVal = document.getElementById('post_title').value;
    const seoTitleVal = document.getElementById('post_seo_title').value;
    const seoDescVal = document.getElementById('post_seo_desc').value;
    const subtitleVal = document.getElementById('post_subtitle').value;
    const slugVal = document.getElementById('post_slug').value;

    document.getElementById('seo-title-count').textContent = seoTitleVal.length + '/60';
    document.getElementById('seo-desc-count').textContent = seoDescVal.length + '/160';

    document.getElementById('serp-title').textContent = seoTitleVal || (titleVal ? titleVal + ' — WordWise' : 'Article Headline — WordWise');
    document.getElementById('serp-slug').textContent = slugVal || 'dispatch';
    document.getElementById('serp-desc').textContent = seoDescVal || (subtitleVal || 'Read the full essay on WordWise publication.');
  }

  // Track edits and trigger debounced autosave
  function markDirty() {
    isDirty = true;
    updateMetrics();
    updateSeoPreview();

    const badge = document.getElementById('autosave-status');
    badge.className = 'inline-flex items-center space-x-1.5 text-amber-500';
    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block animate-pulse"></span><span>Unsaved edits</span>';

    clearTimeout(autosaveTimer);
    autosaveTimer = setTimeout(triggerAutosave, 4000);
  }

  // Background Autosave to /admin/api/autosave.php
  async function triggerAutosave() {
    if (!isDirty || isSavingAutosave || !editorInstance) return;
    isSavingAutosave = true;

    const badge = document.getElementById('autosave-status');
    badge.className = 'inline-flex items-center space-x-1.5 text-amber-500';
    badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block animate-ping"></span><span>Saving draft...</span>';

    try {
      const data = await editorInstance.save();
      const contentJson = JSON.stringify(data);
      const title = document.getElementById('post_title').value;

      const response = await fetch('/admin/api/autosave.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': CSRF_TOKEN
        },
        body: JSON.stringify({
          action: 'autosave',
          post_id: POST_ID,
          title: title,
          content_json: contentJson,
          client_version: clientVersion,
          csrf_token: CSRF_TOKEN
        })
      });

      if (response.status === 409) {
        // Concurrency collision
        const res = await response.json();
        document.getElementById('concurrency-warning').classList.remove('hidden');
        document.getElementById('concurrency-msg').textContent = res.message || 'Concurrency conflict detected.';
        badge.className = 'inline-flex items-center space-x-1.5 text-red-500';
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span><span>Conflict detected</span>';
        isSavingAutosave = false;
        return;
      }

      if (response.ok) {
        const res = await response.json();
        isDirty = false;
        badge.className = 'inline-flex items-center space-x-1.5 text-emerald-600 dark:text-emerald-400';
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span><span>Draft synced (' + res.saved_at + ')</span>';
      } else {
        throw new Error('Save failed');
      }
    } catch (e) {
      badge.className = 'inline-flex items-center space-x-1.5 text-red-500';
      badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span><span>Autosave failed</span>';
    } finally {
      isSavingAutosave = false;
    }
  }

  // Restore Recovered Draft
  async function restoreDraft() {
    if (!recoverableAutosaveData || !editorInstance) return;
    try {
      const parsed = typeof recoverableAutosaveData === 'string' ? JSON.parse(recoverableAutosaveData) : recoverableAutosaveData;
      await editorInstance.render(parsed);
      document.getElementById('draft-recovery-banner').remove();
      markDirty();
    } catch (e) {
      alert('Could not render draft data: ' + e.message);
    }
  }

  // Discard Recovered Draft
  async function discardDraft() {
    try {
      await fetch('/admin/api/autosave.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
        body: JSON.stringify({ action: 'discard_draft', post_id: POST_ID, csrf_token: CSRF_TOKEN })
      });
      document.getElementById('draft-recovery-banner').remove();
    } catch (e) {}
  }

  // Create Checkpoint Revision Snapshot
  async function checkpointRevision() {
    if (!editorInstance || POST_ID <= 0) return;
    try {
      const data = await editorInstance.save();
      const contentJson = JSON.stringify(data);
      const title = document.getElementById('post_title').value;

      const res = await fetch('/admin/api/autosave.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF_TOKEN },
        body: JSON.stringify({
          action: 'save_revision',
          post_id: POST_ID,
          title: title,
          content_json: contentJson,
          csrf_token: CSRF_TOKEN
        })
      });

      const json = await res.json();
      if (json.status === 'success') {
        alert('Checkpoint revision #' + json.revision_id + ' successfully recorded.');
      }
    } catch (e) {
      alert('Failed to save checkpoint: ' + e.message);
    }
  }

  // Zen Mode Toggle
  function toggleZenMode() {
    document.body.classList.toggle('zen-mode');
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && document.body.classList.contains('zen-mode')) {
      toggleZenMode();
    }
    if (e.altKey && (e.key === 'z' || e.key === 'Z')) {
      e.preventDefault();
      toggleZenMode();
    }
  });

  // Scheduled date toggle
  function toggleScheduleInput(status) {
    const box = document.getElementById('schedule-box');
    if (status === 'scheduled') {
      box.classList.remove('hidden');
    } else {
      box.classList.add('hidden');
    }
  }

  // Form Submission
  async function submitFormWithStatus(status) {
    if (status) {
      document.getElementById('post_status').value = status;
    }

    if (editorInstance) {
      try {
        const data = await editorInstance.save();
        document.getElementById('post_content_json').value = JSON.stringify(data);
      } catch (e) {
        console.error('Error extracting blocks from Editor.js:', e);
      }
    }

    window.removeEventListener('beforeunload', warnUnsaved);
    document.getElementById('post-form').submit();
  }

  // Warn on unpersisted edits before closing window
  function warnUnsaved(e) {
    if (isDirty) {
      e.preventDefault();
      e.returnValue = '';
    }
  }
  window.addEventListener('beforeunload', warnUnsaved);

  // -----------------------------------------------------------
  // Media Modal Library Browser
  // -----------------------------------------------------------
  let mediaSearchTimeout = null;

  function openMediaModal(target) {
    document.getElementById('media-modal').classList.remove('hidden');
    loadMediaAssets('');
  }

  function closeMediaModal() {
    document.getElementById('media-modal').classList.add('hidden');
  }

  function searchMediaAssets(query) {
    clearTimeout(mediaSearchTimeout);
    mediaSearchTimeout = setTimeout(() => loadMediaAssets(query), 300);
  }

  async function loadMediaAssets(query) {
    const grid = document.getElementById('media-modal-grid');
    grid.innerHTML = '<div class="col-span-full py-12 text-center font-mono text-xs text-ink-400">Loading assets...</div>';

    try {
      const res = await fetch('/admin/api/media.php?q=' + encodeURIComponent(query));
      const data = await res.json();

      if (data.items && data.items.length > 0) {
        document.getElementById('media-modal-count').textContent = data.total + ' items found';
        grid.innerHTML = '';
        data.items.forEach(item => {
          const card = document.createElement('div');
          card.className = 'group relative border border-paper-200 dark:border-paper-800 rounded overflow-hidden cursor-pointer hover:border-accent transition-colors bg-paper-50 dark:bg-paper-950 p-1.5 flex flex-col justify-between';
          card.onclick = () => selectCoverImage(item.file_name, item.url);

          card.innerHTML = `
            <div class="aspect-video w-full overflow-hidden rounded bg-paper-200 dark:bg-paper-800 flex items-center justify-center">
              <img src="${item.url}" alt="${item.alt_text}" class="w-full h-full object-cover">
            </div>
            <div class="mt-1.5 font-mono text-[10px] truncate text-ink-800 dark:text-ink-200">
              ${item.file_name}
            </div>
            <div class="font-mono text-[9px] text-ink-400">
              ${item.size_formatted}
            </div>
          `;
          grid.appendChild(card);
        });
      } else {
        document.getElementById('media-modal-count').textContent = '0 items';
        grid.innerHTML = '<div class="col-span-full py-12 text-center font-mono text-xs text-ink-400">No media assets found matching query.</div>';
      }
    } catch (e) {
      grid.innerHTML = '<div class="col-span-full py-12 text-center font-mono text-xs text-red-500">Failed to load media assets.</div>';
    }
  }

  // Upload asset inside modal
  async function uploadModalAsset(file) {
    if (!file) return;
    const formData = new FormData();
    formData.append('media_file', file);
    formData.append('csrf_token', CSRF_TOKEN);

    try {
      const res = await fetch('/admin/api/media.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': CSRF_TOKEN },
        body: formData
      });
      const data = await res.json();
      if (data.success && data.file) {
        selectCoverImage(data.file.name, data.file.url);
        closeMediaModal();
      } else {
        alert(data.message || 'File upload failed');
      }
    } catch (e) {
      alert('Upload error: ' + e.message);
    }
  }

  // Select cover image
  function selectCoverImage(fileName, url) {
    document.getElementById('post_image').value = fileName;
    document.getElementById('cover-preview-img').src = url;
    document.getElementById('cover-preview-box').classList.remove('hidden');
    document.getElementById('btn-remove-cover').classList.remove('hidden');
    closeMediaModal();
    markDirty();
  }

  // Remove cover image
  function removeCoverImage() {
    document.getElementById('post_image').value = '';
    document.getElementById('cover-preview-box').classList.add('hidden');
    document.getElementById('btn-remove-cover').classList.add('hidden');
    markDirty();
  }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
