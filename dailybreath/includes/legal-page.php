<?php
declare(strict_types=1);

require_once __DIR__ . '/web-app.php';

function dailybreath_legal_start(string $title, string $description, string $effectiveDate): void
{
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
        <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | Daily Breath</title>
        <?= dailybreath_web_head($title . ' | Daily Breath') ?>
        <style>
            *{box-sizing:border-box}body{margin:0;color:#203329;background:linear-gradient(135deg,#f6eddf,#edf6ee 58%,#dcecdf);font:16px/1.7 Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.legal-shell{width:min(920px,calc(100% - 32px));margin:auto;padding:28px 0 72px}.legal-top{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:28px}.legal-brand{display:flex;align-items:center;gap:12px;color:#153f2b;text-decoration:none;font-weight:950;letter-spacing:.04em}.legal-brand img{width:46px;height:46px;border-radius:13px}.legal-back{color:#245b40;font-weight:800}.legal-hero,.legal-body{border:1px solid #bad0c0;border-radius:28px;background:#fffefaeb;box-shadow:0 24px 70px #173f2c1c}.legal-hero{padding:clamp(28px,6vw,58px);background:linear-gradient(145deg,#123e2b,#082619);color:#fff}.legal-kicker{display:block;color:#f1ca72;font-size:12px;font-weight:950;letter-spacing:.13em;text-transform:uppercase}.legal-hero h1{margin:10px 0 14px;font:500 clamp(42px,7vw,68px)/1 Georgia,serif}.legal-hero p{max-width:720px;color:#d9e7dd;font-size:18px}.legal-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.legal-btn{display:inline-flex;padding:11px 16px;border-radius:999px;color:#173f2c;background:#f1ca72;text-decoration:none;font-weight:900}.legal-btn.secondary{color:#fff;background:#ffffff12;border:1px solid #ffffff42}.legal-body{margin-top:20px;padding:clamp(24px,5vw,48px)}.legal-body h2{margin:34px 0 8px;color:#153f2b;font:700 26px/1.2 Georgia,serif}.legal-body h2:first-child{margin-top:0}.legal-body a{color:#1f6544;font-weight:750}.legal-note{padding:16px 18px;border-left:4px solid #d3a84c;border-radius:4px 14px 14px 4px;background:#f6eedc}.legal-footer{padding:24px 4px 0;color:#597065;font-size:13px}@media(max-width:600px){.legal-top{align-items:flex-start}.legal-back{font-size:13px}.legal-hero,.legal-body{border-radius:22px}.legal-actions{display:grid}.legal-btn{justify-content:center}}
        </style>
    </head>
    <body>
    <main class="legal-shell">
        <header class="legal-top">
            <a class="legal-brand" href="/dailybreath/"><img src="/dailybreath/assets/icons/dailybreath-mark-v2.png" alt=""><span>DAILY BREATH</span></a>
            <a class="legal-back" href="/dailybreath/settings.php">Settings &amp; About</a>
        </header>
        <section class="legal-hero">
            <span class="legal-kicker">Effective <?= htmlspecialchars($effectiveDate, ENT_QUOTES, 'UTF-8') ?></span>
            <h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="legal-actions">
                <a class="legal-btn" href="<?= $title === 'Privacy Policy' ? '/dailybreath/terms.php' : '/dailybreath/privacy.php' ?>"><?= $title === 'Privacy Policy' ? 'Read Terms & Conditions' : 'Read Privacy Policy' ?></a>
                <a class="legal-btn secondary" href="mailto:support@beyond-os.com?subject=Daily%20Breath%20legal%20question">Contact support</a>
            </div>
        </section>
        <article class="legal-body">
    <?php
}

function dailybreath_legal_end(): void
{
    ?>
        </article>
        <footer class="legal-footer">© <?= date('Y') ?> Beyond Imagination Technology · Daily Breath</footer>
    </main>
    <?= dailybreath_web_scripts() ?>
    </body>
    </html>
    <?php
}
