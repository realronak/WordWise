<?php
/**
 * WordWise Editorial Post & Media Seeder
 * Populates authentic, long-form editorial dispatches across diverse categories
 * with canonical block JSON, syntax-highlighted code, callouts, and Unsplash artwork.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/blocks.php';

$admin = db_fetch_one("SELECT user_id, full_name, username FROM users WHERE username = 'admin'");
$authorName = $admin['full_name'] ?? 'Ronak Rathod';
$adminId = $admin['user_id'] ?? 1;

// 1. Ensure Categories Exist
$categoriesToEnsure = [
    [
        'title' => 'Web Architecture',
        'slug' => 'web-architecture',
        'desc' => 'Durable systems, minimal software design, and resilient web infrastructure.'
    ],
    [
        'title' => 'PHP & Backend',
        'slug' => 'php',
        'desc' => 'Modern PHP 8.5 engineering, asynchronous concurrency, and zero-allocation database craft.'
    ],
    [
        'title' => 'Editorial & Design',
        'slug' => 'editorial-design',
        'desc' => 'Typewriter typography, typographic rhythm, and restrained minimalist aesthetics.'
    ],
    [
        'title' => 'Artificial Intelligence',
        'slug' => 'artificial-intelligence',
        'desc' => 'Autonomous agents, tool execution loops, and practical neural architectures.'
    ],
    [
        'title' => 'Linux & DevOps',
        'slug' => 'linux-devops',
        'desc' => 'Omarchy Linux, sovereign self-hosting, Apache optimization, and UNIX craft.'
    ]
];

$catMap = [];
foreach ($categoriesToEnsure as $c) {
    db_execute(
        "INSERT INTO categories (cat_title, cat_slug, cat_description) 
         VALUES (?, ?, ?) 
         ON DUPLICATE KEY UPDATE cat_title = VALUES(cat_title), cat_description = VALUES(cat_description)",
        [$c['title'], $c['slug'], $c['desc']]
    );
    $row = db_fetch_one("SELECT cat_id FROM categories WHERE cat_slug = ?", [$c['slug']]);
    $catMap[$c['slug']] = (int)$row['cat_id'];
}

// 2. Register Downloaded Images in Media Library
$imagesToRegister = [
    [
        'file' => 'img_minimalist_software.jpg',
        'alt' => 'Minimalist wooden desk with laptop, notebook and mechanical pencil',
        'type' => 'image/jpeg'
    ],
    [
        'file' => 'img_php_modern.jpg',
        'alt' => 'High-throughput server rack infrastructure glowing with network fiber lights',
        'type' => 'image/jpeg'
    ],
    [
        'file' => 'img_typewriter_editorial.jpg',
        'alt' => 'Macro close-up of vintage manual typewriter mechanism and mechanical keys',
        'type' => 'image/jpeg'
    ],
    [
        'file' => 'img_ai_agents.jpg',
        'alt' => 'Neural network visualization displaying interconnected luminous knowledge nodes',
        'type' => 'image/jpeg'
    ],
    [
        'file' => 'img_linux_omarchy.jpg',
        'alt' => 'Omarchy Linux workstation displaying dual monochrome terminal windows',
        'type' => 'image/jpeg'
    ]
];

foreach ($imagesToRegister as $img) {
    $fullPath = __DIR__ . '/../images/' . $img['file'];
    $fileSize = file_exists($fullPath) ? filesize($fullPath) : 100000;

    $exists = db_fetch_one("SELECT media_id FROM media WHERE file_name = ?", [$img['file']]);
    if (!$exists) {
        db_execute(
            "INSERT INTO media (file_name, file_path, file_type, file_size, alt_text, uploaded_by, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [$img['file'], 'images/' . $img['file'], $img['type'], $fileSize, $img['alt'], $adminId]
        );
    }
}

// 3. Define the 5 Detailed Editorial Articles
$articles = [
    // -------------------------------------------------------------
    // Article 1: Minimalist Software
    // -------------------------------------------------------------
    [
        'title' => 'The Philosophy of Minimalist Software: Why Less Is More in 2026',
        'slug' => 'the-philosophy-of-minimalist-software',
        'subtitle' => 'Rejecting accidental complexity in favor of durable foundations, native browser primitives, and zero-runtime bloat.',
        'excerpt' => 'An essay exploring how modern web engineering became buried under thousands of transient dependencies, and why returning to semantic simplicity creates superior, lasting software.',
        'category_slug' => 'web-architecture',
        'image' => 'img_minimalist_software.jpg',
        'status' => 'published',
        'is_featured' => 1,
        'is_pinned' => 1,
        'tags' => 'minimalism, architecture, web-craft, engineering-philosophy',
        'seo_title' => 'The Philosophy of Minimalist Software — WordWise',
        'seo_desc' => 'Discover why minimalist software design, semantic HTML, and zero-dependency architectures outlast bloated transient frameworks in 2026.',
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'We live in an era where deploying a simple text blog frequently requires multi-gigabyte build containers, three levels of virtual DOM reconciliation, and hundreds of transient npm dependencies that deprecate every six months. Somewhere along the journey toward developer convenience, we confused technological sophistication with architectural progress.'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'The Creeping Disease of Accidental Complexity',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Software engineering distinguishes between two fundamental forms of difficulty: essential complexity (the intrinsic challenges of the problem domain) and accidental complexity (the self-inflicted friction introduced by our tooling, abstractions, and layers of indirection). In modern web development, accidental complexity vastly outweighs the essential.'
                ]
            ],
            [
                'type' => 'quote',
                'data' => [
                    'text' => 'There are two ways of constructing a software design: One way is to make it so simple that there are obviously no deficiencies, and the other way is to make it so complicated that there are no obvious deficiencies.',
                    'caption' => 'C.A.R. Hoare, 1980 Turing Award Lecture'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'The Power of Native Browser Primitives',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Every browser running today contains a high-performance, battle-tested layout engine, a hardware-accelerated CSS parser, and robust HTTP cache semantics. When we honor the native medium—using semantic HTML, compiled utility CSS, and native ES modules—our websites load instantaneously and remain functional indefinitely.'
                ]
            ],
            [
                'type' => 'code',
                'data' => [
                    'language' => 'php',
                    'code' => "// Clean, parameterized data access without 10 layers of ORM abstraction\nfunction fetch_dispatch(PDO \$pdo, string \$slug): ?array {\n    \$stmt = \$pdo->prepare('SELECT post_id, post_title, post_content FROM posts WHERE post_slug = ? LIMIT 1');\n    \$stmt->execute([\$slug]);\n    return \$stmt->fetch(PDO::FETCH_ASSOC) ?: null;\n}"
                ]
            ],
            [
                'type' => 'warning',
                'data' => [
                    'title' => 'Architectural Maxim',
                    'message' => 'Every dependency you eliminate is a future security advisory you will never have to mitigate and a breaking update you will never have to debug.',
                    'type' => 'tip'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'Principles for Durable Digital Systems',
                    'level' => 3
                ]
            ],
            [
                'type' => 'list',
                'data' => [
                    'style' => 'unordered',
                    'items' => [
                        'Zero-Runtime Framework Lock-In: Templates produce standard semantic HTML that renders identically in any browser.',
                        'Self-Documenting Relational Schemas: MariaDB tables with explicit foreign keys and constraints over opaque document blobs.',
                        'Inspectable State: Storing readable structured JSON alongside pre-rendered HTML for instant reads.',
                        'Sovereign Infrastructure: Running on standard Linux environments without proprietary cloud lock-in.'
                    ]
                ]
            ],
            [
                'type' => 'delimiter',
                'data' => []
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Minimalism is not about deprivation; it is about intentionality. When you strip away the superfluous, what remains is the pure essence of your work—words, typography, and ideas.'
                ]
            ]
        ]
    ],

    // -------------------------------------------------------------
    // Article 2: PHP 8.5 & Backend Engineering
    // -------------------------------------------------------------
    [
        'title' => 'Deep Dive: Building High-Performance Web Systems with Modern PHP 8.5',
        'slug' => 'building-high-performance-systems-with-php-8',
        'subtitle' => 'Exploring JIT compilation, OPcache preloading, typed fiber concurrency, and zero-allocation database pooling.',
        'excerpt' => 'A technical investigation into modern PHP 8.5 internals, demonstrating how native typing, JIT performance, and prepared statements make PHP a formidable backend powerhouse.',
        'category_slug' => 'php',
        'image' => 'img_php_modern.jpg',
        'status' => 'published',
        'is_featured' => 0,
        'is_pinned' => 0,
        'tags' => 'php, backend, performance, mariadb, optimization',
        'seo_title' => 'High-Performance Web Systems with Modern PHP 8.5 — WordWise',
        'seo_desc' => 'A deep dive into PHP 8.5 features, JIT compilation, OPcache preloading, and high-throughput MariaDB prepared statements.',
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'The persistent stereotype of PHP as an unoptimized, dynamic scripting language belongs to a bygone decade. With the introduction of PHP 8.0 through 8.5, the Zend Engine underwent a fundamental renaissance: real Just-In-Time (JIT) compilation, strict static typing, property promotion, and native fibers.'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'OPcache Preloading & Memory Resident Code',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'One of the most consequential advancements in modern PHP is OPcache Preloading. Introduced in PHP 7.4 and refined in subsequent releases, preloading compiles your entire application graph into persistent shared memory upon server boot, completely eliminating file resolution and compilation overhead on incoming HTTP requests.'
                ]
            ],
            [
                'type' => 'code',
                'data' => [
                    'language' => 'php',
                    'code' => "// Strict typing and readonly class declarations in modern PHP\nfinal readonly class ArticlePayload {\n    public function __construct(\n        public int \$id,\n        public string \$title,\n        public string \$slug,\n        public DateTimeImmutable \$publishedAt,\n        public array \$tags = []\n    ) {}\n}"
                ]
            ],
            [
                'type' => 'warning',
                'data' => [
                    'title' => 'Production Optimization Tip',
                    'message' => 'Ensure opcache.validate_timestamps=0 in production /etc/php/php.ini for maximum CPU efficiency. Use graceful Apache reloads (systemctl reload httpd) during deployments.',
                    'type' => 'info'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'Prepared Statements & Connection Architecture',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'When paired with MariaDB 12 over a local UNIX socket (`/run/mysqld/mysqld.sock`), PHP PDO prepared statements execute in single-digit microseconds. Because query parsing and plan caching happen once on the server, the database engine executes parameterized statements with optimal indexing and zero SQL injection vulnerability.'
                ]
            ],
            [
                'type' => 'quote',
                'data' => [
                    'text' => 'Simplicity is about subtracting the obvious and adding the meaningful.',
                    'caption' => 'John Maeda, The Laws of Simplicity'
                ]
            ]
        ]
    ],

    // -------------------------------------------------------------
    // Article 3: Typography as Interface
    // -------------------------------------------------------------
    [
        'title' => 'Typography as Interface: Crafting the Typewriter Aesthetic',
        'slug' => 'typography-as-interface-the-typewriter-aesthetic',
        'subtitle' => 'The optical rhythm of JetBrains Mono, restrained margins, and the tactile honesty of mechanical publishing.',
        'excerpt' => 'Examining the timeless beauty of typewriter-inspired digital publications, exploring how fixed-pitch typography establishes trust, structure, and calm reading focus.',
        'category_slug' => 'editorial-design',
        'image' => 'img_typewriter_editorial.jpg',
        'status' => 'published',
        'is_featured' => 0,
        'is_pinned' => 1,
        'tags' => 'typography, design, jetbrains-mono, typewriter, aesthetics',
        'seo_title' => 'Typography as Interface: The Typewriter Aesthetic — WordWise',
        'seo_desc' => 'Explore the visual theory and design craftsmanship behind typewriter-inspired web aesthetics and monospace editorial reading.',
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'In a digital landscape dominated by neon gradients, floating cards, and chaotic micro-interactions, the typewriter aesthetic offers an oasis of calm. It recalls an era when the written word stood on its own merits, unencumbered by visual gimmicks.'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'The Mechanical Honesty of Fixed-Pitch Glyphs',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Typewriter typography is built upon mechanical discipline: every character occupies the exact same horizontal unit of space. In digital interfaces, monospace type—specifically designed fonts like JetBrains Mono and IBM Plex Mono—communicates craftsmanship, technical rigor, and precision.'
                ]
            ],
            [
                'type' => 'quote',
                'data' => [
                    'text' => 'Good design is as little design as possible. Less, but better—because it concentrates on the essential aspects, and the products are not burdened with non-essentials.',
                    'caption' => 'Dieter Rams, Ten Principles for Good Design'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'Typographic Hierarchy in WordWise',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'In WordWise, typography is not an afterthought; it is the core user interface. Monospace is utilized purposefully for structure, metadata, timestamps, and code, while generous line height and optimal reading measures ensure that long-form reading remains effortless.'
                ]
            ],
            [
                'type' => 'code',
                'data' => [
                    'language' => 'css',
                    'code' => "/* Tailwind Editorial Reading Token Configuration */\n.max-w-reading {\n  max-width: 68ch;\n  line-height: 1.8;\n  letter-spacing: -0.011em;\n}\n.typewriter-badge {\n  font-family: 'JetBrains Mono', monospace;\n  border: 1px solid var(--border-color);\n  padding: 0.25rem 0.6rem;\n  text-transform: uppercase;\n}"
                ]
            ],
            [
                'type' => 'warning',
                'data' => [
                    'title' => 'Design Principle',
                    'message' => 'Restraint is strength. When in doubt, increase whitespace, remove a border, and let the typography carry the weight of your message.',
                    'type' => 'tip'
                ]
            ]
        ]
    ],

    // -------------------------------------------------------------
    // Article 4: Autonomous Agents
    // -------------------------------------------------------------
    [
        'title' => 'Autonomous Agents in Production: Beyond the Chatbot Paradigm',
        'slug' => 'autonomous-agents-in-production',
        'subtitle' => 'Grounding intelligent models through live terminal execution loops, reactive messaging, and verified sandbox boundaries.',
        'excerpt' => 'A practical overview of modern agentic workflows, moving from passive conversational models to autonomous agents capable of inspecting, compiling, and testing real systems.',
        'category_slug' => 'artificial-intelligence',
        'image' => 'img_ai_agents.jpg',
        'status' => 'published',
        'is_featured' => 0,
        'is_pinned' => 0,
        'tags' => 'ai, autonomous-agents, prompt-engineering, systems, automation',
        'seo_title' => 'Autonomous Agents in Production — WordWise',
        'seo_desc' => 'Discover how autonomous AI agents execute tasks in production through live sandbox execution, reactive messaging, and self-correcting feedback loops.',
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'The initial wave of generative artificial intelligence presented models as chat interfaces—conversational assistants waiting for human prompts. However, the true inflection point occurs when models operate as autonomous agents equipped with tools: the ability to read codebases, execute commands in secure sandboxes, and inspect error logs.'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'The Grounding Loop: Hypothesis, Execution, and Verification',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Unanchored language models hallucinate when asked to solve complex programming tasks in a single forward pass. An agentic workflow transforms this by establishing an iterative feedback loop: the agent hypothesizes a solution, applies surgical file changes, executes unit tests in a terminal, and inspects stderr output before declaring completion.'
                ]
            ],
            [
                'type' => 'code',
                'data' => [
                    'language' => 'json',
                    'code' => "{\n  \"action\": \"execute_verification\",\n  \"target\": \"tests/test_milestones4_6.php\",\n  \"assertion_count\": 24,\n  \"status\": \"PASS\",\n  \"feedback\": \"All block renderers and concurrency locks verified.\"\n}"
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'The Three Hallmarks of Resilient Agents',
                    'level' => 3
                ]
            ],
            [
                'type' => 'list',
                'data' => [
                    'style' => 'unordered',
                    'items' => [
                        'Deterministic State Persistence: Storing execution history in canonical JSONL transcripts rather than ephemeral chat memory.',
                        'Surgical Tool Calling: Favoring precise contiguous line replacements over whole-file rewrites.',
                        'Optimistic Verification: Running automated regression suites after every major milestone to detect subtle breakages early.'
                    ]
                ]
            ],
            [
                'type' => 'quote',
                'data' => [
                    'text' => 'Intelligence is not merely prediction; it is the capacity to recognize error, adjust parameters, and achieve an invariant goal despite environmental resistance.',
                    'caption' => 'Antigravity Systems Engineering'
                ]
            ]
        ]
    ],

    // -------------------------------------------------------------
    // Article 5: Omarchy Linux & Self-Hosted Sovereignty
    // -------------------------------------------------------------
    [
        'title' => 'The Independent Hacker’s Guide to Omarchy Linux & Self-Hosted Sovereignty',
        'slug' => 'the-independent-hackers-guide-to-omarchy-linux',
        'subtitle' => 'Why running native MariaDB and Apache on Arch Linux yields sub-millisecond latency and true data ownership.',
        'excerpt' => 'A practical manifesto for self-hosting your digital life and publishing platform on Omarchy Linux, escaping platform rent-seeking and reclaiming system craftsmanship.',
        'category_slug' => 'linux-devops',
        'image' => 'img_linux_omarchy.jpg',
        'status' => 'published',
        'is_featured' => 0,
        'is_pinned' => 0,
        'tags' => 'linux, omarchy, arch-linux, self-hosting, devops, apache',
        'seo_title' => 'The Independent Hacker’s Guide to Omarchy Linux — WordWise',
        'seo_desc' => 'Learn how to configure native Apache, MariaDB, and PHP on Omarchy Linux for lightning-fast self-hosted publishing and sovereign computing.',
        'blocks' => [
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'For years, developers have been conditioned to believe that running a website requires multi-tiered cloud infrastructure, serverless function cold boots, and expensive monthly subscriptions. Yet on a modern machine running Omarchy Linux (Arch Linux), a native Apache and MariaDB stack handles thousands of requests per second while consuming less than 200 megabytes of memory.'
                ]
            ],
            [
                'type' => 'header',
                'data' => [
                    'text' => 'The Beauty of the Native Rolling Release',
                    'level' => 2
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'Omarchy Linux provides pristine, unpatched upstream packages directly from developers. You get the latest PHP releases with bleeding-edge performance features, MariaDB with modern InnoDB storage engines, and modern systemd service orchestration right at your fingertips.'
                ]
            ],
            [
                'type' => 'code',
                'data' => [
                    'language' => 'bash',
                    'code' => "# Running high-throughput scheduled publication via systemd timer\n[Unit]\nDescription=WordWise Scheduled Publishing Runner\n\n[Service]\nType=oneshot\nUser=ron\nExecStart=/usr/bin/php /home/ron/Projects/WordWise/cron/publish_scheduled.php --json\n\n[Install]\nWantedBy=multi-user.target"
                ]
            ],
            [
                'type' => 'warning',
                'data' => [
                    'title' => 'UNIX Philosophy in Action',
                    'message' => 'Write programs that do one thing and do it well. Write programs to work together. Write programs to handle text streams, because that is a universal interface.',
                    'type' => 'info'
                ]
            ],
            [
                'type' => 'paragraph',
                'data' => [
                    'text' => 'When you host your own publishing platform, you own your database, your URLs, and your relationship with your readers. No algorithm can demote your essays, and no subscription change can lock you out of your archives.'
                ]
            ]
        ]
    ]
];

// 4. Insert Articles
echo "=== Seeding Editorial Articles ===\n";

foreach ($articles as $art) {
    $catId = $catMap[$art['category_slug']] ?? 1;
    $contentJson = json_encode([
        'time' => time() * 1000,
        'blocks' => $art['blocks'],
        'version' => '2.30.0'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $contentHtml = render_blocks($contentJson);

    // Check if post already exists by slug
    $existing = db_fetch_one("SELECT post_id FROM posts WHERE post_slug = ?", [$art['slug']]);

    if ($existing) {
        $postId = (int)$existing['post_id'];
        db_execute(
            "UPDATE posts SET 
                post_title = ?, post_subtitle = ?, post_excerpt = ?,
                post_category_id = ?, post_author = ?, post_image = ?,
                post_content = ?, post_content_json = ?, post_tags = ?,
                post_status = ?, post_seo_title = ?, post_seo_desc = ?,
                is_featured = ?, is_pinned = ?, updated_at = NOW() 
             WHERE post_id = ?",
            [
                $art['title'], $art['subtitle'], $art['excerpt'],
                $catId, $authorName, $art['image'],
                $contentHtml, $contentJson, $art['tags'],
                $art['status'], $art['seo_title'], $art['seo_desc'],
                $art['is_featured'], $art['is_pinned'],
                $postId
            ]
        );
        echo "  [UPDATED] #{$postId} '{$art['title']}'\n";
    } else {
        db_execute(
            "INSERT INTO posts (
                post_title, post_slug, post_subtitle, post_excerpt,
                post_category_id, post_author, post_date, post_image,
                post_content, post_content_json, post_tags, post_status,
                post_seo_title, post_seo_desc, is_featured, is_pinned,
                post_version, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [
                $art['title'], $art['slug'], $art['subtitle'], $art['excerpt'],
                $catId, $authorName, $art['image'],
                $contentHtml, $contentJson, $art['tags'], $art['status'],
                $art['seo_title'], $art['seo_desc'], $art['is_featured'], $art['is_pinned']
            ]
        );
        $postId = (int)db_last_id();
        echo "  [CREATED] #{$postId} '{$art['title']}'\n";
    }

    // Sync tags
    db_execute("DELETE FROM post_tags WHERE post_id = ?", [$postId]);
    $tags = array_filter(array_map('trim', explode(',', $art['tags'])));
    foreach ($tags as $tName) {
        $tSlug = slugify($tName);
        if ($tSlug === '') continue;

        db_execute(
            "INSERT INTO tags (tag_name, tag_slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE tag_name = VALUES(tag_name)",
            [$tName, $tSlug]
        );
        $tagRow = db_fetch_one("SELECT tag_id FROM tags WHERE tag_slug = ?", [$tSlug]);
        if ($tagRow) {
            db_execute("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)", [$postId, $tagRow['tag_id']]);
        }
    }
}

echo "=== Seeding Completed Successfully! ===\n";
