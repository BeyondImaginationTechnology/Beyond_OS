<?php
declare(strict_types=1);

/**
 * Returns the user's provider-backed cash accounts. These records are display
 * mirrors only: balances may be changed only by verified provider webhooks and
 * reconciliation jobs once DCBank is connected.
 */
function beyond_cash_accounts(PDO $pdo, int $userId): array
{
    if ($userId < 1) return [];
    $driver = (string)$pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $insert = $driver === 'sqlite'
        ? "INSERT OR IGNORE INTO beyond_cash_accounts(user_id,currency,status) VALUES(?,?,'pending_provider')"
        : "INSERT IGNORE INTO beyond_cash_accounts(user_id,currency,status) VALUES(?,?,'pending_provider')";
    $create = $pdo->prepare($insert);
    foreach (['CAD','USD'] as $currency) $create->execute([$userId,$currency]);
    $statement = $pdo->prepare('SELECT id,currency,available_balance,pending_balance,status,provider FROM beyond_cash_accounts WHERE user_id=? ORDER BY CASE currency WHEN \'CAD\' THEN 0 ELSE 1 END');
    $statement->execute([$userId]);
    $accounts = [];
    foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $account) $accounts[(string)$account['currency']] = $account;
    return $accounts;
}
