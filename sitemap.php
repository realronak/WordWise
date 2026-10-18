<?php
/**
 * WordWise XML Sitemap Generator
 */
require_once __DIR__ . '/includes/db.php';

$baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$posts = db_fetch_all("SELECT post_slug, updated_at FROM posts WHERE post_status = 'published' ORDER BY post_date DESC");
$categories = db_fetch_all("SELECT cat_slug FROM categories");

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc><?php echo $baseUrl; ?>/</loc>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc><?php echo $baseUrl; ?>/articles.php</loc>
    <changefreq>daily</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc><?php echo $baseUrl; ?>/categories.php</loc>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  <url>
    <loc><?php echo $baseUrl; ?>/about.php</loc>
    <changefreq>monthly</changefreq>
    <priority>0.5</priority>
  </url>

  <?php foreach ($categories as $c): ?>
    <url>
      <loc><?php echo $baseUrl; ?>/articles.php?category=<?php echo urlencode($c['cat_slug']); ?></loc>
      <changefreq>weekly</changefreq>
      <priority>0.6</priority>
    </url>
  <?php endforeach; ?>

  <?php foreach ($posts as $p): ?>
    <url>
      <loc><?php echo $baseUrl; ?>/post.php?slug=<?php echo urlencode($p['post_slug']); ?></loc>
      <lastmod><?php echo date('Y-m-d', strtotime($p['updated_at'] ?? 'now')); ?></lastmod>
      <changefreq>monthly</changefreq>
      <priority>0.8</priority>
    </url>
  <?php endforeach; ?>
</urlset>
