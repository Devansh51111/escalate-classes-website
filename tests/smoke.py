"""Run with PHP executable path as argument. No database or real records used."""
import subprocess, sys
php=sys.argv[1] if len(sys.argv)>1 else 'php'
for page in ['home','about','classes','admissions','contact','admin']:
    code='$_SERVER["REQUEST_METHOD"]="GET"; $_GET["page"]="'+page+'"; include "public/index.php";'
    r=subprocess.run([php,'-r',code],capture_output=True,text=True)
    assert r.returncode==0 and '<!doctype html>' in r.stdout,(page,r.stderr)
    if page=='admissions': assert 'Enquiries are not available right now' in r.stdout and 'disabled' in r.stdout
    if page=='about': assert 'Gunjan Ramchandani' in r.stdout
    if page=='contact': assert '34, Swami Vivekanand Nagar' in r.stdout
    print(page+': GET rendered without database configuration')
code='$_SERVER["REQUEST_METHOD"]="POST"; $_POST=["action"=>"enquire","csrf"=>"invalid"]; include "public/index.php";'
r=subprocess.run([php,'-r',code],capture_output=True,text=True)
assert 'The form expired' in r.stdout
print('Invalid CSRF rejected before database access')
