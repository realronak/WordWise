<?php
/**
 * WordWise Scheduled Dispatch Publisher
 * Invoked via cron/CLI to automatically transition scheduled articles to published status
 * when their scheduled_at timestamp has been reached.
 *
 * Usage:
 *   php cron/publish_scheduled.php
 *   php cron/publish_scheduled.php --json
 */

if (php_sapi_name() !== 'cli' && empty($_SERVER['HTTP_X_CRON_SECRET'])) {
    // Basic protection against accidental web triggers
    http_response_code(403);
    die("Access denied: CLI execution only.\n");
}

require_once __DIR__ . '/../includes/db.php';

$jsonOutput = in_array('--json', $argv ?? [], true);
$now = date('Y-m-d H:i:s');

// Fetch all due scheduled posts
$duePosts = db_fetch_all(
    "SELECT post_id, post_title, post_slug, scheduled_at, post_author 
     FROM posts 
     WHERE post_status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= ? 
     ORDER BY scheduled_at ASC",
    [$now]
);

$published = [];

foreach ($duePosts as $post) {
    $postId = (int)$post['post_id'];

    db_execute(
        "UPDATE posts SET post_status = 'published', post_date = CURDATE(), updated_at = NOW() WHERE post_id = ?",
        [$postId]
    );

    // Audit log
    db_execute(
        "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (NULL, 'publish_scheduled', ?, '127.0.0.1')",
        ["Auto-published scheduled essay #{$postId} '{$post['post_title']}' (scheduled for {$post['scheduled_at']})"]
    );

    $published[] = [
        'post_id' => $postId,
        'title' => $post['post_title'],
        'slug' => $post['post_slug'],
        'author' => $post['post_author'],
        'scheduled_at' => $post['scheduled_at'],
        'published_at' => $now
    ];
}

if ($jsonOutput) {
    echo json_encode([
        'status' => 'success',
        'run_at' => $now,
        'count' => count($published),
        'published' => $published
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo "=== WordWise Scheduled Dispatch Runner [{$now}] ===\n";
    if (empty($published)) {
        echo "No scheduled articles currently due for publication.\n";
    } else {
        echo "Successfully published " . count($published) . " scheduled article(s):\n";
        foreach ($published as $p) {
            echo "  [#{$p['post_id']}] \"{$p['title']}\" (due: {$p['scheduled_at']})\n";
        }
    }
}
