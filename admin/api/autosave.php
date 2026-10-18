<?php
/**
 * WordWise Admin CMS - Draft Autosave & Concurrency API
 * Handles live draft updates, optimistic locking concurrency validation,
 * draft retrieval, and revision checkpointing.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/blocks.php';

// 1. Session & Permission Verification
if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'code' => 'unauthorized', 'message' => 'Session expired. Please log in.']);
    exit;
}

if (!auth_can('edit_post')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 'forbidden', 'message' => 'You lack permission to write or edit articles.']);
    exit;
}

// 2. Parse Input (support JSON body or standard form POST)
$rawInput = file_get_contents('php://input');
$input = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if (empty($input)) {
    $input = $_POST;
}

// 3. CSRF Verification
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($input['csrf_token'] ?? '');
if (!csrf_verify($csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'code' => 'csrf_error', 'message' => 'Security token invalid or expired.']);
    exit;
}

$action = $input['action'] ?? 'autosave';
$postId = isset($input['post_id']) && (int)$input['post_id'] > 0 ? (int)$input['post_id'] : 0;
$userId = (int)auth_id();
$currentUser = auth_user();

// Fetch post record if existing post
$post = null;
if ($postId > 0) {
    $post = db_fetch_one("SELECT * FROM posts WHERE post_id = ?", [$postId]);
    if (!$post) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'code' => 'not_found', 'message' => 'Post not found.']);
        exit;
    }

    // Role check: contributors can only edit their own posts
    if ($currentUser['role'] !== 'admin' && $currentUser['role'] !== 'editor') {
        if ($post['post_author'] !== $currentUser['full_name'] && $post['post_author'] !== $currentUser['username']) {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'code' => 'forbidden', 'message' => 'Cannot edit an essay authored by another writer.']);
            exit;
        }
    }
}

// 4. Optimistic Concurrency Check (prevents concurrent overwrite loss)
if ($postId > 0 && isset($input['client_version']) && $input['client_version'] !== '') {
    $clientVersion = (int)$input['client_version'];
    $serverVersion = (int)($post['post_version'] ?? 1);

    if ($serverVersion > $clientVersion) {
        http_response_code(409);
        echo json_encode([
            'status' => 'conflict',
            'code' => 'version_collision',
            'message' => "Concurrency collision: This article was updated elsewhere (Server v{$serverVersion} vs Local v{$clientVersion}).",
            'server_version' => $serverVersion,
            'client_version' => $clientVersion,
            'updated_at' => $post['updated_at']
        ]);
        exit;
    }
}

// 5. Handle Action Dispatch
switch ($action) {
    case 'get_draft':
        $query = $postId > 0
            ? "SELECT * FROM post_autosaves WHERE user_id = ? AND post_id = ? ORDER BY updated_at DESC LIMIT 1"
            : "SELECT * FROM post_autosaves WHERE user_id = ? AND (post_id IS NULL OR post_id = 0) ORDER BY updated_at DESC LIMIT 1";
        $params = $postId > 0 ? [$userId, $postId] : [$userId];
        $draft = db_fetch_one($query, $params);

        echo json_encode([
            'status' => 'success',
            'has_draft' => !empty($draft),
            'draft' => $draft ? [
                'autosave_id' => $draft['autosave_id'],
                'post_id' => $draft['post_id'],
                'title' => $draft['title'],
                'content_json' => $draft['content_json'],
                'updated_at' => $draft['updated_at']
            ] : null
        ]);
        break;

    case 'discard_draft':
        if ($postId > 0) {
            db_execute("DELETE FROM post_autosaves WHERE user_id = ? AND post_id = ?", [$userId, $postId]);
        } else {
            db_execute("DELETE FROM post_autosaves WHERE user_id = ? AND (post_id IS NULL OR post_id = 0)", [$userId]);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Autosaved draft discarded.'
        ]);
        break;

    case 'save_revision':
        if ($postId <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Cannot create a revision checkpoint for an unsaved post.']);
            exit;
        }

        $title = trim($input['title'] ?? ($post['post_title'] ?? 'Untitled Article'));
        $contentJson = $input['content_json'] ?? ($post['post_content_json'] ?? '');
        $contentHtml = render_blocks($contentJson, $post['post_content'] ?? '');

        db_execute(
            "INSERT INTO post_revisions (post_id, user_id, title, content_json, content_html, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
            [$postId, $userId, $title, $contentJson, $contentHtml]
        );
        $revId = (int)db_last_id();

        echo json_encode([
            'status' => 'success',
            'revision_id' => $revId,
            'message' => "Revision checkpoint #{$revId} saved."
        ]);
        break;

    case 'autosave':
    default:
        $title = trim($input['title'] ?? '');
        $contentJson = $input['content_json'] ?? '';

        // Validate JSON integrity if provided
        if ($contentJson !== '') {
            $parsed = json_decode($contentJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Malformed block JSON format.']);
                exit;
            }
        }

        // Upsert into post_autosaves
        $findQuery = $postId > 0
            ? "SELECT autosave_id FROM post_autosaves WHERE user_id = ? AND post_id = ? LIMIT 1"
            : "SELECT autosave_id FROM post_autosaves WHERE user_id = ? AND (post_id IS NULL OR post_id = 0) LIMIT 1";
        $findParams = $postId > 0 ? [$userId, $postId] : [$userId];
        $existing = db_fetch_one($findQuery, $findParams);

        if ($existing) {
            db_execute(
                "UPDATE post_autosaves SET title = ?, content_json = ?, updated_at = NOW() WHERE autosave_id = ?",
                [$title, $contentJson, $existing['autosave_id']]
            );
            $autosaveId = $existing['autosave_id'];
        } else {
            $savePostId = $postId > 0 ? $postId : null;
            db_execute(
                "INSERT INTO post_autosaves (user_id, post_id, title, content_json, updated_at) VALUES (?, ?, ?, ?, NOW())",
                [$userId, $savePostId, $title, $contentJson]
            );
            $autosaveId = (int)db_last_id();
        }

        echo json_encode([
            'status' => 'success',
            'autosave_id' => $autosaveId,
            'post_id' => $postId,
            'saved_at' => date('H:i:s'),
            'timestamp' => time(),
            'message' => 'Draft saved.'
        ]);
        break;
}
