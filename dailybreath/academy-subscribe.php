<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/ecosystem.php';
$wallet=beyond_app_bootstrap('DailyBreath');
require_once __DIR__.'/includes/academy-stripe.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!verify_csrf_token($_POST['csrf']??null))$error='Your session expired. Reload and try again.';
  else try{
    $price=academy_stripe_price();if($price==='')throw new RuntimeException('Academy Stripe price ID is not configured.');
    $session=academy_stripe_request('POST','checkout/sessions',['mode'=>'payment','line_items'=>[['price'=>$price,'quantity'=>1]],'client_reference_id'=>(string)$_SESSION['user_id'],'customer_email'=>(string)($_SESSION['email']??''),'metadata'=>['user_id'=>(string)$_SESSION['user_id'],'product'=>'dailybreath_academy'],'success_url'=>academy_absolute_url('dailybreath/academy.php?checkout=success'),'cancel_url'=>academy_absolute_url('dailybreath/academy-subscribe.php?checkout=cancel')]);
    if(empty($session['url']))throw new RuntimeException('Stripe did not return a checkout URL.');header('Location: '.$session['url']);exit;
  }catch(Throwable $e){$error=$e->getMessage();}
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Unlock | Bible Academy</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;padding:22px;color:#eef5ed;font-family:Inter,system-ui;background:#062416}.card{width:min(520px,100%);padding:34px;border:1px solid #ffffff33;border-radius:26px;background:#0c3928}.price{font-size:48px;color:#f0cf7e;font-weight:900}.btn{padding:14px 20px;border:0;border-radius:12px;color:#173f2c;background:#f0cf7e;font-weight:900;cursor:pointer}.error{padding:13px;border-radius:12px;color:#7d2020;background:#ffdede}a{color:#f0cf7e}</style></head><body><main class="card"><a href="academy.php">← Bible Academy</a><h1>Unlock every Academy module.</h1><p class="price">$4.99 <small>one time</small></p><p>Make a one-time payment to access every Daily Breath Academy module through Beyond-ID.</p><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="btn">Continue to secure checkout</button></form></main><script src="/assets/js/visitor-analytics.js" defer></script></body></html>
