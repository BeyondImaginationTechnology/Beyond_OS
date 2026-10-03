<?php
declare(strict_types=1);
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/remember-me.php';

if (empty($_SESSION['user_id'])) {
    $rememberedUserId = beyondRememberRestore($pdo);
    if ($rememberedUserId) {
        $remembered = $pdo->prepare('SELECT * FROM users WHERE id=? AND status=? LIMIT 1');
        $remembered->execute([$rememberedUserId, 'active']);
        $rememberedUser = $remembered->fetch(PDO::FETCH_ASSOC);
        if ($rememberedUser) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$rememberedUser['id'];
            $_SESSION['email'] = $rememberedUser['email'];
            $_SESSION['name'] = $rememberedUser['name'] ?? trim(($rememberedUser['first_name'] ?? '') . ' ' . ($rememberedUser['last_name'] ?? ''));
            $_SESSION['role'] = $rememberedUser['role'] ?? 'user';
            $_SESSION['locale'] = $rememberedUser['preferred_locale'] ?? 'en';
            $_SESSION['user'] = ['id'=>(int)$rememberedUser['id'],'email'=>$rememberedUser['email'],'role'=>$_SESSION['role']];
            register_session($pdo, (int)$rememberedUser['id']);
        }
    }
}

if (empty($_SESSION['user_id']) || !beyond_refresh_browser_identity($pdo)) {
    $_SESSION['beyond_return_to'] = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: ../auth/login.php?required=1');
    exit;
}

require_once __DIR__ . '/../../includes/ecosystem.php';
beyond_nav_bootstrap('Beyond ID');
