<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']),
        'samesite' => 'Lax',
    ]);
    session_start();
}

function academy_courses(): array
{
    return [
        'essential-math' => [
            'title' => 'Essential Math',
            'short' => 'Math',
            'icon' => '∑',
            'accent' => '#51db78',
            'description' => 'Build confidence with numbers, fractions, percentages, measurement, and everyday problem solving.',
            'skills' => ['Numeracy', 'Fractions and percentages', 'Measurement', 'Applied problem solving'],
            'lessons' => [
                ['Numbers in daily life', 'Read, compare, round, and estimate numbers used in prices, schedules, quantities, and measurements.'],
                ['Operations with confidence', 'Choose and apply addition, subtraction, multiplication, and division to solve practical problems.'],
                ['Fractions, decimals, and percentages', 'Convert between common forms and use them for discounts, portions, and rates.'],
                ['Measurement and geometry', 'Work with length, area, perimeter, volume, time, and unit conversions.'],
                ['Applied problem solving', 'Break multi-step situations into known facts, a strategy, a calculation, and a reasonableness check.'],
            ],
            'questions' => [
                ['q' => 'What is 25% of 80?', 'options' => ['10', '20', '25', '40'], 'answer' => '20'],
                ['q' => 'Which decimal equals 3/4?', 'options' => ['0.25', '0.5', '0.75', '1.25'], 'answer' => '0.75'],
                ['q' => 'A $60 item is discounted by 10%. What is the sale price?', 'options' => ['$50', '$54', '$56', '$59'], 'answer' => '$54'],
                ['q' => 'What is the perimeter of a rectangle measuring 5 by 3?', 'options' => ['8', '15', '16', '30'], 'answer' => '16'],
                ['q' => 'Which is the best first step in a word problem?', 'options' => ['Guess', 'Identify known facts and the question', 'Round every number', 'Multiply everything'], 'answer' => 'Identify known facts and the question'],
            ],
        ],
        'web-development-foundations' => [
            'title' => 'Web Development Foundations',
            'short' => 'Coding',
            'icon' => '</>',
            'accent' => '#448cff',
            'description' => 'Create accessible web pages with HTML, CSS, JavaScript, responsive layouts, and safe development habits.',
            'skills' => ['Semantic HTML', 'Responsive CSS', 'JavaScript fundamentals', 'Accessibility'],
            'lessons' => [
                [
                    'title' => 'How the web works',
                    'summary' => 'Follow a request from URL to server response and learn how HTML, CSS, and JavaScript work together.',
                    'duration' => '15 min',
                    'objectives' => ['Trace a browser request', 'Explain the three browser languages', 'Use developer tools safely'],
                    'sections' => [
                        ['title' => 'A request makes a round trip', 'body' => 'When you enter a URL, the browser finds the server, sends an HTTP request, and receives a response. The response includes a status code, headers, and usually HTML. A 200 means success, a 404 means the resource was not found, and a 500 means the server failed while processing the request.'],
                        ['title' => 'Structure, presentation, behavior', 'body' => 'HTML describes what the content means. CSS controls how it looks and adapts. JavaScript responds to events and changes the page. Keeping these roles clear makes a site easier to test, repair, and maintain.'],
                        ['title' => 'Inspect before guessing', 'body' => 'Browser developer tools show the document, applied styles, network requests, console messages, and accessibility information. Never paste unknown code into the console, and never place passwords or API secrets in public HTML or JavaScript.'],
                    ],
                    'example' => ['title' => 'Request walkthrough', 'steps' => ['You request /academy/.', 'The server runs the PHP route and returns HTML.', 'The browser requests the linked CSS and JavaScript.', 'It builds the page and runs safe client-side interactions.']],
                    'lab' => [
                        'type' => 'web-playground',
                        'title' => 'Build the three layers',
                        'prompt' => 'Change the heading in HTML, the accent color in CSS, and the button message in JavaScript. Run the preview after each change.',
                        'html' => '<main class="card"><h1>Hello, web!</h1><p>HTML gives this card meaning.</p><button id="hello">Try the interaction</button><p id="message" aria-live="polite"></p></main>',
                        'css' => 'body { font: 16px system-ui; padding: 2rem; background: #eef4ff; }\n.card { max-width: 28rem; padding: 2rem; border-radius: 1rem; background: white; box-shadow: 0 1rem 3rem #294a7a22; }\nbutton { padding: .75rem 1rem; border: 0; border-radius: .7rem; background: #2563eb; color: white; }',
                        'js' => "document.querySelector('#hello').addEventListener('click', () => {\n  document.querySelector('#message').textContent = 'JavaScript handled the click.';\n});",
                    ],
                    'check' => ['question' => 'A page loads its HTML but a stylesheet request returns 404. What is the most likely result?', 'options' => ['The content appears without the intended styles', 'The server password is revealed', 'JavaScript becomes HTML', 'The browser deletes the page'], 'answer' => 'The content appears without the intended styles', 'explanation' => 'HTML can still render, but the missing CSS resource cannot style it.'],
                ],
                [
                    'title' => 'Semantic HTML and accessible forms',
                    'summary' => 'Build a meaningful document outline with landmarks, headings, links, buttons, and properly labelled inputs.',
                    'duration' => '18 min',
                    'objectives' => ['Choose semantic elements', 'Build a logical heading structure', 'Label form controls'],
                    'sections' => [
                        ['title' => 'Meaning is part of the interface', 'body' => 'Elements such as header, nav, main, section, article, and footer describe the page structure to browsers and assistive technology. Use headings in a logical order so a learner can scan the document instead of hunting through styled text.'],
                        ['title' => 'Links go places; buttons do things', 'body' => 'Use an anchor for navigation to another URL. Use a button for an action on the current page, such as opening a menu or calculating a result. Native elements already include keyboard behavior that a clickable div does not.'],
                        ['title' => 'Every input needs a name', 'body' => 'Connect a label to each form control with matching for and id values. Provide useful instructions, group related choices with fieldset and legend, and announce validation errors in clear language. Placeholder text is not a replacement for a label.'],
                    ],
                    'example' => ['title' => 'From “div soup” to a document', 'steps' => ['Wrap primary navigation in nav.', 'Put unique page content inside main.', 'Use a real button for the menu action.', 'Connect the email label to its input.']],
                    'lab' => [
                        'type' => 'web-playground',
                        'title' => 'Repair an accessible signup card',
                        'prompt' => 'Add a main landmark, keep one h1, connect the label to the email input, and confirm the submit control is a button.',
                        'html' => '<main><section class="signup" aria-labelledby="signup-title"><h1 id="signup-title">Join the learning club</h1><p>Get one project prompt each week.</p><form><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required><button type="submit">Join</button></form></section></main>',
                        'css' => 'body { font: 16px/1.5 system-ui; padding: 1.5rem; background: #101827; color: white; }\n.signup { max-width: 30rem; margin: auto; padding: 2rem; border: 1px solid #405070; border-radius: 1rem; }\nform { display: grid; gap: .7rem; }\ninput, button { min-height: 44px; padding: .65rem; font: inherit; }\nbutton { background: #60a5fa; border: 0; border-radius: .5rem; color: #08101f; font-weight: 800; }',
                        'js' => "document.querySelector('form').addEventListener('submit', event => {\n  event.preventDefault();\n  alert('Form structure works.');\n});",
                    ],
                    'check' => ['question' => 'Which control is correct for showing and hiding a menu on the same page?', 'options' => ['A button', 'A link with no destination', 'A clickable div', 'An h2'], 'answer' => 'A button', 'explanation' => 'A button represents an action and includes expected keyboard behavior.'],
                ],
                [
                    'title' => 'CSS and responsive layout',
                    'summary' => 'Use the cascade, flexible units, Grid, Flexbox, and mobile-first rules to create layouts that adapt.',
                    'duration' => '20 min',
                    'objectives' => ['Explain the cascade', 'Create flexible layouts', 'Add a purposeful breakpoint'],
                    'sections' => [
                        ['title' => 'The cascade resolves conflicts', 'body' => 'CSS rules compete through origin, importance, specificity, and source order. Prefer small reusable classes over deeply nested selectors. In developer tools, inspect which rule won before adding more specificity.'],
                        ['title' => 'Flexible before fixed', 'body' => 'Use max-width, percentages, rem, minmax(), and gap so content can breathe. Flexbox is excellent for one-dimensional rows or columns. Grid is ideal when rows and columns work together. Images should usually have max-width: 100%.'],
                        ['title' => 'Mobile-first breakpoints', 'body' => 'Start with a simple narrow layout, then add a min-width media query when the content has room for more columns. Choose breakpoints based on where the design stops working—not on a specific phone model.'],
                    ],
                    'example' => ['title' => 'A responsive card grid', 'steps' => ['Start with one column.', 'Use repeat(auto-fit, minmax(12rem, 1fr)).', 'Add a consistent gap.', 'Limit the overall content width for readability.']],
                    'lab' => [
                        'type' => 'web-playground',
                        'title' => 'Make a responsive course grid',
                        'prompt' => 'Resize the preview. Then change the minimum card width and observe when the column count changes.',
                        'html' => '<main><h1>Learning paths</h1><section class="grid"><article><h2>HTML</h2><p>Meaningful structure.</p></article><article><h2>CSS</h2><p>Flexible presentation.</p></article><article><h2>JavaScript</h2><p>Useful behavior.</p></article></section></main>',
                        'css' => 'body { margin: 0; font: 16px/1.5 system-ui; background: #f5f7ff; color: #182033; }\nmain { width: min(68rem, calc(100% - 2rem)); margin: 2rem auto; }\n.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem; }\narticle { padding: 1.25rem; border-radius: 1rem; background: white; box-shadow: 0 .6rem 1.5rem #263b6b18; }\nh2 { color: #3159d8; }',
                        'js' => '',
                    ],
                    'check' => ['question' => 'Which Grid declaration creates as many flexible columns as will fit?', 'options' => ['repeat(auto-fit, minmax(12rem, 1fr))', 'width: 320px', 'position: absolute', 'white-space: nowrap'], 'answer' => 'repeat(auto-fit, minmax(12rem, 1fr))', 'explanation' => 'auto-fit and minmax allow the browser to choose a column count based on available space.'],
                ],
                [
                    'title' => 'JavaScript fundamentals',
                    'summary' => 'Use values, functions, conditions, events, and DOM updates to add predictable behavior.',
                    'duration' => '22 min',
                    'objectives' => ['Store and transform values', 'Handle user events', 'Update the DOM safely'],
                    'sections' => [
                        ['title' => 'Values and functions', 'body' => 'Variables give values useful names. const prevents reassignment and is a strong default; let is useful when a value must change. Functions group reusable behavior and can accept inputs and return results.'],
                        ['title' => 'Events connect users to code', 'body' => 'Use addEventListener to respond to clicks, input, submit, and keyboard events. Keep behavior in JavaScript instead of inline HTML attributes, and validate all values before using them.'],
                        ['title' => 'Make small, safe DOM updates', 'body' => 'Select the element you need, then update textContent, attributes, or classes. Prefer textContent for user-provided text because it does not interpret HTML. Show status changes through an aria-live region when appropriate.'],
                    ],
                    'example' => ['title' => 'A dependable counter', 'steps' => ['Store count as a number.', 'Create one render function.', 'Listen for button clicks.', 'Update textContent and the disabled state.']],
                    'lab' => [
                        'type' => 'web-playground',
                        'title' => 'Finish the focus counter',
                        'prompt' => 'Change the goal, add a Reset button, or prevent the counter from exceeding the goal.',
                        'html' => '<main class="counter"><h1>Focus sessions</h1><p id="status" aria-live="polite">0 of 4 complete</p><button id="add">Complete a session</button></main>',
                        'css' => 'body { display: grid; min-height: 100vh; margin: 0; place-items: center; font: 16px system-ui; background: #16142a; color: white; }\n.counter { padding: 2rem; border: 1px solid #4d4773; border-radius: 1rem; text-align: center; }\nbutton { min-height: 44px; padding: .7rem 1rem; border: 0; border-radius: .6rem; background: #a78bfa; font-weight: 800; }',
                        'js' => "const goal = 4;\nlet count = 0;\nconst status = document.querySelector('#status');\nconst add = document.querySelector('#add');\n\nfunction render() {\n  status.textContent = `${count} of ${goal} complete`;\n  add.disabled = count >= goal;\n}\n\nadd.addEventListener('click', () => {\n  count += 1;\n  render();\n});",
                    ],
                    'check' => ['question' => 'Which property safely inserts user-provided text without interpreting it as HTML?', 'options' => ['textContent', 'innerHTML', 'outerHTML', 'document.write'], 'answer' => 'textContent', 'explanation' => 'textContent treats the value as text, reducing the risk of injecting markup.'],
                ],
                [
                    'title' => 'Capstone: build and test a web page',
                    'summary' => 'Plan, build, test, and refine an accessible responsive landing page as evidence of your skills.',
                    'duration' => '30–45 min',
                    'objectives' => ['Combine HTML, CSS, and JavaScript', 'Test accessibility and responsiveness', 'Explain design decisions'],
                    'sections' => [
                        ['title' => 'Plan before coding', 'body' => 'Write one sentence naming the audience and the page’s primary task. Sketch the content order, then choose semantic elements. A clear plan prevents decorative work from hiding missing content or confusing navigation.'],
                        ['title' => 'Test like a real visitor', 'body' => 'Use only the keyboard. Zoom to 200%. Resize to a narrow screen. Confirm visible focus, readable contrast, useful alternative text, labelled controls, and understandable error messages. Check the console and network panel for failures.'],
                        ['title' => 'Ship a small complete project', 'body' => 'A focused page that works is stronger evidence than a large unfinished site. Review every requirement, remove unused code, and write a short explanation of what you built and what you would improve next.'],
                    ],
                    'example' => ['title' => 'Definition of done', 'steps' => ['One clear h1 and logical landmarks.', 'Responsive layout with no horizontal scrolling.', 'One useful JavaScript interaction.', 'Keyboard access, visible focus, and a completed test checklist.']],
                    'lab' => [
                        'type' => 'web-playground',
                        'title' => 'Build your capstone landing page',
                        'prompt' => 'Turn this starter into a landing page for a club, service, community event, or learning project. Make the call to action useful and accessible.',
                        'html' => '<header><a class="brand" href="#">Northstar Club</a><nav aria-label="Primary"><a href="#about">About</a><a href="#join">Join</a></nav></header><main><section class="hero" id="about"><p class="eyebrow">Learn together</p><h1>A welcoming club for curious builders.</h1><p>Meet weekly, practise a skill, and share what you create.</p><button id="details">Show next meetup</button><p id="meetup" aria-live="polite"></p></section></main>',
                        'css' => 'body { margin: 0; font: 16px/1.6 system-ui; background: #0c1530; color: #f7f8ff; }\nheader { display: flex; justify-content: space-between; gap: 1rem; padding: 1rem max(1rem, calc((100% - 64rem)/2)); }\na { color: #b8c9ff; }\nnav { display: flex; gap: 1rem; }\n.hero { width: min(44rem, calc(100% - 2rem)); margin: 8vh auto; }\nh1 { font-size: clamp(2.5rem, 8vw, 5.5rem); line-height: .95; }\n.eyebrow { color: #82e5c2; font-weight: 800; text-transform: uppercase; }\nbutton { min-height: 46px; padding: .75rem 1rem; border: 0; border-radius: .7rem; background: #82e5c2; color: #07151d; font-weight: 900; }\n:focus-visible { outline: 3px solid #ffd166; outline-offset: 4px; }',
                        'js' => "document.querySelector('#details').addEventListener('click', () => {\n  document.querySelector('#meetup').textContent = 'Next meetup: Saturday at 10:00 AM.';\n});",
                    ],
                    'check' => ['question' => 'Which test best reveals whether interactive controls work without a mouse?', 'options' => ['Keyboard-only navigation', 'Changing the logo', 'Refreshing quickly', 'Opening many tabs'], 'answer' => 'Keyboard-only navigation', 'explanation' => 'Keyboard testing exposes focus, ordering, and operability problems.'],
                ],
            ],
            'project' => [
                'title' => 'Accessible responsive landing page',
                'brief' => 'Create a one-page site for a real or imagined community, product, event, or learning project.',
                'deliverables' => ['Semantic page structure with one clear h1', 'Responsive layout that works from 320px upward', 'One useful JavaScript interaction', 'Keyboard, zoom, contrast, and console test notes'],
            ],
            'questions' => [
                ['q' => 'Which language gives a web page its semantic structure?', 'options' => ['HTML', 'CSS', 'SQL', 'PNG'], 'answer' => 'HTML'],
                ['q' => 'Which element should trigger an action on the current page?', 'options' => ['<div>', '<span>', '<button>', '<title>'], 'answer' => '<button>'],
                ['q' => 'What does a responsive layout do?', 'options' => ['Only works on phones', 'Adapts to available screen space', 'Removes all images', 'Requires an app store'], 'answer' => 'Adapts to available screen space'],
                ['q' => 'Where should sensitive secrets be stored?', 'options' => ['Public JavaScript', 'HTML comments', 'Server-side protected configuration', 'A CSS file'], 'answer' => 'Server-side protected configuration'],
                ['q' => 'What is an important accessibility test?', 'options' => ['Keyboard-only navigation', 'Maximum animation speed', 'Tiny text', 'Removing labels'], 'answer' => 'Keyboard-only navigation'],
                ['q' => 'What does an HTTP 404 status mean?', 'options' => ['The resource was not found', 'The request succeeded', 'The browser is offline forever', 'The CSS is valid'], 'answer' => 'The resource was not found'],
                ['q' => 'Which is the safest way to insert user-provided text into an element?', 'options' => ['textContent', 'innerHTML', 'document.write', 'eval'], 'answer' => 'textContent'],
                ['q' => 'What should every form input have?', 'options' => ['A connected label', 'An animation', 'A fixed width', 'A hidden value'], 'answer' => 'A connected label'],
                ['q' => 'Which layout tool is designed for rows and columns together?', 'options' => ['CSS Grid', 'A PNG', 'A database index', 'HTTP'], 'answer' => 'CSS Grid'],
                ['q' => 'When should a responsive breakpoint be added?', 'options' => ['When the content needs more room', 'For every phone brand', 'At random', 'Only after publishing'], 'answer' => 'When the content needs more room'],
            ],
        ],
        'personal-finance-foundations' => [
            'title' => 'Personal Finance Foundations',
            'short' => 'Finance',
            'icon' => '$',
            'accent' => '#ffbf32',
            'description' => 'Learn practical budgeting, saving, credit, interest, and fraud-awareness skills for everyday decisions.',
            'skills' => ['Budgeting', 'Saving', 'Credit fundamentals', 'Fraud awareness'],
            'lessons' => [
                [
                    'title' => 'Income, needs, wants, and goals',
                    'summary' => 'Map take-home income and sort spending into useful categories without judging every choice.',
                    'duration' => '15 min',
                    'objectives' => ['Use net income', 'Separate needs and wants', 'Turn priorities into goals'],
                    'sections' => [
                        ['title' => 'Plan with money you can actually use', 'body' => 'Gross income is pay before deductions. Net or take-home income is what reaches your account. Build an everyday plan from predictable net income, and treat irregular income carefully until it is received.'],
                        ['title' => 'Needs and wants depend on context', 'body' => 'Needs support safety, health, housing, work, and essential obligations. Wants improve comfort or enjoyment. The same item can change categories by situation, so ask what job the expense performs and whether a lower-cost option would still do it.'],
                        ['title' => 'Give goals a number and a date', 'body' => '“Save more” is difficult to act on. “Save $600 by December by moving $50 each payday” provides a target, deadline, and repeatable step. A goal is part of the plan, not merely what remains at month-end.'],
                    ],
                    'example' => ['title' => 'Sort a payday', 'steps' => ['Start with $1,900 take-home pay.', 'List required bills and essential variable costs.', 'Choose a realistic amount for wants.', 'Move the goal contribution before unplanned spending.']],
                    'lab' => [
                        'type' => 'money-sort',
                        'title' => 'Sort the spending decisions',
                        'prompt' => 'Classify each item for this scenario: a student commutes to work, rents a room, and is saving for a laptop.',
                        'items' => [
                            ['label' => 'Monthly room rent', 'answer' => 'Need'],
                            ['label' => 'Bus pass used for work', 'answer' => 'Need'],
                            ['label' => 'Laptop savings contribution', 'answer' => 'Goal'],
                            ['label' => 'Second streaming subscription', 'answer' => 'Want'],
                        ],
                    ],
                    'check' => ['question' => 'Which income figure is the safest starting point for a monthly spending plan?', 'options' => ['Predictable take-home income', 'Gross salary before deductions', 'A hoped-for bonus', 'Available credit'], 'answer' => 'Predictable take-home income', 'explanation' => 'A plan should start with income that is reliably available after deductions.'],
                ],
                [
                    'title' => 'Build a realistic budget',
                    'summary' => 'Create a monthly plan, prepare for irregular costs, and compare the plan with what actually happens.',
                    'duration' => '20 min',
                    'objectives' => ['Balance planned cash flow', 'Create sinking funds', 'Review plan versus actual'],
                    'sections' => [
                        ['title' => 'A budget is a decision map', 'body' => 'List monthly net income, required bills, flexible essentials, goals, and wants. Income minus all planned uses should be zero or positive. If it is negative, adjust before the month begins instead of relying on credit to hide the gap.'],
                        ['title' => 'Irregular does not mean unexpected', 'body' => 'Annual fees, gifts, school supplies, repairs, and seasonal costs may not occur monthly, but they can be estimated. Divide an annual amount by twelve and set that monthly share aside in a sinking fund.'],
                        ['title' => 'Review without shame', 'body' => 'A useful budget changes when real numbers arrive. Compare planned and actual spending weekly, identify the reason for a difference, and move money deliberately. The goal is better decisions, not a perfect forecast.'],
                    ],
                    'example' => ['title' => 'Make room in a tight plan', 'steps' => ['Calculate the gap.', 'Protect housing, food, transport, minimum obligations, and a small buffer.', 'Reduce or delay flexible wants.', 'Find a structural change if the gap repeats.']],
                    'lab' => ['type' => 'budget', 'title' => 'Monthly budget builder', 'prompt' => 'Enter a sample or real monthly plan. The calculator keeps the information only in this page and does not save it.'],
                    'check' => ['question' => 'A $360 annual insurance bill should contribute how much to a monthly sinking fund?', 'options' => ['$30', '$36', '$60', '$360'], 'answer' => '$30', 'explanation' => '$360 divided across 12 months is $30 per month.'],
                ],
                [
                    'title' => 'Saving and emergency funds',
                    'summary' => 'Set specific savings goals, automate contributions, and build a buffer for essential surprises.',
                    'duration' => '18 min',
                    'objectives' => ['Build an emergency buffer', 'Calculate a goal timeline', 'Automate a savings habit'],
                    'sections' => [
                        ['title' => 'Start with the next likely surprise', 'body' => 'An emergency fund covers urgent, necessary, unplanned costs such as a critical repair or income interruption. A small starter buffer is valuable even before reaching a larger target such as several months of essential expenses.'],
                        ['title' => 'Separate goals by purpose', 'body' => 'Keep emergency savings distinct from planned purchases. Give each goal a target and deadline. Money needed soon should generally remain accessible and stable rather than exposed to large market swings.'],
                        ['title' => 'Automate, then adjust', 'body' => 'Schedule a transfer shortly after payday so saving becomes part of the routine. Start with an amount the budget can sustain. Increase it after debt falls, income rises, or a recurring expense ends.'],
                    ],
                    'example' => ['title' => 'Build a starter buffer', 'steps' => ['Choose a first target of $500.', 'Save $25 each payday.', 'Keep it in a separate accessible account.', 'Refill it after using it for a true emergency.']],
                    'lab' => ['type' => 'savings', 'title' => 'Savings timeline calculator', 'prompt' => 'Choose a target, current amount, and monthly contribution to estimate the time required.'],
                    'check' => ['question' => 'Which expense is the best example of an emergency-fund use?', 'options' => ['An urgent furnace repair', 'A planned vacation', 'A routine subscription', 'A sale on new shoes'], 'answer' => 'An urgent furnace repair', 'explanation' => 'It is necessary, urgent, and not part of ordinary planned spending.'],
                ],
                [
                    'title' => 'Credit, interest, and borrowing',
                    'summary' => 'Understand APR, minimum payments, credit reports, and the full cost of carrying a balance.',
                    'duration' => '22 min',
                    'objectives' => ['Estimate interest cost', 'Compare payment choices', 'Read credit information critically'],
                    'sections' => [
                        ['title' => 'Credit is borrowed money', 'body' => 'A credit limit is not income. When a balance is carried, interest is generally charged using an annual percentage rate (APR). Fees, promotional periods, and compounding rules affect the real cost, so read the agreement rather than relying only on the monthly payment.'],
                        ['title' => 'Minimum is not the target', 'body' => 'A minimum payment may keep an account current but can stretch repayment over years and increase total interest. Paying more than the minimum and stopping new charges usually reduces both time and cost.'],
                        ['title' => 'Reports and scores are different', 'body' => 'A credit report records accounts and payment history; a score summarizes risk using a model. Review reports for errors, protect personal information, pay obligations on time, and avoid applying for credit you do not need.'],
                    ],
                    'example' => ['title' => 'Compare two payments', 'steps' => ['Record the balance and APR.', 'Estimate the monthly interest.', 'Compare how much of each payment reduces principal.', 'Choose a sustainable higher payment when possible.']],
                    'lab' => ['type' => 'credit', 'title' => 'Credit payoff simulator', 'prompt' => 'Compare repayment time and estimated interest. This simplified educational estimate assumes a fixed APR, fixed payment, and no new charges or fees.'],
                    'check' => ['question' => 'If a card payment is only slightly above the monthly interest, what happens?', 'options' => ['The balance falls very slowly', 'The balance disappears immediately', 'APR becomes zero', 'The credit limit becomes income'], 'answer' => 'The balance falls very slowly', 'explanation' => 'Only the amount above interest reduces the principal balance.'],
                ],
                [
                    'title' => 'Capstone: protect your money',
                    'summary' => 'Recognize fraud pressure tactics, verify requests independently, and create a personal money safety plan.',
                    'duration' => '25–35 min',
                    'objectives' => ['Spot common scam signals', 'Verify through official channels', 'Respond quickly after suspected fraud'],
                    'sections' => [
                        ['title' => 'Pressure is a warning signal', 'body' => 'Scammers create urgency, secrecy, fear, or an unusually attractive reward. Requests for passwords, one-time codes, remote computer access, cryptocurrency, gift cards, or immediate transfers deserve a pause.'],
                        ['title' => 'Verify independently', 'body' => 'Do not use the contact information in a suspicious message. Open the official app, type the organization’s known website, call the number on the back of the card, or speak with a trusted person. Legitimate organizations allow time to verify.'],
                        ['title' => 'Act after a mistake', 'body' => 'Contact the financial institution immediately through an official channel, change affected passwords, preserve evidence, review transactions, and follow local identity-theft and fraud-reporting guidance. Fast action may limit damage.'],
                    ],
                    'example' => ['title' => 'The pause–verify–protect routine', 'steps' => ['Pause and do not click or reply.', 'Verify using a separate official channel.', 'Protect accounts and report suspicious activity.', 'Document what happened and monitor statements.']],
                    'lab' => [
                        'type' => 'fraud-spotter',
                        'title' => 'Fraud signal challenge',
                        'prompt' => 'Decide whether each message is safer to continue or should be stopped and independently verified.',
                        'items' => [
                            ['label' => '“Your bank needs the six-digit code we just texted you. Reply in five minutes.”', 'answer' => 'Stop and verify'],
                            ['label' => 'You opened your bank’s official app yourself and reviewed a transaction alert there.', 'answer' => 'Continue carefully'],
                            ['label' => '“Pay an overdue tax balance today using gift cards to avoid arrest.”', 'answer' => 'Stop and verify'],
                            ['label' => 'A seller moves the conversation off-platform and demands cryptocurrency before inspection.', 'answer' => 'Stop and verify'],
                        ],
                    ],
                    'check' => ['question' => 'A caller asks for a one-time verification code and creates urgency. What should you do?', 'options' => ['End the call and contact the organization through an official channel', 'Read the code immediately', 'Install their remote-access app', 'Send a gift card instead'], 'answer' => 'End the call and contact the organization through an official channel', 'explanation' => 'One-time codes are account protections. Verify independently and never share them with an unsolicited caller.'],
                ],
            ],
            'project' => [
                'title' => 'One-month money plan and safety checklist',
                'brief' => 'Create a realistic plan using sample numbers or your own private figures. Do not submit account numbers, passwords, or other sensitive information.',
                'deliverables' => ['Monthly income and spending plan', 'One irregular-expense sinking fund', 'Emergency savings target and contribution', 'Credit payoff comparison or debt-free statement', 'Pause–verify–protect fraud checklist'],
            ],
            'questions' => [
                ['q' => 'What should a useful budget compare?', 'options' => ['Income and planned expenses', 'Likes and followers', 'Passwords and PINs', 'Only cash purchases'], 'answer' => 'Income and planned expenses'],
                ['q' => 'What is the purpose of an emergency fund?', 'options' => ['Cover unexpected essential costs', 'Increase impulse spending', 'Replace all insurance', 'Avoid reviewing bills'], 'answer' => 'Cover unexpected essential costs'],
                ['q' => 'Paying only a credit-card minimum generally does what?', 'options' => ['Eliminates interest', 'Can extend repayment and increase interest', 'Cancels the balance', 'Improves every credit score immediately'], 'answer' => 'Can extend repayment and increase interest'],
                ['q' => 'A message pressures you to share a verification code. What should you do?', 'options' => ['Share it quickly', 'Verify through the organization’s official channel', 'Post it publicly', 'Forward it to friends'], 'answer' => 'Verify through the organization’s official channel'],
                ['q' => 'Which savings goal is most actionable?', 'options' => ['Save more someday', 'Save $25 each payday for six months', 'Never spend money', 'Copy someone else’s budget'], 'answer' => 'Save $25 each payday for six months'],
                ['q' => 'Which figure is best for planning spendable monthly income?', 'options' => ['Predictable take-home income', 'Gross pay before deductions', 'Total credit limits', 'An unconfirmed bonus'], 'answer' => 'Predictable take-home income'],
                ['q' => 'What is a sinking fund?', 'options' => ['Money set aside gradually for a known future cost', 'A fee for using cash', 'A type of fraud', 'A credit score penalty'], 'answer' => 'Money set aside gradually for a known future cost'],
                ['q' => 'What is APR used to describe?', 'options' => ['The annualized cost of borrowing', 'Monthly take-home income', 'The size of an emergency fund', 'A bank password'], 'answer' => 'The annualized cost of borrowing'],
                ['q' => 'What should you do with contact information inside a suspicious message?', 'options' => ['Avoid it and use a known official channel', 'Trust it because it is convenient', 'Send a password to confirm', 'Post it publicly'], 'answer' => 'Avoid it and use a known official channel'],
                ['q' => 'A budget is negative every month. What is the most useful response?', 'options' => ['Change income or spending structurally', 'Ignore it and rely on credit', 'Stop reviewing statements', 'Treat credit as income'], 'answer' => 'Change income or spending structurally'],
            ],
        ],
    ];
}

function academy_course(string $slug): ?array
{
    $courses = academy_courses();
    return $courses[$slug] ?? null;
}

function academy_lesson_title(array $lesson): string
{
    return (string)($lesson['title'] ?? $lesson[0] ?? 'Lesson');
}

function academy_lesson_summary(array $lesson): string
{
    return (string)($lesson['summary'] ?? $lesson[1] ?? '');
}

function academy_user_id(): int
{
    return max(0, (int)($_SESSION['user_id'] ?? 0));
}

function academy_require_user(): int
{
    $userId = academy_user_id();
    if ($userId < 1) {
        $_SESSION['beyond_return_to'] = $_SERVER['REQUEST_URI'] ?? '/academy/dashboard.php';
        header('Location: /beyond-id/auth/login.php?required=1');
        exit;
    }
    return $userId;
}

function academy_csrf(): string
{
    if (empty($_SESSION['academy_certificate_csrf'])) {
        $_SESSION['academy_certificate_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['academy_certificate_csrf'];
}

function academy_verify_csrf(?string $token): bool
{
    return is_string($token) && hash_equals(academy_csrf(), $token);
}

function academy_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $root = beyond_private_root();
    if (!is_dir($root) && !mkdir($root, 0770, true) && !is_dir($root)) {
        throw new RuntimeException('Academy storage is unavailable.');
    }
    $pdo = new PDO('sqlite:' . $root . '/learning-academy.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout=5000; PRAGMA journal_mode=WAL;');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_course_progress (
        user_id INTEGER NOT NULL, course_slug TEXT NOT NULL, lesson_number INTEGER NOT NULL,
        completed_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(user_id, course_slug, lesson_number)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_assessment_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, course_slug TEXT NOT NULL,
        score INTEGER NOT NULL, question_count INTEGER NOT NULL, passed INTEGER NOT NULL DEFAULT 0,
        attempted_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_academy_attempt_user ON academy_assessment_attempts(user_id, course_slug, passed)');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_credentials (
        id INTEGER PRIMARY KEY AUTOINCREMENT, credential_id TEXT NOT NULL UNIQUE, user_id INTEGER NOT NULL,
        course_slug TEXT NOT NULL, learner_name TEXT NOT NULL, score INTEGER NOT NULL,
        issued_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, revoked_at TEXT NULL,
        UNIQUE(user_id, course_slug)
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS academy_badges (
        id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, badge_slug TEXT NOT NULL,
        title TEXT NOT NULL, credential_id TEXT NULL, awarded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(user_id, badge_slug)
    )');
    return $pdo;
}

function academy_completed_lessons(int $userId, string $courseSlug): array
{
    $statement = academy_db()->prepare('SELECT lesson_number FROM academy_course_progress WHERE user_id=? AND course_slug=? ORDER BY lesson_number');
    $statement->execute([$userId, $courseSlug]);
    return array_map('intval', array_column($statement->fetchAll(), 'lesson_number'));
}

function academy_progress(int $userId, string $courseSlug): array
{
    $course = academy_course($courseSlug);
    $total = count($course['lessons'] ?? []);
    $completed = academy_completed_lessons($userId, $courseSlug);
    $attempt = academy_db()->prepare('SELECT MAX(score) FROM academy_assessment_attempts WHERE user_id=? AND course_slug=?');
    $attempt->execute([$userId, $courseSlug]);
    $credential = academy_db()->prepare('SELECT * FROM academy_credentials WHERE user_id=? AND course_slug=? AND revoked_at IS NULL LIMIT 1');
    $credential->execute([$userId, $courseSlug]);
    return [
        'completed' => count($completed),
        'completed_lessons' => $completed,
        'total' => $total,
        'percent' => $total > 0 ? (int)round(count($completed) / $total * 100) : 0,
        'best_score' => (int)($attempt->fetchColumn() ?: 0),
        'credential' => $credential->fetch() ?: null,
    ];
}

function academy_complete_lesson(int $userId, string $courseSlug, int $lesson): void
{
    $course = academy_course($courseSlug);
    if (!$course || $lesson < 1 || $lesson > count($course['lessons'])) {
        throw new InvalidArgumentException('Unknown lesson.');
    }
    $statement = academy_db()->prepare('INSERT OR IGNORE INTO academy_course_progress(user_id,course_slug,lesson_number) VALUES(?,?,?)');
    $statement->execute([$userId, $courseSlug, $lesson]);
}

function academy_lesson_unlocked(int $userId, string $courseSlug, int $lesson): bool
{
    if ($lesson <= 1) {
        return true;
    }
    return in_array($lesson - 1, academy_completed_lessons($userId, $courseSlug), true);
}

function academy_score(array $questions, array $answers): int
{
    $score = 0;
    foreach ($questions as $index => $question) {
        if (hash_equals((string)$question['answer'], (string)($answers[$index] ?? ''))) {
            $score++;
        }
    }
    return $score;
}

function academy_learner_name(): string
{
    $name = trim((string)($_SESSION['name'] ?? ''));
    if ($name !== '') {
        return function_exists('mb_substr') ? mb_substr($name, 0, 120) : substr($name, 0, 120);
    }
    $email = (string)($_SESSION['email'] ?? 'Beyond learner');
    $label = strstr($email, '@', true) ?: $email;
    return function_exists('mb_substr') ? mb_substr($label, 0, 120) : substr($label, 0, 120);
}

function academy_issue_credential(int $userId, string $courseSlug, int $score): array
{
    $course = academy_course($courseSlug);
    if (!$course) {
        throw new InvalidArgumentException('Unknown pathway.');
    }
    $existing = academy_db()->prepare('SELECT * FROM academy_credentials WHERE user_id=? AND course_slug=? LIMIT 1');
    $existing->execute([$userId, $courseSlug]);
    $credential = $existing->fetch();
    if ($credential) {
        return $credential;
    }
    $credentialId = 'BVC-' . strtoupper(implode('-', str_split(bin2hex(random_bytes(12)), 6)));
    $pdo = academy_db();
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare('INSERT INTO academy_credentials(credential_id,user_id,course_slug,learner_name,score) VALUES(?,?,?,?,?)');
        $statement->execute([$credentialId, $userId, $courseSlug, academy_learner_name(), $score]);
        $badge = $pdo->prepare('INSERT OR IGNORE INTO academy_badges(user_id,badge_slug,title,credential_id) VALUES(?,?,?,?)');
        $badge->execute([$userId, $courseSlug, $course['title'] . ' Badge', $credentialId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
    $existing->execute([$userId, $courseSlug]);
    return $existing->fetch();
}

function academy_record_assessment(int $userId, string $courseSlug, array $answers): array
{
    $course = academy_course($courseSlug);
    if (!$course) {
        throw new InvalidArgumentException('Unknown pathway.');
    }
    $progress = academy_progress($userId, $courseSlug);
    if ($progress['completed'] !== $progress['total']) {
        throw new RuntimeException('Complete every lesson before taking the assessment.');
    }
    $questions = $course['questions'];
    $score = academy_score($questions, $answers);
    $passed = $score >= (int)ceil(count($questions) * 0.8);
    $review = [];
    foreach ($questions as $index => $question) {
        $given = (string)($answers[$index] ?? '');
        $review[] = [
            'question' => (string)$question['q'],
            'given' => $given,
            'answer' => (string)$question['answer'],
            'correct' => hash_equals((string)$question['answer'], $given),
        ];
    }
    $statement = academy_db()->prepare('INSERT INTO academy_assessment_attempts(user_id,course_slug,score,question_count,passed) VALUES(?,?,?,?,?)');
    $statement->execute([$userId, $courseSlug, $score, count($questions), $passed ? 1 : 0]);
    $credential = $passed ? academy_issue_credential($userId, $courseSlug, $score) : null;
    return ['score' => $score, 'total' => count($questions), 'passed' => $passed, 'credential' => $credential, 'review' => $review];
}

function academy_credential(string $credentialId): ?array
{
    if (!preg_match('/^BVC-(?:[A-F0-9]{6}-){3}[A-F0-9]{6}$/', strtoupper($credentialId))) {
        return null;
    }
    $statement = academy_db()->prepare('SELECT * FROM academy_credentials WHERE credential_id=? LIMIT 1');
    $statement->execute([strtoupper($credentialId)]);
    return $statement->fetch() ?: null;
}

function academy_badges(int $userId): array
{
    $statement = academy_db()->prepare('SELECT * FROM academy_badges WHERE user_id=? ORDER BY awarded_at DESC');
    $statement->execute([$userId]);
    return $statement->fetchAll();
}
