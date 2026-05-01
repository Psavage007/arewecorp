<?php
require_once 'includes/edgar.php';
require_once 'includes/helpers.php';
require_once 'includes/seo_head.php';

$query    = trim($_GET['q'] ?? '');
$results  = [];
$error    = null;
$searched = false;

if ($query) {
    $searched = true;
    try {
        $results = edgar_search_companies($query);
    } catch (Exception $e) {
        $error = 'Could not reach SEC EDGAR right now. Please try again in a moment.';
    }
}

$title = $query
    ? 'Is ' . $query . ' Family Owned or Corporate? | AreWeCorp'
    : 'Search Company Ownership — Is It Family Owned? | AreWeCorp';

$desc = $query
    ? 'Find out if ' . $query . ' is family owned or corporately owned. Search SEC EDGAR ownership filings on AreWeCorp.'
    : 'Search any US company to find out if it is family owned or corporately owned. Powered by SEC EDGAR beneficial ownership data.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php seo_head([
    'title'       => $title,
    'description' => $desc,
    'canonical'   => 'https://arewecorp.com/search.php' . ($query ? '?q=' . urlencode($query) : ''),
    'noindex'     => empty($query), // don't index empty search page
]); ?>
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="/" class="nav-logo">Are<span>We</span>Corp</a>
        <ul class="nav-links">
            <li><a href="/">Home</a></li>
            <li><a href="/search.php">Search Companies</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1><?= $query ? 'Is ' . htmlspecialchars($query) . ' Family Owned?' : 'Search Company Ownership' ?></h1>
        <form class="search-box" action="/search.php" method="GET" style="max-width:600px;margin-top:1rem" role="search">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search any US company..." autocomplete="off" required aria-label="Search company name">
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<div class="container">

    <?php if ($error): ?>
        <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>

    <?php elseif ($searched && empty($results)): ?>
        <div class="empty-state">
            <div style="font-size:3rem">&#128269;</div>
            <h2>No results for "<?= htmlspecialchars($query) ?>"</h2>
            <p>Try a different spelling, or search for the parent company name.<br>
            Note: Private companies with no SEC filings will not appear.</p>
        </div>

    <?php elseif (!empty($results)): ?>
        <p class="results-meta" style="margin-top:2rem">
            Found <strong><?= count($results) ?></strong> result<?= count($results) !== 1 ? 's' : '' ?> for
            "<strong><?= htmlspecialchars($query) ?></strong>" — click a company to see if it's family owned or corporately owned.
        </p>

        <ul class="results-list" role="list">
            <?php foreach ($results as $co): ?>
            <li class="result-item">
                <a href="/company.php?cik=<?= urlencode($co['cik']) ?>">
                    <div>
                        <div class="result-name"><?= htmlspecialchars($co['name']) ?></div>
                        <div class="result-meta">CIK <?= htmlspecialchars(format_cik($co['cik'])) ?></div>
                    </div>
                    <span class="result-arrow" aria-hidden="true">&#8594;</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

    <?php else: ?>
        <div class="empty-state" style="padding:5rem 2rem">
            <div style="font-size:3rem">&#128269;</div>
            <h2>Search any US company</h2>
            <p>Type a company name above to find out if it is family owned or corporately owned.</p>
        </div>
    <?php endif; ?>

</div>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Are<span>We</span>Corp</div>
        <p>Data from <a href="https://www.sec.gov/edgar" target="_blank" rel="noopener">SEC EDGAR</a>. For informational purposes only.</p>
    </div>
</footer>

</body>
</html>
