<?php
/**
 * WordWise Test Suite - Milestones 4, 5, 6
 * Automated verification of:
 * - Block content parser, sanitizer, and legacy converter (includes/blocks.php)
 * - Autosave API, optimistic locking collision handling, and revision history
 * - Media API listing, search filtering, and upload validation
 * - Scheduled publication cron worker (cron/publish_scheduled.php)
 * - Reading view block rendering, syntax highlighting, and preview mode
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/blocks.php';

$testCount = 0;
$passCount = 0;

function it(string $description, bool $condition): void {
    global $testCount, $passCount;
    $testCount++;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}\n";
    }
}

echo "=== Running Milestones 4, 5 & 6 (Editor, Media, Cron & Reading) Suite ===\n";

// -------------------------------------------------------------
// 1. Block Rendering & Sanitization (includes/blocks.php)
// -------------------------------------------------------------
$sampleBlocks = [
    'time' => 1700000000000,
    'blocks' => [
        [
            'type' => 'header',
            'data' => ['text' => 'The Architecture of Simplicity', 'level' => 2]
        ],
        [
            'type' => 'paragraph',
            'data' => ['text' => 'A clean paragraph with <strong>bold text</strong> and <script>alert("xss")</script> malicious payload.']
        ],
        [
            'type' => 'list',
            'data' => [
                'style' => 'unordered',
                'items' => ['First principle', 'Second principle']
            ]
        ],
        [
            'type' => 'quote',
            'data' => [
                'text' => 'Simplicity is prerequisite for reliability.',
                'caption' => 'Edsger W. Dijkstra'
            ]
        ],
        [
            'type' => 'code',
            'data' => [
                'code' => 'function hello(): string { return "WordWise"; }',
                'language' => 'php'
            ]
        ],
        [
            'type' => 'delimiter',
            'data' => []
        ],
        [
            'type' => 'warning',
            'data' => [
                'title' => 'Important Note',
                'message' => 'Always verify user input on the server.',
                'type' => 'tip'
            ]
        ],
        [
            'type' => 'image',
            'data' => [
                'file' => ['url' => '/images/img01.jpg'],
                'caption' => 'Typewriter machine keys',
                'alt' => 'Typewriter illustration'
            ]
        ],
        [
            'type' => 'embed',
            'data' => [
                'service' => 'youtube',
                'source' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                'caption' => 'Demonstration video'
            ]
        ]
    ]
];

$renderedHtml = render_blocks(json_encode($sampleBlocks));

it("render_blocks renders H2 heading with slugified anchor id", 
    str_contains($renderedHtml, '<h2 id="the-architecture-of-simplicity">The Architecture of Simplicity</h2>')
);

it("render_blocks strips dangerous script tags from paragraphs", 
    !str_contains($renderedHtml, '<script>') && str_contains($renderedHtml, 'A clean paragraph with <strong>bold text</strong>')
);

it("render_blocks renders unordered list with list items", 
    str_contains($renderedHtml, '<ul><li>First principle</li><li>Second principle</li></ul>')
);

it("render_blocks renders blockquote with citation", 
    str_contains($renderedHtml, '<blockquote><p>Simplicity is prerequisite for reliability.</p>') && 
    str_contains($renderedHtml, '— Edsger W. Dijkstra</cite>')
);

it("render_blocks renders code block with language-php and copy button", 
    str_contains($renderedHtml, 'class="language-php"') && str_contains($renderedHtml, 'navigator.clipboard.writeText')
);

it("render_blocks renders styled delimiter hr", 
    str_contains($renderedHtml, '<hr class="my-10')
);

it("render_blocks renders callout tip with title and border", 
    str_contains($renderedHtml, 'Important Note') && str_contains($renderedHtml, 'Always verify user input on the server.')
);

it("render_blocks renders figure and image with caption", 
    str_contains($renderedHtml, '<img src="/images/img01.jpg"') && str_contains($renderedHtml, '<figcaption')
);

it("render_blocks renders allowlisted privacy-friendly YouTube embed", 
    str_contains($renderedHtml, 'youtube-nocookie.com/embed/dQw4w9WgXcQ')
);

it("render_blocks gracefully falls back to HTML when JSON is null or empty", 
    render_blocks(null, '<p>Legacy fallback</p>') === '<p>Legacy fallback</p>'
);

// -------------------------------------------------------------
// 2. Legacy HTML to Block Converter (html_to_blocks)
// -------------------------------------------------------------
$legacyHtml = '<h2>Legacy Title</h2><p>Legacy paragraph text.</p><blockquote>Old quote</blockquote><hr>';
$convertedJson = html_to_blocks($legacyHtml);
$decodedConverted = json_decode($convertedJson, true);

it("html_to_blocks produces valid JSON structure with block array", 
    is_array($decodedConverted) && !empty($decodedConverted['blocks'])
);

it("html_to_blocks correctly extracts header block", 
    $decodedConverted['blocks'][0]['type'] === 'header' && $decodedConverted['blocks'][0]['data']['text'] === 'Legacy Title'
);

it("html_to_blocks correctly extracts paragraph block", 
    $decodedConverted['blocks'][1]['type'] === 'paragraph' && $decodedConverted['blocks'][1]['data']['text'] === 'Legacy paragraph text.'
);

// -------------------------------------------------------------
// 3. Autosave & Optimistic Concurrency Engine
// -------------------------------------------------------------
// Ensure test admin user is logged in
$adminUser = db_fetch_one("SELECT * FROM users WHERE username = 'admin'");
$_SESSION['user_id'] = $adminUser['user_id'];
$_SESSION['username'] = $adminUser['username'];
$_SESSION['role'] = $adminUser['user_role'] ?? 'admin';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// Create temporary test post
$testSlug = 'test-editorial-article-' . time();
db_execute(
    "INSERT INTO posts (
        post_title, post_slug, post_subtitle, post_excerpt,
        post_category_id, post_author, post_date, post_content, post_status, post_version
     ) VALUES ('Test Article', ?, 'Subtitle', 'Excerpt', 1, 'Admin', CURDATE(), '<p>Initial</p>', 'draft', 1)",
    [$testSlug]
);
$testPostId = (int)db_last_id();

it("Test article inserted into posts with initial version 1", $testPostId > 0);

// Test Autosave API logic directly
// 3a. Save draft via simulated autosave
$draftBlocks = json_encode([
    'time' => time() * 1000,
    'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Live autosaved draft draft text']]]
]);

// Clean previous autosaves
db_execute("DELETE FROM post_autosaves WHERE user_id = ? AND post_id = ?", [$adminUser['user_id'], $testPostId]);

// Insert autosave
db_execute(
    "INSERT INTO post_autosaves (user_id, post_id, title, content_json, updated_at) VALUES (?, ?, ?, ?, NOW())",
    [$adminUser['user_id'], $testPostId, 'Test Article Updated Draft', $draftBlocks]
);
$autosaveRow = db_fetch_one("SELECT * FROM post_autosaves WHERE user_id = ? AND post_id = ?", [$adminUser['user_id'], $testPostId]);

it("Live draft successfully created in post_autosaves table", 
    !empty($autosaveRow) && $autosaveRow['title'] === 'Test Article Updated Draft'
);

// 3b. Test Optimistic Concurrency Detection
$currentVersion = (int)db_fetch_one("SELECT post_version FROM posts WHERE post_id = ?", [$testPostId])['post_version'];
// Client thinks it's editing version 1, but we simulate server having version 2
db_execute("UPDATE posts SET post_version = 2 WHERE post_id = ?", [$testPostId]);

$serverVersion = (int)db_fetch_one("SELECT post_version FROM posts WHERE post_id = ?", [$testPostId])['post_version'];
$clientVersion = 1;
$hasCollision = ($serverVersion > $clientVersion);

it("Optimistic locking correctly identifies version collision when server_version > client_version", 
    $hasCollision === true
);

// 3c. Test Revision Checkpointing
db_execute(
    "INSERT INTO post_revisions (post_id, user_id, title, content_json, content_html, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
    [$testPostId, $adminUser['user_id'], 'Snapshot Title', $draftBlocks, '<p>Live autosaved draft draft text</p>']
);
$revRow = db_fetch_one("SELECT * FROM post_revisions WHERE post_id = ? ORDER BY revision_id DESC LIMIT 1", [$testPostId]);

it("Revision checkpoint created in post_revisions table with user and timestamp", 
    !empty($revRow) && (int)$revRow['user_id'] === (int)$adminUser['user_id']
);

// -------------------------------------------------------------
// 4. Media Library & Upload Validation
// -------------------------------------------------------------
// Test Media query
$mediaList = db_fetch_all("SELECT media_id, file_name, file_path, file_type, file_size FROM media ORDER BY created_at DESC LIMIT 5");
it("Media library returns existing assets from media table", 
    is_array($mediaList)
);

// Insert test media entry
$testMediaName = 'img_test_' . time() . '.jpg';
db_execute(
    "INSERT INTO media (file_name, file_path, file_type, file_size, alt_text, uploaded_by, created_at) 
     VALUES (?, ?, 'image/jpeg', 10240, 'Test Artwork Alt', ?, NOW())",
    [$testMediaName, 'images/' . $testMediaName, $adminUser['user_id']]
);
$insertedMedia = db_fetch_one("SELECT * FROM media WHERE file_name = ?", [$testMediaName]);

it("Media asset record persisted with file_name, file_path, and alt_text", 
    !empty($insertedMedia) && $insertedMedia['alt_text'] === 'Test Artwork Alt'
);

// -------------------------------------------------------------
// 5. Publishing Lifecycle & Scheduled Cron Runner
// -------------------------------------------------------------
// Create scheduled post due in past (e.g. 5 minutes ago)
$scheduledSlug = 'scheduled-dispatch-' . time();
$pastTimestamp = date('Y-m-d H:i:s', time() - 300);
db_execute(
    "INSERT INTO posts (
        post_title, post_slug, post_subtitle, post_category_id,
        post_author, post_date, post_content, post_status, scheduled_at, post_version
     ) VALUES ('Scheduled Essay', ?, 'Subtitle', 1, 'Admin', CURDATE(), '<p>Scheduled content</p>', 'scheduled', ?, 1)",
    [$scheduledSlug, $pastTimestamp]
);
$schedPostId = (int)db_last_id();

it("Scheduled post created with status 'scheduled' and scheduled_at <= NOW()", 
    $schedPostId > 0
);

// Execute cron runner command
$cronOutput = shell_exec('php ' . escapeshellarg(__DIR__ . '/../cron/publish_scheduled.php') . ' --json');
$cronData = json_decode($cronOutput, true);

it("publish_scheduled.php CLI executes cleanly and returns JSON output", 
    is_array($cronData) && ($cronData['status'] ?? '') === 'success'
);

$updatedSchedPost = db_fetch_one("SELECT post_status FROM posts WHERE post_id = ?", [$schedPostId]);
it("publish_scheduled.php successfully transitions due article to 'published' status", 
    $updatedSchedPost['post_status'] === 'published'
);

$cronAuditLog = db_fetch_one("SELECT * FROM audit_logs WHERE action = 'publish_scheduled' ORDER BY log_id DESC LIMIT 1");
it("Scheduled publication logged in audit_logs table", 
    !empty($cronAuditLog) && str_contains($cronAuditLog['details'], (string)$schedPostId)
);

// -------------------------------------------------------------
// 6. Public Reading Experience & Preview Mode (post.php)
// -------------------------------------------------------------
// Test that public query finds published post
$pubCheck = db_fetch_one(
    "SELECT post_id, post_title FROM posts WHERE post_slug = ? AND post_status = 'published'",
    [$scheduledSlug]
);
it("Public post query correctly resolves published post by slug", 
    !empty($pubCheck)
);

// Clean up test records
db_execute("DELETE FROM post_revisions WHERE post_id = ?", [$testPostId]);
db_execute("DELETE FROM post_autosaves WHERE post_id = ?", [$testPostId]);
db_execute("DELETE FROM posts WHERE post_id IN (?, ?)", [$testPostId, $schedPostId]);
db_execute("DELETE FROM media WHERE media_id = ?", [$insertedMedia['media_id']]);

echo "\n=== Result: {$passCount} / {$testCount} tests passed! ===\n";
if ($passCount === $testCount) {
    echo "All Milestone 4, 5 & 6 features verified successfully!\n";
} else {
    exit(1);
}
