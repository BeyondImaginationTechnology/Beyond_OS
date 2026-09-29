const test = require('node:test');
const assert = require('node:assert/strict');
const recipes = require('../data/recipes.json');
const library = require('../assets/js/recipe-library.js');

test('the recipe catalog has unique, complete recipe records', () => {
  assert.ok(recipes.length >= 6);
  assert.equal(new Set(recipes.map((recipe) => recipe.id)).size, recipes.length);
  for (const recipe of recipes) {
    assert.ok(recipe.name && recipe.description && recipe.imageAlt);
    assert.ok(recipe.timeMinutes > 0 && recipe.servings > 0);
    assert.ok(recipe.ingredients.length >= 3 && recipe.steps.length >= 2);
    assert.ok(recipe.ingredients.every((ingredient) => Number.isFinite(ingredient.amount)));
  }
});

test('the featured recipe is stable for a date and advances daily', () => {
  const first = library.featuredRecipe(recipes, '2026-09-28');
  assert.equal(library.featuredRecipe(recipes, '2026-09-28').id, first.id);
  assert.notEqual(library.featuredRecipe(recipes, '2026-09-29').id, first.id);
});

test('recipe filtering searches ingredients and applies discovery filters', () => {
  assert.deepEqual(library.filterRecipes(recipes, { query: 'chickpeas' }).map((recipe) => recipe.id), ['lemon-chickpea-bowls']);
  assert.ok(library.filterRecipes(recipes, { filter: 'Vegetarian' }).every((recipe) => recipe.tags.includes('Vegetarian')));
  assert.ok(library.filterRecipes(recipes, { filter: 'Quick' }).every((recipe) => recipe.timeMinutes < 30));
  assert.deepEqual(library.filterRecipes(recipes, { filter: 'Dinner' }).map((recipe) => recipe.category), ['Dinner', 'Dinner', 'Dinner']);
});

test('saved recipe filtering and serving scaling remain local and predictable', () => {
  const saved = new Set(['green-goddess-toast']);
  assert.deepEqual(library.filterRecipes(recipes, { favoritesOnly: true, favorites: saved }).map((recipe) => recipe.id), ['green-goddess-toast']);
  assert.equal(library.scaledAmount(1.5, 4, 2), 3);
  assert.throws(() => library.scaledAmount(1, 2, 0), TypeError);
});
