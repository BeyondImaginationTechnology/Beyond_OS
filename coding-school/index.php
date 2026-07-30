<?php
declare(strict_types=1);
$academyConfig=[
 'slug'=>'coding-school','title'=>'Beyond Coding School','icon'=>'💻','tagline'=>'Builder campus','accent'=>'#6d4aff','base'=>'/coding-school/','css'=>'/coding-school/academy.css?v=20260730-3','headline'=>'Choose a pathway. Build real skills.','description'=>'Six career pathways with 5 modules, 10 guided lessons per module, and three required practice labs before every lesson test. Module 1 is free in every pathway.','default_path'=>'web-designer','group_label'=>'career pathways',
 'paths'=>[
  'web-designer'=>['title'=>'Web Designer','ages'=>'HTML · CSS · UI/UX','icon'=>'🎨','guide'=>'Design responsive, accessible websites and publish a polished portfolio.'],
  'ios-developer'=>['title'=>'iOS Developer','ages'=>'Swift · SwiftUI','icon'=>'🍎','guide'=>'Build native iPhone and iPad apps with Swift, SwiftUI, data, and testing.'],
  'android-developer'=>['title'=>'Android Developer','ages'=>'Kotlin · Compose','icon'=>'🤖','guide'=>'Create modern Android apps with Kotlin, Jetpack Compose, APIs, and storage.'],
  'graphic-design-svg'=>['title'=>'Graphic Design & SVG','ages'=>'Vector · Brand · Motion','icon'=>'✒️','guide'=>'Create scalable graphics, icons, brand systems, and interactive SVG artwork.'],
  'game-development'=>['title'=>'Game Development','ages'=>'Design · Code · Publish','icon'=>'🎮','guide'=>'Build playable 2D games with controls, physics, audio, interface, and polish.'],
  'full-stack-developer'=>['title'=>'Full-Stack Developer','ages'=>'Frontend · API · Database','icon'=>'🧱','guide'=>'Develop complete web applications from interface to database and cloud deployment.']
 ],
 'tracks'=>[
  'web-designer'=>['HTML & Page Structure','CSS & Responsive Layouts','UI/UX & Accessibility','JavaScript Interactions','Portfolio & Deployment'],
  'ios-developer'=>['Swift Foundations','SwiftUI Interfaces','App State & Navigation','Data, Networking & Persistence','Testing & App Store Launch'],
  'android-developer'=>['Kotlin Foundations','Jetpack Compose UI','App Architecture & Navigation','Data, APIs & Storage','Testing & Play Store Launch'],
  'graphic-design-svg'=>['Design Foundations','Vector Shapes & Paths','Typography & Color','SVG Animation & Interaction','Brand System & Portfolio'],
  'game-development'=>['Game Design Fundamentals','2D Worlds & Physics','Player Controls & Systems','Audio, UI & Game Polish','Publishing & Portfolio'],
  'full-stack-developer'=>['Frontend Foundations','JavaScript & TypeScript','Backend APIs & Authentication','Databases & Cloud Deployment','Capstone SaaS Application']
 ]
];
$page=static fn(string $body,string $head=''): string=>"<!doctype html>\n<html lang=\"en\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>Beyond Builder</title>\n  {$head}\n</head>\n<body>\n{$body}\n</body>\n</html>";
$lab=static fn(string $title,string $instruction,string $starter,array $checks,string $hint): array=>compact('title','instruction','starter','checks','hint');
$academyConfig['rich_lessons']['web-designer']['html-page-structure']=[
 [
  'title'=>'Build Your First Semantic Web Page','focus'=>'HTML structure and semantic elements',
  'mission'=>'Build a real profile page in the browser, one working section at a time.',
  'objectives'=>['Create a clear heading hierarchy','Use semantic main and section landmarks','Connect navigation to a section on the same page'],
  'teaching'=>'HTML gives content meaning. You will write code in an editor, run it, inspect the live preview, and revise it instead of only reading about tags.',
  'concept'=>'Choose tags for what the content is: a heading is an h1, primary content belongs in main, and a related content group belongs in section.',
  'example'=>'A useful page starts with one h1, explains its purpose in a paragraph, and groups each major topic under an h2.',
  'practice'=>'Predict which tags a profile page needs before you start coding.',
  'activity'=>'html-foundations',
  'practices'=>[
   $lab('Make content appear','Replace the comments with one main heading and a short introduction paragraph. Run the check and inspect the preview.',$page("  <!-- Add your h1 and paragraph here -->"),['<h1','</h1>','<p','</p>'],'Use one <h1> for the page topic and one <p> for the introduction.'),
   $lab('Create a semantic section','Put the profile content inside main. Add a section with an h2 titled About me and a paragraph.',$page("  <main>\n    <!-- Add an About me section here -->\n  </main>"),['<main','<section','<h2','about me','<p'],'Your section should sit inside <main> and contain an <h2> plus supporting text.'),
   $lab('Connect page navigation','Add a nav link that points to #about, then give the About section id="about".',$page("  <nav>\n    <!-- Add the About link -->\n  </nav>\n  <main>\n    <section>\n      <h2>About me</h2>\n      <p>I am learning to build for the web.</p>\n    </section>\n  </main>"),['<nav','<a','href="#about"','id="about"'],'The href and id must match exactly: href="#about" connects to id="about".')
  ]
 ],
 [
  'title'=>'Shape Clear Text Hierarchy','focus'=>'Headings, paragraphs, emphasis, and lists',
  'mission'=>'Turn an unstructured block of words into content people can scan.',
  'objectives'=>['Order headings without skipping levels','Use emphasis for meaning','Build a real list'],
  'teaching'=>'Readable pages use headings to reveal structure and lists to group related items. Visual size alone does not create meaning.',
  'concept'=>'Use one h1 for the page, h2 elements for major sections, and paragraph or list elements for the content inside them.',
  'example'=>'A recipe page can use an h1 for the recipe name, an h2 for Ingredients, and a ul containing each ingredient.',
  'practice'=>'Outline a page with a title, two sections, and one list.',
  'activity'=>'text-hierarchy',
  'practices'=>[
   $lab('Build the heading outline','Add one h1, two h2 headings, and a paragraph under each section.',$page("  <!-- Build a page titled My Creative Toolkit -->"),['<h1','<h2','<p','my creative toolkit'],'Keep one page-level h1, then use h2 for sibling sections.'),
   $lab('Add meaningful emphasis','Use strong for an important warning and em for a phrase that should be stressed.',$page("  <main>\n    <h1>Project notes</h1>\n    <p><!-- Add an important deadline and an emphasized phrase --></p>\n  </main>"),['<strong','</strong>','<em','</em>'],'Use <strong> for importance and <em> for stress, not just appearance.'),
   $lab('Create a useful list','Build an unordered list with at least three list items under an h2.',$page("  <main>\n    <h1>Launch checklist</h1>\n    <!-- Add a heading and checklist -->\n  </main>"),['<h2','<ul','<li','</ul>'],'Place each checklist item inside its own <li>.')
  ]
 ],
 [
  'title'=>'Create Links That Go Somewhere','focus'=>'URLs, page anchors, and link purpose',
  'mission'=>'Build a navigation group with working internal and external links.',
  'objectives'=>['Write descriptive link text','Connect a page anchor','Open external destinations safely'],
  'teaching'=>'A link combines a destination in href with words that tell people where it leads. Avoid vague text such as click here.',
  'concept'=>'Relative URLs stay within a site, absolute URLs include the full destination, and fragment links connect to an id on the page.',
  'example'=>'An anchor with href="#projects" jumps to an element whose id is projects.',
  'practice'=>'Decide whether three example destinations need a relative, absolute, or fragment URL.',
  'activity'=>'link-paths',
  'practices'=>[
   $lab('Link to a section','Create a Projects link that jumps to a section with id="projects".',$page("  <nav><!-- Add the Projects link --></nav>\n  <main>\n    <section><!-- Add the matching id --><h2>Projects</h2></section>\n  </main>"),['href="#projects"','id="projects"','>projects<'],'The link fragment and section id need the same word.'),
   $lab('Add a site link','Create a link to /academy/ with the text Explore the Academy.',$page("  <main>\n    <h1>Keep learning</h1>\n    <!-- Add the Academy link -->\n  </main>"),['href="/academy/"','explore the academy'],'Use a root-relative path beginning with /.'),
   $lab('Make an external link safe','Link to https://developer.mozilla.org/ in a new tab and add rel="noopener".',$page("  <footer>\n    <!-- Add a descriptive MDN reference link -->\n  </footer>"),['https://developer.mozilla.org/','target="_blank"','rel="noopener"'],'A new-tab link should include both target="_blank" and rel="noopener".')
  ]
 ],
 [
  'title'=>'Publish Accessible Images','focus'=>'Images, alternative text, and captions',
  'mission'=>'Create an image story that still makes sense when the image cannot be seen.',
  'objectives'=>['Write useful alt text','Group media with figure','Attach a visible caption'],
  'teaching'=>'Images need a source and an alternative. Alt text communicates the image purpose; a caption adds visible context for everyone.',
  'concept'=>'Use empty alt text only for decoration. Informative images need concise text that replaces the relevant meaning.',
  'example'=>'A figure can contain an img followed by figcaption so the media and its caption remain connected.',
  'practice'=>'Write alt text that describes purpose rather than every visual detail.',
  'activity'=>'accessible-images',
  'practices'=>[
   $lab('Add an informative image','Add an img using https://placehold.co/480x240 and meaningful alt text.',$page("  <main>\n    <h1>Studio journal</h1>\n    <!-- Add the image -->\n  </main>"),['<img','src="https://placehold.co/480x240"','alt="'],'Describe why the image matters in the alt attribute.'),
   $lab('Connect a caption','Wrap the image in figure and add a figcaption.',$page("  <main>\n    <!-- Build a complete figure here -->\n  </main>"),['<figure','<img','alt="','<figcaption'],'The figure should contain both img and figcaption.'),
   $lab('Mark decoration correctly','Add a decorative divider image with alt="" and a width attribute.',$page("  <section>\n    <h2>Next chapter</h2>\n    <!-- Add a decorative image -->\n  </section>"),['<img','alt=""','width="'],'Decorative images use an empty alt attribute so assistive technology can skip them.')
  ]
 ],
 [
  'title'=>'Build a Form People Can Finish','focus'=>'Labels, input types, and validation',
  'mission'=>'Create a compact contact form with understandable fields and browser validation.',
  'objectives'=>['Connect labels and controls','Choose the right input type','Make required fields explicit'],
  'teaching'=>'Forms work when every control has a clear label, an appropriate type, and a predictable way to submit.',
  'concept'=>'A label for value must match its input id. Name identifies the submitted field, while type changes input behavior.',
  'example'=>'An email input can combine type="email" and required so the browser checks for a value in email form.',
  'practice'=>'Sketch the fields a short project inquiry truly needs.',
  'activity'=>'form-builder',
  'practices'=>[
   $lab('Connect a name label','Add a label for="name" and a text input with matching id and name.',$page("  <form>\n    <!-- Add the name field -->\n  </form>"),['<label','for="name"','id="name"','name="name"'],'The label for and input id must both be name.'),
   $lab('Validate an email','Add a required email field with a connected label.',$page("  <form>\n    <!-- Add the email field -->\n  </form>"),['type="email"','required','for="email"','id="email"'],'Use type="email" plus the required attribute.'),
   $lab('Finish the contact form','Create name and email fields plus a submit button labeled Send inquiry.',$page("  <main>\n    <h1>Project inquiry</h1>\n    <form>\n      <!-- Build the complete form -->\n    </form>\n  </main>"),['<label','type="text"','type="email"','type="submit"','send inquiry'],'A complete form needs labels, controls, and a submit action.')
  ]
 ],
 [
  'title'=>'Organize Data in a Table','focus'=>'Tables, headers, and scope',
  'mission'=>'Turn a small project schedule into an accessible data table.',
  'objectives'=>['Use a table only for data','Separate headings from data cells','Connect column headings with scope'],
  'teaching'=>'Tables describe relationships across rows and columns. They are not a shortcut for visual page layout.',
  'concept'=>'Use th for headings and td for values. Scope tells assistive technology whether a heading applies to a row or column.',
  'example'=>'A schedule can place column headers in thead and project rows in tbody.',
  'practice'=>'Identify the row and column headings in a small schedule.',
  'activity'=>'data-table',
  'practices'=>[
   $lab('Create table structure','Add a table with a thead, tbody, and one row in each.',$page("  <main>\n    <h1>Project schedule</h1>\n    <!-- Add the table -->\n  </main>"),['<table','<thead','<tbody','<tr'],'Use thead for the heading row and tbody for project data.'),
   $lab('Add scoped headers','Create Project and Due date column headers using th scope="col".',$page("  <table>\n    <thead><tr><!-- Add two column headers --></tr></thead>\n    <tbody></tbody>\n  </table>"),['<th','scope="col"','project','due date'],'Both column labels should use <th scope="col">.'),
   $lab('Complete two data rows','Build a table with two headers and at least two rows containing td cells.',$page("  <table>\n    <!-- Complete the schedule -->\n  </table>"),['<thead','<tbody','scope="col"','<td','</table>'],'Add at least two tbody rows when you preview your schedule.')
  ]
 ],
 [
  'title'=>'Choose the Right Page Landmarks','focus'=>'Header, nav, main, article, aside, and footer',
  'mission'=>'Restructure a mini magazine page so its regions are obvious.',
  'objectives'=>['Use one primary main landmark','Separate article from supporting content','Create complete page landmarks'],
  'teaching'=>'Semantic landmarks let browsers and assistive tools understand a page without guessing from class names.',
  'concept'=>'Main contains the unique primary content. Header and footer frame the page, while article and aside describe content roles.',
  'example'=>'A news page can place an article and related-links aside inside main.',
  'practice'=>'Match six content blocks to their best landmark elements.',
  'activity'=>'semantic-landmarks',
  'practices'=>[
   $lab('Frame the page','Add header, main, and footer landmarks around the existing content.',$page("  <!-- Add the page landmarks -->\n  <h1>Beyond Journal</h1>\n  <p>Stories from new builders.</p>"),['<header','<main','<footer'],'Keep the primary page content inside one <main>.'),
   $lab('Separate story and sidebar','Add an article and an aside inside main, each with a heading.',$page("  <main>\n    <!-- Add the article and supporting aside -->\n  </main>"),['<article','<aside','<h2'],'Use article for the standalone story and aside for supporting content.'),
   $lab('Build complete navigation landmarks','Create header, nav with two links, main with article, and footer.',$page("  <!-- Build the magazine page -->"),['<header','<nav','<a','<main','<article','<footer'],'Run through the page from top to bottom and include each requested landmark.')
  ]
 ],
 [
  'title'=>'Write a Professional Document Head','focus'=>'Metadata, title, and viewport',
  'mission'=>'Prepare a page so browsers, search results, and phones understand it.',
  'objectives'=>['Set character encoding and viewport','Write a useful page title','Add a concise description'],
  'teaching'=>'The head contains information about the document rather than the visible page. Good metadata improves display, sharing, and discovery.',
  'concept'=>'The title identifies the current page, while the description summarizes its distinct value.',
  'example'=>'A responsive page typically includes UTF-8 encoding and a viewport set to width=device-width.',
  'practice'=>'Compare generic metadata with a specific title and description.',
  'activity'=>'document-head',
  'practices'=>[
   $lab('Set the browser title','Change the title to Maya Chen — Web Designer.',$page("  <main><h1>Maya Chen</h1></main>"),['<title>maya chen — web designer</title>'],'Edit the title inside head, not the visible h1.'),
   $lab('Add a search description','Add a meta description with original portfolio copy.',$page("  <main><h1>Design portfolio</h1></main>"),['name="description"','content="'],'Place the description meta element inside head.'),
   $lab('Complete the document head','Include charset, viewport, a specific title, and a meta description.',$page("  <main><h1>My portfolio</h1></main>"),['charset="utf-8"','name="viewport"','<title>','name="description"'],'Keep all four pieces inside <head>.')
  ]
 ],
 [
  'title'=>'Repair an Accessibility Audit','focus'=>'Language, skip links, and clear controls',
  'mission'=>'Find and repair accessibility barriers in a small page.',
  'objectives'=>['Declare the document language','Add a working skip link','Name an icon-only control'],
  'teaching'=>'Accessibility is part of the build, not a final decoration. Small semantic choices remove major barriers.',
  'concept'=>'A skip link gives keyboard users a fast route to main content, and accessible names explain controls that have no visible text.',
  'example'=>'A link to #main-content works when main has id="main-content".',
  'practice'=>'Audit a page by navigating its structure without relying on appearance.',
  'activity'=>'accessibility-audit',
  'practices'=>[
   $lab('Declare page language','Add lang="en" to the html element and confirm the document still renders.',$page("  <main><h1>Accessibility lab</h1></main>"),['<html lang="en"'],'The lang attribute belongs on the opening html element.'),
   $lab('Build a working skip link','Add a Skip to content link targeting #main-content and the matching main id.',$page("  <!-- Add the skip link -->\n  <main>\n    <h1>Article</h1>\n  </main>"),['href="#main-content"','id="main-content"','skip to content'],'The skip link must appear before the main content.'),
   $lab('Name an icon button','Add a button containing ★ and give it aria-label="Save favorite".',$page("  <main>\n    <h1>Project card</h1>\n    <!-- Add the icon-only button -->\n  </main>"),['<button','aria-label="save favorite"','★'],'The visible icon is not a reliable accessible name, so add aria-label.')
  ]
 ],
 [
  'title'=>'Ship a One-Page Portfolio','focus'=>'Semantic HTML capstone',
  'mission'=>'Combine the module skills into a portfolio page you can continue styling in Module 2.',
  'objectives'=>['Plan a meaningful page outline','Build navigation and project content','Complete an accessibility self-check'],
  'teaching'=>'A capstone is a working first version, not a perfect final product. Build the structure, test every connection, and improve one weakness.',
  'concept'=>'Strong HTML stays understandable before CSS. If the page outline and links make sense, styling has a solid foundation.',
  'example'=>'A one-page portfolio can include About, Projects, and Contact sections connected by navigation.',
  'practice'=>'Write a three-section content plan before touching the editor.',
  'activity'=>'portfolio-capstone',
  'practices'=>[
   $lab('Build the portfolio shell','Create header, nav, main, and footer with one h1.',$page("  <!-- Build your portfolio shell -->"),['<header','<nav','<main','<footer','<h1'],'Start with the page landmarks before filling in every detail.'),
   $lab('Add connected project sections','Create About and Projects navigation links with matching section ids and headings.',$page("  <header><h1>Your name</h1><nav><!-- Add links --></nav></header>\n  <main><!-- Add matching sections --></main>\n  <footer>Built in Beyond Coding School</footer>"),['href="#about"','href="#projects"','id="about"','id="projects"','<h2'],'Every fragment link needs a matching section id.'),
   $lab('Complete and audit the page','Finish a semantic portfolio with metadata, navigation, two project items, image alt text, and a contact link.',$page("  <!-- Replace this comment with your complete portfolio -->"),['name="description"','<nav','<main','<article','<img','alt="','mailto:','<footer'],'Preview the page, test its links, then revise at least one unclear label before completing the lab.')
  ]
 ]
];
unset($page,$lab);
$academyConfig['scripts']='<script src="/coding-school/assets/js/learning-center.js?v=20260730-2" defer></script>';
require dirname(__DIR__).'/includes/learning-academy.php';
