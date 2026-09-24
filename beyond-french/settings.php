<?php
require_once __DIR__ . '/../includes/ecosystem.php';
$appShell = true;
$pageTitle = 'Settings | Beyond French';
require __DIR__ . '/includes/header.php';
?>
<style>
#language-settings button,#difficulty-settings button{padding:16px;border:1px solid #d7e1f5;border-radius:16px;color:#10224b;background:#fff;font:inherit;font-weight:850;cursor:pointer}#language-settings button.selected,#difficulty-settings button.selected{color:#fff;background:#1768ff;border-color:#1768ff}.settings-shell{max-width:760px;margin:0 auto;padding:30px 20px 110px}.settings-shell h1{font-size:clamp(2.5rem,7vw,4.5rem);letter-spacing:-.06em;line-height:.95;margin:8px 0}.settings-shell .lesson-card{margin-top:18px}.difficulty-preview{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:14px}.difficulty-preview img{width:100%;aspect-ratio:1;object-fit:cover;object-position:top;border-radius:14px;border:1px solid #dbe4f1}.settings-status{color:var(--muted);font-size:.85rem}@media(max-width:560px){.settings-shell{padding-left:18px;padding-right:18px}.difficulty-preview{grid-template-columns:repeat(2,1fr)}}
</style>
<section class="settings-shell"><span class="eyebrow">YOUR EXPERIENCE</span><h1>Settings</h1><p>Choose your difficulty and tutor style for Beyond French.</p>
<div class="lesson-card"><h2>Difficulty and tutor team</h2><div class="app-tool-grid" id="difficulty-settings"><button data-difficulty="beginner" data-age="kids">Beginner</button><button data-difficulty="intermediate" data-age="teen">Intermediate</button><button data-difficulty="advanced" data-age="adult">Advanced</button></div><div class="difficulty-preview" id="difficulty-preview"></div><p class="settings-status" id="difficulty-status" aria-live="polite">Your tutor team is saved on this device.</p></div>
</section>
<script>
const base='<?= h($frenchBase) ?>assets/images/tutors/',difficultyStatus=document.getElementById('difficulty-status'),preview=document.getElementById('difficulty-preview'),tutorNames=['louis','irie','jazzy','pablo'];
const setDifficulty=(difficulty,age)=>{localStorage.setItem('beyond-french.difficulty',difficulty);localStorage.setItem('beyond-french.age',age);document.querySelectorAll('[data-difficulty]').forEach(button=>button.classList.toggle('selected',button.dataset.difficulty===difficulty));const folder=age==='kids'?'':age==='teen'?'high-school/':'advanced/';preview.innerHTML=tutorNames.map(name=>'<img src="'+base+folder+name+'.jpg" alt="'+name+' tutor">').join('');difficultyStatus.textContent=difficulty.charAt(0).toUpperCase()+difficulty.slice(1)+' tutor team selected.'};
document.querySelectorAll('[data-difficulty]').forEach(button=>button.addEventListener('click',()=>setDifficulty(button.dataset.difficulty,button.dataset.age)));setDifficulty(localStorage.getItem('beyond-french.difficulty')||'beginner',localStorage.getItem('beyond-french.age')||'kids');
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
