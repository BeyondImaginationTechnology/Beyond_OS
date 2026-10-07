(() => {
  const section = document.querySelector('.home-primetime');
  if (!section) return;
  const viewer = section.querySelector('.home-primetime__viewer');
  const iframe = viewer.querySelector('iframe');
  const title = viewer.querySelector('h3');
  const official = viewer.querySelector('[data-official-video]');
  const close = () => {
    iframe.removeAttribute('src');
    viewer.hidden = true;
    document.body.style.removeProperty('overflow');
  };
  section.querySelectorAll('[data-primetime-id]').forEach(button => button.addEventListener('click', () => {
    const id = button.dataset.primetimeId || '';
    if (!/^[A-Za-z0-9_-]{11}$/.test(id)) return;
    title.textContent = button.dataset.primetimeTitle || 'YouTube movie';
    official.href = `https://www.youtube.com/watch?v=${id}`;
    iframe.title = `${title.textContent} on YouTube`;
    iframe.src = `https://www.youtube.com/embed/${id}?autoplay=1&rel=0`;
    viewer.hidden = false;
    document.body.style.overflow = 'hidden';
    viewer.querySelector('.home-primetime__close').focus();
  }));
  viewer.querySelector('.home-primetime__close').addEventListener('click', close);
  viewer.addEventListener('click', event => { if (event.target === viewer) close(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && !viewer.hidden) close(); });
})();
