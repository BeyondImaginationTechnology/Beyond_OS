(() => {
  'use strict';

  function featuredRecipe(recipes, date) {
    if (!Array.isArray(recipes) || recipes.length === 0) throw new TypeError('A non-empty recipe list is required.');
    const timestamp = Date.parse(`${date}T00:00:00.000Z`);
    if (!Number.isFinite(timestamp)) throw new TypeError('A valid YYYY-MM-DD date is required.');
    const day = Math.floor(timestamp / 86400000);
    return recipes[((day % recipes.length) + recipes.length) % recipes.length];
  }

  function filterRecipes(recipes, options = {}) {
    const query = String(options.query || '').trim().toLowerCase();
    const filter = options.filter || 'All';
    const favorites = options.favorites instanceof Set ? options.favorites : new Set();
    return recipes.filter((recipe) => {
      const haystack = [recipe.name, recipe.description, recipe.category, ...recipe.tags, ...recipe.ingredients.map((ingredient) => ingredient.name)].join(' ').toLowerCase();
      const matchesSearch = !query || haystack.includes(query);
      const matchesFilter = filter === 'All'
        || (filter === 'Quick' && recipe.timeMinutes < 30)
        || (filter === 'Vegetarian' && recipe.tags.includes('Vegetarian'))
        || (filter === 'Haitian' && recipe.tags.includes('Haitian'))
        || recipe.category === filter;
      return matchesSearch && matchesFilter && (!options.favoritesOnly || favorites.has(recipe.id));
    });
  }

  function scaledAmount(amount, servings, originalServings) {
    if (!Number.isFinite(amount) || !Number.isFinite(servings) || !Number.isFinite(originalServings) || originalServings <= 0) {
      throw new TypeError('Ingredient amounts and serving counts must be finite, with a positive original serving count.');
    }
    return amount * servings / originalServings;
  }

  const library = { featuredRecipe, filterRecipes, scaledAmount };
  globalThis.BeyondKitchenRecipes = library;
  if (typeof module === 'object' && module.exports) module.exports = library;
})();
