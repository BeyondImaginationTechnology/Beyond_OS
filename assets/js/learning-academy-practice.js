(() => {
  'use strict';

  const normalize = value => String(value || '').toLowerCase().replace(/\s+/g, ' ');

  document.querySelectorAll('[data-code-practice]').forEach(form => {
    const editor = form.querySelector('textarea[name="practice_response"]');
    const preview = form.querySelector('iframe');
    const runButton = form.querySelector('[data-run-code]');
    const completeButton = form.querySelector('[data-complete-practice]');
    const feedback = form.querySelector('.la-code-feedback');
    const wasComplete = form.classList.contains('complete');
    let checks = [];

    try {
      const parsed = JSON.parse(form.dataset.checks || '[]');
      if (Array.isArray(parsed)) checks = parsed.map(String).filter(Boolean);
    } catch (error) {
      checks = [];
    }

    if (!editor || !preview || !runButton || !completeButton || !feedback) return;

    const render = () => {
      preview.srcdoc = editor.value;
    };

    const checkCode = () => {
      render();
      const source = normalize(editor.value);
      const missing = checks.filter(requirement => !source.includes(normalize(requirement)));

      if (editor.value.trim().length < 12) {
        feedback.textContent = 'Add more code before checking this practice.';
        feedback.classList.remove('success');
        if (!wasComplete) completeButton.disabled = true;
        return;
      }

      if (missing.length) {
        feedback.textContent = `Almost there. Still needed: ${missing.join(', ')}`;
        feedback.classList.remove('success');
        if (!wasComplete) completeButton.disabled = true;
        return;
      }

      feedback.textContent = 'All requirements passed. Review the preview, then complete this practice.';
      feedback.classList.add('success');
      completeButton.disabled = false;
    };

    editor.addEventListener('input', () => {
      render();
      feedback.textContent = 'Code changed. Run the check again before completing this practice.';
      feedback.classList.remove('success');
      if (!wasComplete) completeButton.disabled = true;
    });
    runButton.addEventListener('click', checkCode);
    render();
  });
})();
