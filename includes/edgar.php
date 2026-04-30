<?php
/**
 * EDGAR API integration
 * Docs: https://efts.sec.gov/LATEST/search-index?q=%22full-text-search%22
 */

define('EDGAR_BASE', 'https://efts.sec.gov/LATEST/search-index');
define('EDGAR_DATA', 'https://data.sec.gov');
define('USER_AGENT', 'AreWeCorp savagep88@gmail.com');

function edgar_get($url): array {
    $opts = [
        'http' => [
            'header' => "User-Agent: " . USER_AGENT . "\r\nAccept: application/json\r\n",
            'timeout' => 10,
        ]
    ];
    $ctx = stream_context_create($opts);
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        throw new Exception("EDGAR request failed: $url");
    }
    return json_decode($response, true) ?? [];
}

function edgar_search_companies(string $query): array {
    $url = 'https://efts.sec.gov/LATEST/search-index?q=' . urlencode('"' . $query . '"') . '&dateRange=custom&startdt=2020-01-01&forms=10-K';
    // Use the company search endpoint instead
    $url = 'https://efts.sec.gov/LATEST/search-index?q=' . urlencode($query) . '&forms=10-K&hits.hits._source=period_of_report,entity_name,file_num,period_of_report';

    // Better: use the full-text company ticker/name search
    $url = 'https://efts.sec.gov/LATEST/search-index?q=%22' . urlencode($query) . '%22&dateRange=custom&startdt=2023-01-01&forms=DEF+14A';

    // Best: use the EDGAR company search API
    $search_url = 'https://efts.sec.gov/LATEST/search-index?q=' . urlencode($query) . '&forms=10-K';

    $company_url = 'https://efts.sec.gov/LATEST/search-index?category=form-type&q=' . urlencode($query);

    // Use the official company search
    $url = 'https://efts.sec.gov/LATEST/search-index?q=' . urlencode($query) . '&dateRange=custom&startdt=2024-01-01&forms=10-K&hits.hits.total.value=true';

    // Correct endpoint for company name search
    $url = 'https://www.sec.gov/cgi-bin/browse-edgar?company=' . urlencode($query) . '&CIK=&type=10-K&dateb=&owner=include&count=20&search_text=&action=getcompany&output=atom';

    // Use the EDGAR full text search
    $data = edgar_get('https://efts.sec.gov/LATEST/search-index?q=' . urlencode($query) . '&forms=10-K&dateRange=custom&startdt=2023-01-01');

    $results = [];
    foreach ($data['hits']['hits'] ?? [] as $hit) {
        $src = $hit['_source'];
        if (isset($src['entity_name'], $src['file_num'])) {
            // Extract CIK from file_num or entity_id
            $cik = ltrim($hit['_id'] ?? '', '0');
            $results[] = [
                'name' => $src['entity_name'],
                'cik'  => $src['entity_id'] ?? $cik,
            ];
        }
    }

    // Deduplicate by CIK
    $seen = [];
    $unique = [];
    foreach ($results as $r) {
        if (!isset($seen[$r['cik']])) {
            $seen[$r['cik']] = true;
            $unique[] = $r;
        }
    }

    return $unique;
}

function edgar_get_company(string $cik): array {
    $padded = str_pad($cik, 10, '0', STR_PAD_LEFT);
    $data = edgar_get(EDGAR_DATA . "/submissions/CIK{$padded}.json");
    return [
        'name'            => $data['name'] ?? 'Unknown',
        'cik'             => $cik,
        'sic'             => $data['sic'] ?? null,
        'sic_description' => $data['sicDescription'] ?? null,
        'state'           => $data['stateOfIncorporation'] ?? null,
        'tickers'         => $data['tickers'] ?? [],
        'filings'         => $data['filings']['recent'] ?? [],
    ];
}

function edgar_get_filings(string $cik, string $form_type, int $limit = 5): array {
    $company = edgar_get_company($cik);
    $filings = $company['filings'];
    $results = [];

    foreach ($filings['form'] ?? [] as $i => $form) {
        if (strtoupper($form) === strtoupper($form_type)) {
            $results[] = [
                'date'        => $filings['filingDate'][$i] ?? null,
                'accession'   => str_replace('-', '', $filings['accessionNumber'][$i] ?? ''),
                'primary_doc' => $filings['primaryDocument'][$i] ?? null,
            ];
            if (count($results) >= $limit) break;
        }
    }

    return $results;
}
