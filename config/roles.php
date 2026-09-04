<?php
declare(strict_types=1);

/** Return the initial role configured outside the public source tree. */
function beyond_signup_role(string $email, string $defaultRole = 'user'): string
{
    $normalized = strtolower(trim($email));
    $configured = ['super_admin'=>[], 'admin'=>[]];
    if (function_exists('beyond_config')) {
        try {
            $configured['super_admin'] = (array)beyond_config('security.super_admin_emails', []);
            $configured['admin'] = (array)beyond_config('security.admin_emails', []);
        } catch (Throwable $exception) {}
    }
    foreach (['super_admin'=>'BEYOND_SUPER_ADMIN_EMAILS', 'admin'=>'BEYOND_ADMIN_EMAILS'] as $role=>$environmentName) {
        $fromEnvironment = trim((string)getenv($environmentName));
        if ($fromEnvironment !== '') $configured[$role] = array_merge($configured[$role], explode(',', $fromEnvironment));
        $emails = array_map(static fn($value): string => strtolower(trim((string)$value)), $configured[$role]);
        if ($normalized !== '' && in_array($normalized, $emails, true)) return $role;
    }
    return in_array($defaultRole, ['user','admin','super_admin'], true) ? $defaultRole : 'user';
}
