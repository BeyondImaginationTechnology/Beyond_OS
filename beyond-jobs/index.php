<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/app-layout.php';
$wallet = beyond_nav_bootstrap('Beyond Jobs');
?>
<!doctype html>
<html lang="en">
<head>
<script>(function(){try{var t=localStorage.getItem('beyond-theme');document.documentElement.dataset.theme=['dark','light','sunset','ocean','forest'].includes(t)?t:'dark';}catch(e){document.documentElement.dataset.theme='dark';}})();</script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="theme-color" content="#24113b">
  <title>Beyond Jobs | Career Builder</title>
  <meta name="description" content="Match jobs to your career path, create a résumé and cover letter, and follow free training and certificate guides.">
  <link rel="manifest" href="<?=e(beyond_url('manifest.webmanifest'))?>">
  <link rel="stylesheet" href="<?=e(beyond_url('assets/css/bos-21.css'))?>">
</head>
<body class="bos-page jobs-page">
<main class="bos-main jobs-main">
  <section class="bos-hero jobs-hero">
    <span class="bos-kicker">Beyond Jobs · Free career builder</span>
    <h1>Build skills.<br>Find your next role.</h1>
    <p>Choose a career path to see matching opportunities, create job-ready application materials, and follow a free training and certificate roadmap.</p>
    <div class="bos-actions"><a class="bos-btn" href="#job-match">Find matching jobs</a><a class="bos-btn secondary" href="#resume-lab">Build my résumé</a></div>
    <div class="jobs-stat-row"><span><b>6</b> career paths</span><span><b>Free</b> training plans</span><span><b>Private</b> browser-only drafts</span></div>
  </section>

  <nav class="jobs-tabs" aria-label="Beyond Jobs tools">
    <a href="#job-match">Job Match</a><a href="#career-plan">Career Plan</a><a href="#resume-lab">AI Résumé Lab</a><a href="#cover-letter">Cover Letter</a><a href="#training">Training &amp; Certificates</a>
  </nav>

  <section class="jobs-panel" id="job-match">
    <div class="panel-heading"><div><span class="bos-kicker">Career match</span><h2>Jobs based on your pathway</h2></div><p>Select a field and experience level. These sample openings demonstrate the matching experience and link to focused searches.</p></div>
    <div class="filter-grid">
      <label>Career path<select id="pathFilter">
        <option value="all">All career paths</option>
        <option>Web Designer</option><option>iOS Developer</option><option>Android Developer</option><option>Graphic Design &amp; SVG</option><option>Game Development</option><option>Full-Stack Development</option>
      </select></label>
      <label>Experience<select id="levelFilter"><option value="all">Any level</option><option>Entry level</option><option>Junior</option><option>Apprenticeship</option><option>Freelance</option></select></label>
      <label>Work style<select id="styleFilter"><option value="all">Any work style</option><option>Remote</option><option>Hybrid</option><option>On-site</option></select></label>
    </div>
    <p class="jobs-count" id="jobsCount" aria-live="polite"></p>
    <div class="job-grid" id="jobGrid"></div>
  </section>

  <section class="jobs-panel" id="career-plan">
    <div class="panel-heading"><div><span class="bos-kicker">Career builder</span><h2>Your next-step plan</h2></div><p>Turn a pathway into a practical four-step plan you can save and revisit.</p></div>
    <div class="career-builder">
      <label>My target role<input id="targetRole" type="text" placeholder="Example: Junior iOS Developer"></label>
      <label>Hours available each week<input id="weeklyHours" type="number" min="1" max="40" value="6"></label>
      <button class="bos-btn" id="buildPlan" type="button">Build my plan</button>
    </div>
    <ol class="plan-output" id="planOutput">
      <li><b>Choose a target.</b><span>Name the role you want next.</span></li>
      <li><b>Learn the core skills.</b><span>Follow one free pathway at a steady weekly pace.</span></li>
      <li><b>Prove your work.</b><span>Finish two portfolio projects and document what you built.</span></li>
      <li><b>Apply with focus.</b><span>Tailor your résumé and cover letter to each opportunity.</span></li>
    </ol>
  </section>

  <section class="jobs-panel" id="resume-lab">
    <div class="panel-heading"><div><span class="bos-kicker">AI-assisted writing</span><h2>Résumé lab</h2></div><p>Enter real details only. The builder turns them into a clean draft you can review and copy.</p></div>
    <div class="builder-layout">
      <form class="builder-form" id="resumeForm">
        <label>Full name<input name="name" required autocomplete="name" placeholder="Your name"></label>
        <label>Target role<input name="role" required placeholder="Junior Web Designer"></label>
        <label>Skills<textarea name="skills" required placeholder="HTML, CSS, Figma, accessibility"></textarea></label>
        <label>Experience or projects<textarea name="experience" required placeholder="Built a responsive portfolio and redesigned a community website"></textarea></label>
        <label>Education or training<textarea name="education" placeholder="Beyond Coding School — Web Designer pathway"></textarea></label>
        <button class="bos-btn" type="submit">Create résumé draft</button>
      </form>
      <article class="draft-card" id="resumeDraft"><span>Your résumé draft will appear here.</span></article>
    </div>
  </section>

  <section class="jobs-panel" id="cover-letter">
    <div class="panel-heading"><div><span class="bos-kicker">Application writer</span><h2>Cover-letter builder</h2></div><p>Create a concise first draft tailored to one company and role.</p></div>
    <div class="builder-layout">
      <form class="builder-form" id="letterForm">
        <label>Company<input name="company" required placeholder="Company name"></label>
        <label>Role<input name="role" required placeholder="Job title"></label>
        <label>Your strongest match<textarea name="match" required placeholder="The skill or project that best fits this role"></textarea></label>
        <label>Why this company?<textarea name="why" required placeholder="What genuinely interests you about the work"></textarea></label>
        <button class="bos-btn" type="submit">Create cover-letter draft</button>
      </form>
      <article class="draft-card letter-draft" id="letterDraft"><span>Your cover-letter draft will appear here.</span></article>
    </div>
  </section>

  <section class="jobs-panel" id="training">
    <div class="panel-heading"><div><span class="bos-kicker">Free learning</span><h2>Training &amp; certificate guide</h2></div><p>Start with free Beyond pathways, build proof-of-work projects, then decide whether a third-party certification fits the jobs you want.</p></div>
    <div class="training-grid">
      <article><span>01</span><h3>Learn</h3><p>Complete lessons in your selected Coding School pathway.</p><a href="/coding-school/">Open free Coding School</a></article>
      <article><span>02</span><h3>Practice</h3><p>Build two projects that solve a real problem and show your process.</p><a href="/coding-school/">Choose a pathway project</a></article>
      <article><span>03</span><h3>Document</h3><p>Add outcomes, screenshots, source files and a short reflection to your portfolio.</p><a href="#resume-lab">Add projects to résumé</a></article>
      <article><span>04</span><h3>Certify</h3><p>Use completion records as training evidence, then compare recognized certificates requested in real listings.</p><button type="button" id="showGuide">View certificate checklist</button></article>
    </div>
    <div class="certificate-guide" id="certificateGuide" hidden>
      <h3>Before choosing a certificate</h3>
      <label><input type="checkbox"> I found the certificate named in several real job listings.</label>
      <label><input type="checkbox"> I checked the issuer, exam cost, renewal rules and accessibility needs.</label>
      <label><input type="checkbox"> I can show projects that demonstrate the same skills.</label>
      <label><input type="checkbox"> I understand that a Beyond course-completion record is not professional licensure.</label>
    </div>
  </section>
</main>
<script>
const jobs=[
  {title:'Junior Web Designer',path:'Web Designer',level:'Junior',style:'Remote',skills:'HTML · CSS · Figma',query:'junior web designer remote'},
  {title:'UI Design Apprentice',path:'Web Designer',level:'Apprenticeship',style:'Hybrid',skills:'Wireframes · Prototypes · Accessibility',query:'ui design apprenticeship'},
  {title:'Junior iOS Developer',path:'iOS Developer',level:'Junior',style:'Hybrid',skills:'Swift · SwiftUI · Xcode',query:'junior ios developer'},
  {title:'Mobile App Intern',path:'iOS Developer',level:'Entry level',style:'Remote',skills:'Swift · APIs · Testing',query:'ios developer internship remote'},
  {title:'Android Developer I',path:'Android Developer',level:'Entry level',style:'On-site',skills:'Kotlin · Compose · Android Studio',query:'entry level android developer'},
  {title:'Android QA & Build Assistant',path:'Android Developer',level:'Apprenticeship',style:'Remote',skills:'Testing · Gradle · Git',query:'android qa junior remote'},
  {title:'SVG Production Designer',path:'Graphic Design & SVG',level:'Freelance',style:'Remote',skills:'SVG · Illustrator · Branding',query:'freelance svg designer'},
  {title:'Junior Graphic Designer',path:'Graphic Design & SVG',level:'Junior',style:'Hybrid',skills:'Layout · Typography · Vector art',query:'junior graphic designer'},
  {title:'Game Design Assistant',path:'Game Development',level:'Entry level',style:'On-site',skills:'Unity · Level design · Prototyping',query:'entry level game designer'},
  {title:'Junior Gameplay Developer',path:'Game Development',level:'Junior',style:'Remote',skills:'C# · Unity · Git',query:'junior gameplay developer remote'},
  {title:'Full-Stack Apprentice',path:'Full-Stack Development',level:'Apprenticeship',style:'Hybrid',skills:'JavaScript · PHP · SQL',query:'full stack developer apprenticeship'},
  {title:'Junior Full-Stack Developer',path:'Full-Stack Development',level:'Junior',style:'Remote',skills:'React · APIs · Databases',query:'junior full stack developer remote'}
];
const $=s=>document.querySelector(s);
const escapeHtml=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
function renderJobs(){
  const p=$('#pathFilter').value,l=$('#levelFilter').value,w=$('#styleFilter').value;
  const visible=jobs.filter(j=>(p==='all'||j.path===p)&&(l==='all'||j.level===l)&&(w==='all'||j.style===w));
  $('#jobsCount').textContent=visible.length+' matching demo '+(visible.length===1?'role':'roles');
  $('#jobGrid').innerHTML=visible.map(j=>'<article class="job-card"><span>'+escapeHtml(j.path)+'</span><h3>'+escapeHtml(j.title)+'</h3><p>'+escapeHtml(j.skills)+'</p><div><b>'+escapeHtml(j.level)+'</b><b>'+escapeHtml(j.style)+'</b></div><a target="_blank" rel="noopener" href="https://www.google.com/search?q='+encodeURIComponent(j.query+' jobs')+'">Search live openings ↗</a></article>').join('')||'<p class="empty-state">No demo roles match all three filters. Try a broader selection.</p>';
}
['#pathFilter','#levelFilter','#styleFilter'].forEach(id=>$(id).addEventListener('change',renderJobs));renderJobs();
$('#buildPlan').addEventListener('click',()=>{
  const role=$('#targetRole').value.trim()||'your target role',hours=Math.max(1,Math.min(40,Number($('#weeklyHours').value)||6));
  $('#planOutput').innerHTML='<li><b>Target '+escapeHtml(role)+'.</b><span>Save five job descriptions and highlight repeated skills.</span></li><li><b>Train '+hours+' hours each week.</b><span>Use a matching Beyond Coding School pathway and schedule three study blocks.</span></li><li><b>Build proof.</b><span>Complete two role-relevant projects, including one based on a real user need.</span></li><li><b>Apply every week.</b><span>Tailor your materials, track applications and improve from feedback.</span></li>';
  localStorage.setItem('beyond-jobs-plan',JSON.stringify({role,hours}));
});
const savedPlan=JSON.parse(localStorage.getItem('beyond-jobs-plan')||'null');if(savedPlan){$('#targetRole').value=savedPlan.role;$('#weeklyHours').value=savedPlan.hours;}
function copyButton(target){const b=document.createElement('button');b.type='button';b.className='copy-draft';b.textContent='Copy draft';b.onclick=async()=>{await navigator.clipboard.writeText(target.innerText.replace('Copy draft','').trim());b.textContent='Copied';setTimeout(()=>b.textContent='Copy draft',1500)};target.appendChild(b)}
$('#resumeForm').addEventListener('submit',e=>{e.preventDefault();const d=Object.fromEntries(new FormData(e.currentTarget));const out=$('#resumeDraft');out.innerHTML='<h3>'+escapeHtml(d.name)+'</h3><strong>'+escapeHtml(d.role)+'</strong><h4>Professional summary</h4><p>Motivated '+escapeHtml(d.role)+' with practical experience in '+escapeHtml(d.skills)+'. Brings a project-focused mindset, clear communication and a commitment to continuous learning.</p><h4>Skills</h4><p>'+escapeHtml(d.skills)+'</p><h4>Selected experience &amp; projects</h4><p>'+escapeHtml(d.experience)+'</p><h4>Education &amp; training</h4><p>'+escapeHtml(d.education||'Add your most relevant education or training.')+'</p>';copyButton(out);localStorage.setItem('beyond-jobs-resume',JSON.stringify(d));});
$('#letterForm').addEventListener('submit',e=>{e.preventDefault();const d=Object.fromEntries(new FormData(e.currentTarget));const out=$('#letterDraft');out.innerHTML='<p>Dear '+escapeHtml(d.company)+' hiring team,</p><p>I am excited to apply for the '+escapeHtml(d.role)+' position. '+escapeHtml(d.match)+'. This experience has prepared me to contribute thoughtfully and learn quickly.</p><p>I am especially interested in '+escapeHtml(d.company)+' because '+escapeHtml(d.why)+'. I would welcome the opportunity to discuss how my skills and projects can support your team.</p><p>Sincerely,<br>Your name</p>';copyButton(out);localStorage.setItem('beyond-jobs-letter',JSON.stringify(d));});
for(const [key,form] of [['beyond-jobs-resume','#resumeForm'],['beyond-jobs-letter','#letterForm']]){const d=JSON.parse(localStorage.getItem(key)||'null');if(d)for(const [n,v] of Object.entries(d)){const f=$(form).elements.namedItem(n);if(f)f.value=v}}
$('#showGuide').addEventListener('click',e=>{const g=$('#certificateGuide');g.hidden=!g.hidden;e.currentTarget.textContent=g.hidden?'View certificate checklist':'Hide certificate checklist'});
</script>
<style>
.jobs-main{width:min(1320px,calc(100% - 28px));padding-bottom:60px}.jobs-hero{background:radial-gradient(circle at 88% 10%,rgba(73,235,198,.28),transparent 28%),radial-gradient(circle at 15% 100%,rgba(176,68,255,.22),transparent 32%),linear-gradient(135deg,#0a1930,#201143 58%,#321342)}.jobs-stat-row{display:flex;flex-wrap:wrap;gap:12px;margin-top:24px}.jobs-stat-row span{padding:10px 14px;border:1px solid rgba(255,255,255,.16);border-radius:999px;background:rgba(255,255,255,.06)}.jobs-stat-row b{color:#58f0cc}.jobs-tabs{position:sticky;top:8px;z-index:5;display:flex;gap:8px;overflow:auto;margin:18px 0;padding:10px;border:1px solid var(--bos-line);border-radius:18px;background:rgba(8,11,30,.88);backdrop-filter:blur(18px)}.jobs-tabs a{white-space:nowrap;padding:10px 14px;border-radius:12px;color:var(--bos-text);text-decoration:none;font-weight:800}.jobs-tabs a:hover{background:rgba(255,255,255,.09)}.jobs-panel{scroll-margin-top:90px;margin:18px 0;padding:clamp(20px,4vw,38px);border:1px solid var(--bos-line);border-radius:28px;background:rgba(18,18,45,.75);box-shadow:0 24px 60px rgba(0,0,0,.22)}.panel-heading{display:flex;justify-content:space-between;gap:26px;align-items:end;margin-bottom:24px}.panel-heading h2{margin:.25rem 0 0;font-size:clamp(1.7rem,4vw,2.7rem)}.panel-heading p{max-width:590px;margin:0;color:var(--bos-muted);line-height:1.6}.filter-grid,.career-builder{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.career-builder{grid-template-columns:2fr 1fr auto;align-items:end}label{display:grid;gap:8px;color:var(--bos-muted);font-weight:800}input,select,textarea{width:100%;padding:13px 14px;border:1px solid var(--bos-line);border-radius:13px;background:#0c1029;color:var(--bos-text);font:inherit}textarea{min-height:104px;resize:vertical}.jobs-count{color:#58f0cc;font-weight:900}.job-grid,.training-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.job-card,.training-grid article,.draft-card{padding:20px;border:1px solid var(--bos-line);border-radius:18px;background:rgba(7,10,28,.76)}.job-card>span{color:#58f0cc;font-size:.78rem;font-weight:950;text-transform:uppercase;letter-spacing:.08em}.job-card h3{margin:8px 0}.job-card p{color:var(--bos-muted)}.job-card div{display:flex;gap:8px;margin:16px 0}.job-card b{padding:6px 9px;border-radius:9px;background:rgba(255,255,255,.08);font-size:.76rem}.job-card a,.training-grid a,.training-grid button{color:#9f8cff;font-weight:900;text-decoration:none}.training-grid button{border:0;background:none;padding:0;font:inherit;cursor:pointer}.plan-output{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:24px 0 0;padding:0;list-style:none;counter-reset:steps}.plan-output li{display:grid;gap:8px;padding:18px;border-radius:16px;background:rgba(8,12,30,.75);border:1px solid var(--bos-line)}.plan-output span{color:var(--bos-muted);line-height:1.5}.builder-layout{display:grid;grid-template-columns:minmax(280px,.85fr) minmax(320px,1.15fr);gap:18px}.builder-form{display:grid;gap:14px}.draft-card{position:relative;min-height:390px;line-height:1.6}.draft-card>span{display:grid;place-items:center;height:100%;color:var(--bos-muted)}.draft-card h3{font-size:2rem;margin:0}.draft-card h4{margin:20px 0 4px;color:#58f0cc;text-transform:uppercase;letter-spacing:.08em;font-size:.78rem}.copy-draft{position:absolute;right:14px;top:14px;border:1px solid var(--bos-line);border-radius:10px;padding:8px 10px;background:#30205d;color:white;font-weight:900;cursor:pointer}.training-grid{grid-template-columns:repeat(4,1fr)}.training-grid article>span{display:grid;place-items:center;width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#7f55ff,#2bd6c2);font-weight:950}.training-grid p{color:var(--bos-muted);line-height:1.5}.certificate-guide{display:grid;gap:12px;margin-top:18px;padding:20px;border-radius:18px;background:rgba(43,214,194,.08);border:1px solid rgba(43,214,194,.25)}.certificate-guide label{grid-template-columns:auto 1fr;align-items:start}.certificate-guide input{width:auto;margin-top:4px}.empty-state{grid-column:1/-1;color:var(--bos-muted)}html[data-theme="sunset"] .jobs-page{background:radial-gradient(circle at 80% 0,rgba(255,111,97,.26),transparent 30%),linear-gradient(180deg,#32113d,#1d102b 48%,#0d1021)}html[data-theme="sunset"] .jobs-panel{background:rgba(62,23,58,.74)}html[data-theme="sunset"] .jobs-tabs{background:rgba(49,17,53,.9)}
@media(max-width:900px){.job-grid{grid-template-columns:repeat(2,1fr)}.training-grid,.plan-output{grid-template-columns:repeat(2,1fr)}.builder-layout{grid-template-columns:1fr}.panel-heading{display:block}.panel-heading p{margin-top:10px}}
@media(max-width:620px){.jobs-main{width:min(100% - 18px,1320px)}.jobs-hero{padding:28px 18px}.filter-grid,.career-builder,.job-grid,.training-grid,.plan-output{grid-template-columns:1fr}.jobs-tabs{top:4px}.jobs-panel{padding:20px 15px}.builder-layout{grid-template-columns:minmax(0,1fr)}}
</style>
<?php bos_page_end(); ?>
