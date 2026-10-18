<?php
/**
 * WordWise Admin CMS - Media Library & Async Upload API
 * Compatible with Editor.js Image Tool and interactive modal media browser.
 */

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

// Verify Authentication
if (!auth_check()) {
    http_response_code(401);
    echo json_encode(['success' => 0, 'status' => 'error', 'message' => 'Authentication required.']);
    exit;
}

$targetDir = dirname(__DIR__, 2) . '/images';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

// -------------------------------------------------------------
// GET: Browse & Search Media Library
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = trim($_GET['q'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(6, (int)($_GET['limit'] ?? 24)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(file_name LIKE ? OR alt_text LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $countRow = db_fetch_one("SELECT COUNT(*) as total FROM media {$whereSql}", $params);
    $total = (int)($countRow['total'] ?? 0);

    $items = db_fetch_all(
        "SELECT media_id, file_name, file_path, file_type, file_size, alt_text, created_at 
         FROM media {$whereSql} 
         ORDER BY created_at DESC 
         LIMIT {$limit} OFFSET {$offset}",
        $params
    );

    $formatted = [];
    foreach ($items as $item) {
        $sizeBytes = (int)$item['file_size'];
        $sizeFormatted = $sizeBytes < 1024 * 1024 
            ? round($sizeBytes / 1024, 1) . ' KB' 
            : round($sizeBytes / (1024 * 1024), 2) . ' MB';

        $url = str_starts_with($item['file_path'], '/') ? $item['file_path'] : '/' . $item['file_path'];

        $formatted[] = [
            'id' => (int)$item['media_id'],
            'file_name' => $item['file_name'],
            'url' => $url,
            'file_type' => $item['file_type'],
            'file_size' => $sizeBytes,
            'size_formatted' => $sizeFormatted,
            'alt_text' => $item['alt_text'] ?? '',
            'created_at' => $item['created_at']
        ];
    }

    echo json_encode([
        'success' => 1,
        'status' => 'success',
        'items' => $formatted,
        'total' => $total,
        'page' => $page,
        'pages' => ceil($total / $limit) ?: 1
    ]);
    exit;
}

// -------------------------------------------------------------
// POST: File Upload or By-URL Fetching
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!auth_can('upload_media')) {
        http_response_code(403);
        echo json_encode(['success' => 0, 'status' => 'error', 'message' => 'Permission denied: Cannot upload assets.']);
        exit;
    }

    // CSRF verification
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!csrf_verify($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => 0, 'status' => 'error', 'message' => 'CSRF verification failed.']);
        exit;
    }

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
        'video/mp4'  => 'mp4',
        'video/webm' => 'webm',
    ];

    // Case 1: Upload via File
    $fileKey = isset($_FILES['image']) ? 'image' : (isset($_FILES['file']) ? 'file' : (isset($_FILES['media_file']) ? 'media_file' : null));

    if ($fileKey && isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileKey];
        $maxBytes = 15 * 1024 * 1024; // 15MB limit

        if ($file['size'] > $maxBytes) {
            http_response_code(400);
            echo json_encode(['success' => 0, 'message' => 'File exceeds 15MB limit.']);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowedMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => 0, 'message' => "Unsupported format ({$mime}). Only JPEG, PNG, WEBP, GIF, and MP4/WEBM are permitted."]);
            exit;
        }

        $ext = $allowedMimes[$mime];
        $prefix = str_starts_with($mime, 'video/') ? 'vid_' : 'img_';
        $generatedName = $prefix . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $generatedName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            http_response_code(500);
            echo json_encode(['success' => 0, 'message' => 'Failed to write file to filesystem.']);
            exit;
        }

        chmod($targetPath, 0644);
        $altText = trim($_POST['alt_text'] ?? pathinfo($file['name'], PATHINFO_FILENAME));

        db_execute(
            "INSERT INTO media (file_name, file_path, file_type, file_size, alt_text, uploaded_by, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$generatedName, 'images/' . $generatedName, $mime, $file['size'], $altText, auth_id()]
        );
        $mediaId = (int)db_last_id();

        db_execute(
            "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, 'upload_media', ?, ?)",
            [auth_id(), "Uploaded asset '{$generatedName}' ({$mime})", $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']
        );

        echo json_encode([
            'success' => 1,
            'file' => [
                'url' => '/images/' . $generatedName,
                'name' => $generatedName,
                'media_id' => $mediaId,
                'size' => $file['size'],
                'alt' => $altText
            ]
        ]);
        exit;
    }

    // Case 2: Upload By URL (Editor.js uploadByUrl)
    $urlToFetch = trim($_POST['url'] ?? '');
    if ($urlToFetch !== '') {
        if (!filter_var($urlToFetch, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $urlToFetch)) {
            http_response_code(400);
            echo json_encode(['success' => 0, 'message' => 'Invalid image URL.']);
            exit;
        }

        // SSRF protection: parse host and resolve IP
        $host = parse_url($urlToFetch, PHP_URL_HOST);
        $ip = gethostbyname($host);
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            http_response_code(400);
            echo json_encode(['success' => 0, 'message' => 'Private network URLs cannot be fetched.']);
            exit;
        }

        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'user_agent' => 'WordWise-Editorial-CMS/1.0',
                'max_redirects' => 3
            ]
        ]);

        $content = @file_get_contents($urlToFetch, false, $ctx);
        if ($content === false || strlen($content) === 0) {
            http_response_code(400);
            echo json_encode(['success' => 0, 'message' => 'Could not fetch image from remote URL.']);
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $content);
        finfo_close($finfo);

        if (!isset($allowedMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => 0, 'message' => "Remote file is not an allowed image format ({$mime})."]);
            exit;
        }

        $ext = $allowedMimes[$mime];
        $generatedName = 'img_url_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $targetDir . '/' . $generatedName;

        file_put_contents($targetPath, $content);
        chmod($targetPath, 0644);
        $fileSize = strlen($content);

        db_execute(
            "INSERT INTO media (file_name, file_path, file_type, file_size, alt_text, uploaded_by, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$generatedName, 'images/' . $generatedName, $mime, $fileSize, 'Remote image asset', auth_id()]
        );
        $mediaId = (int)db_last_id();

        echo json_encode([
            'success' => 1,
            'file' => [
                'url' => '/images/' . $generatedName,
                'name' => $generatedName,
                'media_id' => $mediaId,
                'size' => $fileSize,
                'alt' => 'Remote image asset'
            ]
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => 0, 'message' => 'No file or valid URL provided for upload.']);
    exit;
}
