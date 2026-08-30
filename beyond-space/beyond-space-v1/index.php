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
$chibiCards = [
  ['group'=>'Pluto · Chibi lesson','title'=>'Pluto is still there!','copy'=>'A five-card illustrated lesson about Pluto, its heart, size, orbit, and moons.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/01-pluto-cover.jpg'],
  ['group'=>'Pluto · Chibi lesson','title'=>'Pluto has a big heart','copy'=>'Tombaugh Regio is a bright region shaped by frozen nitrogen and methane ice.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/02-pluto-heart.jpg'],
  ['group'=>'Pluto · Chibi lesson','title'=>'Smaller than the Moon','copy'=>'Pluto is a dwarf planet smaller than Earth’s Moon.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/03-pluto-size.jpg'],
  ['group'=>'Pluto · Chibi lesson','title'=>'The slowest orbit','copy'=>'One trip around the Sun takes Pluto about 248 Earth years.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/04-pluto-orbit.jpg'],
  ['group'=>'Pluto · Chibi lesson','title'=>'Five moon friends','copy'=>'Pluto and Charon orbit as a binary pair, alongside Styx, Nix, Kerberos, and Hydra.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/05-pluto-moons.jpg'],
  ['group'=>'55 Cancri e · Chibi lesson','title'=>'A diamond-rain world?','copy'=>'A hot super-Earth whose carbon-rich interior remains a scientific hypothesis, not a confirmed diamond world.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/06-diamond-rain.jpg'],
  ['group'=>'55 Cancri e · Chibi lesson','title'=>'There’s a planet made of diamonds?','copy'=>'Meet the illustrated “diamond planet” story, framed with the evidence and uncertainty.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/07-diamond-planet.jpg'],
  ['group'=>'55 Cancri e · Chibi lesson','title'=>'Space wealth story','copy'=>'A playful comparison card about the imagined value of a diamond-rich planet.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/08-diamond-value.jpg'],
  ['group'=>'55 Cancri e · Chibi lesson','title'=>'Inside 55 Cancri e','copy'=>'A visual cross-section of the carbon-rich/super-Earth concept.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/09-diamond-core.jpg'],
  ['group'=>'Venus · Chibi lesson','title'=>'Venus is hell','copy'=>'A fiery introduction to Venus: extreme heat, crushing pressure, and corrosive clouds.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/10-venus-cover.jpg'],
  ['group'=>'Venus · Chibi lesson','title'=>'You would be crushed instantly','copy'=>'Venus has surface pressure around 92 times Earth’s, with a dense carbon-dioxide atmosphere.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/11-venus-pressure.jpg'],
  ['group'=>'Venus · Chibi lesson','title'=>'Earth’s twin?','copy'=>'Venus is close to Earth in size, but a runaway greenhouse effect makes the two worlds radically different.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/12-venus-earth-twin.jpg'],
  ['group'=>'Venus · Chibi lesson','title'=>'A day longer than its year','copy'=>'Venus rotates very slowly and backward compared with most planets.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/13-venus-day-year.jpg'],
  ['group'=>'Mars · Chibi lesson','title'=>'Mars is the Red Planet','copy'=>'Iron-bearing dust and rock oxidize, giving Mars its distinctive rusty colour.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/14-mars-cover.jpg'],
  ['group'=>'Mars · Chibi lesson','title'=>'It is red because of rust','copy'=>'Iron plus oxygen produces iron oxides that spread through Martian dust.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/15-mars-rust.jpg'],
  ['group'=>'Mars · Chibi lesson','title'=>'Home to the biggest volcano','copy'=>'Olympus Mons is a giant shield volcano, built by many layers of lava.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/16-mars-volcano.jpg'],
  ['group'=>'Mars · Chibi lesson','title'=>'You can’t breathe there','copy'=>'Mars has a very thin atmosphere, freezing temperatures, and planet-wide dust storms.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/17-mars-freezing.jpg'],
  ['group'=>'Mars · Chibi lesson','title'=>'Two tiny moons','copy'=>'Phobos and Deimos are small, irregular moons that orbit Mars.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/18-earth-mars-moons.jpg'],
  ['group'=>'Uranus · Chibi lesson','title'=>'Uranus spins sideways','copy'=>'Its extreme axial tilt makes Uranus one of the most distinctive worlds in the Solar System.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/19-new-1.jpg'],
  ['group'=>'Uranus · Chibi lesson','title'=>'Why is it tilted?','copy'=>'A giant impact is one leading explanation for Uranus’s unusual tilt.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/20-new-2.jpg'],
  ['group'=>'Uranus · Chibi lesson','title'=>'The coldest planet','copy'=>'Uranus has an icy atmosphere and can reach extremely low temperatures.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/21-new-3.jpg'],
  ['group'=>'Uranus · Chibi lesson','title'=>'A hostile frozen world','copy'=>'No breathable air, extreme cold, and crushing pressure make Uranus uninhabitable.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/22-new-4.jpg'],
  ['group'=>'Uranus · Chibi lesson','title'=>'Faint rings and many moons','copy'=>'Uranus has a faint ring system and a family of major moons.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/23-new-5.jpg'],
  ['group'=>'Jupiter · Chibi lesson','title'=>'Jupiter is the king','copy'=>'The largest planet is a gas giant with a fast day and no solid surface to stand on.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/24-new-6.jpg'],
  ['group'=>'Jupiter · Chibi lesson','title'=>'Bigger than the other planets','copy'=>'Jupiter’s diameter and mass dwarf the rocky worlds of the inner Solar System.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/25-new-7.jpg'],
  ['group'=>'Jupiter · Chibi lesson','title'=>'A storm bigger than Earth','copy'=>'The Great Red Spot is a long-lived storm system, though its size changes over time.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/26-new-8.jpg'],
  ['group'=>'Jupiter · Chibi lesson','title'=>'You can’t land on Jupiter','copy'=>'Jupiter is made mostly of hydrogen and helium, with pressure rising dramatically with depth.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/27-new-9.jpg'],
  ['group'=>'Jupiter · Chibi lesson','title'=>'A huge moon family','copy'=>'Jupiter’s moons include the four Galilean moons: Io, Europa, Ganymede, and Callisto.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/28-new-10.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'Ice and rock rings','copy'=>'Saturn’s rings are mostly ice with rocky and dusty material mixed in.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/29-new-1.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'Ring rain','copy'=>'Tiny ring particles can drift into Saturn’s atmosphere in a process called ring rain.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/30-new-2.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'A lucky time to see them','copy'=>'Saturn’s rings are temporary on cosmic timescales, making our era a beautiful one to observe them.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/31-new-3.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'You can see them now','copy'=>'Saturn’s rings are a spectacular target for backyard telescopes and space missions.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/32-new-4.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'Saturn is losing its rings','copy'=>'Ring material slowly falls inward; the exact timescale is an active area of study.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/33-new-5.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'The most beautiful rings','copy'=>'A friendly introduction to Saturn’s icy ring system and why it stands out.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/34-new-6.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'Rings made of ice and rock','copy'=>'The ring particles range from tiny grains to much larger chunks of water ice.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/35-new-7.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'Could Saturn float?','copy'=>'Saturn’s average density is less than water, though no ocean is large enough to hold it.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/36-new-8.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'A ringed world to explore','copy'=>'Use the illustrated comparison as a launch point for the real Saturn imagery in the explorer.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/37-new-9.jpg'],
  ['group'=>'Saturn · Chibi lesson','title'=>'Moons with hidden oceans','copy'=>'Titan and Enceladus show why Saturn’s moon system is as exciting as its rings.','image'=>'/beyond-space/beyond-space-v1/assets/img/daily-facts/imported/38-new-10.jpg'],
];
$realImages = [
  'hero'=>'https://images-assets.nasa.gov/image/PIA19952/PIA19952~orig.jpg',
  'Mercury'=>'https://images-assets.nasa.gov/image/PIA15162/PIA15162~orig.jpg',
  'Venus'=>'https://images-assets.nasa.gov/image/PIA00271/PIA00271~orig.jpg',
  'Earth'=>'https://images-assets.nasa.gov/image/PIA18033/PIA18033~orig.jpg',
  'Mars'=>'https://images-assets.nasa.gov/image/PIA00407/PIA00407~orig.jpg',
  'Jupiter'=>'https://images-assets.nasa.gov/image/PIA22946/PIA22946~orig.jpg',
  'Saturn'=>'https://images-assets.nasa.gov/image/PIA01364/PIA01364~orig.jpg',
  'Uranus'=>'https://images-assets.nasa.gov/image/PIA18182/PIA18182~orig.jpg',
  'Neptune'=>'https://images-assets.nasa.gov/image/PIA01492/PIA01492~orig.jpg',
  'blackHole'=>'https://svs.gsfc.nasa.gov/vis/a020000/a020100/a020157/blackhole_formation.jpg',
];
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
  <figure class="hero-real-image"><img src="<?=htmlspecialchars($realImages['hero'])?>" alt="Real NASA image of Pluto" loading="eager"><figcaption>Real imagery · NASA / Johns Hopkins APL / SwRI</figcaption></figure>
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
  <div class="chibi-library reveal" id="chibiLesson">
    <div class="section-head compact"><span>Imported Meta AI card library</span><h2>Chibi Space Lessons</h2><p>Your uploaded cards are ready to publish as swipeable lessons. The science notes keep the playful art grounded in what is known.</p></div>
    <div class="chibi-viewer"><button class="carousel-button" id="chibiPrev" type="button" aria-label="Previous chibi lesson">‹</button><figure><img id="chibiImage" src="<?=htmlspecialchars($chibiCards[0]['image'])?>" alt="<?=htmlspecialchars($chibiCards[0]['title'])?>"><figcaption><span id="chibiGroup"><?=htmlspecialchars($chibiCards[0]['group'])?></span><strong id="chibiTitle"><?=htmlspecialchars($chibiCards[0]['title'])?></strong><small id="chibiCopy"><?=htmlspecialchars($chibiCards[0]['copy'])?></small></figcaption></figure><button class="carousel-button" id="chibiNext" type="button" aria-label="Next chibi lesson">›</button></div>
    <div class="chibi-footer"><span id="chibiCount">1 / <?=count($chibiCards)?></span><div class="chibi-dots" id="chibiDots" aria-label="Chibi lesson slides"></div><span class="asset-note">Upload new cards from Daily Studio</span></div>
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
    <button class="world mercury" data-planet="Mercury" data-image="<?=htmlspecialchars($realImages['Mercury'])?>" data-fact="The smallest planet and the closest world to the Sun." aria-label="Mercury"></button>
    <button class="world venus" data-planet="Venus" data-image="<?=htmlspecialchars($realImages['Venus'])?>" data-fact="A cloud-covered world with the hottest surface of any planet." aria-label="Venus"></button>
    <button class="world earth" data-planet="Earth" data-image="<?=htmlspecialchars($realImages['Earth'])?>" data-fact="Our ocean world and the only place currently known to host life." aria-label="Earth"></button>
    <button class="world mars" data-planet="Mars" data-image="<?=htmlspecialchars($realImages['Mars'])?>" data-fact="A cold desert world with giant volcanoes and signs of ancient water." aria-label="Mars"></button>
    <button class="world jupiter" data-planet="Jupiter" data-image="<?=htmlspecialchars($realImages['Jupiter'])?>" data-fact="The largest planet, famous for its Great Red Spot and vast moon system." aria-label="Jupiter"></button>
    <button class="world saturn" data-planet="Saturn" data-image="<?=htmlspecialchars($realImages['Saturn'])?>" data-fact="A gas giant surrounded by an intricate system of icy rings." aria-label="Saturn"></button>
    <button class="world uranus" data-planet="Uranus" data-image="<?=htmlspecialchars($realImages['Uranus'])?>" data-fact="An ice giant rotating almost completely on its side." aria-label="Uranus"></button>
    <button class="world neptune" data-planet="Neptune" data-image="<?=htmlspecialchars($realImages['Neptune'])?>" data-fact="The farthest major planet, with some of the fastest winds known." aria-label="Neptune"></button>
  </div>
  <div class="planet-panel reveal" id="planetPanel"><img id="planetImage" src="<?=htmlspecialchars($realImages['Earth'])?>" alt="Real NASA image of Earth"><div><span>Selected world · NASA imagery</span><h3>Earth</h3><p>Tap any planet above to begin your tour.</p></div></div>
</section>

<section class="split section feature">
  <div class="black-hole reveal"><img src="https://svs.gsfc.nasa.gov/vis/a020000/a020100/a020157/blackhole_formation.jpg" alt="NASA visualization of a black hole accretion disk" loading="lazy"><div class="disc"></div><div class="core"></div><span>Tap to distort spacetime</span></div>
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
<script>window.BS_STORIES = <?= json_encode($featured, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>; window.BS_SIGNS = <?= json_encode($signs, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>; window.BS_CHIBI_CARDS = <?= json_encode($chibiCards, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="/beyond-space/beyond-space-v1/assets/js/app.js?v=1.0.0"></script>
<script src="/assets/js/visitor-analytics.js" defer></script></body></html>
