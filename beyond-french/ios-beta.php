<?php
require_once __DIR__ . '/../includes/ecosystem.php';

$frenchBase = rtrim(beyond_url('beyond-french/'), '/') . '/';
header('Location: ' . $frenchBase, true, 301);
exit;
