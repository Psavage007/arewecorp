<?php
require_once 'includes/edgar.php';
require_once 'includes/helpers.php';

$query   = trim($_GET['q'] ?? '');
$results = [];
$error   = null;
$searched = false;

if ($query) {
    $searched = true;
    try {
        $results = edgar_search_companies($query);
    } catch (Exception $e) {
        $error = 'Could not reach SEC EDGAR right now. Please try again in a moment.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $query ? htmlspecialchars($query) . ' — Search' : 'Search' ?> — AreWeCorp</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (($ga_id = getenv('GA_MEASUREMENT_ID')) !== false && $ga_id): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= htmlspecialchars($ga_id) ?>');</script>
    <?php endif; ?>
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo">Are<span>We</span>Corp</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php">Search</a></li>
        </ul>
    </div>
</nav>

<div class="page-header">
    <div class="container">
        <h1>Company Search</h1>
        <form class="search-box" action="search.php" method="GET" style="max-width:600px">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search any US company..." autocomplete="off" required>
            <button type="submit">Search</button>
        </form>
    </div>
</div>

<div class="container">

    <?php if ($error): ?>
        <div class="error-msg"><?= htmlspecialchars($error) ?></div>

    <?php elseif ($searched && empty($results)): ?>
        <div class="empty-state">
            <div style="font-size:3rem">&#128269;</div>
            <h2>No results for "<?= htmlspecialchars($query) ?>"</h2>
            <p>Try a different spelling, or search for the parent company name.<br>
            Note: Purely private companies with no SEC filings may not appear.</p>
        </div>

    <?php elseif (!empty($results)): ?>
        <p class="results-meta" style="margin-top:2rem">
            Found <strong><?= count($results) ?></strong> result<?= count($results) !== 1 ? 's' : '' ?> for "<strong><?= htmlspecialchars($query) ?></strong>"
        </p>

        <ul class="results-list">
            <?php foreach ($results as $co): ?>
            <li class="result-item">
                <a href="company.php?cik=<?= urlencode($co['cik']) ?>">
                    <div>
                        <div class="result-name"><?= htmlspecialchars($co['name']) ?></div>
                        <div class="result-meta">CIK <?= htmlspecialchars(format_cik($co['cik'])) ?></div>
                    </div>
                    <span class="result-arrow">&#8594;</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

    <?php else: ?>
        <div class="empty-state" style="padding:5rem 2rem">
            <div style="font-size:3rem">&#128269;</div>
            <h2>Search any US company</h2>
            <p>Type a company name above to look up its ownership structure.</p>
        </div>
    <?php endif; ?>

</div>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Are<span>We</span>Corp</div>
        <p>Data from <a href="https://www.sec.gov/edgar" target="_blank">SEC EDGAR</a>. For informational purposes only.</p>
    </div>
</footer>

</body>
</html>
