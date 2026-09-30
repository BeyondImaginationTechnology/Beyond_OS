(() => {
  const $ = (selector) => document.querySelector(selector);
  const flavourButtons = [...document.querySelectorAll('.flavour')];
  const planButtons = [...document.querySelectorAll('.plan')];
  const requestButton = $('#startSeat');
  const csrf = document.body.dataset.requestToken;
  const state = { flavour: 'Gaming', plan: 'Build' };
  let savedRequest = null;

  const select = (buttons, key, value) => {
    const button = buttons.find((item) => item.dataset[key] === value);
    if (!button) return false;
    buttons.forEach((item) => item.classList.toggle('selected', item === button));
    state[key] = value;
    return true;
  };

  const remember = () => {
    try { localStorage.setItem('beyondWebsSeat', JSON.stringify(state)); } catch (_) { /* Storage is optional. */ }
  };

  const render = () => {
    const flavour = flavourButtons.find((item) => item.dataset.flavour === state.flavour);
    const plan = planButtons.find((item) => item.dataset.plan === state.plan);
    if (!flavour || !plan) return;
    $('#chosenFlavour').textContent = $('#previewFlavour').textContent = state.flavour;
    $('#chosenPlan').textContent = $('#previewPlan').textContent = state.plan;
    $('#flavourDescription').textContent = flavour.dataset.desc;
    for (const key of ['ram', 'cpu', 'gpu', 'storage']) {
      $('#' + key).textContent = $('#preview' + key[0].toUpperCase() + key.slice(1)).textContent = plan.dataset[key];
    }
    if (requestButton) {
      const unchanged = savedRequest && savedRequest.flavour === state.flavour && savedRequest.plan === state.plan;
      const locked = savedRequest && savedRequest.status !== 'requested';
      requestButton.disabled = Boolean(unchanged || locked);
      requestButton.innerHTML = locked ? 'Request is being handled <span>✓</span>'
        : unchanged ? 'Request saved <span>✓</span>'
          : savedRequest ? 'Update session request <span>→</span>' : 'Save session request <span>→</span>';
      $('#seatState').textContent = unchanged ? 'REQUEST SAVED' : 'READY TO REQUEST';
    }
    remember();
  };

  const showRequest = (request) => {
    savedRequest = request;
    if (!request) {
      $('#accountStatus').textContent = 'No request yet';
      $('#accountDetails').textContent = 'Choose a BIT OS flavour and session size, then save your request.';
      $('#accountId').textContent = '—';
      $('#accountDate').textContent = '—';
      $('#previewState').textContent = 'NOT REQUESTED';
    } else {
      select(flavourButtons, 'flavour', request.flavour);
      select(planButtons, 'plan', request.plan);
      $('#accountStatus').textContent = request.status === 'requested' ? 'Request saved' : request.status;
      $('#accountDetails').textContent = `${request.flavour} · ${request.plan}. No machine has been started from this page.`;
      $('#accountId').textContent = request.request_id;
      const date = new Date(request.requested_at.replace(' ', 'T') + 'Z');
      $('#accountDate').textContent = Number.isNaN(date.getTime()) ? request.requested_at : date.toLocaleString();
      $('#previewState').textContent = 'REQUEST SAVED';
    }
    render();
  };

  flavourButtons.forEach((button) => button.addEventListener('click', () => {
    select(flavourButtons, 'flavour', button.dataset.flavour);
    render();
  }));
  planButtons.forEach((button) => button.addEventListener('click', () => {
    select(planButtons, 'plan', button.dataset.plan);
    render();
  }));

  try {
    const previous = JSON.parse(localStorage.getItem('beyondWebsSeat') || 'null');
    if (previous && typeof previous === 'object') {
      select(flavourButtons, 'flavour', previous.flavour?.name || previous.flavour);
      select(planButtons, 'plan', previous.plan?.name || previous.plan);
    }
  } catch (_) { /* Use the default configuration. */ }
  render();

  if (!requestButton || !csrf) return;
  fetch('api/session.php', { credentials: 'same-origin', cache: 'no-store' })
    .then(async (response) => {
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Could not load your request.');
      showRequest(data.request);
    })
    .catch((error) => {
      $('#accountStatus').textContent = 'Request status unavailable';
      $('#accountDetails').textContent = error.message;
    });

  requestButton.addEventListener('click', async () => {
    requestButton.disabled = true;
    $('#requestMessage').textContent = 'Saving your request…';
    try {
      const response = await fetch('api/session.php', {
        method: 'POST', credentials: 'same-origin', cache: 'no-store',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ csrf, flavour: state.flavour, plan: state.plan })
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Could not save your request.');
      showRequest(data.request);
      $('#requestMessage').textContent = 'Saved to your Beyond ID. No machine or hourly charge has started.';
    } catch (error) {
      $('#requestMessage').textContent = error.message;
      requestButton.disabled = false;
    }
  });
})();
