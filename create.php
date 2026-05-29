<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name']);
    $email  = trim($_POST['email']);
    $course = trim($_POST['course']);
    $age    = (int)$_POST['age'];
    $stmt = $conn->prepare("INSERT INTO students (name, email, course, age) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $course, $age]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student — SchoolMS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eef0f7;--white:#fff;--primary:#4f6ef7;--pl:#eef1ff;
  --violet:#7c5bf5;--rose:#f56b7c;--teal:#34c9a0;--amber:#f5a623;
  --text:#1a1d2e;--sub:#6b7199;--border:#e4e7f2;
  --g1:linear-gradient(135deg,#4f6ef7,#7c5bf5);
  --shadow:0 2px 16px rgba(79,110,247,.10);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
.sidebar{width:225px;background:var(--white);position:fixed;height:100vh;display:flex;flex-direction:column;padding:24px 14px;border-right:1px solid var(--border);z-index:20;}
.logo{display:flex;align-items:center;gap:10px;padding:0 6px;margin-bottom:28px;}
.logo-mark{width:36px;height:36px;background:var(--g1);border-radius:12px;display:flex;align-items:center;justify-content:center;}
.logo-mark svg{width:20px;height:20px;fill:white;}
.logo-name{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:16px;}
.logo-name span{color:var(--primary);}
.nav-sec{font-size:10px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--sub);padding:0 10px;margin:8px 0 5px;}
.nav-link{display:flex;align-items:center;gap:11px;padding:10px 13px;border-radius:12px;font-size:14px;font-weight:500;color:var(--sub);text-decoration:none;transition:all .18s;margin-bottom:2px;}
.nav-link:hover{background:#f5f7ff;color:var(--primary);}
.nav-link.active{background:var(--pl);color:var(--primary);font-weight:600;}
.nav-link svg{width:17px;height:17px;flex-shrink:0;}
.main{margin-left:225px;flex:1;padding:28px 36px;display:flex;justify-content:center;align-items:flex-start;}
.form-card{background:var(--white);border-radius:24px;padding:32px 36px;width:100%;max-width:560px;box-shadow:var(--shadow);}
.form-card h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:800;margin-bottom:4px;}
.form-card p{font-size:13px;color:var(--sub);margin-bottom:28px;}
.form-group{margin-bottom:20px;}
.form-group label{display:block;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);margin-bottom:7px;}
.form-group input, .form-group select{width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:12px;font-family:'Outfit',sans-serif;font-size:14px;color:var(--text);outline:none;transition:.18s;background:var(--white);}
.form-group input:focus, .form-group select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,110,247,.12);}
.field-error{font-size:11.5px;color:var(--rose);margin-top:5px;display:none;}
/* Task 2: live preview */
.preview-box{background:#f8f9ff;border:1.5px dashed var(--border);border-radius:12px;padding:14px 16px;margin-bottom:24px;display:none;}
.preview-box h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;margin-bottom:8px;color:var(--sub);}
.preview-row{display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:4px;}
.preview-label{color:var(--sub);font-size:12px;width:60px;flex-shrink:0;}
.preview-val{font-weight:600;color:var(--text);}
.btn-row{display:flex;gap:12px;margin-top:8px;}
.btn-p{background:var(--g1);color:white;border:none;padding:12px 24px;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:14px;cursor:pointer;transition:.18s;flex:1;}
.btn-p:hover{opacity:.86;transform:translateY(-1px);}
.btn-sec{background:var(--pl);color:var(--primary);border:none;padding:12px 24px;border-radius:12px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;transition:.18s;}
.btn-sec:hover{background:#dde4ff;}
/* strength bar */
.strength-wrap{margin-top:6px;}
.strength-bar{height:4px;border-radius:2px;background:var(--border);overflow:hidden;margin-bottom:3px;}
.strength-fill{height:100%;width:0%;border-radius:2px;transition:.3s;}
.strength-lbl{font-size:11px;color:var(--sub);}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fu{animation:fadeUp .3s ease both;}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
    <span class="logo-name" style="color:blue">BOSNIA CHELSEA <span>MIS</span></span>
  </div>
  <div class="nav-sec">Main</div>
  <a class="nav-link" href="dashboard.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard</a>
  <a class="nav-link active" href="index.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Students</a>
  <a class="nav-link" href="logout.php"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>Logout</a>
</aside>

<main class="main">
  <div class="form-card fu">
    <h2>Add New Student</h2>
    <p>Fill in the details below to enrol a student.</p>

    <!-- Task 2: Live preview box -->
    <div class="preview-box" id="preview-box">
      <h4>Live Preview</h4>
      <div class="preview-row"><span class="preview-label">Name</span><span class="preview-val" id="pv-name">—</span></div>
      <div class="preview-row"><span class="preview-label">Email</span><span class="preview-val" id="pv-email">—</span></div>
      <div class="preview-row"><span class="preview-label">Course</span><span class="preview-val" id="pv-course">—</span></div>
      <div class="preview-row"><span class="preview-label">Age</span><span class="preview-val" id="pv-age">—</span></div>
    </div>

    <form id="addForm" method="POST" onsubmit="return validateForm()">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" id="f-name" name="name" placeholder="e.g. Alice Mwangi" oninput="updatePreview()" required>
        <div class="field-error" id="err-name">Name is required.</div>
      </div>
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" id="f-email" name="email" placeholder="e.g. alice@school.ac.ke" oninput="updatePreview();validateEmail()" required>
        <div class="field-error" id="err-email">Enter a valid email address.</div>
      </div>
      <div class="form-group">
        <label>Course</label>
        <input type="text" id="f-course" name="course" placeholder="e.g. Computer Science" oninput="updatePreview()" required>
        <div class="field-error" id="err-course">Course is required.</div>
      </div>
      <div class="form-group">
        <label>Age</label>
        <input type="number" id="f-age" name="age" placeholder="e.g. 20" min="10" max="100" oninput="updatePreview()" required>
        <div class="field-error" id="err-age">Enter a valid age (10–100).</div>
      </div>
      <div class="btn-row">
        <button type="submit" class="btn-p">Save Student</button>
        <a href="index.php" class="btn-sec">Cancel</a>
      </div>
    </form>
  </div>
</main>

<script>
// Task 2: Live text preview
function updatePreview(){
  var name   = document.getElementById('f-name').value;
  var email  = document.getElementById('f-email').value;
  var course = document.getElementById('f-course').value;
  var age    = document.getElementById('f-age').value;
  var box    = document.getElementById('preview-box');

  if(name || email || course || age){
    box.style.display = 'block';
    document.getElementById('pv-name').textContent   = name   || '—';
    document.getElementById('pv-email').textContent  = email  || '—';
    document.getElementById('pv-course').textContent = course || '—';
    document.getElementById('pv-age').textContent    = age    || '—';
  } else {
    box.style.display = 'none';
  }
}

// Task 1: Email validation
function validateEmail(){
  var email = document.getElementById('f-email').value;
  var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  document.getElementById('err-email').style.display = (!pattern.test(email) && email.length > 0) ? 'block' : 'none';
}

// Task 1: Full form validation before submit
function validateForm(){
  var name   = document.getElementById('f-name').value.trim();
  var email  = document.getElementById('f-email').value.trim();
  var course = document.getElementById('f-course').value.trim();
  var age    = parseInt(document.getElementById('f-age').value);
  var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var valid = true;

  document.getElementById('err-name').style.display   = !name   ? 'block' : 'none';
  document.getElementById('err-course').style.display = !course ? 'block' : 'none';
  document.getElementById('err-email').style.display  = !pattern.test(email) ? 'block' : 'none';
  document.getElementById('err-age').style.display    = (isNaN(age)||age<10||age>100) ? 'block' : 'none';

  if(!name || !course || !pattern.test(email) || isNaN(age) || age < 10 || age > 100){
    showPopup('Please fix the highlighted errors before saving.');
    valid = false;
  }
  return valid;
}

function showPopup(message, type){
  type = type || 'error';
  var existing = document.getElementById('custom-popup');
  if(existing) existing.remove();
  var colors = {
    error:   {bg:'#fff0f2', border:'#f56b7c', icon:'&#10060;', title:'Error',   text:'#A32D2D'},
    success: {bg:'#edfff8', border:'#34c9a0', icon:'&#10004;', title:'Success', text:'#0F6E56'},
    warning: {bg:'#fff8ec', border:'#f5a623', icon:'&#9888;',  title:'Warning', text:'#854F0B'},
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
    var s=document.createElement('style');
    s.id='popup-styles';
    s.textContent='@keyframes popIn{from{opacity:0;transform:scale(.85) translateY(-20px)}to{opacity:1;transform:scale(1) translateY(0)}}';
    document.head.appendChild(s);
  }
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
