<?php
declare(strict_types=1);
$errors=[];$success=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf(); $action=(string)($_POST['action']??'');
 if($action==='enquire'){
  foreach(['parent_name'=>120,'student_name'=>120,'current_school'=>200,'message'=>2000] as $key=>$max){$form[$key]=trim((string)($_POST[$key]??''));if(mb_strlen($form[$key])>$max)$errors[]='One of your entries is too long.';}
  $form['parent_phone']=preg_replace('/[\s()-]/','',(string)($_POST['parent_phone']??''));
  if(!$form['parent_name']||!$form['student_name'])$errors[]='Enter the parent and student names.';
  if(!preg_match('/^\+?[0-9]{10,15}$/',$form['parent_phone']))$errors[]='Enter a valid phone number.';
  $class=(int)($_POST['class_id']??0);$session=(int)($_POST['session_id']??0);
  $activeClasses=array_column(all_classes(),'id');$activeSessions=array_column(sessions(),'id');
  if(!in_array($class,array_map('intval',$activeClasses),true)||!in_array($session,array_map('intval',$activeSessions),true))$errors[]='Choose an available class and active academic session.';
  if(time()-(int)($_SESSION['last_enquiry']??0)<30)$errors[]='Please wait a moment before sending another enquiry.';
  if(!$errors){
   try{$pdo=db();$pdo->beginTransaction();
    $check=$pdo->prepare('SELECT COUNT(*) FROM admission_enquiries WHERE parent_phone=? AND student_name=?');$check->execute([$form['parent_phone'],$form['student_name']]);$duplicate=(int)$check->fetchColumn()>0;
    $q=$pdo->prepare('INSERT INTO admission_enquiries(parent_name,parent_phone,student_name,class_id,academic_session_id,current_school,message,duplicate_flag) VALUES (?,?,?,?,?,?,?,?)');$q->execute([$form['parent_name'],$form['parent_phone'],$form['student_name'],$class,$session,$form['current_school'],$form['message'],(int)$duplicate]);
    $pdo->commit();$_SESSION['last_enquiry']=time();$_SESSION['flash']='Your enquiry has been received. This is not an admission confirmation.';redirect('?page=admissions');
   }catch(Throwable $exception){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();error_log('Enquiry save failed');$errors[]='Your enquiry could not be saved. Please try again.';}
  }
 }
 if($action==='login'){
  $identity=trim((string)($_POST['identity']??''));$hash=hash('sha256',strtolower($identity).'|'.($_SERVER['REMOTE_ADDR']??''));
  $q=db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE identity_hash=? AND attempted_at>DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$q->execute([$hash]);
  if((int)$q->fetchColumn()>=5){$errors[]='Please wait 15 minutes before trying again.';}
  else{$q=db()->prepare('INSERT INTO login_attempts(identity_hash) VALUES (?)');$q->execute([$hash]);$q=db()->prepare('SELECT * FROM admins WHERE username=? OR email=? LIMIT 1');$q->execute([$identity,$identity]);$user=$q->fetch();
   if($user&&password_verify((string)($_POST['password']??''),$user['password_hash'])){session_regenerate_id(true);$_SESSION['admin_id']=(int)$user['id'];$_SESSION['last_seen']=time();$_SESSION['csrf']=bin2hex(random_bytes(32));redirect('?page=dashboard');}$errors[]='Unable to sign in with those details.';
  }
 }
 if(in_array($action,['logout','save_enquiry','archive','save_settings','add_session','save_class','save_content'],true)){
  require_admin();
  if($action==='logout'){$_SESSION=[];session_destroy();redirect('?page=admin');}
  if($action==='save_enquiry'){
   $statuses=['New','Contacted','Follow-up Required','Visit/Meeting Scheduled','Admitted','Not Interested'];$status=(string)($_POST['status']??'');$follow=trim((string)($_POST['follow_up']??''));$id=(int)($_POST['id']??0);
   if(!in_array($status,$statuses,true))$errors[]='Choose a valid status.';
   if($follow&&!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/',$follow))$errors[]='Enter a valid follow-up date.';
   $note=trim((string)($_POST['note']??''));if(mb_strlen($note)>4000)$errors[]='The note is too long.';
   if(!$errors){db()->beginTransaction();$q=db()->prepare('UPDATE admission_enquiries SET status=?,next_follow_up_at=? WHERE id=?');$q->execute([$status,$follow?str_replace('T',' ',$follow).':00':null,$id]);if($note){$q=db()->prepare('INSERT INTO enquiry_notes(enquiry_id,admin_id,note) VALUES(?,?,?)');$q->execute([$id,$_SESSION['admin_id'],$note]);}db()->commit();redirect('?page=enquiry&id='.$id);}
  }
  if($action==='archive'){$q=db()->prepare('UPDATE admission_enquiries SET archived_at=NOW() WHERE id=?');$q->execute([(int)$_POST['id']]);redirect('?page=dashboard');}
  if($action==='add_session'){$name=trim((string)($_POST['name']??''));if(!$name||mb_strlen($name)>80)$errors[]='Enter a session name under 80 characters.';else{$q=db()->prepare('INSERT INTO academic_sessions(name,is_active) VALUES (?,?)');$q->execute([$name,isset($_POST['is_active'])?1:0]);redirect('?page=settings');}}
  if($action==='save_class'){$id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$description=trim((string)($_POST['description']??''));if(!$name||mb_strlen($name)>80||mb_strlen($description)>2000)$errors[]='Check class name and description lengths.';else{$q=db()->prepare('UPDATE classes SET name=?,description=?,is_active=? WHERE id=?');$q->execute([$name,$description,isset($_POST['is_active'])?1:0,$id]);redirect('?page=settings');}}
  if($action==='save_settings'){
   foreach(['institute_name','city','address','phone','whatsapp','email'] as $key){$value=trim((string)($_POST[$key]??''));if(mb_strlen($value)>300)$errors[]='A setting is too long.';if($key==='email'&&$value&&!filter_var($value,FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid email.';if(in_array($key,['phone','whatsapp'],true)&&$value&&!preg_match('/^\+?[0-9]{10,15}$/',$value))$errors[]='Use international-format phone digits.';$values[$key]=$value;}
   if(!$errors){$q=db()->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');foreach($values as $k=>$v)$q->execute([$k,$v]);redirect('?page=settings');}
  }
  if($action==='save_content'){foreach(['hero_heading','hero_description','about_description'] as $key){$value=trim((string)($_POST[$key]??''));if(mb_strlen($value)>2000)$errors[]='Content must be under 2000 characters.';$values[$key]=$value;}if(!$errors){$q=db()->prepare('INSERT INTO site_content(content_key,content_value) VALUES(?,?) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value)');foreach($values as $k=>$v)$q->execute([$k,$v]);redirect('?page=settings');}}
 }
}
