(() => {
  'use strict';

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const state = { recipes: [], filter: 'All', favoritesOnly: false, favorites: new Set(), activeRecipe: null, servings: 2 };
  const favoritesKey = 'beyond-kitchen-favorites-v1';
  const grid = $('#recipeGrid');
  const daily = $('#dailyRecipe');
  const dialog = $('#recipeDialog');
  const details = $('#recipeDetails');
  const status = $('#statusMessage');
  const search = $('#recipeSearch');
  const dinnerPrompt = $('#dinnerPrompt');
  const dinnerResults = $('#dinnerResults');
  const carouselTrack = $('#carouselTrack');
  let carouselSlides = [];
  let carouselDots = [];
  let carouselImages = [];
  let carouselCaption = '';
  const fmt = new Intl.NumberFormat(undefined, { maximumFractionDigits: 1 });
  const recipesApi = window.BeyondKitchenRecipes;
  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[char]);

  function announce(message) {
    status.textContent = message;
  }

  function readFavorites() {
    try {
      const stored = JSON.parse(localStorage.getItem(favoritesKey) || '[]');
      const recipeIds = new Set(state.recipes.map((recipe) => recipe.id));
      state.favorites = new Set(Array.isArray(stored) ? stored.filter((id) => typeof id === 'string' && recipeIds.has(id)) : []);
    } catch (error) {
      state.favorites = new Set();
      announce('Saved recipes could not be read from this browser. You can still save recipes during this visit.');
    }
  }

  function saveFavorites() {
    try {
      localStorage.setItem(favoritesKey, JSON.stringify([...state.favorites]));
    } catch (error) {
      announce('Your saved recipes could not be stored by this browser. They will remain available until you leave this page.');
    }
    updateSavedCount();
    renderRecipes();
  }

  function updateSavedCount() {
    $('#savedCount').textContent = String(state.favorites.size);
    $('#favoritesToggle').setAttribute('aria-pressed', String(state.favoritesOnly));
  }

  function showCarouselSlide(index) {
    if (!carouselSlides.length) return;
    const nextIndex = Math.min(carouselSlides.length - 1, Math.max(0, index));
    const slideOffset = carouselSlides[nextIndex].getBoundingClientRect().left - carouselTrack.getBoundingClientRect().left;
    carouselTrack.scrollTo({
      left: carouselTrack.scrollLeft + slideOffset,
      behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
    });
    updateCarouselControls(nextIndex);
  }

  function updateCarouselControls(index = Math.round(carouselTrack.scrollLeft / carouselTrack.clientWidth)) {
    if (!carouselSlides.length) return;
    const activeIndex = Math.min(carouselSlides.length - 1, Math.max(0, index));
    carouselDots.forEach((dot, dotIndex) => dot.setAttribute('aria-current', String(dotIndex === activeIndex)));
    $('#carouselCount').textContent = `${activeIndex + 1} / ${carouselSlides.length}`;
    const download = $('#carouselDownload');
    download.hidden = !carouselImages[activeIndex];
    if (carouselImages[activeIndex]) {
      download.href = carouselImages[activeIndex];
      download.download = `beyond-kitchen-slide-${String(activeIndex + 1).padStart(2, '0')}.jpg`;
      download.textContent = `Download slide ${activeIndex + 1}`;
    }
  }

  $('#carouselPrevious').addEventListener('click', () => showCarouselSlide(Math.round(carouselTrack.scrollLeft / carouselTrack.clientWidth) - 1));
  $('#carouselNext').addEventListener('click', () => showCarouselSlide(Math.round(carouselTrack.scrollLeft / carouselTrack.clientWidth) + 1));
  $('#carouselDots').addEventListener('click', (event) => {
    const dot = event.target.closest('[data-carousel-to]');
    if (dot) showCarouselSlide(Number(dot.dataset.carouselTo));
  });
  $('#carouselCaption').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(carouselCaption);
      announce('Post caption copied.');
    } catch (error) {
      window.prompt('Copy the post caption:', carouselCaption);
    }
  });
  carouselTrack.addEventListener('scroll', () => window.requestAnimationFrame(() => updateCarouselControls()), { passive: true });

  function dailyRecipe() {
    const now = new Date();
    const localDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    return recipesApi.featuredRecipe(state.recipes, localDate);
  }

  function localDate() {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  }

  function carouselContent(recipe) {
    const ingredients = recipe.ingredients.map((item) => `${fmt.format(item.amount)} ${item.unit} ${item.name}`.replace(/\s+/g, ' ').trim()).join('\n');
    return [
      { label: "Today's recipe", title: recipe.name, body: recipe.description },
      { label: "What you'll need", title: 'The ingredients', body: ingredients },
      { label: "Let's make it · 1", title: 'Get started', body: recipe.steps[0] },
      { label: "Let's make it · 2", title: 'Bring it together', body: recipe.steps.slice(1).join('\n\n') },
      { label: 'Make it your own', title: 'Ready to enjoy', body: 'Save this recipe for later. Find the full method on Beyond Kitchen.' }
    ];
  }

  function renderCarousel(recipe, manifest = null) {
    const slides = carouselContent(recipe);
    const generated = manifest && manifest.date === localDate() && manifest.recipeId === recipe.id
      && Array.isArray(manifest.images) && manifest.images.length === slides.length;
    carouselImages = generated ? manifest.images.map((path) => `./${path}`) : [];
    carouselCaption = generated && typeof manifest.caption === 'string' ? manifest.caption : '';
    $('#carouselCaption').hidden = !carouselCaption;
    $('#carouselHeading').textContent = recipe.name;
    $('#carouselDescription').textContent = `Five slides with ingredients and steps for ${recipe.name}. Swipe through, then make it your own.`;
    $('#carouselRecipeButton').hidden = false;
    $('#carouselRecipeButton').dataset.open = recipe.id;
    $('#recipeCarousel').setAttribute('aria-label', `${recipe.name} recipe carousel`);
    carouselTrack.innerHTML = slides.map((slide, index) => `<figure class="carousel-slide" data-carousel-slide role="group" aria-roledescription="slide" aria-label="${index + 1} of ${slides.length}">${generated
      ? `<img src="${escapeHtml(carouselImages[index])}" alt="${escapeHtml(`${slide.label}. ${slide.title}. ${slide.body}`)}" width="1080" height="1350" ${index === 0 ? 'fetchpriority="high"' : 'loading="lazy"'}>`
      : `<div class="carousel-slide-content" style="--carousel-photo:url('${escapeHtml(recipe.image)}')"><span class="eyebrow">${escapeHtml(slide.label)}</span><h3>${escapeHtml(slide.title)}</h3><p>${escapeHtml(slide.body)}</p><small>Beyond Kitchen · ${index + 1} / 5</small></div>`}</figure>`).join('');
    $('#carouselDots').innerHTML = slides.map((_, index) => `<button type="button" data-carousel-to="${index}" aria-label="Show slide ${index + 1}" aria-current="${index === 0}"></button>`).join('');
    carouselSlides = $$('[data-carousel-slide]', carouselTrack);
    carouselDots = $$('#carouselDots [data-carousel-to]');
    carouselTrack.scrollLeft = 0;
    updateCarouselControls(0);
  }

  async function loadCarousel(recipe) {
    renderCarousel(recipe);
    try {
      const response = await fetch('./assets/images/daily/latest.json', { cache: 'no-store' });
      if (!response.ok) return;
      const manifest = await response.json();
      if (manifest.date === localDate() && manifest.recipeId === recipe.id) renderCarousel(recipe, manifest);
    } catch (error) {
      // The recipe cards remain usable before the first cron render or while offline.
    }
  }

  function renderDinnerIdeas(data) {
    const recipe = state.recipes.find((item) => item.id === data.cook.recipeId);
    const pickupUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${data.pickup.dish} pickup near me`)}`;
    const deliveryUrl = `https://www.google.com/search?q=${encodeURIComponent(`${data.delivery.dish} delivery near me`)}`;
    dinnerResults.innerHTML = `
      <article class="dinner-result-card"><span class="eyebrow">Cook</span><h3>${escapeHtml(data.cook.name)}</h3><p>${escapeHtml(data.cook.why)}${recipe ? ` · ${recipe.timeMinutes} min` : ''}</p>${recipe
        ? `<button class="dinner-result-action" type="button" data-open="${escapeHtml(recipe.id)}">Open recipe →</button>`
        : '<a class="dinner-result-action" href="#recipes">Browse recipes →</a>'}</article>
      <article class="dinner-result-card"><span class="eyebrow">Pick up</span><h3>${escapeHtml(data.pickup.dish)}</h3><p>${escapeHtml(data.pickup.why)}</p><a class="dinner-result-action" href="${escapeHtml(pickupUrl)}" target="_blank" rel="noopener noreferrer">Search nearby pickup →</a></article>
      <article class="dinner-result-card"><span class="eyebrow">Deliver</span><h3>${escapeHtml(data.delivery.dish)}</h3><p>${escapeHtml(data.delivery.why)}</p><a class="dinner-result-action" href="${escapeHtml(deliveryUrl)}" target="_blank" rel="noopener noreferrer">Search delivery →</a></article>`;
    dinnerResults.hidden = false;
    announce('Three dinner ideas are ready.');
  }

  function renderDinnerUnavailable(message) {
    const recipe = state.recipes.length ? dailyRecipe() : null;
    dinnerResults.innerHTML = `<p class="dinner-message">${escapeHtml(message)}</p>${recipe
      ? `<article class="dinner-result-card"><span class="eyebrow">A recipe for now</span><h3>${escapeHtml(recipe.name)}</h3><p>${escapeHtml(recipe.description)}</p><button class="dinner-result-action" type="button" data-open="${escapeHtml(recipe.id)}">Open recipe →</button></article>`
      : ''}`;
    dinnerResults.hidden = false;
    announce(message);
  }

  $$('.dinner-hints [data-dinner-hint]').forEach((chip) => chip.addEventListener('click', () => {
    const hint = chip.dataset.dinnerHint;
    const separator = dinnerPrompt.value.trim() ? ', ' : '';
    dinnerPrompt.value = (dinnerPrompt.value.trim() + separator + hint).slice(0, 400);
    dinnerPrompt.focus();
  }));
  $('#dinnerForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const submit = $('#dinnerSubmit');
    const prompt = dinnerPrompt.value.trim() || 'Surprise me with a good dinner tonight.';
    submit.disabled = true;
    submit.textContent = 'Finding ideas…';
    dinnerResults.hidden = false;
    dinnerResults.innerHTML = '<p class="dinner-message">Putting a few dinner ideas together…</p>';
    try {
      const response = await fetch('./api/dinner.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prompt })
      });
      const data = await response.json();
      if (!response.ok || !data.ok) throw new Error(data.error || 'AI dinner ideas are unavailable right now.');
      renderDinnerIdeas(data);
    } catch (error) {
      renderDinnerUnavailable(error.message || 'AI dinner ideas are unavailable right now.');
    } finally {
      submit.disabled = false;
      submit.innerHTML = 'Find dinner ideas <span aria-hidden="true">→</span>';
    }
  });

  function recipeMeta(recipe, servings = recipe.servings) {
    return `<div class="recipe-meta">
      <span><b aria-hidden="true">◷</b> ${recipe.timeMinutes} min</span>
      <span><b aria-hidden="true">✳</b> ${escapeHtml(recipe.difficulty)}</span>
      <span><b aria-hidden="true">♧</b> ${servings} servings</span>
    </div>`;
  }

  function favoriteButton(recipe, className = 'favorite-button') {
    const isSaved = state.favorites.has(recipe.id);
    return `<button class="${className}" type="button" data-favorite="${escapeHtml(recipe.id)}" aria-label="${isSaved ? 'Remove' : 'Save'} ${escapeHtml(recipe.name)} ${isSaved ? 'from' : 'to'} saved recipes" aria-pressed="${isSaved}">${isSaved ? '♥' : '♡'}</button>`;
  }

  function imageStyle(recipe) {
    return `background-image:linear-gradient(135deg,#71836b44,#d4c69833),url('${escapeHtml(recipe.image)}')`;
  }

  function renderDaily() {
    const recipe = dailyRecipe();
    daily.innerHTML = `
      <div class="daily-copy">
        <span class="daily-kicker"><span aria-hidden="true">✳</span> ${escapeHtml(recipe.category)} · today's pick</span>
        <h3>${escapeHtml(recipe.name)}</h3>
        <p>${escapeHtml(recipe.description)}</p>
        ${recipeMeta(recipe)}
        <div class="daily-actions">
          <button class="primary-button" type="button" data-open="${escapeHtml(recipe.id)}">Get cooking <span aria-hidden="true">→</span></button>
          ${favoriteButton(recipe)}
        </div>
      </div>
      <div class="daily-photo" role="img" aria-label="${escapeHtml(recipe.imageAlt)}" style="${imageStyle(recipe)}">
        <span class="daily-photo-label">A little inspiration for today</span>
      </div>`;
  }

  function renderRecipes() {
    const query = search.value.trim().toLowerCase();
    const recipes = recipesApi.filterRecipes(state.recipes, {
      query,
      filter: state.filter,
      favoritesOnly: state.favoritesOnly,
      favorites: state.favorites
    });

    grid.innerHTML = recipes.map((recipe) => `
      <article class="recipe-card">
        <div class="recipe-photo" style="${imageStyle(recipe)}">
          <button class="photo-open" type="button" data-open="${escapeHtml(recipe.id)}" aria-label="View ${escapeHtml(recipe.name)} recipe"></button>
          ${favoriteButton(recipe)}
        </div>
        <div class="recipe-card-copy">
          <span class="category-label">${escapeHtml(recipe.category)}</span>
          <h3>${escapeHtml(recipe.name)}</h3>
          <p>${escapeHtml(recipe.description)}</p>
          <div class="card-footer"><span>◷ ${recipe.timeMinutes} min · ${escapeHtml(recipe.difficulty)}</span><span>${escapeHtml(recipe.tags[0])}</span></div>
        </div>
      </article>`).join('');

    $('#recipeCount').textContent = `${recipes.length} ${recipes.length === 1 ? 'recipe' : 'recipes'}`;
    $('#emptyState').hidden = recipes.length > 0;
    updateSavedCount();
  }

  function amountLabel(amount, servings) {
    const scaled = recipesApi.scaledAmount(amount, servings, state.activeRecipe.servings);
    return fmt.format(scaled);
  }

  function renderDetails() {
    const recipe = state.activeRecipe;
    details.innerHTML = `
      <div class="detail-photo" role="img" aria-label="${escapeHtml(recipe.imageAlt)}" style="${imageStyle(recipe)}">
        <h2 id="dialogTitle">${escapeHtml(recipe.name)}</h2>
      </div>
      <div class="detail-body">
        <div class="detail-summary">
          <p>${escapeHtml(recipe.description)}</p>
          ${favoriteButton(recipe, 'favorite-button detail-favorite')}
        </div>
        ${recipeMeta(recipe, state.servings)}
        <div class="detail-columns">
          <section aria-labelledby="ingredientsHeading">
            <h3 id="ingredientsHeading">What you'll need</h3>
            <div class="servings-control" aria-label="Adjust servings">
              <span>Servings</span>
              <button type="button" data-servings="-1" aria-label="Fewer servings">−</button>
              <strong>${state.servings}</strong>
              <button type="button" data-servings="1" aria-label="More servings">+</button>
            </div>
            <ul class="ingredient-list">${recipe.ingredients.map((item) => `
              <li><span>${escapeHtml(item.name)}</span><span>${amountLabel(item.amount, state.servings)} ${escapeHtml(item.unit)}</span></li>`).join('')}
            </ul>
          </section>
          <section aria-labelledby="stepsHeading">
            <h3 id="stepsHeading">Let's make it</h3>
            <ol class="step-list">${recipe.steps.map((step) => `<li>${escapeHtml(step)}</li>`).join('')}</ol>
          </section>
        </div>
      </div>`;
  }

  function openRecipe(id) {
    const recipe = state.recipes.find((item) => item.id === id);
    if (!recipe) return;
    state.activeRecipe = recipe;
    state.servings = recipe.servings;
    renderDetails();
    dialog.showModal();
  }

  function toggleFavorite(id) {
    const recipe = state.recipes.find((item) => item.id === id);
    if (!recipe) return;
    if (state.favorites.has(id)) {
      state.favorites.delete(id);
      announce(`${recipe.name} removed from saved recipes.`);
    } else {
      state.favorites.add(id);
      announce(`${recipe.name} added to saved recipes.`);
    }
    saveFavorites();
    renderDaily();
    if (dialog.open && state.activeRecipe) renderDetails();
  }

  document.addEventListener('click', (event) => {
    const target = event.target.closest('[data-open], [data-favorite], [data-filter], [data-servings], #favoritesToggle, #dialogClose');
    if (!target) return;
    if (target.matches('[data-open]')) openRecipe(target.dataset.open);
    if (target.matches('[data-favorite]')) {
      event.preventDefault();
      event.stopPropagation();
      toggleFavorite(target.dataset.favorite);
    }
    if (target.matches('[data-filter]')) {
      state.filter = target.dataset.filter;
      $$('.filter-chip').forEach((chip) => chip.classList.toggle('active', chip === target));
      renderRecipes();
    }
    if (target.id === 'favoritesToggle') {
      state.favoritesOnly = !state.favoritesOnly;
      target.setAttribute('aria-pressed', String(state.favoritesOnly));
      target.querySelector('span:nth-child(2)').textContent = state.favoritesOnly ? 'All' : 'Saved';
      $('#recipesHeading').textContent = state.favoritesOnly ? 'Your saved recipes' : 'Find your next favourite';
      document.querySelector('.recipe-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
      renderRecipes();
    }
    if (target.id === 'dialogClose') dialog.close();
    if (target.matches('[data-servings]')) {
      state.servings = Math.min(12, Math.max(1, state.servings + Number(target.dataset.servings)));
      renderDetails();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName) && !dialog.open) {
      event.preventDefault();
      search.focus();
    }
  });

  search.addEventListener('input', renderRecipes);
  const now = new Date();
  $('#todayDate').textContent = new Intl.DateTimeFormat(undefined, { weekday: 'long', month: 'long', day: 'numeric' }).format(now);

  fetch('./data/recipes.json')
    .then((response) => {
      if (!response.ok) throw new Error(`Recipe data request failed (${response.status}).`);
      return response.json();
    })
    .then((recipes) => {
      if (!Array.isArray(recipes) || recipes.length === 0) throw new Error('Recipe data is empty.');
      state.recipes = recipes;
      readFavorites();
      renderDaily();
      loadCarousel(dailyRecipe());
      renderRecipes();
    })
    .catch((error) => {
      console.error('Beyond Kitchen could not start:', error);
      daily.innerHTML = '<p class="empty-state">We could not load the recipes. Please refresh to try again.</p>';
      grid.innerHTML = '';
      announce('Recipes could not be loaded. Please refresh to try again.');
    });
})();
