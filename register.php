<?php
session_start();
require 'db.php';
$error=""; $success="";
$ADMIN_KEY = "SCHOOL2026"; // Secret admin registration key

if($_SERVER['REQUEST_METHOD']==='POST'){
  $username = trim($_POST['username']);
  $email    = trim($_POST['email']);
  $password = $_POST['password'];
  $confirm  = $_POST['confirm'];
  $role     = $_POST['role'];
  $adminkey = trim($_POST['admin_key'] ?? '');

  if($password !== $confirm){
    $error = "Passwords do not match.";
  } elseif($role === 'admin' && $adminkey !== $ADMIN_KEY){
    $error = "Invalid admin key. Contact the system administrator.";
  } else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $stmt->execute([$username]);
    if($stmt->fetch()){
      $error = "Username already taken. Choose another.";
    } else {
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
      $stmt->execute([$username, $hashed, $role]);

      // If registering as student, also add to students table
      if($role === 'student'){
          $fullname = trim($_POST['fullname'] ?? $username);
          $sage     = (int)($_POST['age'] ?? 0);
          $scourse  = trim($_POST['course'] ?? '');
          $conn->prepare("INSERT INTO students (name, email, course, age) VALUES (?,?,?,?)")
               ->execute([$fullname, $email, $scourse, $sage]);
      }

      $success = "Account created as ".ucfirst($role)."! You can now login.";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - SchoolMS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--primary:#4f6ef7;--violet:#7c5bf5;--rose:#f56b7c;--teal:#34c9a0;--amber:#f5a623;--text:#1a1d2e;--sub:#6b7199;--border:#e4e7f2;--g1:linear-gradient(135deg,#4f6ef7,#7c5bf5);}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:url('school.jpeg') no-repeat center center;background-size:cover;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-radius:20px;padding:32px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.15);}
.logo{text-align:center;margin-bottom:20px;}
.logo-mark{width:48px;height:48px;background:var(--g1);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;}
.logo-mark svg{width:24px;height:24px;fill:white;}
.logo h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:17px;font-weight:800;color:var(--text);}
.logo p{font-size:13px;color:var(--sub);margin-top:3px;}
.alert-err{background:#fff0f2;color:#A32D2D;border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:12px;border-left:3px solid var(--rose);}
.alert-ok{background:#edfff8;color:#0F6E56;border-radius:10px;padding:10px 14px;font-size:13px;margin-bottom:12px;border-left:3px solid var(--teal);}
.fg{margin-bottom:12px;}
.fg label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);margin-bottom:5px;}
.fg input{width:100%;padding:10px 13px;border:1.5px solid var(--border);border-radius:10px;font-family:'Outfit',sans-serif;font-size:14px;color:var(--text);outline:none;transition:.18s;background:white;}
.fg input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,110,247,.12);}
.ferr{font-size:11px;color:var(--rose);margin-top:3px;display:none;}
/* Role selector */
.role-wrap{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;}
.role-card{border:1.5px solid var(--border);border-radius:12px;padding:14px 12px;cursor:pointer;text-align:center;transition:.18s;position:relative;}
.role-card:hover{border-color:#a0afff;background:#f5f7ff;}
.role-card.selected-student{border-color:var(--teal);background:#edfff8;}
.role-card.selected-admin{border-color:var(--primary);background:#eef1ff;}
.role-card input[type=radio]{position:absolute;opacity:0;width:0;height:0;}
.role-icon{font-size:26px;margin-bottom:6px;}
.role-name{font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;color:var(--text);}
.role-desc{font-size:11px;color:var(--sub);margin-top:2px;}
.role-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-top:5px;display:inline-block;}
.badge-student{background:#edfff8;color:#0F6E56;}
.badge-admin{background:#eef1ff;color:var(--primary);}
/* Admin key field */
.admin-key-wrap{overflow:hidden;max-height:0;transition:max-height .35s ease,opacity .3s;opacity:0;}
.admin-key-wrap.show{max-height:100px;opacity:1;}
.bar-wrap{height:4px;background:#eee;border-radius:2px;margin-top:5px;overflow:hidden;}
.bar-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0%;}
.str-lbl{font-size:11px;color:var(--sub);margin-top:3px;}
.btn{width:100%;background:var(--g1);color:white;border:none;border-radius:10px;padding:11px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:14px;cursor:pointer;margin-top:6px;transition:.18s;}
.btn:hover{opacity:.88;transform:translateY(-1px);}
.foot{text-align:center;margin-top:14px;font-size:13px;color:var(--sub);}
.foot a{color:var(--primary);font-weight:600;text-decoration:none;}
</style>
</head>
<body>
<div class="card">
  <script>
  // Show student fields on page load since student is default
  window.addEventListener('DOMContentLoaded', function(){
    var sf = document.getElementById('student-fields');
    if(sf){ sf.style.maxHeight='300px'; sf.style.opacity='1'; }
  });
  </script>
  <div class="logo">
    <div class="logo-mark"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
    <h2>Create Account</h2>
    <p>Join the School Management System</p>
  </div>

  <?php if($error): ?><div class="alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if($success): ?><div class="alert-ok"><?= htmlspecialchars($success) ?> <a href="login.php" style="color:var(--teal);font-weight:600;">Login here</a></div><?php endif; ?>

  <form id="regForm" method="POST" onsubmit="return validateRegister()">

    <!-- Role selector -->
    <div class="fg"><label>I am registering as</label></div>
    <div class="role-wrap">
      <label class="role-card selected-student" id="card-student" onclick="selectRole('student')">
        <input type="radio" name="role" value="student" checked>
        <div class="role-icon">&#127891;</div>
        <div class="role-name">Student</div>
        <div class="role-desc">Access courses & records</div>
        <span class="role-badge badge-student">Student</span>
      </label>
      <label class="role-card" id="card-admin" onclick="selectRole('admin')">
        <input type="radio" name="role" value="admin">
        <div class="role-icon">&#128274;</div>
        <div class="role-name">Admin</div>
        <div class="role-desc">Full system access</div>
        <span class="role-badge badge-admin">Requires key</span>
      </label>
    </div>

    <!-- Admin key field (hidden until admin selected) -->
    <div class="admin-key-wrap" id="admin-key-wrap">
      <div class="fg">
        <label>&#128272; Admin Secret Key</label>
        <input type="password" id="admin_key" name="admin_key" placeholder="Enter admin registration key">
        <div class="ferr" id="err-key">Admin key is required.</div>
      </div>
    </div>

    <!-- Extra fields for student (shown/hidden by JS) -->
    <div id="student-fields" style="overflow:hidden;max-height:0;opacity:0;transition:max-height .35s ease,opacity .3s;">
      <div class="fg">
        <label>Full Name</label>
        <input type="text" id="reg_fullname" name="fullname" placeholder="e.g. Alice Mwangi">
        <div class="ferr" id="err-fullname">Full name is required for students.</div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="fg">
          <label>Course</label>
          <input type="text" id="reg_course" name="course" placeholder="e.g. Computer Science">
        </div>
        <div class="fg">
          <label>Age</label>
          <input type="number" id="reg_age" name="age" placeholder="e.g. 20" min="10" max="100">
        </div>
      </div>
    </div>

    <div class="fg">
      <label>Username</label>
      <input type="text" id="reg_user" name="username" placeholder="Choose a username" required autofocus>
      <div class="ferr" id="err-user">Username is required.</div>
    </div>
    <div class="fg">
      <label>Email</label>
      <input type="email" id="reg_email" name="email" placeholder="your@email.com" oninput="validateEmail()" required>
      <div class="ferr" id="err-email">Enter a valid email address.</div>
    </div>
    <div class="fg">
      <label>Password</label>
      <input type="password" id="reg_pass" name="password" placeholder="Create a password" oninput="checkStrength()" required>
      <div class="bar-wrap"><div class="bar-fill" id="str-bar"></div></div>
      <div class="str-lbl" id="str-lbl"></div>
      <div class="ferr" id="err-pass">Password must be at least 6 characters.</div>
    </div>
    <div class="fg">
      <label>Confirm Password</label>
      <input type="password" id="reg_confirm" name="confirm" placeholder="Repeat password" oninput="checkMatch()" required>
      <div class="ferr" id="err-match">Passwords do not match.</div>
    </div>

    <button type="submit" class="btn" id="submit-btn">Create Student Account</button>
  </form>
  <div class="foot">Already have an account? <a href="login.php">Sign in</a></div>
</div>

<script>
var currentRole = 'student';

function selectRole(role){
  currentRole = role;
  document.getElementById('card-student').className = 'role-card ' + (role==='student' ? 'selected-student' : '');
  document.getElementById('card-admin').className   = 'role-card ' + (role==='admin'   ? 'selected-admin'   : '');
  var keyWrap = document.getElementById('admin-key-wrap');
  keyWrap.className = 'admin-key-wrap' + (role==='admin' ? ' show' : '');
  document.getElementById('submit-btn').textContent = role==='admin' ? 'Create Admin Account' : 'Create Student Account';
  // Show/hide student extra fields
  var sf = document.getElementById('student-fields');
  if(role === 'student'){
    sf.style.maxHeight = '300px';
    sf.style.opacity = '1';
  } else {
    sf.style.maxHeight = '0';
    sf.style.opacity = '0';
  }
  // Update radio
  document.querySelectorAll('input[name=role]').forEach(function(r){ r.checked = r.value === role; });
}

function validateEmail(){
  var e = document.getElementById('reg_email').value;
  var ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
  document.getElementById('err-email').style.display = (e && !ok) ? 'block' : 'none';
}

function checkStrength(){
  var p = document.getElementById('reg_pass').value;
  var bar = document.getElementById('str-bar'), lbl = document.getElementById('str-lbl');
  if(!p){ bar.style.width='0%'; lbl.textContent=''; return; }
  var s=0;
  if(p.length>=6)s++;if(p.length>=10)s++;if(/[A-Z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[^A-Za-z0-9]/.test(p))s++;
  var lvl=[{w:'20%',c:'#E24B4A',t:'Weak'},{w:'40%',c:'#f5a623',t:'Fair'},{w:'65%',c:'#4f6ef7',t:'Good'},{w:'85%',c:'#34c9a0',t:'Strong'},{w:'100%',c:'#1d9e75',t:'Very strong'}];
  var l = lvl[Math.min(s,lvl.length)-1];
  bar.style.width=l.w; bar.style.background=l.c; lbl.textContent=l.t; lbl.style.color=l.c;
  checkMatch();
}

function checkMatch(){
  var p=document.getElementById('reg_pass').value, c=document.getElementById('reg_confirm').value;
  if(c) document.getElementById('err-match').style.display = (p!==c)?'block':'none';
}

function validateRegister(){
  var u = document.getElementById('reg_user').value.trim();
  var e = document.getElementById('reg_email').value.trim();
  var p = document.getElementById('reg_pass').value;
  var c = document.getElementById('reg_confirm').value;
  var k = document.getElementById('admin_key').value.trim();
  var ep = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if(!u){ showPopup('Username is required.'); return false; }
  if(!ep.test(e)){ showPopup('Please enter a valid email address.'); return false; }
  if(p.length<6){ showPopup('Password must be at least 6 characters.'); return false; }
  if(p!==c){ showPopup('Passwords do not match.'); return false; }
  if(currentRole==='admin' && !k){ showPopup('Admin secret key is required to register as Admin.', 'warning'); return false; }
  if(currentRole==='student'){
    var fn = document.getElementById('reg_fullname').value.trim();
    var course = document.getElementById('reg_course').value.trim();
    var age = parseInt(document.getElementById('reg_age').value);
    if(!fn){ showPopup('Full name is required.', 'warning'); return false; }
    if(!course){ showPopup('Course is required.', 'warning'); return false; }
    if(isNaN(age)||age<10||age>100){ showPopup('Please enter a valid age.', 'warning'); return false; }
  }
  return true;
}

function showPopup(message, type){
  type = type || 'error';
  var existing = document.getElementById('custom-popup');
  if(existing) existing.remove();
  var colors = {
    error:   {border:'#f56b7c', icon:'&#10060;', title:'Error'},
    success: {border:'#34c9a0', icon:'&#10004;', title:'Success'},
    warning: {border:'#f5a623', icon:'&#9888;',  title:'Warning'},
  };
  var c = colors[type] || colors.error;
  var overlay = document.createElement('div');
  overlay.id = 'custom-popup';
  overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.35);z-index:9999;display:flex;align-items:center;justify-content:center;';
  overlay.innerHTML = '<div style="background:white;border-radius:16px;padding:28px 32px;max-width:380px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.18);border-top:4px solid '+c.border+';text-align:center;animation:popIn .25s ease;">'
    +'<div style="font-size:38px;margin-bottom:12px;">'+c.icon+'</div>'
    +'<div style="font-family:\'Plus Jakarta Sans\',sans-serif;font-size:16px;font-weight:700;color:#1a1d2e;margin-bottom:8px;">'+c.title+'</div>'
    +'<div style="font-size:14px;color:#6b7199;line-height:1.6;margin-bottom:20px;">'+message+'</div>'
    +'<button onclick="document.getElementById(\'custom-popup\').remove()" style="background:linear-gradient(135deg,#4f6ef7,#7c5bf5);color:white;border:none;border-radius:10px;padding:10px 28px;font-size:14px;font-weight:600;cursor:pointer;">OK</button>'
    +'</div>';
  overlay.addEventListener('click', function(e){ if(e.target===overlay) overlay.remove(); });
  document.body.appendChild(overlay);
  if(!document.getElementById('popup-styles')){
    var s=document.createElement('style');s.id='popup-styles';
    s.textContent='@keyframes popIn{from{opacity:0;transform:scale(.85) translateY(-20px)}to{opacity:1;transform:scale(1) translateY(0)}}';
    document.head.appendChild(s);
  }
}

</script>
</body>
</html>