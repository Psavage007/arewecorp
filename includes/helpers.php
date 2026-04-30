<?php
function company_initials(string $name): string {
    $words  = preg_split('/\s+/', preg_replace('/\b(inc|llc|ltd|corp|co|the|and|of)\b\.?/i', '', $name));
    $words  = array_filter($words);
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= strtoupper($w[0] ?? '');
    }
    return $initials ?: strtoupper($name[0]);
}

function format_cik(string $cik): string {
    return str_pad($cik, 10, '0', STR_PAD_LEFT);
}

function sic_to_industry(string $sic): string {
    $sic = (int)$sic;
    return match(true) {
        $sic >= 100  && $sic <= 999  => 'Agriculture',
        $sic >= 1000 && $sic <= 1499 => 'Mining',
        $sic >= 1500 && $sic <= 1799 => 'Construction',
        $sic >= 2000 && $sic <= 3999 => 'Manufacturing',
        $sic >= 4000 && $sic <= 4999 => 'Transportation & Utilities',
        $sic >= 5000 && $sic <= 5199 => 'Wholesale Trade',
        $sic >= 5200 && $sic <= 5999 => 'Retail Trade',
        $sic >= 6000 && $sic <= 6799 => 'Finance, Insurance & Real Estate',
        $sic >= 7000 && $sic <= 8999 => 'Services',
        $sic >= 9100 && $sic <= 9999 => 'Government',
        default                       => 'General Business',
    };
}

function nav_html(string $active = ''): string {
    return ''; // rendered inline in each page
}
