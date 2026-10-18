<?php
/**
 * WordWise 404 Not Found Page
 */
require_once __DIR__ . '/includes/db.php';

http_response_code(404);
$customTitle = "404 Page Not Found";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navigation.php';
?>

<main class="flex-grow py-20 flex items-center justify-center">
  <div class="max-w-md mx-auto px-4 text-center space-y-6">
    
    <div class="font-mono text-xs text-accent tracking-widest uppercase">
      [ Error 404 &mdash; Page Not Found ]
    </div>

    <h1 class="text-4xl sm:text-5xl font-mono font-bold text-ink-950 dark:text-ink-50">
      Dispatch Missing
    </h1>

    <p class="font-mono text-xs text-ink-600 dark:text-ink-400 leading-relaxed">
      The essay or page you requested does not exist, has been relocated, or is currently unpublished.
    </p>

    <!-- Search input -->
    <form method="GET" action="/articles.php" class="pt-2">
      <div class="relative">
        <input 
          type="text" 
          name="search" 
          placeholder="Search the archive..." 
          class="w-full pl-4 pr-10 py-2.5 text-sm bg-white dark:bg-paper-900 border border-paper-300 dark:border-paper-800 rounded font-sans focus:border-accent text-ink-900 dark:text-ink-100"
        >
        <button type="submit" class="absolute right-2.5 top-2.5 text-accent font-mono text-xs">
          GO &rarr;
        </button>
      </div>
    </form>

    <div class="pt-4 flex items-center justify-center space-x-4 font-mono text-xs">
      <a href="/" class="typewriter-badge hover:border-accent hover:text-accent">&larr; Return Home</a>
      <a href="/articles.php" class="typewriter-badge hover:border-accent hover:text-accent">Browse Catalogue</a>
    </div>

  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
