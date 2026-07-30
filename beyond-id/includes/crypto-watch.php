<?php
declare(strict_types=1);

function beyond_crypto_networks(): array
{
    return [
        'BTC' => ['name'=>'Bitcoin', 'symbol'=>'BTC', 'decimals'=>8],
        'ETH' => ['name'=>'Ethereum', 'symbol'=>'ETH', 'decimals'=>18],
        'SOL' => ['name'=>'Solana', 'symbol'=>'SOL', 'decimals'=>9],
    ];
}

function beyond_crypto_valid_address(string $network, string $address): bool
{
    $address = trim($address);
    return match ($network) {
        'BTC' => (bool)preg_match('/^(bc1[a-zA-HJ-NP-Z0-9]{11,71}|[13][a-km-zA-HJ-NP-Z1-9]{25,34})$/', $address),
        'ETH' => (bool)preg_match('/^0x[a-fA-F0-9]{40}$/', $address),
        'SOL' => (bool)preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address),
        default => false,
    };
}

function beyond_crypto_http(string $url, ?array $payload = null): array
{
    $headers = ['Accept: application/json', 'User-Agent: Beyond-Wallet/2.3.3'];
    $body = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($payload !== null) $headers[] = 'Content-Type: application/json';
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>4,
            CURLOPT_TIMEOUT=>8, CURLOPT_HTTPHEADER=>$headers, CURLOPT_CUSTOMREQUEST=>$payload === null ? 'GET' : 'POST',
        ]);
        if ($body !== null) curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        $raw = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);
        if (!is_string($raw) || $status < 200 || $status >= 300) throw new RuntimeException($error ?: 'Balance provider returned HTTP '.$status.'.');
    } else {
        $context = stream_context_create(['http'=>[
            'method'=>$payload === null ? 'GET' : 'POST', 'header'=>implode("\r\n", $headers),
            'content'=>$body ?? '', 'timeout'=>8, 'ignore_errors'=>false,
        ]]);
        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw)) throw new RuntimeException('Balance provider is unavailable.');
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) throw new RuntimeException('Balance provider returned invalid data.');
    return $decoded;
}

function beyond_crypto_hex_number(string $hex): float
{
    $hex = strtolower(preg_replace('/^0x/', '', $hex) ?? '');
    $value = 0.0;
    foreach (str_split($hex) as $digit) $value = ($value * 16) + hexdec($digit);
    return $value;
}

function beyond_crypto_balance(string $network, string $address): float
{
    if (!beyond_crypto_valid_address($network, $address)) throw new InvalidArgumentException('Invalid public address.');
    if ($network === 'BTC') {
        $base = rtrim((string)(getenv('BEYOND_BTC_API_URL') ?: 'https://blockstream.info/api'), '/');
        $data = beyond_crypto_http($base.'/address/'.rawurlencode($address));
        $confirmed = ((float)($data['chain_stats']['funded_txo_sum'] ?? 0)) - ((float)($data['chain_stats']['spent_txo_sum'] ?? 0));
        $pending = ((float)($data['mempool_stats']['funded_txo_sum'] ?? 0)) - ((float)($data['mempool_stats']['spent_txo_sum'] ?? 0));
        return ($confirmed + $pending) / 100000000;
    }
    if ($network === 'ETH') {
        $endpoint = trim((string)(getenv('BEYOND_ETH_RPC_URL') ?: 'https://ethereum-rpc.publicnode.com'));
        $data = beyond_crypto_http($endpoint, ['jsonrpc'=>'2.0','method'=>'eth_getBalance','params'=>[$address,'latest'],'id'=>1]);
        if (!isset($data['result']) || !is_string($data['result'])) throw new RuntimeException('Ethereum balance is unavailable.');
        return beyond_crypto_hex_number($data['result']) / 1000000000000000000;
    }
    if ($network === 'SOL') {
        $endpoint = trim((string)(getenv('BEYOND_SOL_RPC_URL') ?: 'https://api.mainnet.solana.com'));
        $data = beyond_crypto_http($endpoint, ['jsonrpc'=>'2.0','method'=>'getBalance','params'=>[$address,['commitment'=>'confirmed']],'id'=>1]);
        if (!isset($data['result']['value'])) throw new RuntimeException('Solana balance is unavailable.');
        return ((float)$data['result']['value']) / 1000000000;
    }
    throw new InvalidArgumentException('Unsupported network.');
}
