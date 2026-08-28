<?php
declare(strict_types=1);
require_once __DIR__ . '/ecosystem.php';

function bos_json_response(array $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
function bos_request_json(): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}
function bos_csrf_token(): string {
    if (empty($_SESSION['bos_csrf'])) $_SESSION['bos_csrf'] = bin2hex(random_bytes(24));
    return (string)$_SESSION['bos_csrf'];
}
function bos_verify_csrf(?string $token): bool { return is_string($token) && hash_equals(bos_csrf_token(), $token); }
function bos_current_user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }
function bos_is_admin(): bool { return in_array((string)($_SESSION['role'] ?? ''), ['admin','super_admin'], true); }
function bos_require_admin(): void { require_beyond_id(); if (!bos_is_admin()) { http_response_code(403); exit('Admin access required.'); } }
function bos_feature_enabled(string $key, bool $fallback=true): bool {
    try { $s=beyond_db()->prepare('SELECT enabled FROM feature_flags WHERE flag_key=? LIMIT 1'); $s->execute([$key]); $v=$s->fetchColumn(); return $v===false?$fallback:(bool)$v; } catch(Throwable $e) { return $fallback; }
}
function bos_log(string $event, array $details=[]): void {
    try { $s=beyond_db()->prepare('INSERT INTO platform_events(user_id,event_name,details_json,created_at) VALUES(?,?,?,NOW())'); $s->execute([bos_current_user_id() ?: null,$event,json_encode($details)]); } catch(Throwable $e) {}
}
function bos_app_card(string $title,string $copy,string $href,string $icon='✦',string $status='Open',?string $brandIcon=null): string {
    if ($brandIcon === '@blank') {
        $iconMarkup = '<span class="bos-card-icon bos-card-icon-blank" aria-hidden="true"></span>';
    } elseif ($brandIcon === '@atom') {
        $iconMarkup = '<span class="bos-card-icon bos-card-brand-tile"><img src="'.e(beyond_url('assets/icons/app-store/beyond-imagination.jpg')).'" alt=""><small>'.e($icon).'</small></span>';
    } elseif (is_string($brandIcon) && $brandIcon !== '') {
        $iconMarkup = '<span class="bos-card-icon bos-card-brand-icon"><img src="'.e(beyond_url($brandIcon)).'" alt="'.e($title).' icon"></span>';
    } else {
        $iconMarkup = '<span class="bos-card-icon">'.e($icon).'</span>';
    }
    return '<a class="bos-card" href="'.e(beyond_url($href)).'">'.$iconMarkup.'<div><strong>'.e($title).'</strong><p>'.e($copy).'</p></div><span class="bos-card-status">'.e($status).'</span></a>';
}
function bos_locked_app_card(string $title,string $copy,string $icon='✦',?string $brandIcon=null): string {
    if ($brandIcon === '@blank') {
        $iconMarkup = '<span class="bos-card-icon bos-card-icon-blank" aria-hidden="true"></span>';
    } elseif ($brandIcon === '@atom') {
        $iconMarkup = '<span class="bos-card-icon bos-card-brand-tile"><img src="'.e(beyond_url('assets/icons/app-store/beyond-imagination.jpg')).'" alt=""><small>'.e($icon).'</small></span>';
    } elseif (is_string($brandIcon) && $brandIcon !== '') {
        $iconMarkup = '<span class="bos-card-icon bos-card-brand-icon"><img src="'.e(beyond_url($brandIcon)).'" alt="'.e($title).' icon"></span>';
    } else {
        $iconMarkup = '<span class="bos-card-icon">'.e($icon).'</span>';
    }
    return '<div class="bos-card bos-card-locked" aria-disabled="true">'.$iconMarkup.'<div><strong>'.e($title).'</strong><p>'.e($copy).'</p></div><span class="bos-card-status">Coming soon</span></div>';
}
