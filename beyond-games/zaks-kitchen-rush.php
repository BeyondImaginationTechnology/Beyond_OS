<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Zak’s Kitchen Rush');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,user-scalable=no">
  <meta name="theme-color" content="#17130d">
  <title>Zak’s Kitchen Rush | Beyond Games</title>
  <meta name="description" content="Prepare Haitian and American dishes, manage timed orders and upgrade Zak’s Kitchen in this original Beyond Games restaurant game.">
  <link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>">
  <link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>">
  <link rel="stylesheet" href="/beyond-games/assets/css/zaks-kitchen-3d.css?v=20260727-1">
</head>
<body class="bos-page kitchen-page">
<main class="kitchen-shell">
  <header class="kitchen-header">
    <a href="/beyond-games/">← Beyond Games</a>
    <div class="kitchen-brand"><span>🍲</span><div><b>ZAK’S KITCHEN RUSH</b><small>Haitian flavour · American classics</small></div></div>
    <button id="soundToggle" type="button" aria-pressed="true">Sound: On</button>
  </header>

  <section class="game-stage" id="gameStage" aria-label="Zak’s Kitchen Rush game">
    <div class="kitchen-scene" aria-hidden="true">
      <div class="awning"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
      <div class="scene-sign"><span>Zak’s</span><b>KITCHEN</b><small>GOOD FOOD · GOOD PEOPLE</small></div>
      <div class="steam steam-one">〰</div><div class="steam steam-two">〰</div>
    </div>

    <div class="game-hud">
      <span>LEVEL <b id="levelValue">1</b></span>
      <span>SCORE <b id="scoreValue">0</b></span>
      <span>COINS <b id="coinValue">0</b></span>
      <span>COMBO <b id="comboValue">×1</b></span>
      <span>TIME <b id="timeValue">75</b></span>
      <span class="rep">RATING <b id="ratingValue">♥♥♥</b></span>
    </div>

    <div class="rush-meter"><span id="rushFill"></span><b id="rushLabel">RUSH 0 / 5</b></div>

    <div class="game-layout">
      <section class="orders-panel" aria-labelledby="ordersHeading">
        <header><div><span>LIVE ORDERS</span><h2 id="ordersHeading">Customer queue</h2></div><b id="queueCount">0 / 3</b></header>
        <div id="orders" class="orders" aria-live="polite"></div>
      </section>

      <section class="cook-panel" aria-labelledby="cookHeading">
        <div class="kitchen-3d-wrap">
          <div class="kitchen-3d-label"><span><i></i> LIVE KITCHEN FLOOR</span><b>BUILD 003 · PERSPECTIVE MODE</b></div>
          <canvas id="kitchen3d" width="1100" height="560" aria-label="Interactive three-dimensional kitchen floor. Select a highlighted station to cook.">Your browser needs canvas support for the 3D kitchen floor.</canvas>
          <p>Tap a station on the floor or use the controls below.</p>
        </div>
        <div class="ticket" id="ticket">
          <span class="ticket-kicker">SELECT AN ORDER</span>
          <h2 id="cookHeading">Ready when you are.</h2>
          <p id="ticketCopy">Choose a customer ticket, then complete the recipe from left to right.</p>
          <div class="recipe-track" id="recipeTrack"></div>
        </div>

        <div class="stations" id="stations" aria-label="Kitchen stations">
          <button type="button" data-station="prep"><span>🔪</span><b>PREP</b><small>Slice & mix</small><i></i></button>
          <button type="button" data-station="season"><span>🧂</span><b>SEASON</b><small>Build flavour</small><i></i></button>
          <button type="button" data-station="stove"><span>🍳</span><b>STOVE</b><small>Sauté & simmer</small><i></i></button>
          <button type="button" data-station="grill"><span>🔥</span><b>GRILL</b><small>Sear & roast</small><i></i></button>
          <button type="button" data-station="fryer"><span>🍟</span><b>FRYER</b><small>Crisp it up</small><i></i></button>
          <button type="button" data-station="plate"><span>🍽️</span><b>PLATE</b><small>Finish & serve</small><i></i></button>
        </div>
        <p class="kitchen-feedback" id="feedback" aria-live="polite">The kitchen is ready.</p>
      </section>
    </div>

    <button class="exit-focus" id="exitFocus" type="button">Exit game</button>

    <div class="game-overlay" id="gameOverlay">
      <div class="overlay-pot">🍲<span>⚡</span></div>
      <span class="overlay-kicker">BEYOND GAMES · PLAYABLE BUILD 003</span>
      <h1 id="overlayTitle">Zak’s Kitchen Rush</h1>
      <p id="overlayCopy">Run a living 3D kitchen floor, move Zak between stations and serve Haitian favourites before patience runs out.</p>
      <div class="how-to"><span><b>1</b> Select an order</span><span><b>2</b> Follow recipe steps</span><span><b>3</b> Build combos</span></div>
      <button id="overlayButton" type="button">Open the kitchen</button>
      <small id="overlaySmall">Tap the 3D floor · Keyboard: 1–6 stations · Accessible controls below</small>
    </div>
  </section>

  <section class="kitchen-lower">
    <article>
      <span class="bos-kicker">Kitchen upgrades</span>
      <h2>Build a better rush</h2>
      <div class="upgrade-grid" id="upgradeGrid"></div>
    </article>
    <article>
      <span class="bos-kicker">Recipe book</span>
      <h2>Haitian & American menu</h2>
      <div class="recipe-book" id="recipeBook"></div>
    </article>
    <aside>
      <span class="bos-kicker">Local game profile</span>
      <h2>Best score <b id="bestScore">0</b></h2>
      <p><strong id="demoBits">0</strong> / 75 demo bit$ earned today</p>
      <div class="achievement-list" id="achievementList"></div>
      <button type="button" id="resetProgress">Reset local progress</button>
    </aside>
  </section>
</main>

<script>
(() => {
  'use strict';
  const $ = (id) => document.getElementById(id);
  const stage = $('gameStage');
  const ordersEl = $('orders');
  const recipeTrack = $('recipeTrack');
  const feedback = $('feedback');
  const overlay = $('gameOverlay');
  const overlayTitle = $('overlayTitle');
  const overlayCopy = $('overlayCopy');
  const overlayButton = $('overlayButton');
  const overlaySmall = $('overlaySmall');
  const exitFocus = $('exitFocus');
  const stationButtons = [...document.querySelectorAll('[data-station]')];
  const stationLabels = {prep:'Prep',season:'Season',stove:'Stove',grill:'Grill',fryer:'Fryer',plate:'Plate'};
  const stationIcons = {prep:'🔪',season:'🧂',stove:'🍳',grill:'🔥',fryer:'🍟',plate:'🍽️'};
  const customers = ['Mika','Jean','Jazzy','Andre','Nadia','Chris','Solange','Maya','David','Ruth','Theo','Lina'];
  const recipes = [
    {id:'spaghetti',name:'Haitian Spaghetti',icon:'🍝',origin:'Haitian',steps:['prep','stove','season','plate'],pay:32,patience:31,unlock:1},
    {id:'breakfast',name:'American Breakfast',icon:'🍳',origin:'American',steps:['prep','stove','grill','plate'],pay:30,patience:29,unlock:1},
    {id:'griot',name:'Griot & Plantains',icon:'🍖',origin:'Haitian',steps:['season','fryer','fryer','plate'],pay:42,patience:36,unlock:1},
    {id:'ribs',name:'Rib Plate',icon:'🍖',origin:'American',steps:['season','grill','grill','plate'],pay:46,patience:38,unlock:2},
    {id:'lamb',name:'Lamb Chop Plate',icon:'🥩',origin:'American',steps:['season','grill','stove','plate'],pay:52,patience:40,unlock:3},
    {id:'pikliz',name:'Pikliz Side',icon:'🥗',origin:'Haitian',steps:['prep','season','plate'],pay:22,patience:24,unlock:2}
  ];
  const upgrades = [
    {id:'prepSpeed',name:'Sharper prep tools',icon:'🔪',copy:'All kitchen actions finish 10% faster.',base:95,max:4},
    {id:'heatSpeed',name:'Hotter equipment',icon:'🔥',copy:'Stove, grill and fryer finish 15% faster.',base:120,max:4},
    {id:'decor',name:'Dining room décor',icon:'✨',copy:'Customers arrive with 12% more patience.',base:110,max:4},
    {id:'counter',name:'Order counter',icon:'🧾',copy:'Hold one more live order at level 2.',base:160,max:2}
  ];
  const achievementDefs = [
    {id:'first',name:'First Plate',copy:'Serve your first order.',reward:5},
    {id:'combo5',name:'Rush Hour',copy:'Reach a ×5 combo.',reward:15},
    {id:'haitian5',name:'Lakou Favourite',copy:'Serve five Haitian dishes.',reward:15},
    {id:'score1500',name:'Kitchen Star',copy:'Score 1,500 points in one run.',reward:20},
    {id:'level3',name:'Head Chef',copy:'Reach level 3.',reward:20}
  ];
  const today = new Date().toISOString().slice(0,10);
  const saveKey = 'beyond-games-zaks-kitchen-rush-v1';
  const blankSave = () => ({best:0,bank:0,upgrades:{prepSpeed:0,heatSpeed:0,decor:0,counter:0},achievements:{},rewardDate:today,demoBits:0,games:0});
  let saved;
  try { saved = JSON.parse(localStorage.getItem(saveKey) || 'null') || blankSave(); } catch (_) { saved = blankSave(); }
  saved.upgrades = Object.assign(blankSave().upgrades, saved.upgrades || {});
  saved.achievements = saved.achievements || {};
  if (saved.rewardDate !== today) { saved.rewardDate = today; saved.demoBits = 0; }

  let running = false, paused = false, level = 1, score = 0, coins = 0, combo = 0, rating = 3, seconds = 75;
  let orders = [], activeId = null, busy = false, spawnTimer = 0, levelTimer = null, tickTimer = null, orderSerial = 0;
  let rush = 0, rushActiveUntil = 0, servedHaitian = 0, sound = true, audioContext = null;

  function save() {
    saved.bank = Math.max(0, Math.round(saved.bank));
    try { localStorage.setItem(saveKey, JSON.stringify(saved)); } catch (_) {}
  }
  function tone(freq=520, duration=.08, type='sine') {
    if (!sound) return;
    try {
      audioContext ||= new (window.AudioContext || window.webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gain = audioContext.createGain();
      oscillator.type = type; oscillator.frequency.value = freq;
      gain.gain.setValueAtTime(.05, audioContext.currentTime);
      gain.gain.exponentialRampToValueAtTime(.001, audioContext.currentTime + duration);
      oscillator.connect(gain); gain.connect(audioContext.destination);
      oscillator.start(); oscillator.stop(audioContext.currentTime + duration);
    } catch (_) {}
  }
  function setFeedback(message, kind='') {
    feedback.textContent = message;
    feedback.className = 'kitchen-feedback' + (kind ? ' ' + kind : '');
  }
  function toast(message) {
    const node = document.createElement('div');
    node.className = 'game-toast'; node.textContent = message;
    stage.appendChild(node); setTimeout(() => node.remove(), 2500);
  }
  function unlock(id) {
    if (saved.achievements[id]) return;
    saved.achievements[id] = true;
    const achievement = achievementDefs.find(item => item.id === id);
    const room = Math.max(0, 75 - saved.demoBits);
    const award = Math.min(achievement.reward, room);
    saved.demoBits += award;
    save(); renderProfile();
    toast(achievement.name + (award ? ` · +${award} demo bit$` : ''));
    tone(830,.15,'triangle');
  }
  function renderProfile() {
    $('bestScore').textContent = saved.best.toLocaleString();
    $('demoBits').textContent = saved.demoBits;
    $('achievementList').innerHTML = achievementDefs.map(item => `<div class="achievement ${saved.achievements[item.id]?'earned':''}"><span>${saved.achievements[item.id]?'✓':'◇'}</span><div><b>${item.name}</b><small>${item.copy} · ${item.reward} demo bit$</small></div></div>`).join('');
  }
  function renderRecipeBook() {
    $('recipeBook').innerHTML = recipes.map(recipe => `<div class="book-recipe ${recipe.unlock>level?'locked':''}"><span>${recipe.icon}</span><div><b>${recipe.name}</b><small>${recipe.origin} · ${recipe.steps.map(step => stationLabels[step]).join(' → ')}</small></div><em>${recipe.unlock>level?'Level '+recipe.unlock:'Open'}</em></div>`).join('');
  }
  function upgradePrice(item) { return item.base * (saved.upgrades[item.id] + 1); }
  function renderUpgrades() {
    $('upgradeGrid').innerHTML = upgrades.map(item => {
      const current = saved.upgrades[item.id];
      const maxed = current >= item.max;
      return `<button type="button" data-upgrade="${item.id}" ${maxed?'disabled':''}><span>${item.icon}</span><div><b>${item.name}</b><small>${item.copy}</small><i>Level ${current} / ${item.max}</i></div><strong>${maxed?'MAX':upgradePrice(item)+' coins'}</strong></button>`;
    }).join('');
    document.querySelectorAll('[data-upgrade]').forEach(button => button.addEventListener('click', () => buyUpgrade(button.dataset.upgrade)));
  }
  function buyUpgrade(id) {
    const item = upgrades.find(upgrade => upgrade.id === id);
    if (!item || saved.upgrades[id] >= item.max) return;
    const price = upgradePrice(item);
    if (saved.bank < price) { toast(`Need ${price - saved.bank} more coins`); tone(180,.12,'square'); return; }
    saved.bank -= price; saved.upgrades[id] += 1; save(); renderUpgrades(); updateHud();
    toast(`${item.name} upgraded`); tone(680,.1,'triangle');
  }
  function maxOrders() { return saved.upgrades.counter >= 2 ? 4 : 3; }
  function availableRecipes() { return recipes.filter(recipe => recipe.unlock <= level); }
  function randomRecipe() { const list = availableRecipes(); return list[Math.floor(Math.random() * list.length)]; }
  function spawnOrder(force=false) {
    if (!running || paused || orders.length >= maxOrders()) return;
    if (!force && Math.random() > .72) return;
    const recipe = randomRecipe();
    const patienceBoost = 1 + saved.upgrades.decor * .12;
    const difficulty = Math.max(.68, 1 - (level - 1) * .055);
    const maxPatience = recipe.patience * patienceBoost * difficulty;
    orders.push({id:++orderSerial,customer:customers[Math.floor(Math.random()*customers.length)],recipe,step:0,patience:maxPatience,maxPatience,created:Date.now()});
    renderOrders();
    tone(390,.05);
  }
  function renderOrders() {
    $('queueCount').textContent = `${orders.length} / ${maxOrders()}`;
    if (!orders.length) {
      ordersEl.innerHTML = '<div class="empty-orders">The next customer is walking in…</div>';
    } else {
      ordersEl.innerHTML = orders.map(order => {
        const percent = Math.max(0, Math.round(order.patience / order.maxPatience * 100));
        return `<button type="button" class="order-card ${activeId===order.id?'active':''}" data-order="${order.id}"><span class="customer-avatar">${order.customer.slice(0,1)}</span><div><small>${order.customer} ordered</small><b>${order.recipe.icon} ${order.recipe.name}</b><em>${order.recipe.origin} · ${order.recipe.pay} coins</em><i><span style="width:${percent}%"></span></i></div><strong>${Math.ceil(order.patience)}s</strong></button>`;
      }).join('');
      document.querySelectorAll('[data-order]').forEach(button => button.addEventListener('click', () => selectOrder(Number(button.dataset.order))));
    }
    renderTicket();
  }
  function selectOrder(id) {
    if (busy) { setFeedback('Finish the current kitchen step first.', 'warn'); return; }
    activeId = id; renderOrders(); tone(460,.04);
  }
  function activeOrder() { return orders.find(order => order.id === activeId) || null; }
  function renderTicket() {
    const order = activeOrder();
    if (!order) {
      $('ticket').classList.remove('active');
      $('cookHeading').textContent = 'Ready when you are.';
      $('ticketCopy').textContent = 'Choose a customer ticket, then complete the recipe from left to right.';
      recipeTrack.innerHTML = '';
      stationButtons.forEach(button => button.classList.remove('next'));
      return;
    }
    $('ticket').classList.add('active');
    $('cookHeading').textContent = `${order.recipe.icon} ${order.recipe.name}`;
    $('ticketCopy').textContent = `${order.customer} is waiting · ${Math.ceil(order.patience)} seconds left`;
    recipeTrack.innerHTML = order.recipe.steps.map((step,index) => `<span class="${index<order.step?'done':index===order.step?'current':''}"><i>${index<order.step?'✓':stationIcons[step]}</i>${stationLabels[step]}</span>`).join('<b>›</b>');
    const next = order.recipe.steps[order.step];
    stationButtons.forEach(button => button.classList.toggle('next', button.dataset.station === next));
  }
  function stationDuration(station) {
    let ms = 930 - (level-1)*25;
    ms *= Math.pow(.9, saved.upgrades.prepSpeed);
    if (['stove','grill','fryer'].includes(station)) ms *= Math.pow(.85, saved.upgrades.heatSpeed);
    if (Date.now() < rushActiveUntil) ms *= .58;
    return Math.max(320, ms);
  }
  function useStation(station, button) {
    if (!running || paused) return;
    const order = activeOrder();
    if (!order) { setFeedback('Select a customer order first.', 'warn'); tone(190,.08,'square'); return; }
    if (busy) return;
    const expected = order.recipe.steps[order.step];
    if (station !== expected) {
      order.patience = Math.max(0, order.patience - 2.5); combo = 0; rush = 0; score = Math.max(0, score - 20);
      setFeedback(`${stationLabels[station]} is not next. Follow the recipe ticket.`, 'bad');
      stage.classList.add('shake'); setTimeout(()=>stage.classList.remove('shake'),260); tone(160,.12,'square'); updateHud(); renderOrders(); return;
    }
    busy = true;
    stationButtons.forEach(item => item.disabled = true);
    button.classList.add('working');
    const duration = stationDuration(station);
    button.style.setProperty('--work-time', duration + 'ms');
    setFeedback(`${stationLabels[station]} in progress…`, 'working'); tone(540,.04);
    setTimeout(() => {
      button.classList.remove('working'); stationButtons.forEach(item => item.disabled = false); busy = false;
      const liveOrder = activeOrder(); if (!liveOrder) return;
      liveOrder.step += 1;
      if (liveOrder.step >= liveOrder.recipe.steps.length) serveOrder(liveOrder); else {
        setFeedback(`${stationLabels[station]} complete. Next: ${stationLabels[liveOrder.recipe.steps[liveOrder.step]]}.`, 'good');
        renderOrders(); tone(660,.05);
      }
    }, duration);
  }
  function serveOrder(order) {
    const patienceRatio = order.patience / order.maxPatience;
    combo += 1; rush += 1;
    if (rush >= 5) { rush = 0; rushActiveUntil = Date.now() + 9000; toast('KITCHEN RUSH · Stations boosted for 9 seconds'); tone(910,.18,'sawtooth'); }
    const multiplier = 1 + Math.min(4, combo-1) * .25;
    const tip = Math.round(order.recipe.pay * (.35 + patienceRatio * .65) * multiplier);
    const points = Math.round(100 + order.recipe.pay * 4 + order.patience * 6) * Math.min(5, combo);
    score += points; coins += tip; saved.bank += tip;
    if (order.recipe.origin === 'Haitian') servedHaitian += 1;
    orders = orders.filter(item => item.id !== order.id); activeId = null;
    setFeedback(`${order.customer} loved the ${order.recipe.name}! +${points.toLocaleString()} score · +${tip} coins`, 'good');
    toast(combo > 1 ? `×${combo} ORDER COMBO` : 'ORDER SERVED');
    unlock('first'); if (combo >= 5) unlock('combo5'); if (servedHaitian >= 5) unlock('haitian5'); if (score >= 1500) unlock('score1500');
    saved.best = Math.max(saved.best, score); save(); renderProfile(); updateHud(); renderOrders(); renderUpgrades(); tone(760,.08,'triangle');
    setTimeout(() => spawnOrder(true), 500);
  }
  function missOrder(order) {
    if (!running) return;
    orders = orders.filter(item => item.id !== order.id); if (activeId === order.id) activeId = null;
    rating -= 1; combo = 0; rush = 0; score = Math.max(0, score - 100);
    setFeedback(`${order.customer} left unhappy. Keep the next order moving.`, 'bad');
    toast('CUSTOMER LOST'); tone(130,.2,'square'); updateHud(); renderOrders();
    if (rating <= 0) endRun(false);
  }
  function updateHud() {
    $('levelValue').textContent = level;
    $('scoreValue').textContent = score.toLocaleString();
    $('coinValue').textContent = Math.round(saved.bank).toLocaleString();
    $('comboValue').textContent = `×${Math.max(1,combo)}`;
    $('timeValue').textContent = seconds;
    $('ratingValue').textContent = '♥'.repeat(Math.max(0,rating)) + '♡'.repeat(Math.max(0,3-rating));
    const activeRush = Date.now() < rushActiveUntil;
    const rushPercent = activeRush ? 100 : (rush / 5 * 100);
    $('rushFill').style.width = rushPercent + '%';
    $('rushLabel').textContent = activeRush ? 'KITCHEN RUSH ACTIVE' : `RUSH ${rush} / 5`;
    stage.classList.toggle('rush-active', activeRush);
  }
  function tick() {
    if (!running || paused) return;
    seconds -= 1;
    orders.forEach(order => order.patience -= 1);
    const missed = orders.filter(order => order.patience <= 0);
    missed.forEach(missOrder);
    spawnTimer += 1;
    const spawnEvery = Math.max(3, 6 - Math.floor((level-1)/2));
    if (spawnTimer >= spawnEvery) { spawnTimer = 0; spawnOrder(); }
    updateHud(); renderOrders();
    if (seconds <= 0 && running) endLevel();
  }
  function resetLevelState() {
    score = level === 1 ? 0 : score;
    combo = 0; rating = 3; rush = 0; rushActiveUntil = 0; servedHaitian = 0;
    seconds = Math.max(55, 78 - (level-1)*4); orders = []; activeId = null; busy = false; spawnTimer = 0;
    stationButtons.forEach(item => { item.disabled = false; item.classList.remove('working','next'); });
    renderRecipeBook(); updateHud(); renderOrders();
  }
  function beginLevel() {
    running = true; paused = false; overlay.classList.add('hidden'); document.body.classList.add('kitchen-playing');
    resetLevelState(); spawnOrder(true); setTimeout(()=>spawnOrder(true),700);
    clearInterval(tickTimer); tickTimer = setInterval(tick, 1000);
    setFeedback(`Level ${level}: keep the line moving.`, 'good');
  }
  function showOverlay(title, copy, button, small='') {
    overlayTitle.textContent = title; overlayCopy.textContent = copy; overlayButton.textContent = button; overlaySmall.textContent = small;
    overlay.classList.remove('hidden');
  }
  function endLevel() {
    running = false; clearInterval(tickTimer); orders = []; activeId = null; document.body.classList.remove('kitchen-playing');
    saved.best = Math.max(saved.best, score); saved.games += 1; save(); renderProfile(); renderUpgrades();
    const stars = rating === 3 ? '★★★' : rating === 2 ? '★★☆' : '★☆☆';
    const bonus = rating * 35 + level * 20; saved.bank += bonus; save();
    if (level >= 3) unlock('level3');
    showOverlay(`Level ${level} complete ${stars}`, `Score ${score.toLocaleString()} · Service bonus ${bonus} coins. Upgrade the kitchen below or continue to a busier shift.`, 'Next shift', 'Unlocked dishes and customer speed increase each level.');
    overlayButton.dataset.action = 'next'; updateHud(); renderUpgrades();
  }
  function endRun(timeUp=false) {
    running = false; clearInterval(tickTimer); orders = []; activeId = null; busy = false; saved.best = Math.max(saved.best, score); saved.games += 1; save(); renderProfile();
    showOverlay(timeUp ? 'Shift complete' : 'Kitchen closed early', `Final score ${score.toLocaleString()}. You earned ${coins} coins this run. Upgrade and try for a stronger service rating.`, 'Play again', 'Progress and upgrades are saved locally on this device.');
    overlayButton.dataset.action = 'restart'; document.body.classList.remove('kitchen-playing');
  }
  function exitGame() {
    if (running && !confirm('Exit this kitchen shift? Current round progress will end.')) return;
    running = false; clearInterval(tickTimer); orders = []; activeId = null; busy = false; document.body.classList.remove('kitchen-playing');
    showOverlay('Zak’s Kitchen Rush','Prepare Haitian favourites and American classics, keep customers happy and build the fastest kitchen in town.','Open the kitchen','Keyboard: 1–6 stations · Mobile: tap controls');
    overlayButton.dataset.action = 'start';
  }
  overlayButton.addEventListener('click', () => {
    const action = overlayButton.dataset.action || 'start';
    if (action === 'resume') {
      paused = false; overlay.classList.add('hidden'); overlayButton.dataset.action = 'start';
      setFeedback('Shift resumed.', 'good'); return;
    }
    if (action === 'next') level += 1; else if (action === 'restart') { level = 1; score = 0; coins = 0; }
    overlayButton.dataset.action = 'start'; beginLevel();
  });
  stationButtons.forEach((button,index) => button.addEventListener('click', () => useStation(button.dataset.station, button)));
  window.addEventListener('keydown', event => {
    if (event.key >= '1' && event.key <= '6') { event.preventDefault(); stationButtons[Number(event.key)-1].click(); }
    if (event.code === 'Escape' && document.body.classList.contains('kitchen-playing')) exitGame();
  });
  $('soundToggle').addEventListener('click', event => { sound = !sound; event.currentTarget.textContent = `Sound: ${sound?'On':'Off'}`; event.currentTarget.setAttribute('aria-pressed', String(sound)); if (sound) tone(600,.05); });
  $('resetProgress').addEventListener('click', () => {
    if (!confirm('Reset Zak’s Kitchen Rush scores, upgrades and achievements?')) return;
    saved = blankSave(); save(); renderProfile(); renderUpgrades(); updateHud(); toast('Local progress reset');
  });
  exitFocus.addEventListener('click', exitGame);
  document.addEventListener('visibilitychange', () => {
    if (document.hidden && running) { paused = true; showOverlay('Kitchen paused','Your active orders are frozen until you return.','Resume shift','No customer patience is lost while paused.'); overlayButton.dataset.action='resume'; }
  });

  renderProfile(); renderUpgrades(); renderRecipeBook(); updateHud(); renderOrders();
})();
</script>
<script src="/beyond-games/assets/js/zaks-kitchen-3d.js?v=20260727-1"></script>

<style>
*{box-sizing:border-box}.kitchen-page{margin:0;min-height:100vh;background:radial-gradient(circle at 50% -20%,#5c351a,#17130d 42%,#080807);color:#fff9ef;touch-action:manipulation}.kitchen-shell{width:min(1280px,calc(100% - 20px));margin:auto;padding:10px 0 54px}.kitchen-header{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;padding:8px 4px 12px}.kitchen-header>a{color:#e7cda9;text-decoration:none;font-weight:850}.kitchen-brand{display:flex;align-items:center;gap:10px;text-align:left}.kitchen-brand>span{font-size:2rem}.kitchen-brand div{display:grid}.kitchen-brand b{font-size:1.05rem;letter-spacing:.08em}.kitchen-brand small{color:#f5b96b;font-size:.65rem;text-transform:uppercase}.kitchen-header button{justify-self:end;border:1px solid rgba(255,255,255,.17);border-radius:10px;padding:8px 10px;background:#2d2116;color:#fff;font-weight:800}.game-stage{position:relative;min-height:690px;overflow:hidden;border:1px solid rgba(255,179,86,.52);border-radius:26px;background:linear-gradient(180deg,#302015 0 29%,#17120d 29% 100%);box-shadow:0 30px 90px rgba(0,0,0,.55)}.kitchen-scene{position:absolute;inset:0 0 auto;height:205px;overflow:hidden;background:radial-gradient(circle at 72% 10%,rgba(255,209,128,.34),transparent 27%),linear-gradient(135deg,#6c271c,#c94d23 54%,#f09a2a)}.awning{display:grid;grid-template-columns:repeat(7,1fr);height:36px}.awning i:nth-child(odd){background:#fff5df}.awning i:nth-child(even){background:#153650}.scene-sign{position:absolute;left:50%;top:61px;transform:translateX(-50%) rotate(-1deg);display:grid;min-width:250px;padding:11px 28px;text-align:center;border:4px solid #fff0cf;border-radius:10px;background:#16334a;box-shadow:0 9px 0 #0b1e2e}.scene-sign span{font:italic 700 2.1rem Georgia;color:#ffd071;line-height:.75}.scene-sign b{font-size:1.55rem;letter-spacing:.18em}.scene-sign small{color:#f3c98f;font-size:.55rem;letter-spacing:.14em}.steam{position:absolute;color:rgba(255,255,255,.35);font-size:4rem;animation:steam 2.4s ease-in-out infinite}.steam-one{left:13%;bottom:-15px}.steam-two{right:12%;bottom:-20px;animation-delay:1s}@keyframes steam{50%{transform:translateY(-25px);opacity:.12}}.game-hud{position:relative;z-index:3;display:flex;justify-content:center;gap:clamp(12px,4vw,43px);padding:12px;background:linear-gradient(#0b0a08e8,rgba(11,10,8,.74));pointer-events:none}.game-hud span{display:grid;text-align:center;color:#c9a97b;font-size:.61rem;font-weight:900;letter-spacing:.08em}.game-hud b{color:#fff;font-size:1rem;letter-spacing:0}.game-hud .rep b{color:#ffbc5e;letter-spacing:.08em}.rush-meter{position:relative;z-index:3;height:19px;border-top:1px solid rgba(255,255,255,.08);border-bottom:1px solid rgba(255,255,255,.08);background:#13100d}.rush-meter span{display:block;width:0;height:100%;background:linear-gradient(90deg,#f0942a,#ffdf64);transition:width .25s}.rush-meter b{position:absolute;inset:0;display:grid;place-items:center;color:#fff;font-size:.56rem;letter-spacing:.15em;text-shadow:0 1px 3px #000}.game-layout{position:relative;z-index:2;display:grid;grid-template-columns:.77fr 1.23fr;gap:14px;padding:151px 16px 16px}.orders-panel,.cook-panel{min-height:492px;border:1px solid rgba(255,255,255,.12);border-radius:20px;background:rgba(17,14,10,.9);backdrop-filter:blur(10px)}.orders-panel{padding:16px}.orders-panel>header{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-bottom:12px}.orders-panel header span{color:#f2ad58;font-size:.58rem;font-weight:950;letter-spacing:.14em}.orders-panel h2{margin:3px 0 0;font-size:1.35rem}.orders-panel header>b{color:#cbb995;font-size:.68rem}.orders{display:grid;gap:9px}.order-card{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;width:100%;padding:11px;border:1px solid rgba(255,255,255,.1);border-radius:15px;background:#211a13;color:#fff;text-align:left;cursor:pointer;transition:.16s}.order-card:hover,.order-card.active{transform:translateY(-1px);border-color:#ffb85c;background:#342316}.customer-avatar{display:grid;place-items:center;width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#f7b35d,#b84425);font-weight:950}.order-card>div{display:grid;min-width:0}.order-card small{color:#c8ad88;font-size:.65rem}.order-card b{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.83rem}.order-card em{color:#f4b768;font-size:.62rem;font-style:normal}.order-card>strong{font-size:.75rem;color:#f4d6ae}.order-card i{height:6px;margin-top:7px;overflow:hidden;border-radius:99px;background:#493225}.order-card i span{display:block;height:100%;border-radius:inherit;background:linear-gradient(90deg,#db342f,#f0b33e,#69c46a);transition:width 1s linear}.empty-orders{padding:30px 12px;text-align:center;color:#9e8d75;border:1px dashed rgba(255,255,255,.12);border-radius:15px}.cook-panel{padding:16px}.ticket{min-height:138px;padding:17px;border:1px dashed rgba(240,183,96,.32);border-radius:16px;background:#f5e8cf;color:#2c2118;box-shadow:0 8px 0 #9a6c3d;transform:rotate(.3deg)}.ticket.active{border-style:solid}.ticket-kicker{font-size:.57rem;font-weight:950;letter-spacing:.15em;color:#9b4b26}.ticket h2{margin:5px 0 2px;font-size:1.5rem}.ticket p{margin:0;color:#745e49;font-size:.76rem}.recipe-track{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-top:13px}.recipe-track>span{display:flex;align-items:center;gap:5px;padding:6px 8px;border:1px solid #d0b48f;border-radius:9px;background:#fff7e7;font-size:.67rem;font-weight:850}.recipe-track>span.current{border-color:#b84924;background:#ffd18a}.recipe-track>span.done{background:#dff1cf;color:#315b20}.recipe-track>span i{font-style:normal}.recipe-track>b{color:#b3936e}.stations{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:18px}.stations button{position:relative;overflow:hidden;min-height:111px;border:1px solid rgba(255,255,255,.13);border-radius:15px;background:linear-gradient(150deg,#30251b,#19140f);color:#fff;cursor:pointer;transition:.16s}.stations button:hover,.stations button.next{border-color:#ffbd65;background:linear-gradient(150deg,#51341c,#21160f);box-shadow:0 0 0 3px rgba(255,183,84,.1)}.stations button:disabled{cursor:wait;opacity:.65}.stations button>span{display:block;font-size:1.8rem}.stations button>b{display:block;margin-top:3px;font-size:.76rem;letter-spacing:.08em}.stations button>small{color:#bba78d;font-size:.59rem}.stations button>i{position:absolute;left:0;bottom:0;width:0;height:5px;background:#ffb74f}.stations button.working>i{animation:work var(--work-time) linear forwards}@keyframes work{to{width:100%}}.kitchen-feedback{min-height:24px;margin:15px 2px 0;color:#bba78d;font-size:.76rem;font-weight:800}.kitchen-feedback.good{color:#83d17e}.kitchen-feedback.bad{color:#ff7b70}.kitchen-feedback.warn{color:#ffc467}.kitchen-feedback.working{color:#f3b969}.game-overlay{position:absolute;z-index:8;inset:0;display:grid;place-content:center;justify-items:center;text-align:center;padding:28px;background:radial-gradient(circle,rgba(84,41,18,.9),rgba(10,9,7,.97) 67%);transition:.2s}.game-overlay.hidden{opacity:0;visibility:hidden;pointer-events:none}.overlay-pot{position:relative;display:grid;place-items:center;width:100px;height:100px;border:4px solid #fff2d0;border-radius:26px;background:#b64522;font-size:4.4rem;box-shadow:0 0 38px rgba(255,175,75,.48)}.overlay-pot span{position:absolute;right:-17px;top:14px;font-size:1.8rem}.overlay-kicker{margin-top:18px;color:#ffc271;font-size:.66rem;font-weight:1000;letter-spacing:.14em}.game-overlay h1{max-width:760px;margin:5px 0;font-size:clamp(2.6rem,7vw,5.8rem);line-height:.9}.game-overlay>p{max-width:620px;margin:7px 0;color:#e5d2b8;line-height:1.5}.how-to{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin:17px 0}.how-to span{padding:8px 11px;border:1px solid rgba(255,255,255,.13);border-radius:11px;color:#d5c1a5;font-size:.68rem}.how-to b{color:#ffbd64}.game-overlay button{min-width:190px;border:0;border-radius:14px;padding:13px 21px;background:linear-gradient(135deg,#ef8e2a,#cf3e23);color:#fff;font:inherit;font-weight:1000;cursor:pointer}.game-overlay small{margin-top:10px;color:#9f8b71}.game-toast{position:absolute;z-index:12;left:50%;top:74px;transform:translateX(-50%);padding:10px 15px;border:1px solid #ffc363;border-radius:999px;background:#301d10;color:#ffd28a;font-size:.76rem;font-weight:950;box-shadow:0 12px 30px #0008;animation:toast 2.5s both}@keyframes toast{0%{opacity:0;transform:translate(-50%,-10px)}14%,76%{opacity:1;transform:translate(-50%,0)}100%{opacity:0;transform:translate(-50%,-10px)}}.shake{animation:shake .25s}@keyframes shake{25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}.rush-active{box-shadow:0 0 0 2px #ffca62,0 30px 100px rgba(255,143,37,.4)}.rush-active .rush-meter span{animation:rushpulse .55s ease-in-out infinite alternate}@keyframes rushpulse{to{filter:brightness(1.35)}}.kitchen-lower{display:grid;grid-template-columns:1fr 1fr .8fr;gap:14px;margin-top:14px}.kitchen-lower>article,.kitchen-lower>aside{padding:20px;border:1px solid rgba(255,255,255,.11);border-radius:19px;background:rgba(31,24,17,.78)}.kitchen-lower h2{margin:.2rem 0 1rem}.upgrade-grid,.recipe-book,.achievement-list{display:grid;gap:8px}.upgrade-grid button{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;padding:11px;border:1px solid rgba(255,255,255,.1);border-radius:13px;background:#2a2017;color:#fff;text-align:left;cursor:pointer}.upgrade-grid button:disabled{opacity:.55;cursor:not-allowed}.upgrade-grid button>span{font-size:1.35rem}.upgrade-grid button div{display:grid}.upgrade-grid small,.book-recipe small,.achievement small{color:#b9a68d;font-size:.64rem}.upgrade-grid i{color:#eab46f;font-size:.58rem;font-style:normal}.upgrade-grid strong{color:#ffc66f;font-size:.68rem}.book-recipe{display:grid;grid-template-columns:auto 1fr auto;gap:10px;align-items:center;padding:10px;border:1px solid rgba(255,255,255,.09);border-radius:12px;background:#281f17}.book-recipe>span{font-size:1.4rem}.book-recipe>div{display:grid}.book-recipe em{color:#77c77a;font-size:.6rem;font-style:normal}.book-recipe.locked{opacity:.55}.book-recipe.locked em{color:#c8a369}.kitchen-lower aside h2 b{color:#ffc66f}.kitchen-lower aside>p{color:#c3ad91}.achievement{display:flex;gap:8px;align-items:center;padding:9px;border:1px solid rgba(255,255,255,.08);border-radius:11px;color:#766957}.achievement>span{display:grid;place-items:center;width:29px;height:29px;border-radius:50%;background:#34271c}.achievement>div{display:grid}.achievement.earned{color:#fff;border-color:rgba(122,207,113,.3)}.achievement.earned>span{color:#a8ed9f;background:#214422}.kitchen-lower aside>button{margin-top:13px;border:1px solid rgba(255,255,255,.13);border-radius:10px;padding:9px 11px;background:none;color:#cbb89d;font-weight:800}.exit-focus{display:none}
@media(max-width:980px){.game-layout{grid-template-columns:1fr;padding-top:151px}.game-stage{min-height:1040px}.orders-panel{min-height:320px}.cook-panel{min-height:500px}.kitchen-lower{grid-template-columns:1fr 1fr}.kitchen-lower aside{grid-column:1/-1}}
@media(max-width:700px){.kitchen-shell{width:100%;padding:4px 7px 26px}.kitchen-header{grid-template-columns:1fr auto}.kitchen-brand{display:none}.kitchen-header>a{font-size:.75rem}.kitchen-header button{padding:7px 8px;font-size:.7rem}.game-stage{height:76dvh;min-height:620px;border-radius:17px;touch-action:none}.kitchen-scene{height:150px}.scene-sign{top:48px;min-width:195px;padding:8px 18px}.scene-sign span{font-size:1.55rem}.scene-sign b{font-size:1.1rem}.game-layout{height:100%;grid-template-columns:.82fr 1.18fr;gap:7px;padding:105px 7px 7px}.orders-panel,.cook-panel{min-height:0;height:calc(76dvh - 140px);padding:8px;border-radius:13px;overflow:hidden}.orders-panel h2{font-size:.95rem}.orders-panel>header{margin-bottom:7px}.orders{gap:5px;max-height:calc(100% - 45px);overflow:auto}.order-card{grid-template-columns:1fr auto;padding:7px;gap:5px}.customer-avatar{display:none}.order-card small,.order-card em{font-size:.53rem}.order-card b{font-size:.66rem}.order-card>strong{font-size:.62rem}.ticket{min-height:110px;padding:9px;box-shadow:0 5px 0 #9a6c3d}.ticket h2{font-size:.95rem}.ticket p{font-size:.58rem}.recipe-track{gap:3px;margin-top:7px}.recipe-track>span{padding:4px;font-size:.52rem}.recipe-track>span{font-size:0}.recipe-track>span i{font-size:.73rem}.stations{grid-template-columns:repeat(2,1fr);gap:5px;margin-top:10px}.stations button{min-height:70px}.stations button>span{font-size:1.25rem}.stations button>b{font-size:.58rem}.stations button>small{display:none}.kitchen-feedback{margin-top:7px;font-size:.58rem}.game-hud{gap:5px;justify-content:space-around;padding:7px 46px 7px 2px}.game-hud span{font-size:.44rem}.game-hud b{font-size:.67rem}.rush-meter{height:15px}.rush-meter b{font-size:.48rem}.game-overlay{padding:16px 13px}.overlay-pot{width:66px;height:66px;font-size:2.9rem}.overlay-kicker{margin-top:9px;font-size:.53rem}.game-overlay h1{font-size:clamp(2.3rem,12vw,3.3rem)}.game-overlay>p{max-width:350px;font-size:.8rem}.how-to{margin:9px 0}.how-to span{padding:6px 7px;font-size:.57rem}.game-overlay small{display:none}.kitchen-lower{grid-template-columns:1fr}.kitchen-lower aside{grid-column:auto}.kitchen-playing{overflow:hidden;overscroll-behavior:none}.kitchen-playing #beyond-os-shell,.kitchen-playing .kitchen-header,.kitchen-playing .kitchen-lower{display:none!important}.kitchen-playing .kitchen-shell{width:100%;height:100dvh;padding:0}.kitchen-playing .game-stage{position:fixed;z-index:1000;inset:0;width:100%;height:100dvh;border:0;border-radius:0}.kitchen-playing .orders-panel,.kitchen-playing .cook-panel{height:calc(100dvh - 140px)}.kitchen-playing .exit-focus{position:absolute;z-index:10;display:block;top:max(46px,calc(env(safe-area-inset-top) + 40px));right:7px;padding:7px 9px;border:1px solid rgba(255,255,255,.2);border-radius:999px;background:rgba(26,17,10,.82);color:#fff;font-size:.65rem;font-weight:900}.kitchen-playing .game-layout{padding-bottom:max(7px,env(safe-area-inset-bottom))}}
@media(max-width:460px){.game-stage{height:78dvh}.game-layout{grid-template-columns:.9fr 1.1fr}.orders-panel,.cook-panel{height:calc(78dvh - 140px)}.order-card em{display:none}.orders-panel header>b{display:none}.stations button{min-height:62px}.ticket-kicker{font-size:.48rem}.kitchen-playing .orders-panel,.kitchen-playing .cook-panel{height:calc(100dvh - 140px)}}
</style>
<?php bos_page_end(); ?>
