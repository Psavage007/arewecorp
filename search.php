<?php
require_once 'includes/edgar.php';
require_once 'includes/db.php';

$query = trim($_GET['q'] ?? '');
$results = [];
$error = null;

if ($query) {
    try {
        $results = edgar_search_companies($query);
    } catch (Exception $e) {
        $error = 'Could not fetch results. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?= htmlspecialchars($query) ?> - AreWeCorp</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">AreWeCorp</a></h1>
        </div>
    </header>

    <main class="container">
        <form class="search-form" action="search.php" method="GET">
            <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Search company name..." required>
            <button type="submit">Look Up</button>
        </form>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php elseif (empty($results)): ?>
            <p class="no-results">No results found for "<?= htmlspecialchars($query) ?>".</p>
        <?php else: ?>
            <h2>Results for "<?= htmlspecialchars($query) ?>"</h2>
            <ul class="results-list">
                <?php foreach ($results as $company): ?>
                    <li>
                        <a href="company.php?cik=<?= urlencode($company['cik']) ?>">
                            <strong><?= htmlspecialchars($company['name']) ?></strong>
                            <span class="cik">CIK: <?= htmlspecialchars($company['cik']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>Data sourced from <a href="https://www.sec.gov/edgar" target="_blank">SEC EDGAR</a>.</p>
        </div>
    </footer>
</body>
</html>
