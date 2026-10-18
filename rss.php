<?php
/**
 * WordWise RSS 2.0 Syndication Feed
 */
require_once __DIR__ . '/includes/db.php';

$siteName = get_setting('site_name', 'WordWise');
$siteDesc = get_setting('site_description', 'An editorial publication.');
$baseUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$posts = db_fetch_all(
    "SELECT p.*, c.cat_title 
     FROM posts p 
     LEFT JOIN categories c ON p.post_category_id = c.cat_id 
     WHERE p.post_status = 'published' 
     ORDER BY p.post_date DESC LIMIT 20"
);

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo htmlspecialchars($siteName, ENT_XML1); ?></title>
    <link><?php echo $baseUrl; ?>/</link>
    <description><?php echo htmlspecialchars($siteDesc, ENT_XML1); ?></description>
    <language>en-us</language>
    <atom:link href="<?php echo $baseUrl; ?>/rss.php" rel="self" type="application/rss+xml" />
    <lastBuildDate><?php echo date(DATE_RSS); ?></lastBuildDate>

    <?php foreach ($posts as $p): ?>
      <item>
        <title><?php echo htmlspecialchars($p['post_title'], ENT_XML1); ?></title>
        <link><?php echo $baseUrl; ?>/post.php?slug=<?php echo urlencode($p['post_slug']); ?></link>
        <guid isPermaLink="true"><?php echo $baseUrl; ?>/post.php?slug=<?php echo urlencode($p['post_slug']); ?></guid>
        <pubDate><?php echo date(DATE_RSS, strtotime($p['post_date'])); ?></pubDate>
        <author><?php echo htmlspecialchars($p['post_author'], ENT_XML1); ?></author>
        <?php if (!empty($p['cat_title'])): ?>
          <category><?php echo htmlspecialchars($p['cat_title'], ENT_XML1); ?></category>
        <?php endif; ?>
        <description><?php echo htmlspecialchars(mb_strimwidth(strip_tags($p['post_content']), 0, 300, '...'), ENT_XML1); ?></description>
      </item>
    <?php endforeach; ?>
  </channel>
</rss>
