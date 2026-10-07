(() => {
  const $ = (selector) => document.querySelector(selector);
  const flavourButtons = [...document.querySelectorAll('.flavour')];
  const planButtons = [...document.querySelectorAll('.plan')];
  const modeButtons = [...document.querySelectorAll('.mode')];
  const requestButton = $('#startSeat');
  const csrf = document.body.dataset.requestToken;
  const state = { flavour: 'Home', plan: 'Build', work_mode: 'developer' };
  const modeNames = { developer: 'Linux developer workstation', creative: 'Linux creator workstation', gaming: 'Linux game launcher' };
  let savedRequest = null;

  const select = (buttons, key, value) => {
    const button = buttons.find((item) => item.dataset[key] === value);
    if (!button) return false;
    buttons.forEach((item) => {
      item.classList.toggle('selected', item === button);
      item.setAttribute('aria-pressed', item === button ? 'true' : 'false');
    });
    state[key] = value;
    return true;
  };

  const remember = () => {
    try { localStorage.setItem('beyondWebsSeat', JSON.stringify(state)); } catch (_) { /* Storage is optional. */ }
  };

  const setHidden = (selector, hidden) => { const element = $(selector); if (element) element.hidden = hidden; };

  const renderControls = (request) => {
    if (!$('#sessionControls')) return;
    const available = Boolean(request);
    setHidden('#sessionControls', !available);
    if (!available) return;
    $('#accountStatus').textContent = request.status === 'requested' ? 'Request saved'
      : request.status === 'running' ? 'VPS session running'
        : request.status === 'stopped' ? 'VPS session stopped'
          : request.status.charAt(0).toUpperCase() + request.status.slice(1);
    $('#accountAccess').textContent = request.session_access_ready ? 'Ready · protected noVNC' : 'noVNC · pending session';
    $('#accountRate').textContent = request.hourly_rate || 'Not configured';
    const seconds = Number(request.usage_seconds_estimate || 0);
    $('#accountUsage').textContent = seconds > 0 ? `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}m` : 'Not started';
    const hours = Number(request.billable_hours_estimate || 0);
    $('#accountEstimate').textContent = hours ? `${hours} billed hour${hours === 1 ? '' : 's'} at ${request.hourly_rate || 'rate unavailable'} · estimate` : 'No usage yet';
    $('#sessionMessage').textContent = '';
    const accepted = Boolean(request.start_enabled && request.hourly_rate);
    setHidden('#startVps', !(request.status === 'requested' && accepted));
    setHidden('#resumeVps', !(request.status === 'stopped' && accepted));
    setHidden('#openDesktop', !(request.status === 'running' && request.session_access_ready));
    setHidden('#stopVps', request.status !== 'running');
    if (request.status === 'requested' && !accepted) {
      $('#sessionMessage').textContent = 'Session start is disabled until provisioning, customer billing, and a confirmed hourly rate are configured.';
    } else if (request.status === 'stopped') {
      $('#sessionMessage').textContent = 'Your VM disk is retained for the next session and may continue to incur storage charges.';
    } else if (request.status === 'provisioning') {
      $('#sessionMessage').textContent = 'Google Cloud is preparing your VM.';
    } else if (request.status === 'running') {
      $('#sessionMessage').textContent = `The VM automatically stops after its ${Math.round(Number(request.session_runtime_seconds || 14400) / 3600)} hour session limit.`;
    }
  };

  const render = () => {
    const flavour = flavourButtons.find((item) => item.dataset.flavour === state.flavour);
    const plan = planButtons.find((item) => item.dataset.plan === state.plan);
    if (!flavour || !plan || !modeNames[state.work_mode]) return;
    $('#chosenFlavour').textContent = $('#previewFlavour').textContent = state.flavour;
    $('#chosenPlan').textContent = $('#previewPlan').textContent = state.plan;
    $('#chosenMode').textContent = modeNames[state.work_mode];
    $('#flavourDescription').textContent = flavour.dataset.desc;
    for (const key of ['ram', 'cpu', 'gpu', 'storage']) {
      $('#' + key).textContent = $('#preview' + key[0].toUpperCase() + key.slice(1)).textContent = plan.dataset[key];
    }
    if (requestButton) {
      const unchanged = savedRequest && savedRequest.status === 'requested' && savedRequest.flavour === state.flavour && savedRequest.plan === state.plan && savedRequest.work_mode === state.work_mode;
      const locked = savedRequest && savedRequest.status !== 'requested';
      requestButton.disabled = Boolean(unchanged || locked);
      requestButton.innerHTML = locked ? 'Session request is locked <span>✓</span>'
        : unchanged ? 'Request saved <span>✓</span>'
          : savedRequest ? 'Update session request <span>→</span>' : 'Save session request <span>→</span>';
      $('#seatState').textContent = unchanged ? 'REQUEST SAVED' : savedRequest?.status === 'running' ? 'SESSION RUNNING' : 'READY TO REQUEST';
    }
    renderControls(savedRequest);
    remember();
  };

  const showRequest = (request) => {
    savedRequest = request;
    if (!request) {
      $('#accountStatus').textContent = 'No request yet';
      $('#accountDetails').textContent = 'Choose a BIT OS flavour and machine profile, then save your request.';
      $('#accountId').textContent = '—';
      $('#accountDate').textContent = '—';
      $('#previewState').textContent = 'NOT REQUESTED';
      render();
      return;
    }
    select(flavourButtons, 'flavour', request.flavour);
    select(planButtons, 'plan', request.plan);
    state.work_mode = modeNames[request.work_mode] ? request.work_mode : 'developer';
    select(modeButtons, 'mode', state.work_mode);
    $('#accountDetails').textContent = `${request.flavour} · ${request.plan} · ${modeNames[state.work_mode]}.`;
    $('#accountId').textContent = request.request_id;
    const date = new Date(request.requested_at.replace(' ', 'T') + 'Z');
    $('#accountDate').textContent = Number.isNaN(date.getTime()) ? request.requested_at : date.toLocaleString();
    $('#previewState').textContent = request.status === 'running' ? 'SESSION RUNNING' : request.status.toUpperCase();
    render();
  };

  async function postAction(action, extra = {}) {
    const response = await fetch('api/session.php', {
      method: 'POST', credentials: 'same-origin', cache: 'no-store',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ csrf, action, ...extra }),
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Could not update your VPS session.');
    if (data.request) showRequest(data.request);
    return data;
  }

  flavourButtons.forEach((button) => button.addEventListener('click', () => {
    select(flavourButtons, 'flavour', button.dataset.flavour);
    render();
  }));
  planButtons.forEach((button) => button.addEventListener('click', () => {
    select(planButtons, 'plan', button.dataset.plan);
    render();
  }));
  modeButtons.forEach((button) => button.addEventListener('click', () => {
    state.work_mode = button.dataset.mode;
    select(modeButtons, 'mode', state.work_mode);
    render();
  }));

  try {
    const previous = JSON.parse(localStorage.getItem('beyondWebsSeat') || 'null');
    if (previous && typeof previous === 'object') {
      select(flavourButtons, 'flavour', previous.flavour?.name || previous.flavour);
      select(planButtons, 'plan', previous.plan?.name || previous.plan);
      state.work_mode = modeNames[previous.work_mode] ? previous.work_mode : 'developer';
      select(modeButtons, 'mode', state.work_mode);
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
      await postAction('save', { flavour: state.flavour, plan: state.plan, work_mode: state.work_mode });
      $('#requestMessage').textContent = 'Saved to your Beyond ID. No machine or usage starts yet.';
    } catch (error) {
      $('#requestMessage').textContent = error.message;
      requestButton.disabled = false;
    }
  });

  const startSession = async (button) => {
    if (!savedRequest?.hourly_rate || !window.confirm(`Start this VPS session at ${savedRequest.hourly_rate}? Compute usage is metered by the hour. The SSD can continue to incur storage charges after the VM stops.`)) return;
    button.disabled = true;
    $('#sessionMessage').textContent = 'Starting your VPS session…';
    try { await postAction('start', { accept_rate: savedRequest.hourly_rate }); }
    catch (error) { $('#sessionMessage').textContent = error.message; }
    finally { button.disabled = false; }
  };
  $('#startVps')?.addEventListener('click', (event) => startSession(event.currentTarget));
  $('#resumeVps')?.addEventListener('click', (event) => startSession(event.currentTarget));
  $('#stopVps')?.addEventListener('click', async (event) => {
    if (!window.confirm('Stop your VPS session now? Its SSD data is retained and may continue to incur storage charges.')) return;
    event.currentTarget.disabled = true;
    $('#sessionMessage').textContent = 'Stopping your VPS session…';
    try { await postAction('stop'); }
    catch (error) { $('#sessionMessage').textContent = error.message; }
    finally { event.currentTarget.disabled = false; }
  });
  $('#openDesktop')?.addEventListener('click', async () => {
    const windowName = `BeyondWebs_${Date.now()}`;
    const desktopWindow = window.open('about:blank', windowName);
    if (!desktopWindow) { $('#sessionMessage').textContent = 'Allow pop-ups to open the protected desktop.'; return; }
    desktopWindow.document.title = 'Connecting to BIT OS';
    desktopWindow.document.body.textContent = 'Checking your Beyond ID session…';
    try {
      const response = await fetch('api/session.php?action=access-ticket', { credentials: 'same-origin', cache: 'no-store' });
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Could not authorize desktop access.');
      const form = document.createElement('form');
      form.method = 'post';
      form.action = `${data.gateway_origin}/auth/exchange`;
      form.target = windowName;
      const ticket = document.createElement('input');
      ticket.type = 'hidden'; ticket.name = 'ticket'; ticket.value = data.ticket;
      form.append(ticket); document.body.append(form); form.submit(); form.remove();
    } catch (error) {
      desktopWindow.document.body.textContent = error.message;
      $('#sessionMessage').textContent = error.message;
    }
  });
})();
