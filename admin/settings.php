<?php
require __DIR__.'/_admin.php';$pdo=db();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 admin_validate_csrf();
 try{ $data=['phone'=>trim((string)($_POST['phone']??'')),'whatsapp'=>preg_replace('/\D+/','',(string)($_POST['whatsapp']??'')),'contact_email'=>trim((string)($_POST['contact_email']??'')),'address'=>trim((string)($_POST['address']??''))];
 if(!preg_match('/^[+0-9()\-\s]{9,30}$/',$data['phone'])||!preg_match('/^[0-9]{10,15}$/',$data['whatsapp']))throw new RuntimeException('Please provide a valid phone and WhatsApp number.');
 if($data['contact_email']!==''&&!filter_var($data['contact_email'],FILTER_VALIDATE_EMAIL))throw new RuntimeException('Invalid email address.');
 foreach($data as $key=>$value){$q=$pdo->prepare('SELECT COUNT(*) FROM settings WHERE key_name=?');$q->execute([$key]);$present=$q->fetchColumn();$q=$pdo->prepare($present?'UPDATE settings SET value=? WHERE key_name=?':'INSERT INTO settings(value,key_name) VALUES (?,?)');$q->execute([$value,$key]);}
 flash_set('Contact settings saved.');header('Location: settings.php');exit;
 }catch(Throwable $ex){$error=$ex->getMessage();}
}
admin_header('Website Settings');flash_show();if($error)echo '<div class="error">'.e($error).'</div>';?><div class="panel settings-panel"><div class="panel-header"><h2>Business Contact Details</h2></div><form method="post" class="admin-form"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><div class="admin-form-grid"><label>Phone number<input name="phone" value="<?=e(setting('phone',PHONE_PRIMARY))?>" required></label><label>WhatsApp number (country code, e.g. 9477... )<input name="whatsapp" value="<?=e(setting('whatsapp',WA_NUMBER))?>" required></label><label>Email (optional)<input name="contact_email" type="email" value="<?=e(setting('contact_email'))?>"></label><label>Outlet address<input name="address" value="<?=e(setting('address','Kuliyapitiya, Sri Lanka'))?>"></label></div><button type="submit" class="admin-btn">Save Settings →</button></form><div class="admin-hint">Note: This panel updates the global phone/WhatsApp details. Before launch, review static company copy and replace brochure phone numbers where they appear on the Contact page, if needed.</div></div><?php admin_footer();?>
