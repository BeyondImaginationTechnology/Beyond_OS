<?php
require_once __DIR__.'/../includes/app-layout.php';
$wallet = bos_page_start('Beyond OS', 'Privacy Policy', 'Privacy practices for Beyond OS, Beyond ID, connected apps, analytics, payments, rewards, and account controls.');
?>
<main class="bos-main">
    <section class="bos-hero">
        <span class="bos-kicker">Effective August 22, 2026</span>
        <h1>Privacy Policy</h1>
        <p>This Privacy Policy explains how Beyond Imagination Technology collects, uses, shares, protects, and lets you manage information connected to Beyond OS, Beyond ID, Beyond Wallet, and connected apps.</p>
        <div class="bos-actions">
            <a class="bos-btn" href="/legal/terms.php">Read Terms of Service</a>
            <a class="bos-btn secondary" href="/contact.php">Privacy request</a>
        </div>
    </section>

    <section class="bos-section">
        <h2>Information we collect</h2>
        <p>We collect information you provide, such as name, email address, profile details, preferences, support messages, marketplace listings, submissions, comments, uploaded files, and other content you choose to add. We also collect information needed to create and protect your account, including hashed credentials, verification status, security events, app connections, session records, and login activity.</p>

        <h2>App, wallet, marketplace, and subscription data</h2>
        <p>When you use connected apps, we may process progress, lessons, watch activity, wellness entries, creative projects, purchases, rewards, bit$ activity, transaction records, subscription status, task submissions, payout status, creator or seller activity, and related operational data. Sensitive journal, wellness, identity, and payment-related information should be access-controlled and used only for the service feature that needs it.</p>

        <h2>Payment and verification providers</h2>
        <p>Payments, subscriptions, refunds, payouts, identity checks, fraud checks, tax forms, and card or wallet features may be processed by third-party providers. We receive limited information from those providers, such as payment status, customer identifiers, transaction metadata, eligibility status, and error messages. We do not intentionally store full payment card numbers on our public web servers.</p>

        <h2>First-party visitor analytics</h2>
        <p>Beyond OS measures page views, active visits, app usage, device category, operating system, browser family, viewport size, language, timezone, referring domain, country code, and limited network information to understand performance, troubleshoot access, and improve the service. Visitor and session identifiers are one-way hashed using a protected key stored outside the public web root. URL query parameters, referrer query parameters, and full browser user-agent strings are not stored, and browser Do Not Track signals are respected.</p>

        <h2>Cookies and local storage</h2>
        <p>We use cookies, sessions, and local storage for sign-in, security, app preferences, theme and currency choices, analytics, cart or checkout flows, and remembering settings. Some features may not work properly if these technologies are disabled.</p>

        <h2>Daily Breath iOS local data and optional iCloud sync</h2>
        <p>The Daily Breath iOS app stores journal entries, Bible notes, highlights, favorite collections, challenge progress, and daily history in protected local app storage. If you explicitly enable encrypted iCloud sync, the app encrypts that private data on your device before placing it in your Apple iCloud key-value store. The encryption key is stored through iCloud Keychain. Beyond Imagination Technology does not receive the unencrypted journal or Bible-note content through this sync feature. You may turn sync off without deleting the local copy.</p>

        <h2>DailyBreath web app data</h2>
        <p>The DailyBreath web app associates saved breathing sessions, encrypted reflection journal entries, and weekly challenge progress with your Beyond ID. Journal content is encrypted before database storage. Bible highlights, favorite collections, private Bible notes, theme, narration speed, and reduced-motion preferences are stored locally in your browser and do not automatically transfer to another device. Your browser manages the installed app and offline shell. Optional newsletter signup stores the name and email address you submit so we can deliver those messages.</p>

        <h2>How we use information</h2>
        <p>We use information to authenticate users, operate connected apps, sync progress, process purchases, manage rewards and wallet activity, provide support, send service notices, personalize experiences, maintain security, prevent fraud and abuse, comply with legal obligations, debug issues, analyze performance, and improve products.</p>

        <h2>How we share information</h2>
        <p>We do not sell personal information. We may share information with connected Beyond apps, hosting providers, payment processors, email services, analytics and security tools, app stores, professional advisors, and authorities when needed to operate the services, complete a transaction, comply with law, protect users, or enforce our Terms. Public profiles, listings, comments, marketplace items, forum posts, and creator content may be visible to other users depending on the feature.</p>

        <h2>AI features</h2>
        <p>Some services may send prompts, uploaded files, generated content, or contextual app data to AI providers to produce requested outputs, moderation signals, summaries, tutoring, translations, creative assets, or support responses. Do not submit information you are not authorized to share.</p>

        <h2>Retention</h2>
        <p>We keep information while your account is active and as needed for service operation, security, legal compliance, accounting, dispute resolution, fraud prevention, backups, and legitimate business purposes. Some analytics and security records may be shortened, hashed, aggregated, or deleted on a separate schedule.</p>

        <h2>Security</h2>
        <p>We use reasonable administrative, technical, and organizational safeguards designed to protect information, including access controls, hashed passwords, session protections, and restricted administrative views. No online system can be guaranteed completely secure.</p>

        <h2>Your choices and rights</h2>
        <p>You may request access, correction, export, or deletion of your information where applicable. You can update certain profile, preference, notification, session, and app connection settings from your account dashboard. You may unsubscribe from optional marketing messages, but we may still send transactional, security, billing, or account notices.</p>

        <h2>Children</h2>
        <p>Some apps may include family-friendly, preschool, junior learning, or educational content, but Beyond ID account features are not intended for children under 13 without appropriate parental or school involvement. If you believe a child provided personal information without proper consent, contact us so we can review and take appropriate action.</p>

        <h2>International use</h2>
        <p>The services may be operated from the United States and used in other regions. By using the services, you understand that information may be processed in locations with different data protection laws than your own.</p>

        <h2>Changes to this Policy</h2>
        <p>We may update this Privacy Policy as the ecosystem evolves. When changes are material, we will take reasonable steps to provide notice, such as updating the effective date, posting a notice, or asking for consent where required.</p>

        <h2>Contact</h2>
        <p>Privacy questions and requests may be sent through <a href="/contact.php">Beyond Imagination Technology support</a>.</p>
    </section>
</main>
<?php bos_page_end(); ?>
