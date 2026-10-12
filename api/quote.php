<?php
require_once __DIR__.'/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function respond(int $status,bool $ok,string $error='',string $ref=''): never {http_response_code($status);echo json_encode(['ok'=>$ok,'error'=>$error,'reference'=>$ref],JSON_UNESCAPED_UNICODE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST')respond(405,false,'Method not allowed.');
if(!is_installed())respond(503,false,'Online quotation service is not configured yet. Please contact us via WhatsApp.');
if(!empty($_POST['website']))respond(400,false,'Submission rejected.');
if(!empty($_SESSION['last_quote_at'])&&time()-(int)$_SESSION['last_quote_at']<20)respond(429,false,'Please wait before sending another enquiry.');
$fields=[];$limits=['customer_name'=>100,'phone'=>30,'email'=>150,'company'=>150,'product'=>140,'material'=>40,'quantity'=>50,'dimensions'=>120,'printing'=>30,'delivery_location'=>160,'notes'=>3000];
foreach($limits as $name=>$length) { $value=trim((string)($_POST[$name]??''));if(mb_strlen($value)>$length)respond(422,false,"$name is too long.");$fields[$name]=$value; }
if(mb_strlen($fields['customer_name'])<2||!preg_match('/^[+0-9()\-\s]{9,30}$/',$fields['phone'])||$fields['quantity']===''||$fields['product']==='')respond(422,false,'Please enter your name, valid phone, product and quantity.');
if($fields['email']!==''&&!filter_var($fields['email'],FILTER_VALIDATE_EMAIL))respond(422,false,'Invalid email address.');
$allowed=['to-discuss','printed','plain'];if(!in_array($fields['printing'],$allowed,true))respond(422,false,'Invalid printing selection.');
$validProducts=array_column(site_products(),'id');if(!in_array($fields['product'],array_merge($validProducts,['other']),true))respond(422,false,'Invalid product choice.');
$filename='';$original='';
if(isset($_FILES['artwork'])&&$_FILES['artwork']['error']!==UPLOAD_ERR_NO_FILE){
    $file=$_FILES['artwork'];if($file['error']!==UPLOAD_ERR_OK)respond(422,false,'File upload failed.');
    if($file['size']>5*1024*1024)respond(422,false,'Maximum file size is 5MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
    if(!isset($map[$mime]))respond(422,false,'Invalid file type. Use JPG, PNG, WEBP or PDF.');
    $dir=__DIR__.'/../storage/private';if(!is_dir($dir)&&!mkdir($dir,0700,true))respond(500,false,'Upload storage unavailable.');
    $filename=bin2hex(random_bytes(18)).'.'.$map[$mime];$original=mb_substr(basename((string)$file['name']),0,180);
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$filename))respond(500,false,'Cannot save attachment.');
}
$reference='CP-'.date('Ymd').'-'.strtoupper(bin2hex(random_bytes(3)));
try {
    $pdo=db();
    $stmt=$pdo->prepare('INSERT INTO quotations (reference,customer_name,phone,email,company,product,material,quantity,dimensions,printing,delivery_location,notes,attachment_name,attachment_original,status,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$reference,$fields['customer_name'],$fields['phone'],$fields['email'],$fields['company'],$fields['product'],$fields['material'],$fields['quantity'],$fields['dimensions'],$fields['printing'],$fields['delivery_location'],$fields['notes'],$filename,$original,'new',date('Y-m-d H:i:s')]);
    $_SESSION['last_quote_at']=time();respond(200,true,'',$reference);
} catch(Throwable $e) {error_log('Quote error: '.$e->getMessage());if($filename)@unlink(__DIR__.'/../storage/private/'.$filename);respond(500,false,'Could not save the enquiry. Please contact us directly.');}
