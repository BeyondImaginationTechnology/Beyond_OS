<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/bootstrap.php';

const JAGUAR_DRAW_IMAGE_MAX_BYTES = 9 * 1024 * 1024;
const JAGUAR_DRAW_IMAGE_RETENTION_SECONDS = 7 * 86400;

function jaguar_draw_image_directory(): string
{
    $directory = beyond_private_directory('ai/jaguar-draw-images', 'jaguar-draw-images');
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Private Jaguar Draw image storage is unavailable.');
    }
    @chmod($directory, 0700);
    return $directory;
}

function jaguar_draw_image_extension(string $mimeType): ?string
{
    return match ($mimeType) {
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        default => null,
    };
}

/** Decode and verify the bounded data URL returned by the private image worker. */
function jaguar_draw_image_decode(string $dataUrl): ?array
{
    if (strlen($dataUrl) > 12 * 1024 * 1024
        || !preg_match('~^data:(image/(?:png|jpeg|webp));base64,([A-Za-z0-9+/]+={0,2})$~D', $dataUrl, $match)) {
        return null;
    }
    $bytes = base64_decode($match[2], true);
    if (!is_string($bytes) || $bytes === '' || strlen($bytes) > JAGUAR_DRAW_IMAGE_MAX_BYTES) return null;
    $info = @getimagesizefromstring($bytes);
    if (!is_array($info) || ($info['mime'] ?? '') !== $match[1]
        || (int)($info[0] ?? 0) < 1 || (int)($info[0] ?? 0) > 4096
        || (int)($info[1] ?? 0) < 1 || (int)($info[1] ?? 0) > 4096
        || jaguar_draw_image_extension($match[1]) === null) return null;
    return ['bytes' => $bytes, 'mime_type' => $match[1]];
}

function jaguar_draw_image_file_path(string $receiptId, string $mimeType): ?string
{
    $extension = jaguar_draw_image_extension($mimeType);
    if (!preg_match('/^[a-f0-9]{16}$/', $receiptId) || $extension === null) return null;
    return jaguar_draw_image_directory() . DIRECTORY_SEPARATOR . $receiptId . '.' . $extension;
}

/** Remove expired private files gradually as signed-in Draw activity continues. */
function jaguar_draw_image_cleanup(PDO $pdo, int $limit = 20): void
{
    try {
        $statement = $pdo->prepare('SELECT receipt_id,mime_type FROM jaguar_draw_images WHERE expires_at<=? ORDER BY expires_at LIMIT ?');
        $statement->bindValue(1, time(), PDO::PARAM_INT);
        $statement->bindValue(2, max(1, min(100, $limit)), PDO::PARAM_INT);
        $statement->execute();
        $expired = $statement->fetchAll(PDO::FETCH_ASSOC);
        foreach ($expired as $image) {
            $path = jaguar_draw_image_file_path((string)$image['receipt_id'], (string)$image['mime_type']);
            if ($path !== null && !is_link($path) && is_file($path)) @unlink($path);
            $pdo->prepare('DELETE FROM jaguar_draw_images WHERE receipt_id=? AND expires_at<=?')
                ->execute([(string)$image['receipt_id'], time()]);
        }
    } catch (Throwable $exception) {
        error_log('Jaguar Draw image cleanup deferred.');
    }
}

/** Persist before wallet capture so a successful charge always has a recovery URL. */
function jaguar_draw_image_store(PDO $pdo, int $userId, string $idempotencyKey, array $image): ?array
{
    $bytes = $image['bytes'] ?? null;
    $mimeType = (string)($image['mime_type'] ?? '');
    if ($userId < 1 || !is_string($bytes) || $bytes === '' || strlen($bytes) > JAGUAR_DRAW_IMAGE_MAX_BYTES
        || jaguar_draw_image_extension($mimeType) === null
        || !preg_match('/^draw:v1:u' . $userId . ':[a-f0-9]{32}$/', $idempotencyKey)) return null;

    try {
        jaguar_draw_image_cleanup($pdo, 10);
        $hold = $pdo->prepare("SELECT 1 FROM jaguar_draw_holds h JOIN beyond_wallets w ON w.id=h.wallet_id WHERE h.idempotency_key=? AND h.status='held' AND w.user_id=? LIMIT 1");
        $hold->execute([$idempotencyKey, $userId]);
        if ($hold->fetchColumn() === false) return null;

        $receiptId = substr(hash('sha256', $idempotencyKey), 0, 16);
        $path = jaguar_draw_image_file_path($receiptId, $mimeType);
        if ($path === null || is_link($path) || file_exists($path)) return null;
        $temporary = dirname($path) . DIRECTORY_SEPARATOR . '.' . $receiptId . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $written = @file_put_contents($temporary, $bytes, LOCK_EX);
        if ($written !== strlen($bytes)) { @unlink($temporary); return null; }
        @chmod($temporary, 0600);
        if (!@rename($temporary, $path)) { @unlink($temporary); return null; }

        $now = time();
        try {
            $insert = $pdo->prepare('INSERT INTO jaguar_draw_images(idempotency_key,receipt_id,mime_type,sha256,byte_size,created_at,expires_at) VALUES(?,?,?,?,?,?,?)');
            $insert->execute([$idempotencyKey, $receiptId, $mimeType, hash('sha256', $bytes), strlen($bytes), $now, $now + JAGUAR_DRAW_IMAGE_RETENTION_SECONDS]);
        } catch (Throwable $exception) {
            @unlink($path);
            throw $exception;
        }
        return ['receipt_id' => $receiptId, 'mime_type' => $mimeType, 'expires_at' => $now + JAGUAR_DRAW_IMAGE_RETENTION_SECONDS];
    } catch (Throwable $exception) {
        error_log('Jaguar Draw image could not be stored privately.');
        return null;
    }
}

/** Resolve only a charged image owned by this wallet user and verify its stored bytes. */
function jaguar_draw_image_load(PDO $pdo, int $userId, string $receiptId): ?array
{
    if ($userId < 1 || !preg_match('/^[a-f0-9]{16}$/', $receiptId)) return null;
    jaguar_draw_image_cleanup($pdo, 5);
    $statement = $pdo->prepare("SELECT i.mime_type,i.sha256,i.byte_size FROM jaguar_draw_images i JOIN jaguar_draw_holds h ON h.idempotency_key=i.idempotency_key JOIN beyond_wallets w ON w.id=h.wallet_id WHERE i.receipt_id=? AND i.expires_at>? AND h.status='charged' AND w.user_id=? LIMIT 1");
    $statement->execute([$receiptId, time(), $userId]);
    $metadata = $statement->fetch(PDO::FETCH_ASSOC);
    if (!is_array($metadata)) return null;
    $path = jaguar_draw_image_file_path($receiptId, (string)$metadata['mime_type']);
    if ($path === null || is_link($path) || !is_file($path)) return null;
    $size = @filesize($path);
    if (!is_int($size) || $size !== (int)$metadata['byte_size'] || $size > JAGUAR_DRAW_IMAGE_MAX_BYTES) return null;
    $bytes = @file_get_contents($path);
    if (!is_string($bytes) || !hash_equals((string)$metadata['sha256'], hash('sha256', $bytes))) return null;
    return ['bytes' => $bytes, 'mime_type' => (string)$metadata['mime_type']];
}

/** Build a same-origin retrieval URL for either /ai or root-mounted deployments. */
function jaguar_draw_image_url(string $receiptId): string
{
    $scriptDirectory = str_replace('\\', '/', dirname(dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/ai/api/chat.php'))));
    $basePath = $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : rtrim($scriptDirectory, '/');
    return $basePath . '/api/draw-image.php?receipt=' . rawurlencode($receiptId);
}
