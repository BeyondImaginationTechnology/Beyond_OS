<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';
require_once dirname(__DIR__, 3) . '/includes/beyond-ai.php';
require dirname(__DIR__) . '/_header.php';

$imageReady = trim((string)beyond_ai_config('azure_image_key', '')) !== ''
    && trim((string)beyond_ai_config('azure_image_endpoint', '')) !== '';
$speechReady = trim((string)beyond_config('narration.azure.api_key', '')) !== ''
    && trim((string)beyond_config('narration.azure.region', '')) !== '';
?>
<link rel="stylesheet" href="/server/admin/daily-studio/studio.css">
<link rel="stylesheet" href="/server/admin/daily-studio/studio-sunset.css">
<div class="studio-head"><div><p class="studio-eyebrow">FrenchQuest</p><h1>Azure Game Assets</h1><p class="muted">Generate one Xcode-ready ZIP containing five destination backgrounds and three game-feedback voices.</p></div><a class="btn" href="voice-settings.php#azure-game-assets">Azure settings</a></div>
<section class="card">
  <h2>World Tour asset pack</h2>
  <p>The artwork uses the configured Azure Foundry image deployment. Voice lines use the configured Azure Speech voice. Keys remain on the server and are never included in the ZIP or iOS app.</p>
  <div class="two">
    <div class="field"><label>Azure image generation</label><small><?=$imageReady?'Ready':'Needs an image key and Foundry endpoint'?></small></div>
    <div class="field"><label>Azure Speech</label><small><?=$speechReady?'Ready':'Needs a Speech key and region'?></small></div>
  </div>
  <?php if ($imageReady && $speechReady): ?>
    <form method="post" action="api/generate-frenchquest-assets.php">
      <input type="hidden" name="csrf" value="<?=DailyStudio::esc(Auth::csrf())?>">
      <button class="btn" type="submit">Generate and download ZIP</button>
      <p class="muted">Generation makes eight Azure requests and can take several minutes. Extract the ZIP directly into <code>FrenchQuestApple/Resources</code>.</p>
    </form>
  <?php else: ?>
    <a class="btn" href="voice-settings.php#azure-game-assets">Complete Azure settings</a>
  <?php endif; ?>
</section>
<?php require dirname(__DIR__) . '/_footer.php'; ?>
