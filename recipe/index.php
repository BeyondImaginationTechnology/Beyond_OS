<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/ecosystem.php';

$recipesPath = __DIR__ . '/data/recipes.json';
$recipesJson = file_get_contents($recipesPath);
if ($recipesJson === false) {
    throw new RuntimeException('Beyond Kitchen recipe data could not be loaded.');
}
$recipes = json_decode($recipesJson, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($recipes) || !array_is_list($recipes) || $recipes === []) {
    throw new RuntimeException('Beyond Kitchen recipe data must be a non-empty list.');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#f6f5ef">
  <meta name="description" content="A fresh recipe for today, with simple ingredients, clear steps, and ideas worth making again.">
  <title>Beyond Kitchen | A little inspiration for today</title>
  <link rel="canonical" href="https://recipe.beyondimagination.co.technology/">
  <link rel="manifest" href="./manifest.webmanifest">
  <link rel="icon" href="./assets/kitchen-mark.svg" type="image/svg+xml">
  <link rel="stylesheet" href="./assets/css/app.css?v=0.0.7">
</head>
<body>
  <div class="app-shell">
    <header class="topbar">
      <a class="brand" href="./" aria-label="Beyond Kitchen home">
        <span class="brand-mark" aria-hidden="true">b</span>
        <span>Beyond <strong>Kitchen</strong></span>
      </a>
      <nav class="top-actions" aria-label="Main navigation">
        <a href="#dinner">Dinner ideas</a>
        <a href="#budget">Budget</a>
        <a href="#recipes">Recipes</a>
        <button class="favorite-nav" id="favoritesToggle" type="button" aria-pressed="false">
          <span aria-hidden="true">♡</span> <span>Saved</span> <span class="saved-count" id="savedCount">0</span>
        </button>
      </nav>
    </header>

    <main>
      <section class="welcome" aria-labelledby="welcomeTitle">
        <div class="welcome-copy">
          <p class="eyebrow"><span class="eyebrow-dot"></span> A fresh start, every day</p>
          <h1 id="welcomeTitle">Good food, <em>made simple.</em></h1>
          <p class="welcome-description">A little inspiration for what to make next. Thoughtful recipes, everyday ingredients, and no-fuss steps.</p>
          <div class="welcome-note"><span aria-hidden="true">✳</span><span>Take what you need. Make it your own.</span></div>
        </div>
        <div class="welcome-stamp" aria-label="Good things are cooking">
          <span class="stamp-leaves" aria-hidden="true">✳</span>
          <span>MADE FOR<br>YOUR EVERYDAY</span>
          <span class="stamp-small">GOOD THINGS ARE COOKING</span>
        </div>
      </section>

      <section class="dinner-section" id="dinner" aria-labelledby="dinnerHeading">
        <div class="dinner-intro">
          <p class="eyebrow">Dinner guide · beta 0.0.1</p>
          <h2 id="dinnerHeading">What's for dinner?</h2>
          <p>Tell us your mood, time, budget, or what's in the fridge. Get one idea to cook, one to pick up, and one to have delivered.</p>
        </div>
        <form id="dinnerForm" class="dinner-form">
          <label for="dinnerPrompt">What sounds good tonight?</label>
          <textarea id="dinnerPrompt" rows="3" maxlength="400" placeholder="I'm tired, want something spicy, and have about $25…"></textarea>
          <div class="dinner-hints" aria-label="Quick dinner preferences">
            <button type="button" data-dinner-hint="under 20 minutes">20 min</button>
            <button type="button" data-dinner-hint="under $25">Under $25</button>
            <button type="button" data-dinner-hint="low energy">Low energy</button>
            <button type="button" data-dinner-hint="spicy">Spicy</button>
            <button type="button" data-dinner-hint="vegetarian">Vegetarian</button>
          </div>
          <button class="primary-button" id="dinnerSubmit" type="submit">Find dinner ideas <span aria-hidden="true">→</span></button>
          <p class="dinner-fineprint">Pickup and delivery are dish ideas. Check nearby menus, prices, and availability before ordering.</p>
        </form>
        <div class="dinner-results" id="dinnerResults" aria-live="polite" hidden></div>
      </section>

      <section class="daily-section" aria-labelledby="dailyHeading">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Your daily inspiration</p>
            <h2 id="dailyHeading">Today's recipe</h2>
          </div>
          <time id="todayDate"></time>
        </div>
        <article class="daily-card" id="dailyRecipe" aria-live="polite"></article>
      </section>

      <section class="carousel-section" aria-labelledby="carouselHeading">
        <div class="carousel-copy">
          <p class="eyebrow">Today's recipe carousel</p>
          <h2 id="carouselHeading">A little inspiration for today</h2>
          <p id="carouselDescription">Swipe through today's recipe, from ingredients to the finished plate.</p>
          <button class="primary-button" id="carouselRecipeButton" type="button" hidden>Make this recipe <span aria-hidden="true">→</span></button>
          <button class="secondary-button" id="carouselCaption" type="button" hidden>Copy post caption</button>
          <a class="photo-credit" id="carouselDownload" hidden download>Download current slide</a>
        </div>
        <div class="carousel-viewer" id="recipeCarousel" role="region" aria-roledescription="carousel" aria-label="Today's recipe carousel">
          <div class="carousel-track" id="carouselTrack" tabindex="0"></div>
          <div class="carousel-controls">
            <button class="carousel-arrow" id="carouselPrevious" type="button" aria-label="Previous slide">←</button>
            <div class="carousel-dots" id="carouselDots" role="group" aria-label="Choose a recipe slide"></div>
            <span class="carousel-count" id="carouselCount" aria-live="polite">1 / 5</span>
            <button class="carousel-arrow" id="carouselNext" type="button" aria-label="Next slide">→</button>
          </div>
        </div>
      </section>

      <section class="budget-section" id="budget" aria-labelledby="budgetHeading">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Plan your plate</p>
            <h2 id="budgetHeading">What might it cost?</h2>
          </div>
        </div>
        <p class="budget-intro">Pick a recipe, region, and store to compare a cooking budget in CAD or USD.</p>
        <div class="budget-controls">
          <label>Recipe <select id="budgetRecipe" aria-label="Recipe to compare"></select></label>
          <label>Region <select id="budgetRegion" aria-label="Region for budget"></select></label>
          <label>Store <select id="budgetStore" aria-label="Preferred store"></select></label>
        </div>
        <div class="budget-result" id="budgetResult" aria-live="polite">Loading recipe budgets…</div>
        <p class="budget-note">Illustrative ingredient-use estimates seeded September 2026, including small pantry portions. Regional and store differences are planning assumptions, not live shelf prices or exchange quotes. Package sizes, sales, tax, and availability can change your checkout total. <a href="https://www150.statcan.gc.ca/t1/tbl1/en/tv.action?pid=1810024502" target="_blank" rel="noopener noreferrer">Canadian food prices ↗</a> · <a href="https://www.ers.usda.gov/data-products/food-price-outlook/" target="_blank" rel="noopener noreferrer">U.S. food price outlook ↗</a></p>
      </section>

      <section class="recipe-section" id="recipes" aria-labelledby="recipesHeading">
        <div class="section-heading recipe-heading">
          <div>
            <p class="eyebrow">A good place to start</p>
            <h2 id="recipesHeading">Find your next favourite</h2>
          </div>
          <p class="recipe-count" id="recipeCount" aria-live="polite"></p>
        </div>
        <div class="discovery-controls">
          <label class="search-box">
            <span aria-hidden="true">⌕</span>
            <span class="visually-hidden">Search recipes</span>
            <input id="recipeSearch" type="search" placeholder="Search recipes or ingredients" autocomplete="off">
            <kbd>/</kbd>
          </label>
          <div class="filter-list" role="group" aria-label="Filter recipes">
            <button class="filter-chip active" type="button" data-filter="All">All recipes</button>
            <button class="filter-chip" type="button" data-filter="Quick">Under 30 min</button>
            <button class="filter-chip" type="button" data-filter="Vegetarian">Vegetarian</button>
            <button class="filter-chip" type="button" data-filter="Dinner">Dinner</button>
            <button class="filter-chip" type="button" data-filter="Haitian">Haitian</button>
          </div>
        </div>
        <div class="recipe-grid" id="recipeGrid" aria-live="polite"></div>
        <p class="empty-state" id="emptyState" hidden>No recipes match just yet. Try another search or filter.</p>
      </section>

      <section class="closing-note" aria-label="A note about cooking">
        <span aria-hidden="true">✳</span>
        <p>Cooking is better when it feels like yours.<br><strong>Start with a recipe. Finish with your own touch.</strong></p>
      </section>
    </main>

    <footer class="site-footer">
      <a href="https://beyondimagination.co.technology/">← Beyond Imagination Technology</a>
      <span>Beyond Kitchen · 0.0.2</span>
    </footer>
  </div>

  <dialog class="recipe-dialog" id="recipeDialog" aria-labelledby="dialogTitle">
    <button class="dialog-close" id="dialogClose" type="button" aria-label="Close recipe">×</button>
    <div id="recipeDetails"></div>
  </dialog>
  <p class="sr-status" id="statusMessage" role="status" aria-live="polite"></p>

  <script src="./assets/js/recipe-library.js?v=0.0.2" defer></script>
  <script src="./assets/js/app.js?v=0.0.8" defer></script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => navigator.serviceWorker.register('./service-worker.js'));
    }
  </script>
</body>
</html>
