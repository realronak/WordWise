<?php
/**
 * WordWise Admin CMS - Media Library
 * Secure file uploads with finfo MIME detection, random unique filenames,
 * alt text metadata, and safe deletion checks.
 */
$pageTitle = "Media Library";
$pageHeader = "Asset Repository";
require_once __DIR__ . '/includes/admin_header.php';

$errors = [];

// Handle File Upload or Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors[] = "Security token mismatch. Please try again.";
    } else {
        $action = $_POST['action'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if ($action === 'upload') {
            if (!auth_can('upload_media')) {
                $errors[] = "You do not have permission to upload media.";
            } elseif (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "File upload failed or no file selected.";
            } else {
                $file = $_FILES['media_file'];
                $maxBytes = 5 * 1024 * 1024; // 5MB

                if ($file['size'] > $maxBytes) {
                    $errors[] = "File size exceeds the 5MB maximum limit.";
                } else {
                    // Actual MIME inspection
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);

                    $allowedMimes = [
                        'image/jpeg' => 'jpg',
                        'image/png'  => 'png',
                        'image/webp' => 'webp',
                        'image/gif'  => 'gif',
                    ];

                    if (!isset($allowedMimes[$mime])) {
                        $errors[] = "Invalid file type ({$mime}). Only JPEG, PNG, WEBP, and GIF images are permitted.";
                    } else {
                        $ext = $allowedMimes[$mime];
                        $cleanOriginal = pathinfo($file['name'], PATHINFO_FILENAME);
                        $generatedName = 'img_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $targetDir = dirname(__DIR__) . '/images';
                        $targetPath = $targetDir . '/' . $generatedName;

                        if (!is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                            $errors[] = "Failed to store uploaded file on filesystem.";
                        } else {
                            chmod($targetPath, 0644);
                            $altText = trim($_POST['alt_text'] ?? $cleanOriginal);

                            db_execute(
                                "INSERT INTO media (file_name, file_path, file_type, file_size, alt_text, uploaded_by) 
                                 VALUES (?, ?, ?, ?, ?, ?)",
                                [$generatedName, 'images/' . $generatedName, $mime, $file['size'], $altText, auth_id()]
                            );

                            db_execute(
                                "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'upload_media', ?, ?)",
                                [auth_id(), "Uploaded asset '{$generatedName}' ({$mime})", $ip]
                            );

                            $_SESSION['flash_success'] = "Image &ldquo;{$generatedName}&rdquo; uploaded successfully.";
                            header("Location: /admin/media.php");
                            exit;
                        }
                    }
                }
            }
        } elseif ($action === 'delete') {
            $mediaId = (int)($_POST['media_id'] ?? 0);
            $mediaItem = db_fetch_one("SELECT * FROM media WHERE media_id = ?", [$mediaId]);

            if (!$mediaItem) {
                $_SESSION['flash_error'] = "Asset not found.";
            } else {
                // Safety check: Prevent deletion if file is currently used in posts
                $usageCount = (int)db_fetch_one(
                    "SELECT COUNT(*) as c FROM posts WHERE post_image = ?",
                    [$mediaItem['file_name']]
                )['c'];

                if ($usageCount > 0) {
                    $_SESSION['flash_error'] = "Cannot delete: Image is currently assigned to {$usageCount} article(s).";
                } else {
                    $diskPath = dirname(__DIR__) . '/' . $mediaItem['file_path'];
                    if (file_exists($diskPath)) {
                        unlink($diskPath);
                    }
                    db_execute("DELETE FROM media WHERE media_id = ?", [$mediaId]);
                    db_execute(
                        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'delete_media', ?, ?)",
                        [auth_id(), "Deleted media asset '{$mediaItem['file_name']}'", $ip]
                    );
                    $_SESSION['flash_success'] = "Asset deleted.";
                }
            }
            header("Location: /admin/media.php");
            exit;
        }
    }
}

// Fetch all media items
$mediaItems = db_fetch_all(
    "SELECT m.*, u.username 
     FROM media m 
     LEFT JOIN users u ON m.uploaded_by = u.user_id 
     ORDER BY m.created_at DESC"
);
?>

<div class="space-y-8">
  
  <!-- Upload Box -->
  <div class="editorial-card p-6 rounded-sm space-y-4">
    <div class="pb-3 border-b border-paper-200 dark:border-paper-800">
      <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">Upload New Media Asset</h2>
      <p class="font-mono text-xs text-ink-500">MIME-verified storage for article covers and editorial illustrations.</p>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="p-4 bg-red-500/10 border border-red-500/30 text-red-600 text-xs font-mono rounded space-y-1">
        <?php foreach ($errors as $err): ?><p>&bull; <?php echo e($err); ?></p><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="/admin/media.php" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-end">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="action" value="upload">

      <div class="sm:col-span-6 space-y-1">
        <label for="media_file" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
          Select Image File (Max 5MB)
        </label>
        <input 
          type="file" 
          name="media_file" 
          id="media_file" 
          required 
          accept="image/jpeg,image/png,image/webp,image/gif"
          class="w-full text-xs font-mono text-ink-600 dark:text-ink-400 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-xs file:font-mono file:bg-paper-200 dark:file:bg-paper-800 file:text-ink-800 dark:file:text-ink-200 hover:file:bg-accent hover:file:text-white"
        >
      </div>

      <div class="sm:col-span-4 space-y-1">
        <label for="alt_text" class="block font-mono text-xs uppercase tracking-wider text-ink-700 dark:text-ink-300">
          Alt Text / Caption
        </label>
        <input 
          type="text" 
          name="alt_text" 
          id="alt_text" 
          placeholder="Descriptive text for accessibility"
          class="w-full px-3 py-1.5 text-xs bg-paper-50 dark:bg-paper-950 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100"
        >
      </div>

      <div class="sm:col-span-2">
        <button 
          type="submit" 
          class="w-full py-2 px-4 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded transition-colors"
        >
          Upload Asset
        </button>
      </div>
    </form>
  </div>

  <!-- Media Grid -->
  <div>
    <div class="flex items-center justify-between pb-4 border-b border-paper-200 dark:border-paper-800 mb-6">
      <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">Repository Assets</h2>
      <span class="font-mono text-xs text-ink-400"><?php echo count($mediaItems); ?> items</span>
    </div>

    <?php if (empty($mediaItems)): ?>
      <div class="editorial-card p-12 text-center rounded-sm">
        <p class="font-mono text-xs text-ink-400 italic">No media assets found in repository.</p>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($mediaItems as $item): ?>
          <div class="editorial-card rounded-sm overflow-hidden flex flex-col justify-between group">
            
            <div class="overflow-hidden bg-paper-100 dark:bg-paper-950 border-b border-paper-200 dark:border-paper-800 h-44 flex items-center justify-center">
              <img 
                src="/<?php echo e($item['file_path']); ?>" 
                alt="<?php echo e($item['alt_text'] ?? ''); ?>" 
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                loading="lazy"
              >
            </div>

            <div class="p-4 space-y-2 flex-grow flex flex-col justify-between">
              <div>
                <p class="font-mono text-xs font-bold text-ink-900 dark:text-ink-100 truncate" title="<?php echo e($item['file_name']); ?>">
                  <?php echo e($item['file_name']); ?>
                </p>
                <p class="font-sans text-xs text-ink-500 line-clamp-1 mt-0.5" title="<?php echo e($item['alt_text'] ?? ''); ?>">
                  <?php echo e($item['alt_text'] ?? 'No alt text provided'); ?>
                </p>
                <div class="flex items-center justify-between text-[10px] font-mono text-ink-400 pt-2 border-t border-paper-100 dark:border-paper-800/40">
                  <span><?php echo round($item['file_size'] / 1024); ?> KB &bull; <?php echo e($item['file_type']); ?></span>
                  <span><?php echo date('M Y', strtotime($item['created_at'])); ?></span>
                </div>
              </div>

              <div class="pt-2 flex items-center justify-between font-mono text-xs border-t border-paper-100 dark:border-paper-800/40">
                <button 
                  type="button" 
                  onclick="navigator.clipboard.writeText('<?php echo e($item['file_name']); ?>'); this.textContent = 'COPIED!'; setTimeout(() => this.textContent = 'COPY NAME', 1500);" 
                  class="typewriter-badge hover:border-accent hover:text-accent"
                >
                  COPY NAME
                </button>

                <form method="POST" action="/admin/media.php" class="inline" onsubmit="return confirm('Permanently remove this media asset?');">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="media_id" value="<?php echo $item['media_id']; ?>">
                  <button type="submit" class="text-red-500 hover:text-red-700 text-xs">
                    Delete
                  </button>
                </form>
              </div>

            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
