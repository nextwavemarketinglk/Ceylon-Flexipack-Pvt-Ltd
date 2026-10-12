<?php
require_once __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {echo json_encode(site_products(), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}catch(Throwable $err){http_response_code(500);echo json_encode(['error'=>'Catalogue currently unavailable']);}
