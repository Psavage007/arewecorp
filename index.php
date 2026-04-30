<?php
// AreWeCorp - US Company Ownership Lookup
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AreWeCorp - Company Ownership Lookup</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>AreWeCorp</h1>
            <p class="tagline">Find out who really owns any US company</p>
        </div>
    </header>

    <main class="container">
        <form class="search-form" action="search.php" method="GET">
            <input
                type="text"
                name="q"
                placeholder="Search company name (e.g. Apple, Koch Industries...)"
                autocomplete="off"
                required
            >
            <button type="submit">Look Up</button>
        </form>

        <div class="info-cards">
            <div class="card">
                <h3>Family Owned</h3>
                <p>Companies where founding families or individuals hold majority control through shares or voting rights.</p>
            </div>
            <div class="card">
                <h3>Corporate Owned</h3>
                <p>Companies controlled by institutional investors, private equity, or publicly traded parent entities.</p>
            </div>
            <div class="card">
                <h3>Data Source</h3>
                <p>Ownership data pulled directly from SEC EDGAR filings — proxy statements, 13D/13G beneficial ownership reports.</p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>Data sourced from <a href="https://www.sec.gov/edgar" target="_blank">SEC EDGAR</a>. For informational purposes only.</p>
        </div>
    </footer>
</body>
</html>
