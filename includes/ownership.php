<?php
require_once __DIR__ . '/edgar.php';

/**
 * Known institutional investors — presence indicates corporate ownership.
 */
const INSTITUTIONS = [
    'vanguard', 'blackrock', 'state street', 'fidelity', 'invesco',
    'dimensional', 'northern trust', 't. rowe', 'capital group',
    'geode', 'bank of america', 'morgan stanley', 'wells fargo',
    'jpmorgan', 'goldman sachs', 'schwab', 'pimco', 'american funds',
];

/**
 * Known family / individual ownership indicators.
 */
const FAMILY_SIGNALS = [
    'family', 'trust', 'foundation', 'estate', 'partners lp',
    'holdings llc', 'enterprises', 'revocable', 'living trust',
];

/**
 * Analyze whether a company is family-owned or corporately owned.
 *
 * @return array {type, label, summary, owners, confidence}
 */
function analyze_ownership(string $cik, array $company): array {
    $name    = $company['name'];
    $owners  = [];
    $signals = ['family' => 0, 'corporate' => 0];

    // 1. Fetch beneficial owners (13D/13G filers)
    try {
        $beneficial = edgar_get_beneficial_owners($cik, $name);
        foreach ($beneficial as $bo) {
            $lower = strtolower($bo['name']);
            $type  = classify_owner($lower);
            $signals[$type]++;
            $owners[] = [
                'name' => $bo['name'],
                'type' => $type,
                'role' => '13D/13G Filer (≥5% owner)',
                'pct'  => null,
            ];
        }
    } catch (Exception $e) {
        // continue with other signals
    }

    // 2. Category signal from EDGAR
    $category = strtolower($company['category'] ?? '');
    if (str_contains($category, 'non-accelerated') || str_contains($category, 'smaller reporting')) {
        $signals['family'] += 1; // smaller companies more likely family
    }

    // 3. SIC-based heuristic (certain industries skew family)
    $sic = (int)($company['sic'] ?? 0);
    if (($sic >= 5200 && $sic <= 5999) || ($sic >= 7000 && $sic <= 7999)) {
        $signals['family'] += 1; // retail/hospitality skew family
    }

    // Determine verdict
    if ($signals['family'] > $signals['corporate']) {
        $type = 'family';
    } elseif ($signals['corporate'] > $signals['family']) {
        $type = 'corporate';
    } else {
        // Default: check if it's a large cap (likely corporate)
        $type = (!empty($company['exchanges'])) ? 'corporate' : 'unknown';
    }

    $summary = build_summary($type, $name, $owners, $company);

    return [
        'type'       => $type,
        'label'      => $type === 'family' ? 'Family / Founder Owned' : ($type === 'corporate' ? 'Corporately Owned' : 'Ownership Unclear'),
        'summary'    => $summary,
        'owners'     => array_slice($owners, 0, 10),
        'confidence' => max($signals['family'], $signals['corporate']) > 2 ? 'high' : 'moderate',
    ];
}

function classify_owner(string $lower_name): string {
    foreach (INSTITUTIONS as $inst) {
        if (str_contains($lower_name, $inst)) return 'corporate';
    }
    foreach (FAMILY_SIGNALS as $sig) {
        if (str_contains($lower_name, $sig)) return 'family';
    }
    // Likely an individual if it looks like a person's name (contains comma or common name patterns)
    if (preg_match('/\b(mr|mrs|ms|dr|jr|sr|ii|iii)\b/i', $lower_name)) return 'family';
    return 'corporate'; // default to corporate for unknown entities
}

function build_summary(string $type, string $name, array $owners, array $company): string {
    $inst_owners    = array_filter($owners, fn($o) => $o['type'] === 'corporate');
    $family_owners  = array_filter($owners, fn($o) => $o['type'] === 'family');
    $inst_names     = array_map(fn($o) => $o['name'], array_slice(array_values($inst_owners), 0, 3));
    $family_names   = array_map(fn($o) => $o['name'], array_slice(array_values($family_owners), 0, 2));

    if ($type === 'family') {
        $who = !empty($family_names) ? implode(', ', $family_names) : 'individual insiders or a founding family';
        return "{$name} appears to be controlled by {$who}, based on SEC beneficial ownership filings. Family or individual shareholders hold significant voting power.";
    } elseif ($type === 'corporate') {
        $who = !empty($inst_names) ? implode(', ', $inst_names) : 'large institutional investors';
        return "{$name} is primarily owned by institutional shareholders including {$who}. Ownership is broadly distributed among investment firms and funds with no single controlling individual.";
    } else {
        return "Ownership structure for {$name} could not be definitively determined from available SEC filings. The company may be private or have limited public disclosure requirements.";
    }
}
