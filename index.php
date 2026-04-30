<?php
require_once 'includes/helpers.php';
$featured = require 'data/featured.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AreWeCorp — Who Owns That Company?</title>
    <meta name="description" content="Find out if any US company is family owned or corporately owned. Powered by SEC EDGAR data.">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav>
    <div class="container nav-inner">
        <a href="index.php" class="nav-logo">Are<span>We</span>Corp</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="search.php">Search</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
        </ul>
    </div>
</nav>

<section class="hero">
    <div class="container hero-content">
        <div class="hero-eyebrow">Powered by SEC EDGAR Data</div>
        <h1>Who Really Owns<br><span>That Company?</span></h1>
        <p>Search any US company to find out if it's family owned or corporately owned — sourced directly from SEC filings.</p>

        <div class="search-wrap">
            <form class="search-box" action="search.php" method="GET">
                <input type="text" name="q" placeholder="Search any company — Walmart, Apple, Koch..." autocomplete="off" required>
                <button type="submit">Look It Up</button>
            </form>
            <p class="search-hint">
                Try:
                <span onclick="document.querySelector('.search-box input').value='Walmart'">Walmart</span>
                <span onclick="document.querySelector('.search-box input').value='Ford Motor'">Ford Motor</span>
                <span onclick="document.querySelector('.search-box input').value='Apple'">Apple</span>
                <span onclick="document.querySelector('.search-box input').value='News Corp'">News Corp</span>
            </p>
        </div>
    </div>
</section>

<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat">
            <div class="stat-num">12,000+</div>
            <div class="stat-label">Companies in Database</div>
        </div>
        <div class="stat">
            <div class="stat-num">SEC</div>
            <div class="stat-label">Official Data Source</div>
        </div>
        <div class="stat">
            <div class="stat-num">13D/13G</div>
            <div class="stat-label">Filing Types Analyzed</div>
        </div>
        <div class="stat">
            <div class="stat-num">Free</div>
            <div class="stat-label">Always Free to Search</div>
        </div>
    </div>
</div>

<div class="container">

    <!-- Family Owned -->
    <section class="section">
        <div class="section-header">
            <h2 class="section-title"><span class="green"></span>Featured: Family Owned</h2>
            <a href="search.php?filter=family" class="view-all">View more &rarr;</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($featured['family'] as $co): ?>
            <a href="company.php?cik=<?= urlencode($co['cik']) ?>" class="company-card">
                <div class="card-top">
                    <div class="card-logo"><?= company_initials($co['name']) ?></div>
                    <span class="badge family">Family Owned</span>
                </div>
                <div>
                    <div class="card-name"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="card-industry"><?= htmlspecialchars($co['industry']) ?></div>
                </div>
                <div class="card-owners">
                    <strong>Key owners:</strong> <?= htmlspecialchars($co['owners']) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Corporate Owned -->
    <section class="section" style="padding-top:0">
        <div class="section-header">
            <h2 class="section-title"><span class="red"></span>Featured: Corporately Owned</h2>
            <a href="search.php?filter=corporate" class="view-all">View more &rarr;</a>
        </div>
        <div class="cards-grid">
            <?php foreach ($featured['corporate'] as $co): ?>
            <a href="company.php?cik=<?= urlencode($co['cik']) ?>" class="company-card">
                <div class="card-top">
                    <div class="card-logo"><?= company_initials($co['name']) ?></div>
                    <span class="badge corporate">Corporate</span>
                </div>
                <div>
                    <div class="card-name"><?= htmlspecialchars($co['name']) ?></div>
                    <div class="card-industry"><?= htmlspecialchars($co['industry']) ?></div>
                </div>
                <div class="card-owners">
                    <strong>Key owners:</strong> <?= htmlspecialchars($co['owners']) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

</div>

<!-- How It Works -->
<section class="how-section" id="how-it-works">
    <div class="container">
        <h2 class="section-title" style="color:#fff;text-align:center;margin-bottom:0">How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <h3>Search a Company</h3>
                <p>Type any US company name into the search bar. We search across 12,000+ SEC-registered companies.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h3>We Analyze SEC Filings</h3>
                <p>We pull 13D/13G beneficial ownership reports and proxy statements filed directly with the SEC.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h3>Get the Verdict</h3>
                <p>See who the major owners are and whether the company is family controlled or institutionally owned.</p>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container footer-inner">
        <div class="footer-logo">Are<span>We</span>Corp</div>
        <p>Data from <a href="https://www.sec.gov/edgar" target="_blank">SEC EDGAR</a>. For informational purposes only.</p>
    </div>
</footer>

</body>
</html>
