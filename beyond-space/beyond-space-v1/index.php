<?php
require_once __DIR__ . '/../../includes/ecosystem.php';
$beyondWallet = beyond_app_bootstrap('Beyond Space', false);
$featured = [
  ['title'=>'Solar System','eyebrow'=>'Explore','icon'=>'🪐','copy'=>'Travel from the Sun to Neptune and compare every world along the way.'],
  ['title'=>'Deep Space','eyebrow'=>'Discover','icon'=>'🌌','copy'=>'Explore galaxies, nebulae, stars, pulsars, and the mysteries beyond our neighbourhood.'],
  ['title'=>'Space Technology','eyebrow'=>'Innovation','icon'=>'🚀','copy'=>'Learn how rockets, satellites, telescopes, rovers, and space stations work.'],
  ['title'=>'Life Beyond Earth','eyebrow'=>'Astrobiology','icon'=>'👽','copy'=>'Investigate habitable worlds, biosignatures, ocean moons, and the search for life.'],
];
$dailySpaceFacts = [
  ['number'=>1,'world'=>'Pluto','title'=>'Pluto has a heart-shaped glacier.','fact'=>'Pluto’s bright Tombaugh Regio includes a vast nitrogen-ice plain that helps drive winds and weather across its surface.','lesson'=>'Dwarf planets can have active landscapes, weather, and complex geology.','source_url'=>'https://www.instagram.com/beyondspaceapp/stories/highlights/17997645209797807/'],
  ['number'=>2,'world'=>'Venus','title'=>'Venus is hell.','fact'=>'Venus reaches about 464°C, has crushing atmospheric pressure, and clouds of sulfuric acid. Its surface is hostile, but its atmosphere teaches us about runaway greenhouse warming.','lesson'=>'Atmosphere and pressure can transform a planet’s climate.','source_url'=>'https://www.instagram.com/beyondspaceapp/p/DcZ8zHaGwcF/'],
  ['number'=>3,'world'=>'Diamond Planet','title'=>'There’s a planet made of diamonds?','fact'=>'55 Cancri e is a hot super-Earth whose composition is still being studied; earlier models suggested a carbon-rich interior, but “diamond planet” remains a hypothesis.','lesson'=>'Astronomy separates an exciting possibility from a confirmed discovery.','source_url'=>'https://www.instagram.com/beyondspaceapp/p/DcZ-HqVG-yp/'],
  ['number'=>4,'world'=>'Mars','title'=>'Mars is the Red Planet.','fact'=>'Iron minerals in Martian dust oxidize and give Mars its rusty red colour. Valleys, deltas, minerals, and sediments also show that ancient water shaped parts of its surface.','lesson'=>'Colour can be a clue to chemistry, while landscapes preserve planetary history.','source_url'=>'https://www.instagram.com/beyondspaceapp/p/DccfoJUG8pL/'],
  ['number'=>5,'world'=>'Jupiter','title'=>'Jupiter is the king of planets.','fact'=>'Jupiter is the largest planet in the Solar System, a gas giant with no solid surface, and its day lasts about 10 hours.','lesson'=>'Size, composition, and rotation help distinguish the worlds in our Solar System.','source_url'=>'https://www.instagram.com/beyondspaceapp/p/DccnVoum1hZ/'],
  ['number'=>6,'world'=>'Saturn','title'=>'Saturn has the most beautiful rings.','fact'=>'Saturn’s rings are made mostly of ice and rock. They may be only around 100 million years old—far younger than Saturn itself.','lesson'=>'A planet’s visible features can form and change long after the planet.','source_url'=>'https://www.instagram.com/beyondspaceapp/p/DccpDJSm73U/'],
  ['number'=>7,'world'=>'Uranus','title'=>'Uranus spins sideways.','fact'=>'Uranus has an axial tilt of about 98 degrees, a day of roughly 17 hours, a year of 84 Earth years, and temperatures near −224°C.','lesson'=>'Today’s lesson: compare composition, atmosphere, rotation, and orbit before drawing conclusions about a planet’s climate.','source_url'=>'https://www.instagram.com/beyondspaceapp/p/Dccq5qnm-dT/'],
];
$dailyFactStart = new DateTimeImmutable('2026-08-24');
$dailyFactToday = new DateTimeImmutable('today');
$dailyFactOffset = max(0, min(count($dailySpaceFacts) - 1, (int)$dailyFactStart->diff($dailyFactToday)->format('%r%a')));
$dailyFact = $dailySpaceFacts[$dailyFactOffset];
$signs = [
  ['name'=>'Aries','symbol'=>'♈','dates'=>'Mar 21 – Apr 19','element'=>'Fire','message'=>'Lead with curiosity today. A bold question may open a surprising path.'],
  ['name'=>'Taurus','symbol'=>'♉','dates'=>'Apr 20 – May 20','element'=>'Earth','message'=>'Slow down and notice the details. Steady progress beats a rushed launch.'],
  ['name'=>'Gemini','symbol'=>'♊','dates'=>'May 21 – Jun 20','element'=>'Air','message'=>'Share an idea, ask a question, and let conversation spark discovery.'],
  ['name'=>'Cancer','symbol'=>'♋','dates'=>'Jun 21 – Jul 22','element'=>'Water','message'=>'Protect your energy while staying open to one meaningful connection.'],
  ['name'=>'Leo','symbol'=>'♌','dates'=>'Jul 23 – Aug 22','element'=>'Fire','message'=>'Let your creativity take the spotlight. Build something unmistakably yours.'],
  ['name'=>'Virgo','symbol'=>'♍','dates'=>'Aug 23 – Sep 22','element'=>'Earth','message'=>'A small adjustment can improve the whole system. Refine before expanding.'],
  ['name'=>'Libra','symbol'=>'♎','dates'=>'Sep 23 – Oct 22','element'=>'Air','message'=>'Balance imagination with evidence. The strongest choice may combine both.'],
  ['name'=>'Scorpio','symbol'=>'♏','dates'=>'Oct 23 – Nov 21','element'=>'Water','message'=>'Look beneath the surface. A hidden pattern is ready to be understood.'],
  ['name'=>'Sagittarius','symbol'=>'♐','dates'=>'Nov 22 – Dec 21','element'=>'Fire','message'=>'Explore beyond the familiar. A new subject may become your next obsession.'],
  ['name'=>'Capricorn','symbol'=>'♑','dates'=>'Dec 22 – Jan 19','element'=>'Earth','message'=>'Choose one ambitious target and give it structure. Momentum follows clarity.'],
  ['name'=>'Aquarius','symbol'=>'♒','dates'=>'Jan 20 – Feb 18','element'=>'Air','message'=>'Your unusual perspective is useful today. Test the idea instead of shrinking it.'],
  ['name'=>'Pisces','symbol'=>'♓','dates'=>'Feb 19 – Mar 20','element'=>'Water','message'=>'Make room for wonder, then ground it with one practical next step.'],
];
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#030612">
  <title>Beyond Space — Academy, Astronomy & Horoscopes</title>
  <meta name="description" content="Explore a complete astronomy academy, interactive space science, daily entertainment horoscopes, and zodiac reflections.">
  <link rel="icon" href="/beyond-space/beyond-space-v1/assets/img/beyond-space-logo.webp">
  <link rel="stylesheet" href="/beyond-space/beyond-space-v1/assets/css/app.css?v=1.0.0">
  <link rel="stylesheet" href="/beyond-space/beyond-space-v1/assets/css/enhancements.css?v=2.4">
</head>
<body>
<div class="space-dust" aria-hidden="true"></div>
<header class="topbar">
  <a class="brand" href="#top" aria-label="Beyond Space home">
    <img src="/beyond-space/beyond-space-v1/assets/img/beyond-space-logo.webp" alt="Beyond Space logo">
    <span><b>Beyond Space</b><small>Explorer + Academy</small></span>
  </a>
  <button class="menu" id="menuBtn" aria-label="Open menu">☰</button>
  <nav id="nav">
    <a href="#daily-fact">Daily Fact</a><a href="#explore">Explore</a><a href="/beyond-space/academy.php">Academy</a><a href="#system">Solar System</a><a href="#horoscope">Daily Astrology</a><a href="#quiz">Quiz</a>
  </nav>
</header>

<main id="top">
<section class="hero">
  <div class="nebula"></div><div class="planet planet-one"></div><div class="planet planet-two"></div><div class="moon"></div>
  <div class="orbit orbit-a"><i></i></div><div class="orbit orbit-b"><i></i></div><div class="rocket" aria-hidden="true">🚀</div>
  <div class="hero-copy reveal">
    <span class="kicker">🚀 Launch into the unknown</span>
    <h1>The universe is<br><em>yours to explore.</em></h1>
    <p>Discover planets, galaxies, astronomy, space technology, possible life beyond Earth, and a daily astrology experience—all inside one animated learning world.</p>
    <div class="actions"><a class="btn primary" href="/beyond-space/academy.php">Enter Space Academy</a><a class="btn ghost" href="#explore">Explore universe</a><button class="btn ghost" id="watchIntro">▶ Watch launch</button></div>
    <div class="stats"><span><b>50</b> academy lessons</span><span><b>8</b> planets</span><span><b>12</b> zodiac signs</span></div>
  </div>
  <button class="scroll-cue" aria-label="Scroll to explore" onclick="document.querySelector('#explore').scrollIntoView({behavior:'smooth'})">⌄</button>
</section>

<section class="section daily-fact" id="daily-fact">
  <div class="daily-fact-card reveal">
    <div class="daily-fact-top"><span class="kicker">Daily Space Fact · <?= $dailyFact['number'] ?>/55</span><span class="daily-fact-date"><?=htmlspecialchars(date('l · M j, Y'))?></span></div>
    <div class="daily-fact-grid"><div><span class="daily-fact-world" id="dailyFactWorld">🪐 <?=htmlspecialchars($dailyFact['world'])?></span><h2 id="dailyFactTitle"><?=htmlspecialchars($dailyFact['title'])?></h2><p id="dailyFactCopy"><?=htmlspecialchars($dailyFact['fact'])?></p><?php if(!empty($dailyFact['source_url'])):?><a class="daily-fact-source" id="dailyFactSource" href="<?=htmlspecialchars($dailyFact['source_url'])?>" target="_blank" rel="noopener">Imported from Beyond Space Instagram ↗</a><?php else:?><a class="daily-fact-source" id="dailyFactSource" hidden target="_blank" rel="noopener"></a><?php endif;?><img class="daily-fact-art" id="dailyFactAsset" hidden alt=""></div><aside><strong>Today’s lesson</strong><p id="dailyFactLesson"><?=htmlspecialchars($dailyFact['lesson'])?></p><a class="btn ghost" href="/beyond-space/academy.php?view=lesson&amp;age=cosmic-explorer&amp;module=solar-system-planetary-science&amp;lesson=7">Open the lesson →</a></aside></div>
  </div>
</section>

<section class="section" id="explore">
  <div class="section-head reveal"><span>Choose a destination</span><h2>Explore Beyond Earth</h2><p>Every destination opens as a visual, interactive story rather than a static textbook page.</p></div>
  <div class="cards">
    <?php foreach($featured as $i=>$item): ?>
    <button class="card reveal" data-story="<?= $i ?>">
      <span class="card-icon"><?= htmlspecialchars($item['icon']) ?></span>
      <small><?= htmlspecialchars($item['eyebrow']) ?></small>
      <h3><?= htmlspecialchars($item['title']) ?></h3>
      <p><?= htmlspecialchars($item['copy']) ?></p>
      <b>Open experience →</b>
    </button>
    <?php endforeach; ?>
  </div>
</section>

<section class="section solar" id="system">
  <div class="section-head reveal"><span>Interactive orbit</span><h2>Solar System Explorer</h2><p>Select a planet to reveal its quick profile.</p></div>
  <div class="solar-stage reveal">
    <div class="sun"></div>
    <button class="world mercury" data-planet="Mercury" data-fact="The smallest planet and the closest world to the Sun." aria-label="Mercury"></button>
    <button class="world venus" data-planet="Venus" data-fact="A cloud-covered world with the hottest surface of any planet." aria-label="Venus"></button>
    <button class="world earth" data-planet="Earth" data-fact="Our ocean world and the only place currently known to host life." aria-label="Earth"></button>
    <button class="world mars" data-planet="Mars" data-fact="A cold desert world with giant volcanoes and signs of ancient water." aria-label="Mars"></button>
    <button class="world jupiter" data-planet="Jupiter" data-fact="The largest planet, famous for its Great Red Spot and vast moon system." aria-label="Jupiter"></button>
    <button class="world saturn" data-planet="Saturn" data-fact="A gas giant surrounded by an intricate system of icy rings." aria-label="Saturn"></button>
    <button class="world uranus" data-planet="Uranus" data-fact="An ice giant rotating almost completely on its side." aria-label="Uranus"></button>
    <button class="world neptune" data-planet="Neptune" data-fact="The farthest major planet, with some of the fastest winds known." aria-label="Neptune"></button>
  </div>
  <div class="planet-panel reveal" id="planetPanel"><span>Selected world</span><h3>Earth</h3><p>Tap any planet above to begin your tour.</p></div>
</section>

<section class="split section feature">
  <div class="black-hole reveal"><div class="disc"></div><div class="core"></div><span>Tap to distort spacetime</span></div>
  <div class="reveal"><span class="kicker">Cosmic phenomenon</span><h2>Inside a Black Hole</h2><p>Use animations to visualize gravity, the event horizon, accretion disks, and why light bends around massive objects.</p>
    <div class="fact" id="factBox"><b>Quick fact</b><span>A black hole is detected through its effects on nearby matter and light.</span></div>
    <button class="btn primary" id="nextFact">Reveal another fact</button>
  </div>
</section>

<section class="section horoscope" id="horoscope">
  <div class="section-head reveal"><span>Astrology • For entertainment</span><h2>Your Daily Cosmic Reading</h2><p>Every day gets a fresh sign-based reflection. Choose a sign or use the sun sign finder; astronomy and astrology remain clearly separated throughout the app.</p></div>
  <div class="astro-profile reveal">
    <div><span class="kicker">Sun sign finder</span><h3>Build your astrology profile</h3><p>Enter a birth date to find the traditional Western sun sign. Your choice can be remembered on this device.</p></div>
    <label>Birth date<input id="birthDate" type="date"></label><button class="btn primary" id="findSign" type="button">Find my sign</button><output id="signResult" aria-live="polite">No birth date saved.</output>
  </div>
  <div class="horoscope-layout">
    <div class="zodiac-grid reveal" id="zodiacGrid">
      <?php foreach($signs as $i=>$sign): ?>
      <button data-sign="<?= $i ?>"><b><?= $sign['symbol'] ?></b><span><?= htmlspecialchars($sign['name']) ?></span></button>
      <?php endforeach; ?>
    </div>
    <article class="reading reveal" id="reading"><span class="reading-symbol">♈</span><small>Daily reading · <span id="readingDate"><?=htmlspecialchars(date('M j'))?></span> · <span id="readingSource">Beyond Space original</span></small><h3>Aries</h3><em>Mar 21 – Apr 19</em><div class="reading-meta"><span id="readingElement">Fire</span><span id="readingEnergy">Energy: initiate</span></div><p>Lead with curiosity today. A bold question may open a surprising path.</p><small class="disclaimer">Astrology content is provided for reflection and entertainment, not as scientific, medical, legal, financial, or professional advice.</small></article>
  </div>
  <div class="compatibility reveal">
    <div><span class="kicker">Compatibility explorer</span><h3>Compare two signs</h3><p>A playful reflection on traditional element pairings—not a prediction about any relationship.</p></div>
    <label>First sign<select id="compatOne"><?php foreach($signs as $sign):?><option><?=htmlspecialchars($sign['name'])?></option><?php endforeach;?></select></label><label>Second sign<select id="compatTwo"><?php foreach($signs as $sign):?><option><?=htmlspecialchars($sign['name'])?></option><?php endforeach;?></select></label><button class="btn ghost" id="compareSigns" type="button">Compare</button><output id="compatResult" aria-live="polite">Choose two signs to explore.</output>
  </div>
</section>

<section class="section missions">
  <div class="section-head reveal"><span>Humanity in motion</span><h2>Mission Timeline</h2></div>
  <div class="timeline" role="list">
    <article class="era reveal"><b>1957</b><h3>Sputnik 1</h3><p>The first artificial satellite begins the space age.</p></article>
    <article class="era reveal"><b>1969</b><h3>Apollo 11</h3><p>Humans walk on the Moon for the first time.</p></article>
    <article class="era reveal"><b>1977</b><h3>Voyager</h3><p>Twin probes begin a journey through and beyond the outer Solar System.</p></article>
    <article class="era reveal"><b>2021</b><h3>James Webb</h3><p>A new infrared observatory opens an extraordinary window on cosmic history.</p></article>
  </div>
</section>

<section class="section quiz" id="quiz">
  <div class="section-head reveal"><span>One-minute challenge</span><h2>Test your space knowledge</h2></div>
  <div class="quiz-box reveal">
    <p id="question">Which planet is the largest in our Solar System?</p>
    <div id="answers"></div><div id="feedback" aria-live="polite"></div>
    <button class="btn ghost hidden" id="nextQuestion">Next question</button>
  </div>
</section>

<section class="section beta" id="about">
  <div class="reveal"><span class="kicker">Built for Beyond Learn</span><h2>A complete cosmic learning world.</h2><p>Study five astronomy modules, complete 50 narrated lessons, run mission labs, save progress, pass assessments, explore the Solar System, and enjoy a clearly separated astrology experience.</p><div class="actions"><a class="btn primary" href="/beyond-space/academy.php">Start the free module</a></div></div>
  <div class="beta-list reveal"><span>✓ Five complete science modules</span><span>✓ 50 narrated lessons and labs</span><span>✓ Saved progress and assessments</span><span>✓ Daily horoscope and sign profile</span><span>✓ Mobile and motion-reduction support</span></div>
</section>
</main>

<footer><img src="/beyond-space/beyond-space-v1/assets/img/beyond-space-logo.webp" alt=""><p>Beyond Space • Part of Beyond Learn</p><small>Astronomy education with a separate entertainment astrology experience</small></footer>

<div class="modal" id="modal" aria-hidden="true"><div class="modal-card"><button class="close" aria-label="Close">×</button><span id="modalIcon">🪐</span><small id="modalEyebrow"></small><h2 id="modalTitle"></h2><p id="modalCopy"></p><a class="btn primary" href="/beyond-space/academy.php">Study this in Space Academy</a></div></div>
<div class="modal" id="videoModal" aria-hidden="true"><div class="modal-card video-card"><button class="close" aria-label="Close">×</button><div class="cinema"><div class="cinema-earth"></div><div class="cinema-moon"></div><div class="cinema-rocket">🚀</div></div><h2>Welcome to Beyond Space</h2><p>A lightweight animated launch sequence ready to be replaced by an MP4 or WebM cinematic.</p></div></div>
<script>window.BS_STORIES = <?= json_encode($featured, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>; window.BS_SIGNS = <?= json_encode($signs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="/beyond-space/beyond-space-v1/assets/js/app.js?v=1.0.0"></script>
<script src="/assets/js/visitor-analytics.js" defer></script></body></html>
