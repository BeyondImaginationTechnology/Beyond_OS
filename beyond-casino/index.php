<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/ecosystem.php';
beyond_nav_bootstrap('Beyond Casino');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#160b25">
  <title>Beyond Casino — Social Play | Beyond OS</title>
  <meta name="description" content="Free social-play slots, roulette, blackjack, and Texas Hold'em using temporary demo bit$. No purchase necessary and no cash value.">
  <link rel="stylesheet" href="assets/casino.css?v=20260721-1">
</head>
<body>
<header class="top wrap"><a class="brand" href="/">BEYOND <span>OS</span></a><a class="back" href="/app-store/">App Store &rarr;</a></header>
<main class="wrap">
  <section class="hero">
    <span class="eyebrow">BEYOND CASINO — SOCIAL PLAY</span>
    <h1>Play the vibe.<br>Not the stakes.</h1>
    <p>Four familiar casino games powered only by temporary demo bit$. Your Beyond Wallet is never charged or changed.</p>
    <div class="notice">Entertainment only &middot; No purchase necessary &middot; No cash value</div>
  </section>

  <section class="lounge" aria-label="Beyond Casino games">
    <div class="lounge-bar">
      <div><span class="eyebrow">SOCIAL-PLAY LOUNGE</span><h2>Choose a table</h2></div>
      <div class="balance" aria-live="polite"><span data-balance>1,000</span> demo bit$</div>
    </div>
    <div class="game-tabs" role="tablist" aria-label="Casino games">
      <button class="game-tab is-active" type="button" role="tab" aria-selected="true" aria-controls="slots-panel" data-game-tab="slots">🎰 Lucky Spin</button>
      <button class="game-tab" type="button" role="tab" aria-selected="false" aria-controls="roulette-panel" data-game-tab="roulette">🎯 Roulette</button>
      <button class="game-tab" type="button" role="tab" aria-selected="false" aria-controls="blackjack-panel" data-game-tab="blackjack">♠ Blackjack</button>
      <button class="game-tab" type="button" role="tab" aria-selected="false" aria-controls="holdem-panel" data-game-tab="holdem">♣ Texas Hold’em</button>
    </div>

    <section class="game-panel is-active" id="slots-panel" role="tabpanel" data-game-panel="slots" aria-labelledby="slots-title">
      <div class="game-heading"><div><span class="game-kicker">INSTANT PLAY</span><h2 id="slots-title">Neon Lucky Spin</h2></div><span class="wager">10 demo bit$</span></div>
      <div class="reels" aria-label="Slot reels"><div class="reel" id="r1">🍒</div><div class="reel" id="r2">⭐</div><div class="reel" id="r3">7</div></div>
      <div class="status" id="slots-status" aria-live="polite">Match three symbols to win.</div>
      <button class="play-button" id="slots-spin" type="button">Spin for 10 demo bit$</button>
      <div class="rules"><div class="rule"><strong>50 bit$</strong>Any three matching</div><div class="rule"><strong>100 bit$</strong>Three stars</div><div class="rule"><strong>250 bit$</strong>Triple sevens</div></div>
    </section>

    <section class="game-panel" id="roulette-panel" role="tabpanel" data-game-panel="roulette" aria-labelledby="roulette-title" hidden>
      <div class="game-heading"><div><span class="game-kicker">EUROPEAN WHEEL</span><h2 id="roulette-title">Beyond Roulette</h2></div><span class="wager">10 demo bit$</span></div>
      <div class="roulette-layout">
        <div class="roulette-wheel" aria-label="Roulette result"><div class="roulette-result is-green" id="roulette-result">0</div><span>EUROPEAN</span></div>
        <div class="table-controls"><label for="roulette-bet">Choose an even-money bet</label><select id="roulette-bet"><option value="red">Red</option><option value="black">Black</option><option value="odd">Odd</option><option value="even">Even</option><option value="low">1–18</option><option value="high">19–36</option></select><div class="chip-row"><span class="chip red-chip">Red</span><span class="chip black-chip">Black</span><span class="chip green-chip">0</span></div></div>
      </div>
      <div class="status" id="roulette-status" aria-live="polite">Zero is green and loses all even-money bets.</div>
      <button class="play-button" id="roulette-spin" type="button">Spin for 10 demo bit$</button>
    </section>

    <section class="game-panel" id="blackjack-panel" role="tabpanel" data-game-panel="blackjack" aria-labelledby="blackjack-title" hidden>
      <div class="game-heading"><div><span class="game-kicker">DEALER STANDS ON 17</span><h2 id="blackjack-title">Beyond Blackjack</h2></div><span class="wager">20 demo bit$</span></div>
      <div class="card-table">
        <div class="hand"><div class="hand-label"><span>Dealer</span><strong id="dealer-score">—</strong></div><div class="playing-cards" id="dealer-cards"><span class="empty-hand">Start a round to deal.</span></div></div>
        <div class="table-line"></div>
        <div class="hand"><div class="hand-label"><span>Your hand</span><strong id="player-score">—</strong></div><div class="playing-cards" id="player-cards"><span class="empty-hand">Blackjack pays 3:2.</span></div></div>
      </div>
      <div class="status" id="blackjack-status" aria-live="polite">Start a 20 demo bit$ round.</div>
      <div class="action-row"><button class="secondary-button" id="blackjack-hit" type="button" disabled>Hit</button><button class="secondary-button" id="blackjack-stand" type="button" disabled>Stand</button><button class="play-button" id="blackjack-deal" type="button">Deal for 20 demo bit$</button></div>
    </section>

    <section class="game-panel" id="holdem-panel" role="tabpanel" data-game-panel="holdem" aria-labelledby="holdem-title" hidden>
      <div class="game-heading"><div><span class="game-kicker">HEADS-UP SHOWDOWN</span><h2 id="holdem-title">Texas Hold’em</h2></div><span class="wager">25 demo bit$</span></div>
      <div class="poker-table">
        <div class="hand compact"><div class="hand-label"><span>House hand</span><strong id="holdem-house-label">Waiting</strong></div><div class="playing-cards" id="holdem-house"><span class="empty-hand">Two hidden cards</span></div></div>
        <div class="community"><span class="community-label">Community cards</span><div class="playing-cards" id="holdem-community"><span class="empty-hand">Deal to begin.</span></div></div>
        <div class="hand compact"><div class="hand-label"><span>Your hand</span><strong id="holdem-player-label">Waiting</strong></div><div class="playing-cards" id="holdem-player"><span class="empty-hand">Best five-card hand wins.</span></div></div>
      </div>
      <div class="status" id="holdem-status" aria-live="polite">Deal, then reveal the flop, turn, and river.</div>
      <button class="play-button" id="holdem-action" type="button">Deal for 25 demo bit$</button>
      <div class="rules poker-rules"><div class="rule"><strong>Flop</strong>Three community cards</div><div class="rule"><strong>Turn</strong>Fourth community card</div><div class="rule"><strong>River</strong>Final card and showdown</div></div>
    </section>
  </section>
  <p class="fine">Demo bit$ exist only on this page and reset when you reload. There are no deposits, purchases, withdrawals, prizes, transfers, or redemption for money or anything of value. Outcomes are generated locally for entertainment.</p>
</main>
<footer class="app-legal-footer"><nav class="app-legal-links" aria-label="Beyond Casino legal information"><a href="/legal/privacy.php?app=Beyond%20Casino">Privacy</a><a href="/legal/terms.php?app=Beyond%20Casino">Terms</a></nav></footer>
<script src="assets/casino.js?v=20260721-1" defer></script>
<script src="/assets/js/visitor-analytics.js" defer></script></body>
</html>

