<?php
require_once __DIR__.'/../includes/bootstrap.php';
admin_required();
function admin_header(string $title): void { $self=basename($_SERVER['PHP_SELF']);?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=e($title)?> | Ceylon Packaging Admin</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body><header class="admin-head"><a class="admin-brand" href="index.php">◆ CEYLON<span>PACKAGING</span> <small>ADMIN</small></a><nav><a href="index.php" <?=$self==='index.php'?'class="on"':''?>>Overview</a><a href="products.php" <?=$self==='products.php'?'class="on"':''?>>Products</a><a href="quotes.php" <?=$self==='quotes.php'?'class="on"':''?>>Quotations</a><a href="settings.php" <?=$self==='settings.php'?'class="on"':''?>>Settings</a><a href="../index.php" target="_blank" rel="noopener noreferrer">View Website ↗</a><a href="logout.php">Log Out</a></nav></header><main class="admin-main"><div class="title-row"><h1><?=e($title)?></h1><p>Signed in as <?=e($_SESSION['admin_user']??'Admin')?></p></div>
<?php }
function admin_footer(): void {echo '</main></body></html>';}
function flash_set(string $message): void {$_SESSION['flash']=$message;}
function flash_show(): void {if(!empty($_SESSION['flash'])){echo '<div class="flash">'.e($_SESSION['flash']).'</div>';unset($_SESSION['flash']);}}
function admin_csrf(): string {return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">';}
function admin_validate_csrf(): void {if(!csrf_check($_POST['csrf']??null)){http_response_code(403);exit('Invalid security token');}}
