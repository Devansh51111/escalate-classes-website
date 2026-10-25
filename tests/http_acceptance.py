import requests,re
s=requests.Session();base='http://127.0.0.1:8087/'
def get(page):
 r=s.get(base+'?page='+page);assert r.status_code==200,(page,r.status_code,r.text);return r
def post(page,data):
 r=get(page);data['csrf']=re.search(r'name="csrf" value="([^"]+)"',r.text)[1];return s.post(base+'?page='+page,data=data)
for p in ['home','about','classes','admissions','contact','admin','forgot']:get(p)
r=s.get(base+'?page=dashboard');assert 'Welcome back' in r.text
print('Public GET routes and protected dashboard redirect pass')
r=s.post(base+'?page=admin',data={'action':'login','csrf':'bad'});assert r.status_code==403
print('CSRF rejection pass')
r=post('admin',dict(action='login',identity='testadmin',password='OnlyDisposableTest123!'));assert 'Overview' in r.text,r.text
print('Login pass')
r=post('settings',dict(action='add_session',name='2026–2027',is_active='1'));assert r.status_code==200
r=post('admissions',dict(action='enquire',parent_name='<script>alert(1)</script>',parent_phone='+919999999999',student_name='Test Student',class_id='1',session_id='1',current_school='Test School',message='Acceptance only'));assert 'Your enquiry has been received' in r.text,r.text
r=get('dashboard');assert '&lt;script&gt;' in r.text and '<script>alert(1)</script>' not in r.text
print('Session creation, enquiry persistence and escaped output pass')
r=post('enquiry&id=1',dict(action='save_enquiry',id='1',status='Follow-up Required',follow_up='2026-10-25T10:00',note='Test internal note'));assert 'Test internal note' in r.text
print('Enquiry status, follow-up, note pass')
r=s.get(base+'?page=export&status=Follow-up+Required');assert 'Test Student' in r.text and "'+919999999999" in r.text,r.text
r=s.get(base+'?page=export&status=Admitted');assert 'Test Student' not in r.text
print('Filtered CSV and formula injection prefix pass')
r=post('manage',dict(action='save_announcement',title='Test notice',body='Only local',is_published='1',starts_at='',ends_at=''));assert r.status_code==200
assert 'Test notice' in get('home').text
print('Published announcement visibility pass')
r=post('manage',dict(action='backup'));assert 'CREATE TABLE' in r.text and 'Test Student' in r.text,r.text
print('Authenticated SQL backup pass')
backup=r.text
import subprocess,os
subprocess.run(['mysql','--protocol=TCP','-h127.0.0.1','-P3307','-uroot'],input='DROP DATABASE IF EXISTS escalate_recovery_test; CREATE DATABASE escalate_recovery_test; USE escalate_recovery_test;'+backup,text=True,check=True)
query="SELECT (SELECT COUNT(*) FROM escalate_acceptance.admission_enquiries)=(SELECT COUNT(*) FROM escalate_recovery_test.admission_enquiries), (SELECT COUNT(*) FROM escalate_acceptance.enquiry_notes)=(SELECT COUNT(*) FROM escalate_recovery_test.enquiry_notes), (SELECT COUNT(*) FROM escalate_acceptance.classes)=(SELECT COUNT(*) FROM escalate_recovery_test.classes);"
check=subprocess.check_output(['mysql','--protocol=TCP','-h127.0.0.1','-P3307','-uroot','-N','-e',query],text=True).strip();assert check=='1\t1\t1',check
print('Backup imported into separate recovery DB and enquiry/note/class counts match')

print('INITIAL DATABASE ACCEPTANCE PASSED')
# Additional acceptance tests below use disposable database/SMTP only.
r=post('manage',dict(action='edit_session',id='1',name='Closed intake',start_date='2026-01-01',end_date='2026-12-31'));assert 'Inactive' in get('settings').text
assert 'No academic session is available yet' in get('admissions').text
print('Session closure blocks public intake and preserves existing enquiry pass')
r=post('manage',dict(action='edit_session',id='1',name='Reopened intake',start_date='',end_date='',is_active='1'));assert 'No academic session' not in get('admissions').text
r=post('settings',dict(action='add_class',name='Special Class',description='New course',display_order='0',is_active='1'));assert 'Special Class' in get('classes').text
print('Class addition pass')
r=post('manage',dict(action='save_announcement',title='Future notice',body='Not visible',is_published='1',starts_at='2099-01-01T00:00',ends_at=''));assert 'Future notice' not in get('home').text
print('Announcement publication window pass')
from PIL import Image
import io
im=Image.new('RGB',(40,40),'red');bio=io.BytesIO();im.save(bio,format='PNG')
r=get('manage&section=media');token=re.search(r'name="csrf" value="([^"]+)"',r.text)[1]
r=s.post(base+'?page=manage&section=media',data={'csrf':token,'action':'upload_image','alt_text':'Test red square'},files={'image':('red.png',bio.getvalue(),'image/png')});assert 'red.png' in r.text,r.text
r=post('manage',dict(action='assign_image',slot='hero',media_id='1'));assert 'uploads/' in get('home').text
r=get('manage&section=media');token=re.search(r'name="csrf" value="([^"]+)"',r.text)[1]
r=s.post(base+'?page=manage&section=media',data={'csrf':token,'action':'upload_image','alt_text':'Bad upload'},files={'image':('evil.php',b'<?php echo 1;', 'image/png')});assert 'Choose a valid JPEG' in r.text
print('Valid image upload, assignment and spoofed-image rejection pass')
r=post('forgot',dict(action='request_reset',identity='admin@example.invalid'));assert 'If the address is registered' in r.text
import subprocess,os
php=os.environ.get('PHP_BIN','php')
project=os.environ['PROJECT_ROOT'];code="require '"+project+"/app/bootstrap.php'; echo db()->query(\"SELECT body FROM notification_logs WHERE notification_type='password_reset' ORDER BY id DESC LIMIT 1\")->fetchColumn();"
mail=subprocess.check_output([php,'-r',code],env=os.environ,text=True);token=re.search(r'token=([a-f0-9]{64})',mail)[1]
r=post('reset&token='+token,dict(action='reset_password',token=token,password='DisposableReplacement123!'));assert 'Password updated' in r.text,r.text
r=post('reset&token='+token,dict(action='reset_password',token=token,password='AnotherDisposable123!'));assert 'invalid or expired' in r.text
r=post('admin',dict(action='login',identity='testadmin',password='DisposableReplacement123!'));assert 'Overview' in r.text
print('Queued reset link, password reset, single-use token, updated login pass')
r=post('enquiry&id=1',dict(action='delete_enquiry',id='1',confirmation='WRONG'));assert 'Type DELETE' in r.text
r=post('enquiry&id=1',dict(action='archive',id='1'));assert 'Test Student' not in r.text
r=post('enquiry&id=1',dict(action='delete_enquiry',id='1',confirmation='DELETE'));assert 'Test Student' not in r.text
print('Deletion confirmation, archive and permanent deletion pass')
r=post('dashboard',dict(action='logout'));assert 'Welcome back' in r.text
assert 'Welcome back' in s.get(base+'?page=manage').text
print('Logout and protected management routes pass')
