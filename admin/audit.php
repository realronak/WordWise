<?php
/**
 * WordWise Admin CMS - Audit Trail
 * Chronological immutable audit log of administrative activities and logins.
 */
$pageTitle = "Audit Trail";
$pageHeader = "Security Audit";
require_once __DIR__ . '/includes/admin_header.php';

auth_require('admin');

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$totalCount = (int)db_fetch_one("SELECT COUNT(*) as c FROM audit_logs")['c'];
$totalPages = max(1, (int)ceil($totalCount / $perPage));

$logs = db_fetch_all(
    "SELECT a.*, u.username, u.full_name, u.user_role 
     FROM audit_logs a 
     LEFT JOIN users u ON a.user_id = u.user_id 
     ORDER BY a.created_at DESC, a.log_id DESC 
     LIMIT {$perPage} OFFSET {$offset}"
);
?>

<div class="space-y-6">
  
  <div class="flex items-center justify-between pb-4 border-b border-paper-200 dark:border-paper-800">
    <div>
      <h1 class="text-2xl font-mono font-bold text-ink-950 dark:text-ink-50">
        System Audit Trail
      </h1>
      <p class="font-mono text-xs text-ink-500 mt-1">
        Chronological record of authentication, content mutations, and configuration adjustments.
      </p>
    </div>

    <span class="font-mono text-xs text-ink-400">
      <?php echo $totalCount; ?> recorded events
    </span>
  </div>

  <div class="editorial-card rounded-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs font-mono">
        <thead class="bg-paper-100 dark:bg-paper-950/60 border-b border-paper-200 dark:border-paper-800 text-ink-400 uppercase">
          <tr>
            <th class="p-4">Timestamp</th>
            <th class="p-4">Action</th>
            <th class="p-4">Actor</th>
            <th class="p-4">Details</th>
            <th class="p-4 text-right">Origin IP</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-paper-200 dark:divide-paper-800">
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="5" class="p-8 text-center text-ink-400 italic">No events recorded.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $l): ?>
              <tr class="hover:bg-paper-50 dark:hover:bg-paper-800/40 transition-colors">
                <td class="p-4 whitespace-nowrap text-ink-500">
                  <?php echo date('Y-m-d H:i:s', strtotime($l['created_at'])); ?>
                </td>
                <td class="p-4 whitespace-nowrap">
                  <span class="typewriter-badge text-[10px] uppercase font-bold text-accent border-accent/40">
                    <?php echo e($l['action']); ?>
                  </span>
                </td>
                <td class="p-4 whitespace-nowrap">
                  <?php if (!empty($l['username'])): ?>
                    <span class="font-bold text-ink-900 dark:text-ink-100">@<?php echo e($l['username']); ?></span>
                    <span class="text-[10px] text-ink-400 block"><?php echo e($l['user_role']); ?></span>
                  <?php else: ?>
                    <span class="text-ink-400 italic">System / Unknown</span>
                  <?php endif; ?>
                </td>
                <td class="p-4 text-ink-800 dark:text-ink-200 max-w-md">
                  <?php echo e($l['details']); ?>
                </td>
                <td class="p-4 text-right text-ink-400 whitespace-nowrap">
                  <?php echo e($l['ip_address'] ?? '127.0.0.1'); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="p-4 border-t border-paper-200 dark:border-paper-800 flex items-center justify-between text-xs font-mono">
        <div>
          <?php if ($page > 1): ?>
            <a href="/admin/audit.php?page=<?php echo $page - 1; ?>" class="typewriter-badge hover:border-accent hover:text-accent">&larr; Previous</a>
          <?php endif; ?>
        </div>
        <span class="text-ink-400">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
        <div>
          <?php if ($page < $totalPages): ?>
            <a href="/admin/audit.php?page=<?php echo $page + 1; ?>" class="typewriter-badge hover:border-accent hover:text-accent">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
