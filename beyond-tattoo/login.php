<?php
declare(strict_types=1);
require __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    $current = bt_current_user();
    if ($current && ($current['account_type'] ?? '') === 'owner' && !empty($current['onboarding_complete'])) {
        redirect('dashboard.php');
    }
    redirect('onboarding.php?workspace=studio');
}
$returnTo = beyond_url('beyond-tattoo/onboarding.php?workspace=studio');
$_SESSION['beyond_return_to'] = $returnTo;
redirect(beyond_url('beyond-id/auth/login.php?required=1&app=beyond-tattoo&return=' . rawurlencode($returnTo)));
