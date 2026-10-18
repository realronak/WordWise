<?php
/**
 * WordWise Admin CMS Dashboard
 * Real database-backed metrics, recent dispatches, quick actions, and audit logs.
 */
$pageTitle = "Dashboard";
$pageHeader = "Executive Overview";
require_once __DIR__ . '/includes/admin_header.php';

// Real Database-backed Metrics
$publishedCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_status = 'published'")['c'];
$draftCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_status = 'draft'")['c'];
$scheduledCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM posts WHERE post_status = 'scheduled'")['c'];
$categoryCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM categories")['c'];
$tagCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM tags")['c'];
$totalViews = (int)db_fetch_one("SELECT COALESCE(SUM(post_views), 0) as v FROM posts")['v'];
$userCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM users")['c'];

// Recent Posts
$recentPosts = db_fetch_all(
    "SELECT p.*, c.cat_title 
     FROM posts p 
     LEFT JOIN categories c ON p.post_category_id = c.cat_id 
     ORDER BY p.created_at DESC, p.post_id DESC LIMIT 5"
);

// Recent Audit Logs
$recentLogs = db_fetch_all(
    "SELECT a.*, u.username, u.full_name 
     FROM audit_logs a 
     LEFT JOIN users u ON a.user_id = u.user_id 
     ORDER BY a.created_at DESC LIMIT 5"
);
?>

<div class="space-y-8">
  
  <!-- Welcome & Quick Action Bar -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-paper-200 dark:border-paper-800">
    <div>
      <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
        Welcome back, <?php echo e($currentUser['full_name']); ?>
      </h1>
      <p class="font-mono text-xs text-ink-500 mt-1">
        Connected to MariaDB 12 on Omarchy Linux &bull; Role: <?php echo strtoupper($currentRole); ?>
      </p>
    </div>

    <div class="flex items-center space-x-3">
      <a href="/admin/post-edit.php" class="px-4 py-2 bg-accent hover:bg-accent-hover text-white font-mono text-xs uppercase tracking-wider font-semibold rounded shadow-sm transition-colors flex items-center space-x-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Write Dispatch</span>
      </a>
      <a href="/admin/media.php" class="px-3 py-2 border border-paper-300 dark:border-paper-800 hover:border-accent text-ink-700 dark:text-ink-300 font-mono text-xs rounded transition-colors">
        Media
      </a>
    </div>
  </div>

  <!-- Real Metric Cards Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    
    <div class="editorial-card p-5 rounded-sm space-y-1">
      <div class="flex items-center justify-between text-xs font-mono text-ink-400">
        <span>PUBLISHED</span>
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
      </div>
      <div class="text-3xl font-mono font-bold text-ink-950 dark:text-ink-50">
        <?php echo $publishedCount; ?>
      </div>
      <p class="text-[11px] font-mono text-ink-500">Live articles in publication</p>
    </div>

    <div class="editorial-card p-5 rounded-sm space-y-1">
      <div class="flex items-center justify-between text-xs font-mono text-ink-400">
        <span>DRAFTS &amp; SCHEDULED</span>
        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
      </div>
      <div class="text-3xl font-mono font-bold text-ink-950 dark:text-ink-50">
        <?php echo $draftCount + $scheduledCount; ?>
      </div>
      <p class="text-[11px] font-mono text-ink-500"><?php echo $draftCount; ?> draft &bull; <?php echo $scheduledCount; ?> scheduled</p>
    </div>

    <div class="editorial-card p-5 rounded-sm space-y-1">
      <div class="flex items-center justify-between text-xs font-mono text-ink-400">
        <span>TOTAL READS</span>
        <span class="w-2 h-2 rounded-full bg-accent"></span>
      </div>
      <div class="text-3xl font-mono font-bold text-ink-950 dark:text-ink-50">
        <?php echo number_format($totalViews); ?>
      </div>
      <p class="text-[11px] font-mono text-ink-500">Verified unique views</p>
    </div>

    <div class="editorial-card p-5 rounded-sm space-y-1">
      <div class="flex items-center justify-between text-xs font-mono text-ink-400">
        <span>TAXONOMY</span>
        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
      </div>
      <div class="text-3xl font-mono font-bold text-ink-950 dark:text-ink-50">
        <?php echo $categoryCount; ?> / <?php echo $tagCount; ?>
      </div>
      <p class="text-[11px] font-mono text-ink-500">Categories / Tags</p>
    </div>

  </div>

  <!-- Recent Dispatches Table -->
  <div class="editorial-card rounded-sm overflow-hidden">
    <div class="p-5 border-b border-paper-200 dark:border-paper-800 flex items-center justify-between">
      <div>
        <h2 class="font-mono font-bold text-lg text-ink-950 dark:text-ink-50">Recent Dispatches</h2>
        <p class="font-mono text-xs text-ink-400">Articles recently created or updated</p>
      </div>
      <a href="/admin/posts.php" class="font-mono text-xs text-accent hover:underline">
        View All (<?php echo $publishedCount + $draftCount + $scheduledCount; ?>) &rarr;
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs font-mono">
        <thead class="bg-paper-100 dark:bg-paper-950/60 border-b border-paper-200 dark:border-paper-800 text-ink-400 uppercase">
          <tr>
            <th class="p-4">Title</th>
            <th class="p-4">Category</th>
            <th class="p-4">Author</th>
            <th class="p-4">Status</th>
            <th class="p-4">Views</th>
            <th class="p-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-paper-200 dark:divide-paper-800">
          <?php if (empty($recentPosts)): ?>
            <tr>
              <td colspan="6" class="p-8 text-center text-ink-400 italic font-mono">
                No dispatches found in database. Click &ldquo;Write Dispatch&rdquo; to begin.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentPosts as $p): ?>
              <tr class="hover:bg-paper-50 dark:hover:bg-paper-800/40 transition-colors">
                <td class="p-4">
                  <a href="/admin/post-edit.php?id=<?php echo $p['post_id']; ?>" class="font-mono font-bold text-sm text-ink-900 dark:text-ink-100 hover:text-accent">
                    <?php echo e($p['post_title']); ?>
                  </a>
                  <?php if ($p['is_featured']): ?>
                    <span class="typewriter-badge text-[10px] bg-accent/10 border-accent text-accent ml-2">Featured</span>
                  <?php endif; ?>
                </td>
                <td class="p-4 text-ink-500">
                  <?php echo e($p['cat_title'] ?? 'General'); ?>
                </td>
                <td class="p-4 text-ink-500">
                  <?php echo e($p['post_author']); ?>
                </td>
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
                <td class="p-4 text-ink-500">
                  <?php echo $p['post_views']; ?>
                </td>
                <td class="p-4 text-right space-x-2">
                  <a href="/post.php?slug=<?php echo urlencode($p['post_slug']); ?>" target="_blank" class="text-ink-400 hover:text-accent" title="Preview public post">
                    Preview
                  </a>
                  <span>&bull;</span>
                  <a href="/admin/post-edit.php?id=<?php echo $p['post_id']; ?>" class="text-accent hover:underline font-bold">
                    Edit
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Recent Administrative Activity (Audit Trail) -->
  <div class="editorial-card p-6 rounded-sm space-y-4">
    <div class="flex items-center justify-between pb-3 border-b border-paper-200 dark:border-paper-800">
      <h3 class="font-mono text-xs uppercase tracking-wider text-ink-950 dark:text-ink-50 font-bold">
        Recent Activity Log
      </h3>
      <?php if ($currentRole === 'admin'): ?>
        <a href="/admin/audit.php" class="font-mono text-xs text-accent hover:underline">Full Audit Log &rarr;</a>
      <?php endif; ?>
    </div>

    <div class="space-y-3 font-mono text-xs">
      <?php if (empty($recentLogs)): ?>
        <p class="text-ink-400 italic">No activity recorded yet.</p>
      <?php else: ?>
        <?php foreach ($recentLogs as $log): ?>
          <div class="flex items-center justify-between py-1.5 border-b border-paper-100 dark:border-paper-800/40 last:border-0">
            <div class="flex items-center space-x-2">
              <span class="typewriter-badge text-[10px]"><?php echo e($log['action']); ?></span>
              <span class="text-ink-700 dark:text-ink-300"><?php echo e($log['details']); ?></span>
              <span class="text-ink-400 text-[11px]">(by <?php echo e($log['username'] ?? 'System'); ?>)</span>
            </div>
            <time class="text-ink-400 text-[11px]"><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></time>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
