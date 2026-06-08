<?php
$host = "localhost";
$dbname = "Students MIS";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalCourses  = $conn->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    $totalUsers    = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $avgAge        = round($conn->query("SELECT AVG(age) FROM students")->fetchColumn(), 1);

    $courses = $conn->query("SELECT c.id, c.name, c.teacher, c.file_count, c.status, COUNT(s.id) as total
        FROM courses c LEFT JOIN students s ON s.course = c.name
        GROUP BY c.id ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

    $lessons = $conn->query("SELECT l.*, c.name as course_name FROM lessons l
        JOIN courses c ON c.id = l.course_id ORDER BY l.scheduled_date DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

    $recentStudents = $conn->query("SELECT * FROM students ORDER BY id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

    $chartData = json_encode(array_map(fn($c) => ['course'=>$c['name'],'count'=>(int)$c['total']], $courses));

    // Results
    $results = $conn->query("SELECT r.*, s.name as student_name, c.name as course_name
        FROM results r
        JOIN students s ON s.id = r.student_id
        JOIN courses c ON c.id = r.course_id
        ORDER BY r.uploaded_at DESC")->fetchAll(PDO::FETCH_ASSOC);

    $students_list = $conn->query("SELECT id, name FROM students ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// ── Handle POST actions ──
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once __DIR__.'/db.php';
    // Re-connect for writes (same credentials)
    $wconn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $wconn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $action = $_POST['action'] ?? '';

    // Add Course
    if($action === 'add_course'){
        $name   = trim($_POST['cname']);
        $desc   = trim($_POST['cdesc']);
        $teacher= trim($_POST['cteacher']);
        $status = $_POST['cstatus'];
        $wconn->prepare("INSERT INTO courses (name,description,teacher,file_count,status) VALUES (?,?,?,0,?)")
              ->execute([$name,$desc,$teacher,$status]);
        header("Location: ".$_SERVER['PHP_SELF']."?page=courses&msg=Course+added");
        exit;
    }

    // Delete Course
    if($action === 'delete_course'){
        $wconn->prepare("DELETE FROM courses WHERE id=?")->execute([$_POST['cid']]);
        header("Location: ".$_SERVER['PHP_SELF']."?page=courses&msg=Course+deleted");
        exit;
    }

    // Upload Result
    if($action === 'upload_result'){
        $sid    = (int)$_POST['student_id'];
        $cid    = (int)$_POST['course_id'];
        $score  = (float)$_POST['score'];
        $grade  = trim($_POST['grade']);
        $remarks= trim($_POST['remarks']);
        $wconn->prepare("INSERT INTO results (student_id,course_id,score,grade,remarks) VALUES (?,?,?,?,?)
            ON DUPLICATE KEY UPDATE score=VALUES(score),grade=VALUES(grade),remarks=VALUES(remarks)")
              ->execute([$sid,$cid,$score,$grade,$remarks]);
        header("Location: ".$_SERVER['PHP_SELF']."?page=results&msg=Result+saved");
        exit;
    }

    // Delete Result
    if($action === 'delete_result'){
        $wconn->prepare("DELETE FROM results WHERE id=?")->execute([$_POST['rid']]);
        header("Location: ".$_SERVER['PHP_SELF']."?page=results&msg=Result+deleted");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Students MIS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eef0f7;--white:#ffffff;--primary:#4f6ef7;--pl:#eef1ff;
  --violet:#7c5bf5;--rose:#f56b7c;--teal:#34c9a0;--amber:#f5a623;
  --text:#1a1d2e;--sub:#6b7199;--border:#e4e7f2;
  --g1:linear-gradient(135deg,#4f6ef7,#7c5bf5);
  --g2:linear-gradient(135deg,#7c5bf5,#f56b7c);
  --g3:linear-gradient(135deg,#f56b7c,#f5a623);
  --g4:linear-gradient(135deg,#34c9a0,#4f6ef7);
  --shadow:0 2px 16px rgba(79,110,247,.10);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:225px;background:var(--white);position:fixed;height:100vh;display:flex;flex-direction:column;padding:24px 14px;border-right:1px solid var(--border);z-index:20;overflow-y:auto;}
.logo{display:flex;align-items:center;gap:10px;padding:0 6px;margin-bottom:28px;}
.logo-mark{width:36px;height:36px;background:var(--g1);border-radius:12px;display:flex;align-items:center;justify-content:center;}
.logo-mark svg{width:20px;height:20px;fill:white;}
.logo-name{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:16px;}
.logo-name span{color:var(--primary);}
.nav-sec{font-size:10px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--sub);padding:0 10px;margin:8px 0 5px;}
.nav-link{display:flex;align-items:center;gap:11px;padding:10px 13px;border-radius:12px;font-size:14px;font-weight:500;color:var(--sub);text-decoration:none;transition:all .18s;margin-bottom:2px;cursor:pointer;}
.nav-link:hover{background:#f5f7ff;color:var(--primary);}
.nav-link.active{background:var(--pl);color:var(--primary);font-weight:600;}
.nav-link svg{width:17px;height:17px;flex-shrink:0;}
.sidebar-help{margin-top:auto;background:linear-gradient(135deg,#eef1ff,#f5eeff);border-radius:16px;padding:16px 12px;text-align:center;flex-shrink:0;}
.help-ball{width:38px;height:38px;background:var(--g1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;}
.help-ball svg{width:18px;height:18px;fill:white;}
.sidebar-help h5{font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;margin-bottom:3px;}
.sidebar-help p{font-size:11px;color:var(--sub);line-height:1.4;}

/* MAIN */
.main{margin-left:225px;flex:1;display:flex;gap:20px;padding:24px 22px;}
.content{flex:1;min-width:0;}

/* PAGES */
.page{display:none;}
.page.active{display:block;}

/* topbar */
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.search{display:flex;align-items:center;gap:9px;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:10px 16px;width:280px;}
.search svg{width:15px;height:15px;flex-shrink:0;opacity:.4;}
.search input{border:none;outline:none;font-family:'Outfit',sans-serif;font-size:13.5px;color:var(--text);background:transparent;width:100%;}
.search input::placeholder{color:var(--sub);}
.topbar-r{display:flex;align-items:center;gap:10px;}
.date-lbl{font-size:12.5px;color:var(--sub);font-weight:500;}
.notif{width:38px;height:38px;background:var(--white);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;}
.notif svg{width:16px;height:16px;opacity:.6;}
.ndot{width:7px;height:7px;background:var(--rose);border-radius:50%;position:absolute;top:8px;right:8px;border:1.5px solid white;}

/* banner */
.banner{background:var(--white);border-radius:20px;padding:26px 28px;display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;box-shadow:var(--shadow);overflow:hidden;position:relative;}
.banner::before{content:'';position:absolute;width:200px;height:200px;background:radial-gradient(circle,#eef1ff,transparent 70%);border-radius:50%;right:150px;top:-60px;}
.banner h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:800;margin-bottom:5px;}
.banner p{font-size:13px;color:var(--sub);max-width:320px;line-height:1.55;margin-bottom:16px;}
.btn-p{background:var(--g1);color:white;border:none;padding:10px 20px;border-radius:11px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:13px;cursor:pointer;transition:.18s;}
.btn-p:hover{opacity:.86;transform:translateY(-1px);}
.banner-art{flex-shrink:0;width:120px;height:85px;}

/* stats */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin-bottom:22px;}
.stat{background:var(--white);border-radius:18px;padding:17px 18px;display:flex;align-items:center;gap:13px;box-shadow:var(--shadow);transition:.2s;cursor:pointer;}
.stat:hover{transform:translateY(-2px);}
.sicon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sicon svg{width:21px;height:21px;fill:white;}
.si1{background:var(--g1);}.si2{background:var(--g2);}.si3{background:var(--g3);}.si4{background:var(--g4);}
.sbody h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:800;line-height:1;}
.sbody p{font-size:11.5px;color:var(--sub);margin-top:3px;font-weight:500;}
.trend{font-size:11px;font-weight:600;margin-top:4px;}
.up{color:var(--teal);}.neu{color:var(--sub);}

/* section hdr */
.sec-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:13px;}
.sec-hdr h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;}
.view-all{font-size:12.5px;color:var(--primary);font-weight:600;text-decoration:none;cursor:pointer;}

/* course cards */
.courses-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:22px;}
.cc{border-radius:18px;padding:20px 18px;color:white;position:relative;overflow:hidden;cursor:pointer;transition:.2s;box-shadow:0 6px 24px rgba(0,0,0,.13);}
.cc:hover{transform:translateY(-3px);}
.cc:nth-child(1){background:var(--g1);}.cc:nth-child(2){background:var(--g2);}.cc:nth-child(3){background:var(--g3);}
.cc::after{content:'';position:absolute;bottom:-18px;right:-18px;width:80px;height:80px;background:rgba(255,255,255,.1);border-radius:50%;}
.cc::before{content:'';position:absolute;top:-28px;right:28px;width:65px;height:65px;background:rgba(255,255,255,.06);border-radius:50%;}
.cc-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;}
.cc-top h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;line-height:1.3;max-width:130px;}
.cc-badge{background:rgba(255,255,255,.22);padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;}
.cc-count{font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;margin-bottom:3px;}
.cc-sub{font-size:11.5px;opacity:.8;}
.cc-bar{height:4px;background:rgba(255,255,255,.2);border-radius:2px;margin-top:12px;overflow:hidden;}
.cc-fill{height:100%;background:rgba(255,255,255,.7);border-radius:2px;transition:width .6s ease;}
.cc-footer{display:flex;align-items:center;gap:6px;margin-top:10px;font-size:11.5px;opacity:.85;}
.cc-footer svg{width:13px;height:13px;fill:rgba(255,255,255,.85);}

/* table */
.tbl-wrap{background:var(--white);border-radius:20px;padding:18px 20px;box-shadow:var(--shadow);}
.tbl-filters{display:flex;align-items:center;gap:7px;margin-bottom:14px;flex-wrap:wrap;}
.fbtn{padding:6px 13px;border-radius:8px;font-size:12px;font-weight:600;border:1.5px solid var(--border);background:transparent;color:var(--sub);cursor:pointer;transition:.15s;font-family:'Outfit',sans-serif;}
.fbtn.active,.fbtn:hover{background:var(--pl);color:var(--primary);border-color:var(--pl);}
table{width:100%;border-collapse:collapse;}
thead th{text-align:left;font-size:11px;font-weight:700;color:var(--sub);text-transform:uppercase;letter-spacing:.6px;padding:0 10px 11px;border-bottom:1px solid var(--border);}
tbody tr{transition:.15s;cursor:pointer;}
tbody tr:hover{background:#f8f9ff;}
tbody td{padding:11px 10px;font-size:13.5px;border-bottom:1px solid var(--border);}
tbody tr:last-child td{border-bottom:none;}
.sname{display:flex;align-items:center;gap:10px;}
.av{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:white;flex-shrink:0;}
.badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.bb{background:#eef1ff;color:var(--primary);}
.bv{background:#f3eeff;color:var(--violet);}
.br{background:#fff0f2;color:var(--rose);}
.bt{background:#edfff8;color:var(--teal);}
.ba{background:#fff8ec;color:var(--amber);}

/* lessons table */
.payment-done{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--teal);}
.payment-pending{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--amber);}
.pay-dot{width:7px;height:7px;border-radius:50%;}

/* RIGHT PANEL */
.right-panel{width:262px;flex-shrink:0;display:flex;flex-direction:column;gap:16px;}
.profile-card{background:var(--white);border-radius:20px;padding:20px 16px;text-align:center;box-shadow:var(--shadow);}
.pav{width:66px;height:66px;border-radius:50%;background:var(--g1);margin:0 auto 10px;display:flex;align-items:center;justify-content:center;font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;font-weight:800;color:white;}
.profile-card h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;}
.profile-card .role{font-size:12px;color:var(--sub);margin:3px 0 12px;}
.btn-o{border:1.5px solid var(--primary);color:var(--primary);background:transparent;padding:7px 18px;border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;font-size:12px;cursor:pointer;transition:.18s;}
.btn-o:hover{background:var(--primary);color:white;}

.chart-card{background:var(--white);border-radius:20px;padding:18px 16px;box-shadow:var(--shadow);}
.chart-card h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;margin-bottom:12px;}
#donut-wrap{display:flex;justify-content:center;margin-bottom:12px;}
.legend{display:flex;flex-direction:column;gap:7px;}
.leg-row{display:flex;align-items:center;justify-content:space-between;font-size:12px;}
.leg-l{display:flex;align-items:center;gap:7px;color:var(--sub);}
.leg-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;}
.leg-v{font-weight:700;color:var(--text);}

.cal-card{background:var(--white);border-radius:20px;padding:16px;box-shadow:var(--shadow);}
.cal-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.cal-hdr h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;}
.cal-nav{width:26px;height:26px;border-radius:8px;background:var(--bg);border:none;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:.15s;}
.cal-nav:hover{background:var(--border);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;}
.cal-lbl{font-size:9.5px;font-weight:700;color:var(--sub);text-align:center;padding:3px 0;}
.cal-d{aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:7px;font-size:11px;cursor:pointer;transition:.15s;color:var(--text);}
.cal-d:hover{background:var(--bg);}
.cal-d.today{background:var(--primary);color:white;font-weight:700;}
.cal-d.ev{color:var(--primary);font-weight:600;position:relative;}
.cal-d.ev::after{content:'';position:absolute;bottom:2px;left:50%;transform:translateX(-50%);width:3px;height:3px;background:var(--primary);border-radius:50%;}
.cal-d.dim{color:var(--border);}

.rem-card{background:var(--white);border-radius:20px;padding:16px;box-shadow:var(--shadow);}
.rem-card h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;margin-bottom:10px;}
.rem-item{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);}
.rem-item:last-child{border-bottom:none;}
.rem-ico{width:30px;height:30px;border-radius:9px;background:var(--pl);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.rem-ico svg{width:13px;height:13px;fill:var(--primary);}
.rem-info h5{font-size:12px;font-weight:600;margin-bottom:1px;}
.rem-info p{font-size:10.5px;color:var(--sub);}

/* course detail page */
.back-btn{display:inline-flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:var(--primary);cursor:pointer;margin-bottom:18px;background:var(--pl);border:none;padding:8px 14px;border-radius:10px;font-family:'Outfit',sans-serif;}
.back-btn:hover{opacity:.8;}
.detail-header{background:var(--white);border-radius:20px;padding:24px 26px;margin-bottom:18px;box-shadow:var(--shadow);display:flex;align-items:center;justify-content:space-between;}
.detail-header h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:800;}
.detail-header p{font-size:13px;color:var(--sub);margin-top:4px;}

@keyframes fadeUp{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
.fu{animation:fadeUp .35s ease both;}
.d1{animation-delay:.06s;}.d2{animation-delay:.12s;}.d3{animation-delay:.18s;}.d4{animation-delay:.24s;}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
    <span class="logo-name" style="color:blue">BOSNIA CHELSEA <span>MIS</span></span>
  </div>

  <div class="nav-sec">Main</div>
  <a class="nav-link active" onclick="showPage('dashboard')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard
  </a>
  <a class="nav-link" onclick="showPage('students')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>Students
  </a>
  <a class="nav-link" onclick="showPage('courses')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>Courses
  </a>
  <a class="nav-link" onclick="showPage('lessons')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Schedule
  </a>
  <a class="nav-link" onclick="showPage('users')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>Users
  </a>

  <div class="nav-sec" style="margin-top:10px;">Account</div>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>Settings
  </a>

  <div class="sidebar-help">
    <div class="help-ball"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg></div>
    <h5>Need help?</h5>
    <p>Having trouble with the system?</p>
  </div>
</aside>

<main class="main">
<div class="content">

  <!-- TOPBAR (shared) -->
  <div class="topbar fu">
    <div class="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="#6b7199" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" id="search-input" placeholder="Search...">
    </div>
    <div class="topbar-r">
      <span class="date-lbl" id="js-date"></span>
      <div class="notif"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg><div class="ndot"></div></div>
    </div>
  </div>

  <!-- ══ PAGE: DASHBOARD ══ -->
  <div class="page active" id="page-dashboard">
    <div class="banner fu d1">
      <div>
        <h2>Welcome back, Admin 👋</h2>
        <p>You have <strong><?= $totalStudents ?> students</strong> enrolled across <strong><?= $totalCourses ?> courses</strong>. Here's your overview.</p>
        <button class="btn-p" onclick="showPage('students')">Manage Students</button>
      </div>
      <div class="banner-art">
        <svg viewBox="0 0 120 85" xmlns="http://www.w3.org/2000/svg">
          <rect x="10" y="44" width="50" height="34" rx="4" fill="#4f6ef7" opacity=".12"/>
          <rect x="16" y="38" width="50" height="34" rx="4" fill="#7c5bf5" opacity=".2"/>
          <rect x="22" y="32" width="50" height="36" rx="4" fill="url(#bk)"/>
          <defs><linearGradient id="bk" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#4f6ef7"/><stop offset="100%" stop-color="#7c5bf5"/></linearGradient></defs>
          <rect x="28" y="39" width="30" height="3" rx="1.5" fill="white" opacity=".5"/>
          <rect x="28" y="46" width="22" height="3" rx="1.5" fill="white" opacity=".3"/>
          <rect x="28" y="53" width="26" height="3" rx="1.5" fill="white" opacity=".3"/>
          <circle cx="88" cy="26" r="18" fill="#f56b7c" opacity=".15"/>
          <circle cx="88" cy="26" r="12" fill="#f56b7c" opacity=".3"/>
          <text x="88" y="31" text-anchor="middle" font-size="13" fill="#f56b7c" font-weight="bold" font-family="Plus Jakarta Sans">A+</text>
        </svg>
      </div>
    </div>

    <div class="stats fu d2">
      <div class="stat" onclick="showPage('students')">
        <div class="sicon si1"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
        <div class="sbody"><h3><?= $totalStudents ?></h3><p>Total Students</p><span class="trend up">↑ Enrolled</span></div>
      </div>
      <div class="stat" onclick="showPage('courses')">
        <div class="sicon si2"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
        <div class="sbody"><h3><?= $totalCourses ?></h3><p>Active Courses</p><span class="trend up">↑ Running</span></div>
      </div>
      <div class="stat" onclick="showPage('users')">
        <div class="sicon si3"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg></div>
        <div class="sbody"><h3><?= $totalUsers ?></h3><p>System Users</p><span class="trend neu">→ Registered</span></div>
      </div>
      <div class="stat">
        <div class="sicon si4"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="sbody"><h3><?= $avgAge ?></h3><p>Avg. Student Age</p><span class="trend neu">→ Years</span></div>
      </div>
    </div>

    <div class="sec-hdr fu d2">
      <h3>Courses</h3>
      <span class="view-all" onclick="showPage('courses')">View All ›</span>
    </div>
    <div class="courses-grid fu d2">
      <?php foreach (array_slice($courses,0,3) as $i=>$c):
        $pct = $totalStudents > 0 ? round(($c['total']/$totalStudents)*100) : 0; ?>
      <div class="cc" onclick="showCourse(<?= $c['id'] ?>,'<?= htmlspecialchars(addslashes($c['name'])) ?>')">
        <div class="cc-top">
          <h4><?= htmlspecialchars($c['name']) ?></h4>
          <span class="cc-badge"><?= htmlspecialchars($c['status']) ?></span>
        </div>
        <div class="cc-count"><?= $c['total'] ?></div>
        <div class="cc-sub">students enrolled</div>
        <?php if($c['teacher']): ?><div class="cc-footer"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg><?= htmlspecialchars($c['teacher']) ?></div><?php endif; ?>
        <div class="cc-bar"><div class="cc-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="sec-hdr fu d3"><h3>Recent Students</h3><span class="view-all" onclick="showPage('students')">View All ›</span></div>
    <div class="tbl-wrap fu d3">
      <div class="tbl-filters" id="dash-filters">
        <button class="fbtn active" onclick="filterTable('all',this)">All</button>
        <?php foreach($courses as $c): ?>
        <button class="fbtn" onclick="filterTable('<?= htmlspecialchars(addslashes($c['name'])) ?>',this)"><?= htmlspecialchars($c['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <table id="students-table">
        <thead><tr><th>Student</th><th>Email</th><th>Course</th><th>Age</th></tr></thead>
        <tbody>
          <?php
          $avColors=['#4f6ef7','#7c5bf5','#f56b7c','#34c9a0','#f5a623','#6b7199','#e07038','#2d9fd8'];
          $bClasses=['bb','bv','br','bt','ba'];
          foreach($recentStudents as $idx=>$s):
            $parts=explode(' ',trim($s['name']));
            $ini=strtoupper(substr($parts[0],0,1)).(isset($parts[1])?strtoupper(substr($parts[1],0,1)):'');
            $col=$avColors[$idx%count($avColors)];
            $bc=$bClasses[$idx%count($bClasses)];
          ?>
          <tr data-course="<?= htmlspecialchars($s['course']) ?>" onclick="showStudentDetail(<?= $s['id'] ?>)">
            <td><div class="sname"><div class="av" style="background:<?= $col ?>"><?= $ini ?></div><div><div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($s['name']) ?></div><div style="font-size:11px;color:var(--sub);">ID #<?= str_pad($s['id'],4,'0',STR_PAD_LEFT) ?></div></div></div></td>
            <td style="color:var(--sub);font-size:13px;"><?= htmlspecialchars($s['email']) ?></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['course']) ?></span></td>
            <td style="font-weight:600;"><?= $s['age'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ PAGE: STUDENTS ══ -->
  <div class="page" id="page-students">
    <div class="sec-hdr fu"><h3>All Students</h3></div>
    <div class="tbl-wrap fu d1">
      <div class="tbl-filters">
        <button class="fbtn active" onclick="filterTable2('all',this)">All</button>
        <?php foreach($courses as $c): ?>
        <button class="fbtn" onclick="filterTable2('<?= htmlspecialchars(addslashes($c['name'])) ?>',this)"><?= htmlspecialchars($c['name']) ?></button>
        <?php endforeach; ?>
      </div>
      <table id="all-students-table">
        <thead><tr><th>Student</th><th>Email</th><th>Course</th><th>Age</th></tr></thead>
        <tbody>
          <?php foreach($recentStudents as $idx=>$s):
            $parts=explode(' ',trim($s['name']));
            $ini=strtoupper(substr($parts[0],0,1)).(isset($parts[1])?strtoupper(substr($parts[1],0,1)):'');
            $col=$avColors[$idx%count($avColors)];
            $bc=$bClasses[$idx%count($bClasses)]; ?>
          <tr data-course="<?= htmlspecialchars($s['course']) ?>">
            <td><div class="sname"><div class="av" style="background:<?= $col ?>"><?= $ini ?></div><div><div style="font-weight:600;font-size:13px;"><?= htmlspecialchars($s['name']) ?></div><div style="font-size:11px;color:var(--sub);">ID #<?= str_pad($s['id'],4,'0',STR_PAD_LEFT) ?></div></div></div></td>
            <td style="color:var(--sub);font-size:13px;"><?= htmlspecialchars($s['email']) ?></td>
            <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['course']) ?></span></td>
            <td style="font-weight:600;"><?= $s['age'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ PAGE: RESULTS ══ -->
  <div id="page-results" class="page" style="display:none">
    <div class="page-header">
      <div><h2 class="page-title">Results</h2><p class="page-sub">Upload and manage student results</p></div>
    </div>
    <?php if(isset($_GET['msg'])): ?>
    <div style="background:#edfff8;border:1px solid #34c9a0;border-radius:10px;padding:10px 16px;margin-bottom:16px;color:#0F6E56;font-size:13px;">&#10004; <?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <!-- Upload Result Form -->
    <div style="background:var(--white);border-radius:18px;padding:22px 24px;box-shadow:var(--shadow);margin-bottom:22px;">
      <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;margin-bottom:16px;">&#128196; Upload Result</h3>
      <form method="POST" onsubmit="return validateResult()">
        <input type="hidden" name="action" value="upload_result">
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;">
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);display:block;margin-bottom:5px;">Student</label>
            <select name="student_id" id="res-student" style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Outfit',sans-serif;font-size:13px;outline:none;background:white;">
              <option value="">Select student...</option>
              <?php foreach($students_list as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);display:block;margin-bottom:5px;">Course</label>
            <select name="course_id" id="res-course" style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Outfit',sans-serif;font-size:13px;outline:none;background:white;">
              <option value="">Select course...</option>
              <?php foreach($courses as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);display:block;margin-bottom:5px;">Score (%)</label>
            <input type="number" name="score" id="res-score" min="0" max="100" placeholder="e.g. 85" style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Outfit',sans-serif;font-size:13px;outline:none;">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);display:block;margin-bottom:5px;">Grade</label>
            <select name="grade" style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Outfit',sans-serif;font-size:13px;outline:none;background:white;">
              <option value="A">A — Excellent (70-100%)</option>
              <option value="B">B — Good (60-69%)</option>
              <option value="C">C — Average (50-59%)</option>
              <option value="D">D — Below Average (40-49%)</option>
              <option value="F">F — Fail (0-39%)</option>
            </select>
          </div>
          <div>
            <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--sub);display:block;margin-bottom:5px;">Remarks</label>
            <input type="text" name="remarks" placeholder="e.g. Excellent performance" style="width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-family:'Outfit',sans-serif;font-size:13px;outline:none;">
          </div>
        </div>
        <button type="submit" style="background:var(--g1);color:white;border:none;border-radius:10px;padding:10px 24px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:13px;cursor:pointer;">Upload Result</button>
      </form>
    </div>

    <!-- Results Table -->
    <div style="background:var(--white);border-radius:18px;padding:18px 20px;box-shadow:var(--shadow);">
      <h3 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;margin-bottom:14px;">All Results</h3>
      <?php if(empty($results)): ?>
      <p style="text-align:center;color:var(--sub);padding:24px;font-size:13px;">No results uploaded yet.</p>
      <?php else: ?>
      <table style="width:100%;border-collapse:collapse;">
        <thead style="background:#f7f8ff;">
          <tr>
            <th style="padding:10px 12px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);">Student</th>
            <th style="padding:10px 12px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);">Course</th>
            <th style="padding:10px 12px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);">Score</th>
            <th style="padding:10px 12px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);">Grade</th>
            <th style="padding:10px 12px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);">Remarks</th>
            <th style="padding:10px 12px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($results as $r):
            $gc = ['A'=>'#edfff8','B'=>'#eef1ff','C'=>'#fff8ec','D'=>'#fff0f2','F'=>'#fff0f2'];
            $gt = ['A'=>'#0F6E56','B'=>'#4f6ef7','C'=>'#854F0B','D'=>'#A32D2D','F'=>'#A32D2D'];
            $bg = $gc[$r['grade']] ?? '#f5f5f5';
            $tc = $gt[$r['grade']] ?? '#333';
          ?>
          <tr style="border-bottom:1px solid var(--border);">
            <td style="padding:10px 12px;font-size:13px;font-weight:600;"><?= htmlspecialchars($r['student_name']) ?></td>
            <td style="padding:10px 12px;font-size:13px;color:var(--sub);"><?= htmlspecialchars($r['course_name']) ?></td>
            <td style="padding:10px 12px;font-size:13px;font-weight:700;"><?= $r['score'] ?>%</td>
            <td style="padding:10px 12px;">
              <span style="background:<?= $bg ?>;color:<?= $tc ?>;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;"><?= $r['grade'] ?></span>
            </td>
            <td style="padding:10px 12px;font-size:13px;color:var(--sub);"><?= htmlspecialchars($r['remarks']) ?></td>
            <td style="padding:10px 12px;">
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this result?')">
                <input type="hidden" name="action" value="delete_result">
                <input type="hidden" name="rid" value="<?= $r['id'] ?>">
                <button type="submit" style="background:#fff0f2;color:var(--rose);border:none;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- ══ PAGE: COURSES ══ -->
  <div class="page" id="page-courses">
    <div class="sec-hdr fu"><h3>All Courses</h3></div>
    <div class="courses-grid fu d1" style="grid-template-columns:repeat(3,1fr);">
      <?php foreach($courses as $i=>$c):
        $pct=$totalStudents>0?round(($c['total']/$totalStudents)*100):0;
        $grads=['background:var(--g1)','background:var(--g2)','background:var(--g3)','background:var(--g4)'];
        $g=$grads[$i%count($grads)]; ?>
      <div class="cc" style="<?= $g ?>" onclick="showCourse(<?= $c['id'] ?>,'<?= htmlspecialchars(addslashes($c['name'])) ?>')">
        <div class="cc-top">
          <h4><?= htmlspecialchars($c['name']) ?></h4>
          <span class="cc-badge"><?= htmlspecialchars($c['status']) ?></span>
        </div>
        <div class="cc-count"><?= $c['total'] ?></div>
        <div class="cc-sub">students enrolled</div>
        <?php if($c['teacher']): ?><div class="cc-footer"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg><?= htmlspecialchars($c['teacher']) ?></div><?php endif; ?>
        <div class="cc-bar"><div class="cc-fill" style="width:<?= $pct ?>%"></div></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ══ PAGE: COURSE DETAIL ══ -->
  <div class="page" id="page-course-detail">
    <button class="back-btn" onclick="showPage('courses')">← Back to Courses</button>
    <div class="detail-header fu">
      <div>
        <h2 id="detail-course-name"></h2>
        <p id="detail-course-sub"></p>
      </div>
      <span class="badge bb" style="font-size:13px;padding:7px 16px;">Active</span>
    </div>
    <div class="tbl-wrap fu d1">
      <table id="detail-students-table">
        <thead><tr><th>Student</th><th>Email</th><th>Age</th></tr></thead>
        <tbody id="detail-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- ══ PAGE: LESSONS ══ -->
  <div class="page" id="page-lessons">
    <div class="sec-hdr fu"><h3>Schedule / Lessons</h3></div>
    <div class="tbl-wrap fu d1">
      <table>
        <thead><tr><th>Course</th><th>Title</th><th>Teacher</th><th>Date</th><th>Payment</th></tr></thead>
        <tbody>
          <?php foreach($lessons as $l): ?>
          <tr>
            <td><span class="badge bb"><?= htmlspecialchars($l['course_name']) ?></span></td>
            <td style="font-weight:600;"><?= htmlspecialchars($l['title']) ?></td>
            <td style="color:var(--sub);"><?= htmlspecialchars($l['teacher'] ?? '—') ?></td>
            <td><?= $l['scheduled_date'] ? date('d M Y', strtotime($l['scheduled_date'])) : '—' ?></td>
            <td>
              <?php if($l['payment_status']==='Done'): ?>
                <span class="payment-done"><span class="pay-dot" style="background:var(--teal)"></span>Done</span>
              <?php else: ?>
                <span class="payment-pending"><span class="pay-dot" style="background:var(--amber)"></span>Pending</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ PAGE: USERS ══ -->
  <div class="page" id="page-users">
    <div class="sec-hdr fu"><h3>System Users</h3></div>
    <div class="tbl-wrap fu d1">
      <table>
        <thead><tr><th>#</th><th>User Info</th></tr></thead>
        <tbody>
          <?php
          $users = $conn->query("SELECT * FROM users LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
          foreach($users as $idx=>$u):
            $cols = array_keys($u);
          ?>
          <tr>
            <td style="color:var(--sub);font-size:12px;"><?= $idx+1 ?></td>
            <td>
              <?php foreach($cols as $col): if($col==='id') continue; ?>
              <span style="margin-right:12px;font-size:13px;"><strong><?= $col ?>:</strong> <?= htmlspecialchars($u[$col]) ?></span>
              <?php endforeach; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /content -->

<!-- RIGHT PANEL -->
<aside class="right-panel">
  <div class="profile-card fu d1">
    <div class="pav">A</div>
    <h3>Administrator</h3>
    <p class="role">System Admin</p>
    <button class="btn-o">Edit Profile</button>
  </div>

  <div class="chart-card fu d2">
    <h4>Enrollment by Course</h4>
    <div id="donut-wrap"><canvas id="donut" width="120" height="120"></canvas></div>
    <div class="legend" id="legend"></div>
  </div>

  <div class="cal-card fu d3">
    <div class="cal-hdr">
      <button class="cal-nav" id="cal-prev">‹</button>
      <h4 id="cal-label"></h4>
      <button class="cal-nav" id="cal-next">›</button>
    </div>
    <div class="cal-grid" id="cal-grid"></div>
  </div>

  <div class="rem-card fu d4">
    <h4>Reminders</h4>
    <?php foreach(array_slice($recentStudents,0,3) as $s): ?>
    <div class="rem-item">
      <div class="rem-ico"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
      <div class="rem-info"><h5><?= htmlspecialchars($s['name']) ?></h5><p><?= htmlspecialchars($s['course']) ?> · enrolled</p></div>
    </div>
    <?php endforeach; ?>
  </div>
</aside>
</main>

<script>
// ── All student data for JS filtering ──
const allStudents = <?= json_encode($recentStudents) ?>;
const avColors = ['#4f6ef7','#7c5bf5','#f56b7c','#34c9a0','#f5a623','#6b7199','#e07038','#2d9fd8'];
const bClasses = ['bb','bv','br','bt','ba'];

// ── Page routing ──
function showPage(name){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.nav-link').forEach(l=>l.classList.remove('active'));
  document.getElementById('page-'+name).classList.add('active');
  document.querySelector(`.nav-link[onclick*="${name}"]`)?.classList.add('active');
  document.getElementById('search-input').value='';
}

// ── Course detail ──
function showCourse(id, name){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.getElementById('page-course-detail').classList.add('active');
  document.getElementById('detail-course-name').textContent = name;
  const filtered = allStudents.filter(s => s.course === name);
  document.getElementById('detail-course-sub').textContent = filtered.length + ' students enrolled in this course';
  const tbody = document.getElementById('detail-tbody');
  tbody.innerHTML = filtered.length ? filtered.map((s,i)=>{
    const parts = s.name.trim().split(' ');
    const ini = parts[0][0].toUpperCase()+(parts[1]?parts[1][0].toUpperCase():'');
    const col = avColors[i % avColors.length];
    return `<tr>
      <td><div class="sname"><div class="av" style="background:${col}">${ini}</div>
      <div><div style="font-weight:600;font-size:13px;">${s.name}</div>
      <div style="font-size:11px;color:var(--sub);">ID #${String(s.id).padStart(4,'0')}</div></div></div></td>
      <td style="color:var(--sub);font-size:13px;">${s.email}</td>
      <td style="font-weight:600;">${s.age}</td></tr>`;
  }).join('') : '<tr><td colspan="3" style="text-align:center;color:var(--sub);padding:20px;">No students in this course</td></tr>';
}

// ── Table filter ──
function filterTable(course, btn){
  document.querySelectorAll('#dash-filters .fbtn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#students-table tbody tr').forEach(tr=>{
    tr.style.display = (course==='all'||tr.dataset.course===course)?'':'none';
  });
}
function filterTable2(course, btn){
  document.querySelectorAll('#page-students .fbtn').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#all-students-table tbody tr').forEach(tr=>{
    tr.style.display = (course==='all'||tr.dataset.course===course)?'':'none';
  });
}

// ── Search ──
document.getElementById('search-input').addEventListener('input', function(){
  const q = this.value.toLowerCase();
  document.querySelectorAll('#students-table tbody tr, #all-students-table tbody tr').forEach(tr=>{
    tr.style.display = tr.textContent.toLowerCase().includes(q)?'':'none';
  });
});

// ── Date ──
document.getElementById('js-date').textContent = new Date().toLocaleDateString('en-US',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

// ── Calendar ──
const MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS=['Mo','Tu','We','Th','Fr','Sa','Su'];
const now=new Date(); let cy=now.getFullYear(),cm=now.getMonth();
function buildCal(){
  document.getElementById('cal-label').textContent=MONTHS[cm]+' '+cy;
  const g=document.getElementById('cal-grid'); g.innerHTML='';
  DAYS.forEach(d=>{const e=document.createElement('div');e.className='cal-lbl';e.textContent=d;g.appendChild(e);});
  const first=new Date(cy,cm,1).getDay(), off=first===0?6:first-1;
  const dim=new Date(cy,cm,0).getDate(), total=new Date(cy,cm+1,0).getDate();
  for(let i=off;i>0;i--){const e=document.createElement('div');e.className='cal-d dim';e.textContent=dim-i+1;g.appendChild(e);}
  for(let d=1;d<=total;d++){
    const e=document.createElement('div');e.className='cal-d';
    if(d===now.getDate()&&cm===now.getMonth()&&cy===now.getFullYear())e.classList.add('today');
    else if([5,12,19,25].includes(d))e.classList.add('ev');
    e.textContent=d;g.appendChild(e);
  }
}
buildCal();
document.getElementById('cal-prev').onclick=()=>{cm--;if(cm<0){cm=11;cy--;}buildCal();};
document.getElementById('cal-next').onclick=()=>{cm++;if(cm>11){cm=0;cy++;}buildCal();};

// ── Donut chart ──
const chartData=<?= $chartData ?>;
const colors=['#4f6ef7','#7c5bf5','#f56b7c','#34c9a0','#f5a623','#2d9fd8'];
const canvas=document.getElementById('donut'),ctx=canvas.getContext('2d');
const total=chartData.reduce((s,d)=>s+d.count,0);
let sa=-Math.PI/2;
chartData.forEach((d,i)=>{
  const sl=(d.count/Math.max(total,1))*(2*Math.PI);
  ctx.beginPath();ctx.moveTo(60,60);ctx.arc(60,60,50,sa,sa+sl);ctx.closePath();
  ctx.fillStyle=colors[i%colors.length];ctx.fill();sa+=sl;
});
ctx.beginPath();ctx.arc(60,60,28,0,2*Math.PI);ctx.fillStyle='white';ctx.fill();
ctx.fillStyle='#1a1d2e';ctx.font='bold 12px Plus Jakarta Sans';ctx.textAlign='center';ctx.textBaseline='middle';
ctx.fillText(total,60,54);ctx.font='9px Outfit';ctx.fillStyle='#6b7199';ctx.fillText('students',60,66);
const leg=document.getElementById('legend');
chartData.forEach((d,i)=>{
  const row=document.createElement('div');row.className='leg-row';
  row.innerHTML=`<span class="leg-l"><span class="leg-dot" style="background:${colors[i%colors.length]}"></span>${d.course}</span><span class="leg-v">${d.count}</span>`;
  leg.appendChild(row);
});

// ── Results & Course validation ──
function validateResult(){
  var s = document.getElementById('res-student').value;
  var c = document.getElementById('res-course').value;
  var sc = document.getElementById('res-score').value;
  if(!s){ showPopup('Please select a student.','warning'); return false; }
  if(!c){ showPopup('Please select a course.','warning'); return false; }
  if(sc===''||sc<0||sc>100){ showPopup('Score must be between 0 and 100.','warning'); return false; }
  return true;
}
function validateCourse(){
  var n = document.getElementById('c-name').value.trim();
  var t = document.getElementById('c-teacher').value.trim();
  if(!n){ showPopup('Course name is required.','warning'); return false; }
  if(!t){ showPopup('Teacher name is required.','warning'); return false; }
  return true;
}
// Auto-grade based on score
document.addEventListener('DOMContentLoaded', function(){
  var scoreInput = document.getElementById('res-score');
  if(scoreInput){
    scoreInput.addEventListener('input', function(){
      var sc = parseFloat(this.value);
      var gradeSelect = document.querySelector('select[name=grade]');
      if(!gradeSelect) return;
      if(sc>=70) gradeSelect.value='A';
      else if(sc>=60) gradeSelect.value='B';
      else if(sc>=50) gradeSelect.value='C';
      else if(sc>=40) gradeSelect.value='D';
      else gradeSelect.value='F';
    });
  }
  // Auto-open page from URL param
  var urlPage = new URLSearchParams(window.location.search).get('page');
  if(urlPage) showPage(urlPage);
});

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

<?php if(!empty($_SESSION['just_logged_in'])): $_SESSION['just_logged_in']=false; ?>
<script>
window.addEventListener('DOMContentLoaded', function(){
  showPopup('Welcome back, <strong><?= htmlspecialchars($_SESSION["username"]) ?></strong>! Logged in as <strong><?= ucfirst($_SESSION["role"]) ?></strong>.', 'success');
});
</script>
<?php endif; ?>
</body>
</html>