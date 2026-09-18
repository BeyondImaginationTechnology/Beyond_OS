<?php
declare(strict_types=1);

// Daily Breath keeps its chat UI on this origin. Its marker lets the shared
// Jaguar endpoint apply the Daily Breath-only scope after parsing the body.
$_SERVER['HTTP_X_DAILYBREATH_CHAT'] = '1';
require __DIR__ . '/../../ai/api/chat.php';
