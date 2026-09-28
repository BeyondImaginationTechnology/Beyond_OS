(() => {
  const state = {
    flavour: { name: 'Gaming', desc: 'A performance focused desktop for your games and play.' },
    plan: { name: 'Build', ram: '16 GB', cpu: '8 cores', gpu: 'Performance GPU', storage: '256 GB SSD' }
  };
  const $ = (selector) => document.querySelector(selector);
  const render = () => {
    $('#chosenFlavour').textContent = state.flavour.name;
    $('#chosenPlan').textContent = state.plan.name;
    $('#flavourDescription').textContent = state.flavour.desc;
    $('#ram').textContent = state.plan.ram;
    $('#cpu').textContent = state.plan.cpu;
    $('#gpu').textContent = state.plan.gpu;
    $('#storage').textContent = state.plan.storage;
    localStorage.setItem('beyondWebsSeat', JSON.stringify(state));
  };
  document.querySelectorAll('.flavour').forEach((button) => button.addEventListener('click', () => {
    document.querySelector('.flavour.selected')?.classList.remove('selected');
    button.classList.add('selected');
    state.flavour = { name: button.dataset.flavour, desc: button.dataset.desc };
    render();
  }));
  document.querySelectorAll('.plan').forEach((button) => button.addEventListener('click', () => {
    document.querySelector('.plan.selected')?.classList.remove('selected');
    button.classList.add('selected');
    state.plan = { name: button.dataset.plan, ram: button.dataset.ram, cpu: button.dataset.cpu, gpu: button.dataset.gpu, storage: button.dataset.storage };
    render();
  }));
  $('#startSeat')?.addEventListener('click', () => {
    $('#seatState').textContent = 'SESSION REQUESTED';
    $('#startSeat').innerHTML = 'VPS session request saved <span>✓</span>';
    $('#startSeat').disabled = true;
  });
  $('#signInSeat')?.addEventListener('click', () => localStorage.setItem('beyondWebsSeat', JSON.stringify(state)));
  render();
})();
