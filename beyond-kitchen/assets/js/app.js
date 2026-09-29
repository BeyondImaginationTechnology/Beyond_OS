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
  const carouselTrack = $('#carouselTrack');
  const carouselSlides = carouselTrack ? $$('[data-carousel-slide]', carouselTrack) : [];
  const carouselDots = $$('#recipeCarousel [data-carousel-to]');
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
  }

  if (carouselTrack && carouselSlides.length) {
    $('#carouselPrevious').addEventListener('click', () => {
      showCarouselSlide(Math.round(carouselTrack.scrollLeft / carouselTrack.clientWidth) - 1);
    });
    $('#carouselNext').addEventListener('click', () => {
      showCarouselSlide(Math.round(carouselTrack.scrollLeft / carouselTrack.clientWidth) + 1);
    });
    carouselDots.forEach((dot) => dot.addEventListener('click', () => showCarouselSlide(Number(dot.dataset.carouselTo))));
    carouselTrack.addEventListener('scroll', () => {
      window.requestAnimationFrame(() => updateCarouselControls());
    }, { passive: true });
  }

  function dailyRecipe() {
    const now = new Date();
    const localDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    return recipesApi.featuredRecipe(state.recipes, localDate);
  }

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
    return `background-image:linear-gradient(135deg,#71836b44,#d4c69833),url("${escapeHtml(recipe.image)}")`;
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
      renderRecipes();
    })
    .catch((error) => {
      console.error('Beyond Kitchen could not start:', error);
      daily.innerHTML = '<p class="empty-state">We could not load the recipes. Please refresh to try again.</p>';
      grid.innerHTML = '';
      announce('Recipes could not be loaded. Please refresh to try again.');
    });
})();
