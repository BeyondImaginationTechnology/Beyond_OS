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
  <link rel="manifest" href="<?= e(beyond_url('beyond-kitchen/manifest.webmanifest')) ?>">
  <link rel="icon" href="<?= e(beyond_url('beyond-kitchen/assets/kitchen-mark.svg')) ?>" type="image/svg+xml">
  <link rel="stylesheet" href="<?= e(beyond_url('beyond-kitchen/assets/css/app.css?v=0.0.2')) ?>">
</head>
<body>
  <div class="app-shell">
    <header class="topbar">
      <a class="brand" href="<?= e(beyond_url('')) ?>" aria-label="Beyond Kitchen home">
        <span class="brand-mark" aria-hidden="true">b</span>
        <span>Beyond <strong>Kitchen</strong></span>
      </a>
      <nav class="top-actions" aria-label="Main navigation">
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
          <p class="eyebrow">Fresh from our kitchen</p>
          <h2 id="carouselHeading">Lemon chickpea bowls</h2>
          <p>Five little steps to a bright, crunchy lunch with lemon-dill yogurt. Swipe through the recipe, then make it your own.</p>
          <button class="primary-button" type="button" data-open="lemon-chickpea-bowls">Make this recipe <span aria-hidden="true">→</span></button>
          <a class="photo-credit" href="https://unsplash.com/photos/a-bowl-of-food-with-a-spoon-in-it-tjL5vFb12qE" target="_blank" rel="noreferrer">Food photo by Gloria Liu on Unsplash</a>
        </div>
        <div class="carousel-viewer" id="recipeCarousel" role="region" aria-roledescription="carousel" aria-label="Lemon chickpea bowls recipe photos">
          <div class="carousel-track" id="carouselTrack" tabindex="0">
            <figure class="carousel-slide" data-carousel-slide role="group" aria-roledescription="slide" aria-label="1 of 5">
              <img src="<?= e(beyond_url('beyond-kitchen/assets/images/lemon-chickpea-carousel/slide-01.jpg')) ?>" alt="Lemon chickpea bowl with cucumber and tomatoes, introducing the recipe." width="1080" height="1350" fetchpriority="high">
            </figure>
            <figure class="carousel-slide" data-carousel-slide role="group" aria-roledescription="slide" aria-label="2 of 5">
              <img src="<?= e(beyond_url('beyond-kitchen/assets/images/lemon-chickpea-carousel/slide-02.jpg')) ?>" alt="Shopping list for two bowls: chickpeas, brown rice, cucumber, tomatoes, yogurt, lemon, dill, and olive oil." width="1080" height="1350" loading="lazy">
            </figure>
            <figure class="carousel-slide" data-carousel-slide role="group" aria-roledescription="slide" aria-label="3 of 5">
              <img src="<?= e(beyond_url('beyond-kitchen/assets/images/lemon-chickpea-carousel/slide-03.jpg')) ?>" alt="Roast dried chickpeas at 425 degrees Fahrenheit until crisp and golden." width="1080" height="1350" loading="lazy">
            </figure>
            <figure class="carousel-slide" data-carousel-slide role="group" aria-roledescription="slide" aria-label="4 of 5">
              <img src="<?= e(beyond_url('beyond-kitchen/assets/images/lemon-chickpea-carousel/slide-04.jpg')) ?>" alt="Stir together lemon-herb yogurt, divide rice into bowls, and top with vegetables, chickpeas, and sauce." width="1080" height="1350" loading="lazy">
            </figure>
            <figure class="carousel-slide" data-carousel-slide role="group" aria-roledescription="slide" aria-label="5 of 5">
              <img src="<?= e(beyond_url('beyond-kitchen/assets/images/lemon-chickpea-carousel/slide-05.jpg')) ?>" alt="Finished lemon chickpea bowls with cucumber, tomatoes, and a lemon-dill drizzle." width="1080" height="1350" loading="lazy">
            </figure>
          </div>
          <div class="carousel-controls">
            <button class="carousel-arrow" id="carouselPrevious" type="button" aria-label="Previous photo">←</button>
            <div class="carousel-dots" role="group" aria-label="Choose a recipe photo">
              <button type="button" data-carousel-to="0" aria-label="Show photo 1" aria-current="true"></button>
              <button type="button" data-carousel-to="1" aria-label="Show photo 2" aria-current="false"></button>
              <button type="button" data-carousel-to="2" aria-label="Show photo 3" aria-current="false"></button>
              <button type="button" data-carousel-to="3" aria-label="Show photo 4" aria-current="false"></button>
              <button type="button" data-carousel-to="4" aria-label="Show photo 5" aria-current="false"></button>
            </div>
            <span class="carousel-count" id="carouselCount" aria-live="polite">1 / 5</span>
            <button class="carousel-arrow" id="carouselNext" type="button" aria-label="Next photo">→</button>
          </div>
        </div>
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
      <a href="<?= e(beyond_url('')) ?>">← Beyond Imagination Technology</a>
      <span>Beyond Kitchen · 0.0.1</span>
    </footer>
  </div>

  <dialog class="recipe-dialog" id="recipeDialog" aria-labelledby="dialogTitle">
    <button class="dialog-close" id="dialogClose" type="button" aria-label="Close recipe">×</button>
    <div id="recipeDetails"></div>
  </dialog>
  <p class="sr-status" id="statusMessage" role="status" aria-live="polite"></p>

  <script src="<?= e(beyond_url('beyond-kitchen/assets/js/recipe-library.js?v=0.0.1')) ?>" defer></script>
  <script src="<?= e(beyond_url('beyond-kitchen/assets/js/app.js?v=0.0.2')) ?>" defer></script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => navigator.serviceWorker.register('<?= e(beyond_url('beyond-kitchen/service-worker.js')) ?>'));
    }
  </script>
</body>
</html>
