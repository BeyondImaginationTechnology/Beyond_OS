<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/ecosystem.php';
beyond_nav_bootstrap('Investor Brief');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#070915">
<title>Investor Brief | Beyond OS</title>
<meta name="description" content="Beyond OS is a web-first consumer ecosystem connecting health, education, creator commerce and entertainment through one identity and rewards layer.">
<link rel="icon" type="image/svg+xml" href="/assets/images/bos-logo-mark.svg">
<style>
:root{--bg:#050713;--panel:#0d1224;--text:#f7f8ff;--muted:#aeb5c9;--line:rgba(255,255,255,.12);--purple:#7b67ff;--pink:#ec4caa;--green:#51db78;--gold:#ffbf32;--blue:#448cff}*{box-sizing:border-box}html{scroll-behavior:smooth;background:var(--bg)}body{margin:0;color:var(--text);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:radial-gradient(circle at 82% 3%,rgba(107,76,255,.2),transparent 28%),linear-gradient(180deg,#070a18 0,#03050d 72%);overflow-x:hidden}a{color:inherit}.wrap{width:min(1160px,calc(100% - 32px));margin-inline:auto}.top{min-height:82px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{display:flex;align-items:center;text-decoration:none;font-size:21px;font-weight:850;letter-spacing:-.045em}.brand img{width:37px;height:37px;margin-right:10px;border:1px solid rgba(255,255,255,.14);border-radius:11px;background:#090b18}.brand span{margin-left:5px;color:#a998ff}.top nav{display:flex;align-items:center;gap:24px}.top nav a{text-decoration:none;color:#c3c9d8;font-size:13px;font-weight:700}.button{display:inline-flex;align-items:center;justify-content:center;min-height:49px;padding:0 21px;border:1px solid rgba(255,255,255,.16);border-radius:12px;background:linear-gradient(105deg,#526dff,#8658f6 50%,#e950aa);color:#fff!important;text-decoration:none;font-size:13px;font-weight:800;box-shadow:0 14px 36px rgba(101,72,255,.28)}.button.secondary{background:rgba(255,255,255,.045);box-shadow:none}.eyebrow{display:inline-flex;align-items:center;gap:9px;color:#b9adff;font-size:11px;font-weight:850;letter-spacing:.16em;text-transform:uppercase}.eyebrow:before{content:"";width:7px;height:7px;border-radius:50%;background:#7f67ff;box-shadow:0 0 14px #7f67ff}.hero{padding:92px 0 72px}.hero h1{max-width:980px;margin:24px 0 25px;font-size:clamp(58px,8.4vw,108px);line-height:.88;letter-spacing:-.075em}.hero h1 span{display:block;background:linear-gradient(100deg,#76e798 0,#7b8cff 48%,#ec66b4 92%);background-clip:text;color:transparent}.hero-copy{display:grid;grid-template-columns:1.2fr .8fr;gap:50px;align-items:end}.hero-copy>p{max-width:700px;margin:0;color:#d1d6e4;font-size:clamp(18px,2.25vw,25px);line-height:1.5;font-weight:560}.hero-actions{display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap}.facts{display:grid;grid-template-columns:repeat(4,1fr);border:1px solid var(--line);border-radius:23px;background:rgba(255,255,255,.035);overflow:hidden;box-shadow:0 26px 80px rgba(0,0,0,.26)}.fact{padding:28px;border-right:1px solid var(--line)}.fact:last-child{border-right:0}.fact strong{display:block;font-size:clamp(31px,4vw,48px);line-height:1;letter-spacing:-.055em}.fact span{display:block;margin-top:9px;color:var(--muted);font-size:12px;line-height:1.45}.fact:last-child strong{font-size:24px;line-height:1.05;color:#8ce5a8}.section{padding:104px 0}.section-head{display:grid;grid-template-columns:1fr 1fr;gap:50px;align-items:end;margin-bottom:38px}.section h2{max-width:760px;margin:15px 0 0;font-size:clamp(42px,6vw,72px);line-height:.96;letter-spacing:-.06em}.section-head p{max-width:540px;margin:0;color:var(--muted);font-size:16px;line-height:1.7}.thesis-grid{display:grid;grid-template-columns:1.15fr .85fr;gap:14px}.thesis-card{min-height:300px;padding:32px;border:1px solid var(--line);border-radius:24px;background:linear-gradient(145deg,rgba(255,255,255,.065),rgba(255,255,255,.02))}.thesis-card.main{grid-row:span 2;min-height:615px;display:flex;flex-direction:column;justify-content:flex-end;background:radial-gradient(circle at 74% 18%,rgba(112,87,255,.28),transparent 34%),radial-gradient(circle at 28% 12%,rgba(81,219,120,.13),transparent 28%),linear-gradient(155deg,#121833,#090b18)}.thesis-card .index{color:#9d8cff;font-size:11px;font-weight:900;letter-spacing:.16em}.thesis-card h3{margin:18px 0 10px;font-size:clamp(27px,4vw,43px);line-height:1.02;letter-spacing:-.05em}.thesis-card p{max-width:620px;margin:0;color:var(--muted);font-size:15px;line-height:1.68}.loop{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.loop-card{position:relative;min-height:270px;padding:25px;border:1px solid var(--line);border-radius:22px;background:linear-gradient(150deg,rgba(18,23,45,.92),rgba(7,10,22,.98));overflow:hidden}.loop-card:before{content:"";position:absolute;inset:0 0 auto;height:3px;background:var(--accent)}.loop-card:nth-child(1){--accent:var(--pink)}.loop-card:nth-child(2){--accent:var(--gold)}.loop-card:nth-child(3){--accent:var(--blue)}.loop-card:nth-child(4){--accent:var(--green)}.loop-card b{color:var(--accent);font-size:11px;letter-spacing:.14em}.loop-card h3{margin:25px 0 11px;font-size:27px;letter-spacing:-.045em}.loop-card p{margin:0;color:var(--muted);font-size:14px;line-height:1.62}.loop-card span{position:absolute;right:18px;bottom:12px;color:rgba(255,255,255,.05);font-size:65px;font-weight:900}.model{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}.model-card{padding:28px;border:1px solid var(--line);border-radius:20px;background:rgba(255,255,255,.035)}.model-card h3{margin:0 0 8px;font-size:21px;letter-spacing:-.035em}.model-card p{margin:0;color:var(--muted);font-size:14px;line-height:1.6}.model-card small{display:block;margin-top:18px;color:#a99cff;font-size:10px;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.roadmap{display:grid;grid-template-columns:.9fr 1.1fr;gap:16px}.focus{padding:34px;border:1px solid rgba(124,97,255,.4);border-radius:24px;background:radial-gradient(circle at 90% 10%,rgba(236,76,170,.16),transparent 33%),linear-gradient(145deg,rgba(38,35,96,.75),rgba(22,16,48,.8))}.focus h3{margin:16px 0 12px;font-size:36px;letter-spacing:-.05em}.focus p{margin:0;color:#c8cede;line-height:1.65}.milestones{display:grid;gap:10px}.milestone{display:grid;grid-template-columns:auto 1fr;gap:18px;padding:20px 22px;border:1px solid var(--line);border-radius:18px;background:rgba(255,255,255,.035)}.milestone b{display:grid;width:34px;height:34px;place-items:center;border-radius:10px;background:rgba(123,103,255,.16);color:#bcb1ff;font-size:12px}.milestone h3{margin:0 0 5px;font-size:17px}.milestone p{margin:0;color:var(--muted);font-size:13px;line-height:1.5}.cta{margin:20px auto 90px;padding:48px;border:1px solid rgba(132,102,255,.38);border-radius:28px;background:radial-gradient(circle at 88% 18%,rgba(236,76,170,.23),transparent 30%),linear-gradient(120deg,rgba(32,35,92,.95),rgba(53,21,70,.92));display:flex;align-items:center;justify-content:space-between;gap:32px}.cta h2{max-width:720px;margin:12px 0 10px;font-size:clamp(34px,5vw,58px);line-height:.98;letter-spacing:-.055em}.cta p{max-width:640px;margin:0;color:#c8cede;line-height:1.6}.cta .button{flex:0 0 auto}.footer{padding:32px 0 45px;border-top:1px solid var(--line);display:flex;justify-content:space-between;gap:20px;color:#858da2;font-size:11px}.footer a{text-decoration:none}.disclosure{max-width:720px;line-height:1.5}@media(max-width:900px){.top nav>a:not(.button){display:none}.hero-copy,.section-head,.thesis-grid,.roadmap{grid-template-columns:1fr}.hero-actions{justify-content:flex-start}.facts{grid-template-columns:repeat(2,1fr)}.fact:nth-child(2){border-right:0}.fact:nth-child(-n+2){border-bottom:1px solid var(--line)}.thesis-card.main{grid-row:auto;min-height:400px}.loop{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.wrap{width:min(100% - 22px,1160px)}.top{min-height:72px}.brand{font-size:18px}.brand img{width:32px;height:32px}.top .button{min-height:42px;padding-inline:14px}.hero{padding:68px 0 55px}.hero h1{font-size:clamp(53px,17vw,75px)}.hero-copy{gap:28px}.hero-actions{display:grid}.facts{grid-template-columns:1fr}.fact{border-right:0;border-bottom:1px solid var(--line)!important}.fact:last-child{border-bottom:0!important}.section{padding:76px 0}.section-head{gap:20px}.section h2{font-size:42px}.thesis-card,.focus{padding:25px}.thesis-card.main{min-height:340px}.loop,.model{grid-template-columns:1fr}.loop-card{min-height:220px}.cta{padding:31px 25px;align-items:flex-start;flex-direction:column}.cta .button{width:100%}.footer{flex-direction:column}}
</style>
</head>
<body>
<header class="top wrap">
    <a class="brand" href="/"><img src="/assets/images/bos-logo-mark.svg" alt="">BEYOND <span>OS</span></a>
    <nav aria-label="Investor brief navigation"><a href="#thesis">Thesis</a><a href="#model">Model</a><a href="#roadmap">Seed focus</a><a class="button secondary" href="/">Explore product</a></nav>
</header>
<main>
    <section class="hero wrap">
        <span class="eyebrow">Beyond Imagination Corp. · Seed-stage brief</span>
        <h1>One identity.<span>A universe of useful apps.</span></h1>
        <div class="hero-copy">
            <p>Beyond OS is a web-first consumer ecosystem connecting wellness, education, creator commerce and entertainment—designed so every product increases the value of the next.</p>
            <div class="hero-actions"><a class="button" href="#thesis">Explore the thesis ↓</a><a class="button secondary" href="/app-store/">Try the products</a></div>
        </div>
    </section>
    <section class="facts wrap" aria-label="Platform facts">
        <div class="fact"><strong>18</strong><span>web product hubs in active development</span></div>
        <div class="fact"><strong>4</strong><span>connected consumer pillars</span></div>
        <div class="fact"><strong>1</strong><span>shared identity and wallet layer</span></div>
        <div class="fact"><strong>Web-first</strong><span>testable now, before native distribution</span></div>
    </section>
    <section class="section wrap" id="thesis">
        <div class="section-head"><div><span class="eyebrow">The platform thesis</span><h2>Consumer apps are fragmented. Human lives are not.</h2></div><p>People learn, create, earn and unwind across disconnected products, accounts and reward systems. Beyond OS is designed as the connective layer: distinct experiences that share identity, progress and value.</p></div>
        <div class="thesis-grid">
            <article class="thesis-card main"><span class="index">01 · THE OPPORTUNITY</span><h3>Build focused products.<br>Compound them as a platform.</h3><p>Each Beyond product can solve a specific need on its own. Together, the ecosystem creates more reasons to return, more ways to discover value and a shared relationship with the customer.</p></article>
            <article class="thesis-card"><span class="index">02 · THE WEDGE</span><h3>Useful experiences, available on the web.</h3><p>DailyBreath, Beyond Academy, Beyond TV, Games and creator tools make the platform tangible today—without waiting for an app-store launch.</p></article>
            <article class="thesis-card"><span class="index">03 · THE MOAT</span><h3>Shared context across every product.</h3><p>Beyond ID, bit$ rewards and a unified catalog create the foundation for cross-product personalization, progression and distribution.</p></article>
        </div>
    </section>
    <section class="section wrap">
        <div class="section-head"><div><span class="eyebrow">The engagement loop</span><h2>Four pillars. One compounding relationship.</h2></div><p>Content attracts attention, tools create utility, rewards recognize participation and identity carries that value across the ecosystem.</p></div>
        <div class="loop">
            <article class="loop-card"><b>LIVE</b><h3>Build a habit</h3><p>Wellness and daily-life tools create repeat reasons to open Beyond OS.</p><span>01</span></article>
            <article class="loop-card"><b>LEARN</b><h3>Make progress</h3><p>Academies turn curiosity into visible skills, practice and achievement.</p><span>02</span></article>
            <article class="loop-card"><b>EARN</b><h3>Create value</h3><p>Marketplace, seller tools and bit$ connect contribution with rewards.</p><span>03</span></article>
            <article class="loop-card"><b>EXPLORE</b><h3>Stay engaged</h3><p>TV, games and discovery surfaces bring people back into the loop.</p><span>04</span></article>
        </div>
    </section>
    <section class="section wrap" id="model">
        <div class="section-head"><div><span class="eyebrow">Business-model design</span><h2>Multiple revenue paths, one customer relationship.</h2></div><p>The seed-stage goal is to validate which products drive durable engagement and which monetization paths best fit that behavior. These are planned revenue engines, not reported revenue.</p></div>
        <div class="model">
            <article class="model-card"><h3>Premium memberships</h3><p>Optional paid tiers for deeper learning, wellness and entertainment experiences.</p><small>Recurring revenue path</small></article>
            <article class="model-card"><h3>Creator marketplace</h3><p>Transaction revenue from digital and physical goods sold through Beyond Market.</p><small>Commerce path</small></article>
            <article class="model-card"><h3>Digital goods &amp; upgrades</h3><p>Cosmetic, creative and utility purchases across games and creator experiences.</p><small>Product-led path</small></article>
            <article class="model-card"><h3>Aligned partnerships</h3><p>Selective sponsorship, distribution and institutional programs that fit the ecosystem.</p><small>Partnership path</small></article>
        </div>
    </section>
    <section class="section wrap" id="roadmap">
        <div class="section-head"><div><span class="eyebrow">Seed focus</span><h2>Turn breadth into a repeatable growth engine.</h2></div><p>Capital would move Beyond OS from a broad working product universe toward a measured, focused platform with clear acquisition, retention and monetization signals.</p></div>
        <div class="roadmap">
            <article class="focus"><span class="eyebrow">What this round unlocks</span><h3>Focus. Measure. Scale.</h3><p>Prioritize the highest-retention web experiences, strengthen the shared platform underneath them and use evidence—not assumptions—to sequence native expansion.</p></article>
            <div class="milestones">
                <article class="milestone"><b>01</b><div><h3>Prove the wedge</h3><p>Concentrate onboarding and activation around the strongest repeat-use products.</p></div></article>
                <article class="milestone"><b>02</b><div><h3>Harden the platform</h3><p>Unify Beyond ID, rewards, analytics, safety and cross-product navigation.</p></div></article>
                <article class="milestone"><b>03</b><div><h3>Validate monetization</h3><p>Test premium and marketplace behavior with clear cohort-level measurement.</p></div></article>
                <article class="milestone"><b>04</b><div><h3>Expand with evidence</h3><p>Bring proven experiences to iOS and other native surfaces after web validation.</p></div></article>
            </div>
        </div>
    </section>
    <section class="cta wrap">
        <div><span class="eyebrow">Founder conversation</span><h2>See the product, then talk about what comes next.</h2><p>The live web ecosystem is the demo. The investor brief is the starting point for a deeper conversation about the roadmap, round and milestones.</p></div>
        <a class="button" href="mailto:support@beyondimagination.co.technology?subject=Beyond%20OS%20investor%20briefing">Request a briefing →</a>
    </section>
</main>
<footer class="footer wrap"><span class="disclosure">Product and strategy overview for discussion purposes. Planned features, revenue paths and milestones are forward-looking and are not a solicitation or offer of securities.</span><span>© 2026 Beyond Imagination Corp.</span></footer>
<script src="/assets/js/visitor-analytics.js" defer></script>
</body>
</html>
