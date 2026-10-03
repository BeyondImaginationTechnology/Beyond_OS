(() => {
  'use strict';

  const section = document.querySelector('#meals');
  if (!section) return;
  const week = document.querySelector('#mealWeek');
  const weekLabel = document.querySelector('#mealWeekLabel');
  const groceryList = document.querySelector('#groceryList');
  const grocerySummary = document.querySelector('#grocerySummary');
  const copyButton = document.querySelector('#copyGrocery');
  const kitchenUrl = section.dataset.catalogUrl.replace(/data\/recipes\.json(?:\?.*)?$/, '');
  const storageKey = 'beyond-health-meal-plan-v1';
  const slots = ['Breakfast', 'Lunch', 'Dinner'];
  const prepOptions = [5, 10, 15, 20, 30, 45];
  const state = { recipes: [], offset: 0, meals: {}, prepMinutes: {} };
  let groceryLines = [];

  try {
    const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
    if (stored && typeof stored === 'object') {
      if (stored.meals && typeof stored.meals === 'object') state.meals = stored.meals;
      if (stored.prepMinutes && typeof stored.prepMinutes === 'object') state.prepMinutes = stored.prepMinutes;
    }
  } catch (_) {
    // A damaged local plan should not prevent the planner from opening.
  }

  function announce(message) {
    const toast = document.querySelector('#toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2400);
  }

  function persist() {
    try {
      localStorage.setItem(storageKey, JSON.stringify({ meals: state.meals, prepMinutes: state.prepMinutes }));
      return true;
    } catch (_) {
      announce('This browser could not save your meal plan.');
      return false;
    }
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char]);
  }

  function monday() {
    const date = new Date();
    date.setHours(12, 0, 0, 0);
    date.setDate(date.getDate() - ((date.getDay() + 6) % 7) + state.offset * 7);
    return date;
  }

  function dateKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
  }

  function dayAt(start, index) {
    const date = new Date(start);
    date.setDate(date.getDate() + index);
    return date;
  }

  function recommendation(minutes) {
    return state.recipes
      .filter((recipe) => recipe.category === 'Breakfast' && recipe.timeMinutes <= minutes)
      .sort((a, b) => b.timeMinutes - a.timeMinutes || a.name.localeCompare(b.name))[0] || null;
  }

  function recipeOptions(selected) {
    return `<option value="">Choose a recipe</option>${state.recipes.map((recipe) =>
      `<option value="${escapeHtml(recipe.id)}"${recipe.id === selected ? ' selected' : ''}>${escapeHtml(recipe.name)} · ${recipe.timeMinutes} min</option>`
    ).join('')}`;
  }

  function render() {
    if (!state.recipes.length) return;
    const start = monday();
    const end = dayAt(start, 6);
    const dateFormat = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' });
    weekLabel.textContent = `${dateFormat.format(start)} – ${dateFormat.format(end)}`;
    const recipeById = new Map(state.recipes.map((recipe) => [recipe.id, recipe]));
    week.innerHTML = Array.from({ length: 7 }, (_, index) => {
      const day = dayAt(start, index);
      const key = dateKey(day);
      const minutes = prepOptions.includes(Number(state.prepMinutes[key])) ? Number(state.prepMinutes[key]) : 15;
      const suggested = recommendation(minutes);
      const breakfastId = state.meals[`${key}|Breakfast`];
      const rows = slots.map((slot) => {
        const selected = state.meals[`${key}|${slot}`] || '';
        const recipe = recipeById.get(selected);
        return `<div class="meal-slot"><label for="meal-${key}-${slot}">${slot}</label><select id="meal-${key}-${slot}" data-meal-date="${key}" data-meal-slot="${slot}">${recipeOptions(selected)}</select></div>${recipe ? `<div class="meal-meta">${escapeHtml(slot)} · ${recipe.timeMinutes} min · <a href="${escapeHtml(kitchenUrl)}?recipe=${encodeURIComponent(recipe.id)}">See recipe in Beyond Kitchen</a></div>` : ''}`;
      }).join('');
      return `<article class="card meal-day"><h3>${new Intl.DateTimeFormat(undefined, { weekday: 'long', month: 'short', day: 'numeric' }).format(day)}</h3><div class="meal-slot"><label for="prep-${key}">Morning prep</label><select id="prep-${key}" data-prep-date="${key}">${prepOptions.map((value) => `<option value="${value}"${value === minutes ? ' selected' : ''}>${value} minutes</option>`).join('')}</select></div><p class="meal-meta">${suggested ? `Fits your morning: <strong>${escapeHtml(suggested.name)}</strong> · ${suggested.timeMinutes} min` : 'No breakfast recipe fits yet. Try a longer prep window.'} ${suggested && breakfastId !== suggested.id ? `<button class="secondary" type="button" data-use-suggestion="${key}" data-recipe-id="${escapeHtml(suggested.id)}">Add breakfast</button>` : ''}</p>${rows}</article>`;
    }).join('');

    const amounts = new Map();
    let planned = 0;
    for (let index = 0; index < 7; index++) {
      const key = dateKey(dayAt(start, index));
      for (const slot of slots) {
        const recipe = recipeById.get(state.meals[`${key}|${slot}`]);
        if (!recipe) continue;
        planned++;
        for (const ingredient of recipe.ingredients) {
          const name = ingredient.name.trim();
          const unit = ingredient.unit.trim();
          const itemKey = `${name.toLowerCase()}|${unit.toLowerCase()}`;
          const item = amounts.get(itemKey) || { name, unit, amount: 0 };
          item.amount += Number(ingredient.amount) || 0;
          amounts.set(itemKey, item);
        }
      }
    }
    groceryLines = [...amounts.values()].sort((a, b) => a.name.localeCompare(b.name)).map((item) =>
      `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(item.amount)} ${item.unit} ${item.name}`.replace(/\s+/g, ' ').trim()
    );
    grocerySummary.textContent = planned ? `${planned} planned meal${planned === 1 ? '' : 's'} this week · ingredients use the recipes' default servings.` : 'Choose a recipe to build your list.';
    groceryList.innerHTML = groceryLines.map((line) => `<li>${escapeHtml(line)}</li>`).join('');
    copyButton.hidden = groceryLines.length === 0;
  }

  week.addEventListener('change', (event) => {
    const target = event.target;
    if (target.matches('[data-prep-date]')) {
      state.prepMinutes[target.dataset.prepDate] = Number(target.value);
    } else if (target.matches('[data-meal-date]')) {
      const key = `${target.dataset.mealDate}|${target.dataset.mealSlot}`;
      if (target.value) state.meals[key] = target.value;
      else delete state.meals[key];
    } else return;
    persist();
    render();
  });
  week.addEventListener('click', (event) => {
    const button = event.target.closest('[data-use-suggestion]');
    if (!button) return;
    state.meals[`${button.dataset.useSuggestion}|Breakfast`] = button.dataset.recipeId;
    persist();
    render();
  });
  document.querySelector('#mealPrevious').addEventListener('click', () => { state.offset--; render(); });
  document.querySelector('#mealNext').addEventListener('click', () => { state.offset++; render(); });
  document.querySelector('#mealCurrent').addEventListener('click', () => { state.offset = 0; render(); });
  copyButton.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(groceryLines.join('\n'));
      announce('Grocery list copied.');
    } catch (_) {
      announce('Clipboard unavailable. Select the list to copy it.');
    }
  });

  fetch(section.dataset.catalogUrl)
    .then((response) => { if (!response.ok) throw new Error('Recipe catalog unavailable'); return response.json(); })
    .then((recipes) => {
      if (!Array.isArray(recipes) || !recipes.length) throw new Error('Recipe catalog is empty');
      state.recipes = recipes;
      render();
    })
    .catch(() => { week.innerHTML = '<p class="empty">Beyond Kitchen recipes could not load. Try again when connected.</p>'; });
})();
