(() => {
  'use strict';

  const form = document.querySelector('[data-narration-form]');
  if (!form) return;

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const fields = {
    lesson: form.elements.lesson_id,
    provider: form.elements.provider,
    type: form.elements.narration_type,
    language: form.elements.language,
    voice: form.elements.voice,
    text: form.elements.text,
    instructions: form.elements.instructions,
    speed: form.elements.speed,
    format: form.elements.format,
  };
  const previewButton = form.querySelector('[data-preview-narration]');
  const submitButton = form.querySelector('button[type="submit"]');
  const status = document.querySelector('[data-narration-status]');
  const result = document.querySelector('[data-narration-result]');
  const resultTitle = document.querySelector('[data-result-title]');
  const resultMessage = document.querySelector('[data-result-message]');
  const audio = document.querySelector('[data-narration-audio]');
  const audioLink = document.querySelector('[data-audio-link]');
  const textCount = document.querySelector('[data-text-count]');
  const speedOutput = document.querySelector('[data-speed-output]');
  const workflow = [...document.querySelectorAll('.workflow-steps li')];
  let objectUrl = '';
  let lessons = [];

  try {
    lessons = JSON.parse(
      document.querySelector('#french-lessons-data')?.textContent || '[]',
    );
  } catch {
    lessons = [];
  }

  const setStatus = (message, tone = 'neutral') => {
    if (!status) return;
    status.textContent = message;
    status.dataset.tone = tone;
  };

  const setBusy = (busy) => {
    previewButton.disabled = busy;
    submitButton.disabled = busy;
    fields.provider.disabled = busy;
    fields.type.disabled = busy;
    fields.language.disabled = busy;
    fields.voice.disabled = busy;
    form.classList.toggle('is-busy', busy);
  };

  const setStep = (active) => {
    workflow.forEach((item, index) => {
      item.classList.toggle('active', index === active);
      item.classList.toggle('complete', index < active);
    });
  };

  const selectedLesson = () =>
    lessons.find((lesson) => Number(lesson.id) === Number(fields.lesson.value));

  const updateCount = () => {
    if (textCount) textCount.textContent = String(fields.text.value.length);
  };

  const applyLessonText = () => {
    const lesson = selectedLesson();
    if (!lesson) return;
    if (fields.type.value === 'video') {
      fields.language.value = 'en-US';
      fields.text.value = lesson.video_script || lesson.texts?.['en-US'] || '';
      fields.instructions.value =
        'Narrate a polished vertical App Store preview. Sound warm, energetic, and encouraging. Pause briefly before each language name, pronounce every phrase naturally, and finish with confident emphasis on “go beyond French.”';
    } else {
      fields.text.value =
        lesson.texts?.[fields.language.value] || lesson.text || '';
      fields.instructions.value =
        'Speak like a warm and encouraging language teacher. Use natural pacing and clear pronunciation.';
    }
    updateCount();
  };

  const requestPayload = () => ({
    lesson_id: Number(fields.lesson.value),
    provider: fields.provider.value,
    voice: fields.voice.value,
    language: fields.language.value,
    text: fields.text.value.trim(),
    instructions: fields.instructions.value.trim(),
    speed: Number(fields.speed.value),
    format: fields.format.value,
    save: true,
    csrf_token: csrf,
  });

  const errorMessage = async (response) => {
    try {
      return (await response.json()).message || 'Narration could not be completed.';
    } catch {
      return 'Narration could not be completed.';
    }
  };

  const showAudio = (src, title, message, link = '') => {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = src.startsWith('blob:') ? src : '';
    audio.src = src;
    result.hidden = false;
    resultTitle.textContent = title;
    resultMessage.textContent = message;
    audioLink.hidden = !link;
    if (link) audioLink.href = link;
    audio.load();
  };

  const loadVoices = async () => {
    setStep(0);
    fields.voice.innerHTML = '<option value="">Loading voices...</option>';
    fields.voice.disabled = true;
    setStatus('Loading the available voices...');
    try {
      const response = await fetch(form.dataset.voicesUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify({
          provider: fields.provider.value,
          language: fields.language.value,
          csrf_token: csrf,
        }),
      });
      if (!response.ok) throw new Error(await errorMessage(response));
      const payload = await response.json();
      fields.voice.innerHTML = '';
      for (const voice of payload.voices || []) {
        fields.voice.add(new Option(voice.label || voice.id, voice.id));
      }
      if (!fields.voice.options.length) {
        fields.voice.add(new Option('No configured voices', ''));
      }
      fields.voice.disabled = false;
      setStep(1);
      setStatus(
        payload.configured
          ? 'Choose a voice, then preview the narration.'
          : 'Voices loaded. Add this provider key in the private server configuration before generating.',
        payload.configured ? 'success' : 'warning',
      );
    } catch (error) {
      fields.voice.innerHTML = '<option value="">Voices unavailable</option>';
      fields.voice.disabled = false;
      setStatus(error.message || 'Voices are unavailable.', 'error');
    }
  };

  fields.lesson.addEventListener('change', applyLessonText);
  fields.type.addEventListener('change', () => {
    if (fields.type.value === 'phrase' && fields.language.value === 'en-US') {
      fields.language.value = 'fr-CA';
    }
    applyLessonText();
    loadVoices();
  });
  fields.language.addEventListener('change', () => {
    if (fields.type.value !== 'video') applyLessonText();
    loadVoices();
  });
  fields.provider.addEventListener('change', loadVoices);
  fields.voice.addEventListener('change', () => setStep(1));
  fields.text.addEventListener('input', updateCount);
  fields.speed.addEventListener('input', () => {
    speedOutput.textContent = `${Number(fields.speed.value).toFixed(2)}x`;
  });

  previewButton.addEventListener('click', async () => {
    if (!form.reportValidity()) return;
    setBusy(true);
    setStep(2);
    setStatus('Generating a private preview...');
    try {
      const payload = requestPayload();
      payload.save = false;
      const response = await fetch(form.dataset.previewUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(payload),
      });
      if (!response.ok) throw new Error(await errorMessage(response));
      const blob = await response.blob();
      const src = URL.createObjectURL(blob);
      showAudio(
        src,
        fields.type.value === 'video' ? 'Daily video preview' : 'Narration preview',
        'Listen before generating the final lesson MP3.',
      );
      setStatus('Preview ready. Generate the final MP3 when it sounds right.', 'success');
      audio.play().catch(() => {});
    } catch (error) {
      setStatus(error.message || 'Preview failed.', 'error');
    } finally {
      setBusy(false);
    }
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    setBusy(true);
    setStep(3);
    setStatus('Generating, validating, and attaching the final MP3...');
    try {
      const response = await fetch(form.dataset.generateUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrf},
        body: JSON.stringify(requestPayload()),
      });
      if (!response.ok) throw new Error(await errorMessage(response));
      const payload = await response.json();
      showAudio(
        payload.audio_url,
        fields.type.value === 'video'
          ? 'Published daily video voiceover'
          : 'Published lesson narration',
        payload.cached
          ? 'Loaded the matching cached MP3.'
          : 'The MP3 is attached and ready for the daily video renderer.',
        payload.audio_url,
      );
      setStep(4);
      workflow[4]?.classList.add('complete');
      setStatus(
        payload.cached
          ? 'Matching narration already existed and was reused.'
          : 'Narration generated, attached, and published.',
        'success',
      );
    } catch (error) {
      setStatus(error.message || 'Generation failed.', 'error');
    } finally {
      setBusy(false);
    }
  });

  applyLessonText();
  updateCount();
  loadVoices();
})();
