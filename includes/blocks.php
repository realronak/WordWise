<?php
/**
 * WordWise Block Content Parser & Sanitizer
 * Canonical JSON block-based storage -> Safe semantic editorial HTML
 */

require_once __DIR__ . '/security.php';

/**
 * Validate and render canonical Editor.js block JSON to sanitized HTML
 */
function render_blocks(?string $json, string $fallbackHtml = ''): string {
    if (empty($json)) {
        return sanitize_html($fallbackHtml);
    }

    $data = json_decode($json, true);
    if (!is_array($data) || empty($data['blocks']) || !is_array($data['blocks'])) {
        return sanitize_html($fallbackHtml);
    }

    $htmlParts = [];
    foreach ($data['blocks'] as $block) {
        $type = $block['type'] ?? '';
        $blockData = $block['data'] ?? [];

        switch ($type) {
            case 'paragraph':
                $text = trim($blockData['text'] ?? '');
                if ($text !== '') {
                    $htmlParts[] = '<p>' . sanitize_html($text) . '</p>';
                }
                break;

            case 'header':
                $level = (int)($blockData['level'] ?? 2);
                if ($level < 2 || $level > 4) $level = 2;
                $text = trim($blockData['text'] ?? '');
                if ($text !== '') {
                    $cleanText = sanitize_html($text);
                    $anchor = slugify(strip_tags($cleanText));
                    $htmlParts[] = "<h{$level} id=\"{$anchor}\">{$cleanText}</h{$level}>";
                }
                break;

            case 'list':
                $style = ($blockData['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
                $items = $blockData['items'] ?? [];
                if (!empty($items) && is_array($items)) {
                    $listHtml = "<{$style}>";
                    foreach ($items as $item) {
                        $itemText = is_string($item) ? $item : ($item['content'] ?? '');
                        if (trim($itemText) !== '') {
                            $listHtml .= '<li>' . sanitize_html($itemText) . '</li>';
                        }
                    }
                    $listHtml .= "</{$style}>";
                    $htmlParts[] = $listHtml;
                }
                break;

            case 'quote':
                $text = trim($blockData['text'] ?? '');
                $caption = trim($blockData['caption'] ?? '');
                if ($text !== '') {
                    $quoteHtml = '<blockquote><p>' . sanitize_html($text) . '</p>';
                    if ($caption !== '') {
                        $quoteHtml .= '<cite class="block mt-2 font-mono text-xs text-ink-500">— ' . e($caption) . '</cite>';
                    }
                    $quoteHtml .= '</blockquote>';
                    $htmlParts[] = $quoteHtml;
                }
                break;

            case 'delimiter':
                $htmlParts[] = '<hr class="my-10 border-paper-200 dark:border-paper-800">';
                break;

            case 'callout':
            case 'warning':
                $title = trim($blockData['title'] ?? '');
                $message = trim($blockData['message'] ?? $blockData['text'] ?? '');
                $calloutType = in_array($blockData['type'] ?? '', ['tip', 'warning', 'info'], true) ? $blockData['type'] : 'info';
                
                $borderClass = 'border-l-4 border-accent bg-paper-100 dark:bg-paper-900';
                if ($calloutType === 'warning') {
                    $borderClass = 'border-l-4 border-amber-500 bg-amber-500/10 text-amber-900 dark:text-amber-200';
                } elseif ($calloutType === 'tip') {
                    $borderClass = 'border-l-4 border-emerald-500 bg-emerald-500/10 text-emerald-900 dark:text-emerald-200';
                }

                $box = "<div class=\"p-4 my-6 rounded-r-sm font-mono text-xs space-y-1.5 {$borderClass}\">";
                if ($title !== '') {
                    $box .= "<div class=\"font-bold uppercase tracking-wider text-[11px]\">" . e($title) . "</div>";
                }
                $box .= "<div>" . sanitize_html($message) . "</div></div>";
                $htmlParts[] = $box;
                break;

            case 'image':
                $url = trim($blockData['file']['url'] ?? $blockData['url'] ?? '');
                $caption = trim($blockData['caption'] ?? '');
                $alt = trim($blockData['alt'] ?? $caption ?? 'Article illustration');

                if ($url !== '') {
                    // Sanitize path (must be local /images/... or http/https)
                    if (str_starts_with($url, '/') || filter_var($url, FILTER_VALIDATE_URL)) {
                        $fig = '<figure class="my-8 space-y-2">';
                        $fig .= '<img src="' . e($url) . '" alt="' . e($alt) . '" class="w-full rounded border border-paper-200 dark:border-paper-800" loading="lazy">';
                        if ($caption !== '') {
                            $fig .= '<figcaption class="text-center font-mono text-xs text-ink-400 italic">' . e($caption) . '</figcaption>';
                        }
                        $fig .= '</figure>';
                        $htmlParts[] = $fig;
                    }
                }
                break;

            case 'code':
                $code = $blockData['code'] ?? '';
                $lang = strtolower(trim($blockData['language'] ?? 'plaintext'));
                $validLangs = ['php', 'javascript', 'typescript', 'python', 'sql', 'bash', 'html', 'css', 'json', 'yaml', 'plaintext'];
                if (!in_array($lang, $validLangs, true)) $lang = 'plaintext';

                if ($code !== '') {
                    $codeId = 'code_' . bin2hex(random_bytes(4));
                    $block = '<div class="my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800 bg-[#0d1117] text-white font-mono text-xs not-prose">';
                    $block .= '<div class="px-4 py-2 bg-[#161b22] border-b border-[#30363d] flex items-center justify-between text-[11px] text-gray-400">';
                    $block .= '<span class="uppercase tracking-wider font-semibold">' . e($lang) . '</span>';
                    $block .= '<button type="button" onclick="navigator.clipboard.writeText(document.getElementById(\'' . $codeId . '\').textContent); this.textContent=\'COPIED!\'; setTimeout(() => this.textContent=\'COPY\', 2000);" class="hover:text-white transition-colors">COPY</button>';
                    $block .= '</div>';
                    $block .= '<pre class="p-4 overflow-x-auto"><code id="' . $codeId . '" class="language-' . e($lang) . '">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></pre>';
                    $block .= '</div>';
                    $htmlParts[] = $block;
                }
                break;

            case 'video':
            case 'embed':
                $url = trim($blockData['embed'] ?? $blockData['source'] ?? $blockData['url'] ?? '');
                $caption = trim($blockData['caption'] ?? '');

                if ($url !== '') {
                    $embedHtml = '';
                    // Allowlisted YouTube embeds
                    if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([a-zA-Z0-9_-]{11})#i', $url, $matches)) {
                        $videoId = $matches[1];
                        $embedHtml = '<div class="aspect-video w-full my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800">';
                        $embedHtml .= '<iframe src="https://www.youtube-nocookie.com/embed/' . e($videoId) . '" class="w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                        $embedHtml .= '</div>';
                    } elseif (preg_match('#vimeo\.com/(?:video/)?([0-9]+)#i', $url, $matches)) {
                        $videoId = $matches[1];
                        $embedHtml = '<div class="aspect-video w-full my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800">';
                        $embedHtml .= '<iframe src="https://player.vimeo.com/video/' . e($videoId) . '" class="w-full h-full" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                        $embedHtml .= '</div>';
                    } elseif (preg_match('/\.(mp4|webm)$/i', $url)) {
                        // Direct video file
                        $embedHtml = '<div class="my-6 rounded overflow-hidden border border-paper-200 dark:border-paper-800">';
                        $embedHtml .= '<video controls class="w-full" preload="metadata"><source src="' . e($url) . '">Your browser does not support HTML5 video.</video>';
                        $embedHtml .= '</div>';
                    }

                    if ($embedHtml !== '') {
                        if ($caption !== '') {
                            $embedHtml .= '<p class="text-center font-mono text-xs text-ink-400 italic -mt-4 mb-6">' . e($caption) . '</p>';
                        }
                        $htmlParts[] = $embedHtml;
                    }
                }
                break;
        }
    }

    return implode("\n\n", $htmlParts);
}

/**
 * Convert legacy HTML content into Editor.js block JSON structure
 */
function html_to_blocks(string $html): string {
    if (trim($html) === '') {
        return json_encode(['time' => time() * 1000, 'blocks' => [], 'version' => '2.30.0']);
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $blocks = [];
    $container = $dom->getElementsByTagName('div')->item(0);

    if ($container) {
        foreach ($container->childNodes as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) continue;
            $tag = strtolower($node->nodeName);

            if (in_array($tag, ['h1', 'h2', 'h3', 'h4'], true)) {
                $level = (int)substr($tag, 1);
                $blocks[] = [
                    'type' => 'header',
                    'data' => [
                        'text'  => $node->textContent,
                        'level' => max(2, min(4, $level))
                    ]
                ];
            } elseif ($tag === 'p') {
                $blocks[] = [
                    'type' => 'paragraph',
                    'data' => ['text' => $node->textContent]
                ];
            } elseif ($tag === 'blockquote') {
                $blocks[] = [
                    'type' => 'quote',
                    'data' => [
                        'text'    => $node->textContent,
                        'caption' => ''
                    ]
                ];
            } elseif ($tag === 'pre') {
                $blocks[] = [
                    'type' => 'code',
                    'data' => [
                        'code'     => $node->textContent,
                        'language' => 'plaintext'
                    ]
                ];
            } elseif (in_array($tag, ['ul', 'ol'], true)) {
                $items = [];
                foreach ($node->childNodes as $li) {
                    if ($li->nodeName === 'li') {
                        $items[] = $li->textContent;
                    }
                }
                $blocks[] = [
                    'type' => 'list',
                    'data' => [
                        'style' => $tag === 'ol' ? 'ordered' : 'unordered',
                        'items' => $items
                    ]
                ];
            } elseif ($tag === 'hr') {
                $blocks[] = [
                    'type' => 'delimiter',
                    'data' => []
                ];
            }
        }
    }

    if (empty($blocks)) {
        $blocks[] = [
            'type' => 'paragraph',
            'data' => ['text' => strip_tags($html)]
        ];
    }

    return json_encode([
        'time'    => time() * 1000,
        'blocks'  => $blocks,
        'version' => '2.30.0'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
