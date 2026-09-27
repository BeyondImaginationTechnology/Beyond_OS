<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300, stale-while-revalidate=3600');

$adTagUrl = trim((string)(getenv('BEYOND_TV_VAST_TAG_URL') ?: ''));
$configuredDuration = (int)(getenv('BEYOND_TV_AD_BREAK_SECONDS') ?: 300);
$breakDuration = max(60, min(600, $configuredDuration));
$enabled = $adTagUrl !== '' && filter_var($adTagUrl, FILTER_VALIDATE_URL) !== false;

echo json_encode([
    'enabled' => $enabled,
    'provider' => 'google-ima',
    'ad_tag_url' => $enabled ? $adTagUrl : '',
    'break_duration' => $breakDuration,
    'label' => 'Commercial break',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
