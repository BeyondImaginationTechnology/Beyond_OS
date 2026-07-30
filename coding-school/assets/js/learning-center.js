(() => {
  'use strict';

  const narration = document.querySelector('[data-narrate-lesson]');
  if (narration && 'speechSynthesis' in window) {
    narration.addEventListener('click', () => {
      if (speechSynthesis.speaking) {
        speechSynthesis.cancel();
        narration.textContent = '🔊 Listen to this free lesson';
        return;
      }
      const copy = [...document.querySelectorAll('[data-narration-copy]')]
        .map(node => node.textContent)
        .join('. ');
      const voice = new SpeechSynthesisUtterance(copy);
      voice.rate = 0.94;
      voice.onend = () => {
        narration.textContent = '🔊 Listen to this free lesson';
      };
      speechSynthesis.speak(voice);
      narration.textContent = '■ Stop narration';
    });
  }

  const challenges = {
    'html-foundations': {
      question: 'Which element should contain the unique main content of a page?',
      choices: ['<div>', '<main>', '<span>'],
      answer: 1,
      feedback: 'Correct. <main> identifies the page’s unique primary content.'
    },
    'text-hierarchy': {
      question: 'What should follow an h1 for a major section?',
      choices: ['<h2>', '<h4>', '<small>'],
      answer: 0,
      feedback: 'Correct. An h2 creates the next clear level in the outline.'
    },
    'link-paths': {
      question: 'Which href connects to id="projects" on this page?',
      choices: ['projects', '#projects', '/#project'],
      answer: 1,
      feedback: 'Correct. The # fragment points to the matching id.'
    },
    'accessible-images': {
      question: 'Which attribute replaces an informative image when it cannot be seen?',
      choices: ['title', 'name', 'alt'],
      answer: 2,
      feedback: 'Correct. Useful alt text communicates the image’s purpose.'
    },
    'form-builder': {
      question: 'What connects a label to an input?',
      choices: ['Matching for and id', 'Matching class names', 'Their visual position'],
      answer: 0,
      feedback: 'Correct. The label’s for value must match the input id.'
    },
    'data-table': {
      question: 'Which element represents a table heading?',
      choices: ['<td>', '<th>', '<tr>'],
      answer: 1,
      feedback: 'Correct. <th> identifies a row or column heading.'
    },
    'semantic-landmarks': {
      question: 'Where does the page’s unique primary content belong?',
      choices: ['<aside>', '<footer>', '<main>'],
      answer: 2,
      feedback: 'Correct. A page should have one primary main landmark.'
    },
    'document-head': {
      question: 'Which content belongs in the document head?',
      choices: ['The page title metadata', 'The visible navigation', 'The article body'],
      answer: 0,
      feedback: 'Correct. Browser and discovery metadata belongs in head.'
    },
    'accessibility-audit': {
      question: 'What makes a skip link work?',
      choices: ['A matching href fragment and id', 'A bright background', 'Opening a new tab'],
      answer: 0,
      feedback: 'Correct. The skip link href must match the main content id.'
    },
    'portfolio-capstone': {
      question: 'What should still make sense before CSS loads?',
      choices: ['The HTML structure and links', 'Only the color palette', 'Only the animations'],
      answer: 0,
      feedback: 'Correct. Semantic structure is the foundation of the page.'
    }
  };

  document.querySelectorAll('.la-interactive').forEach(section => {
    const mount = section.querySelector('.math-activity');
    const challenge = challenges[section.dataset.mathActivity];
    if (!mount || !challenge) return;

    const panel = document.createElement('div');
    panel.className = 'coding-quick-check';
    panel.innerHTML = `<strong>Predict before you build</strong><p>${challenge.question}</p><div class="coding-quick-check__choices"></div><span aria-live="polite">Choose the best answer.</span>`;
    mount.replaceChildren(panel);

    const choices = panel.querySelector('.coding-quick-check__choices');
    const status = panel.querySelector('[aria-live]');
    challenge.choices.forEach((choice, index) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = choice;
      button.addEventListener('click', () => {
        choices.querySelectorAll('button').forEach(item => item.classList.remove('correct', 'incorrect'));
        const correct = index === challenge.answer;
        button.classList.add(correct ? 'correct' : 'incorrect');
        status.textContent = correct ? challenge.feedback : 'Not quite. Compare the meaning of each choice, then try again.';
      });
      choices.appendChild(button);
    });
  });
})();
