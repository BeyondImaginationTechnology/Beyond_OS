<?php
declare(strict_types=1);
$academyConfig=[
 'slug'=>'coding-school','title'=>'Beyond Coding School','icon'=>'💻','tagline'=>'Builder campus','accent'=>'#6d4aff','base'=>'/coding-school/','css'=>'/coding-school/academy.css?v=20260730-2','headline'=>'Choose a pathway. Build real skills.','description'=>'Six career pathways with 5 modules and 10 guided lessons per module, plus lesson tests, module exams, and saved progress. Module 1 is free in every pathway.','default_path'=>'web-designer','group_label'=>'career pathways',
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
$academyConfig['scripts']='<script src="/coding-school/assets/js/learning-center.js?v=20260730-1" defer></script>';
require dirname(__DIR__).'/includes/learning-academy.php';
