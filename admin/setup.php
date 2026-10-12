<?php
require_once __DIR__.'/../includes/bootstrap.php';
if(is_installed()){header('Location: login.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    if(!csrf_check($_POST['csrf']??null))$error='Invalid security token.';
    elseif(strlen((string)($_POST['password']??''))<12)$error='Password must contain at least 12 characters.';
    elseif(!preg_match('/^[a-zA-Z0-9_.-]{3,32}$/',(string)($_POST['username']??'')))$error='Username must be 3–32 letters, numbers, dashes or underscores.';
    else {
      try {
        $driver=(string)($_POST['driver']??'sqlite');
        if($driver==='mysql') {
            $c=['driver'=>'mysql','host'=>(string)($_POST['host']??''),'dbname'=>(string)($_POST['dbname']??''),'username'=>(string)($_POST['db_user']??''),'password'=>(string)($_POST['db_pass']??'')];
            $pdo=new PDO('mysql:host='.$c['host'].';dbname='.$c['dbname'].';charset=utf8mb4',$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $schema=file_get_contents(__DIR__.'/../data/schema_mysql.sql');foreach(array_filter(array_map('trim',explode(';',$schema))) as $sql)$pdo->exec($sql);
        } else {
            if(!extension_loaded('pdo_sqlite'))throw new RuntimeException('PDO SQLite is not enabled on this server. Use MySQL.');
            $storage=__DIR__.'/../storage';if(!is_dir($storage)&&!mkdir($storage,0700,true))throw new RuntimeException('Cannot create storage folder.');
            $pdo=new PDO('sqlite:'.$storage.'/ceylon.sqlite',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$pdo->exec(file_get_contents(__DIR__.'/../data/schema_sqlite.sql'));
        }
        $count=(int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
        if($count)throw new RuntimeException('Administrator is already configured.');
        $pdo->beginTransaction();
        $q=$pdo->prepare('INSERT INTO admins (username,password_hash) VALUES (?,?)');$q->execute([$_POST['username'],password_hash((string)$_POST['password'],PASSWORD_DEFAULT)]);
        $q=$pdo->prepare('INSERT INTO products (slug,name_en,name_si,category,description_en,description_si,image,featured,active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach(products_seed() as $i=>$p) $q->execute([$p['id'],$p['name_en'],$p['name_si'],$p['category'],$p['description_en'],$p['description_si'],$p['image'],(int)$p['featured'],1,$i]);
        $s=$pdo->prepare('INSERT INTO settings (key_name,value) VALUES (?,?)');foreach(['phone'=>PHONE_PRIMARY,'whatsapp'=>WA_NUMBER,'contact_email'=>'','address'=>'Kuliyapitiya, Sri Lanka'] as $k=>$v)$s->execute([$k,$v]);
        $pdo->commit();
        if($driver==='mysql') { $config='<?php return '.var_export($c,true).';';if(file_put_contents(__DIR__.'/../config.php',$config,LOCK_EX)===false)throw new RuntimeException('Database setup succeeded, but cannot write config.php. Create config.php manually.'); }
        header('Location: login.php?installed=1');exit;
      }catch(Throwable $ex){$error='Setup failed: '.$ex->getMessage();}
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>First-Time Setup — Ceylon Packaging</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body class="login-body"><div class="login-card setup-card"><a class="admin-brand" href="../index.php">◆ CEYLON<span>PACKAGING</span></a><h1>Set up your website</h1><p>Create the first administrator and initialise product catalogues and quotation storage. This runs once.</p><?php if($error):?><div class="error"><?=e($error)?></div><?php endif;?><form method="post"><?= '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">' ?><label>Admin username<input name="username" value="<?=e($_POST['username']??'admin')?>" required minlength="3" maxlength="32"></label><label>Admin password (12+ characters)<input type="password" name="password" required minlength="12" autocomplete="new-password"></label><label>Database type<select name="driver" id="driver"><option value="sqlite">SQLite — simplest setup</option><option value="mysql">MySQL — for cPanel hosting</option></select></label><div id="mysqlOptions" style="display:none"><label>MySQL host<input name="host" value="localhost"></label><label>Database name<input name="dbname"></label><label>Database username<input name="db_user"></label><label>Database password<input name="db_pass" type="password"></label></div><button class="admin-btn" type="submit">Create Website Admin →</button></form><p class="admin-hint">For SQLite the storage directory must be writable. For MySQL, create a database and user in cPanel first.</p></div><script>document.querySelector('#driver').onchange=e=>document.querySelector('#mysqlOptions').style.display=e.target.value==='mysql'?'block':'none';</script></body></html>
