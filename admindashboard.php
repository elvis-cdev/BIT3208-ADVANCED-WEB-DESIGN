<?php
$host = "localhost";
$dbname = "Students MIS";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $totalStudents   = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $totalCourses    = $conn->query("SELECT COUNT(DISTINCT course) FROM students")->fetchColumn();
    $totalUsers      = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $avgAge          = round($conn->query("SELECT AVG(age) FROM students")->fetchColumn(), 1);

    // Students per course
    $coursesStmt = $conn->query("SELECT course, COUNT(*) as total FROM students GROUP BY course ORDER BY total DESC");
    $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent students
    $recentStmt = $conn->query("SELECT * FROM students ORDER BY id DESC LIMIT 8");
    $recentStudents = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

    // Course enrollment breakdown for chart
    $chartData = json_encode(array_map(fn($c) => ['course' => $c['course'], 'count' => (int)$c['total']], $courses));

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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
:root {
  --bg: #eef0f7;
  --white: #ffffff;
  --sidebar: #ffffff;
  --primary: #4f6ef7;
  --primary-light: #eef1ff;
  --violet: #7c5bf5;
  --rose: #f56b7c;
  --teal: #34c9a0;
  --amber: #f5a623;
  --text: #1a1d2e;
  --sub: #6b7199;
  --border: #e4e7f2;
  --g1: linear-gradient(135deg,#4f6ef7,#7c5bf5);
  --g2: linear-gradient(135deg,#7c5bf5,#f56b7c);
  --g3: linear-gradient(135deg,#f56b7c,#f5a623);
  --g4: linear-gradient(135deg,#34c9a0,#4f6ef7);
  --shadow: 0 2px 16px rgba(79,110,247,.10);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

/* ── SIDEBAR ── */
.sidebar{width:230px;background:var(--white);position:fixed;height:100vh;display:flex;flex-direction:column;padding:26px 16px;border-right:1px solid var(--border);z-index:20;}
.logo{display:flex;align-items:center;gap:10px;padding:0 6px;margin-bottom:32px;}
.logo-mark{width:36px;height:36px;background:var(--g1);border-radius:12px;display:flex;align-items:center;justify-content:center;}
.logo-mark svg{width:20px;height:20px;fill:white;}
.logo-name{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:16px;}
.logo-name span{color:var(--primary);}

.nav-section{font-size:10px;font-weight:700;letter-spacing:1.1px;text-transform:uppercase;color:var(--sub);padding:0 10px;margin:6px 0 6px;}
.nav-link{display:flex;align-items:center;gap:11px;padding:10px 13px;border-radius:12px;font-size:14px;font-weight:500;color:var(--sub);text-decoration:none;transition:all .18s;margin-bottom:2px;}
.nav-link:hover{background:#f5f7ff;color:var(--primary);}
.nav-link.active{background:var(--primary-light);color:var(--primary);font-weight:600;}
.nav-link svg{width:17px;height:17px;flex-shrink:0;}

.sidebar-help{margin-top:auto;background:linear-gradient(135deg,#eef1ff,#f5eeff);border-radius:16px;padding:18px 14px;text-align:center;}
.help-ball{width:40px;height:40px;background:var(--g1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;}
.help-ball svg{width:18px;height:18px;fill:white;}
.sidebar-help h5{font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;margin-bottom:4px;}
.sidebar-help p{font-size:11px;color:var(--sub);line-height:1.45;}

/* ── MAIN ── */
.main{margin-left:230px;flex:1;display:flex;gap:22px;padding:26px 24px;}
.content{flex:1;min-width:0;}

/* topbar */
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;}
.search{display:flex;align-items:center;gap:9px;background:var(--white);border:1px solid var(--border);border-radius:12px;padding:10px 16px;width:290px;}
.search svg{width:15px;height:15px;flex-shrink:0;opacity:.45;}
.search input{border:none;outline:none;font-family:'Outfit',sans-serif;font-size:13.5px;color:var(--text);background:transparent;width:100%;}
.search input::placeholder{color:var(--sub);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.date-badge{font-size:12.5px;color:var(--sub);font-weight:500;}
.notif-btn{width:38px;height:38px;background:var(--white);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;}
.notif-btn svg{width:16px;height:16px;opacity:.6;}
.notif-dot{width:7px;height:7px;background:var(--rose);border-radius:50%;position:absolute;top:8px;right:8px;border:1.5px solid white;}

/* banner */
.banner{background:var(--white);border-radius:20px;padding:28px 30px;display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;box-shadow:var(--shadow);overflow:hidden;position:relative;}
.banner::before{content:'';position:absolute;width:220px;height:220px;background:radial-gradient(circle,#eef1ff 0%,transparent 70%);border-radius:50%;right:160px;top:-60px;}
.banner-text h2{font-family:'Plus Jakarta Sans',sans-serif;font-size:21px;font-weight:800;margin-bottom:5px;}
.banner-text p{font-size:13px;color:var(--sub);max-width:340px;line-height:1.55;margin-bottom:18px;}
.btn-primary{background:var(--g1);color:white;border:none;padding:10px 22px;border-radius:11px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:700;font-size:13px;cursor:pointer;transition:.18s;text-decoration:none;display:inline-block;}
.btn-primary:hover{opacity:.86;transform:translateY(-1px);}
.banner-art{flex-shrink:0;width:130px;height:90px;}

/* stat cards */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px;}
.stat{background:var(--white);border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px;box-shadow:var(--shadow);transition:.2s;}
.stat:hover{transform:translateY(-2px);}
.stat-icon{width:46px;height:46px;border-radius:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-icon svg{width:21px;height:21px;fill:white;}
.si1{background:var(--g1);}
.si2{background:var(--g2);}
.si3{background:var(--g3);}
.si4{background:var(--g4);}
.stat-body h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:22px;font-weight:800;line-height:1;}
.stat-body p{font-size:11.5px;color:var(--sub);margin-top:3px;font-weight:500;}
.stat-body .trend{font-size:11px;font-weight:600;margin-top:4px;}
.trend.up{color:var(--teal);}
.trend.neutral{color:var(--sub);}

/* section hdr */
.sec-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.sec-hdr h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;}
.view-all{font-size:12.5px;color:var(--primary);font-weight:600;text-decoration:none;display:flex;align-items:center;gap:3px;}

/* course cards */
.courses-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px;}
.course-card{border-radius:18px;padding:22px 20px;color:white;position:relative;overflow:hidden;cursor:pointer;transition:.2s;box-shadow:0 6px 24px rgba(0,0,0,.13);}
.course-card:hover{transform:translateY(-3px);}
.course-card:nth-child(1){background:var(--g1);}
.course-card:nth-child(2){background:var(--g2);}
.course-card:nth-child(3){background:var(--g3);}
.course-card::after{content:'';position:absolute;bottom:-18px;right:-18px;width:90px;height:90px;background:rgba(255,255,255,.1);border-radius:50%;}
.course-card::before{content:'';position:absolute;top:-30px;right:30px;width:70px;height:70px;background:rgba(255,255,255,.06);border-radius:50%;}
.cc-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.cc-top h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14.5px;font-weight:700;line-height:1.3;}
.cc-badge{background:rgba(255,255,255,.2);padding:4px 9px;border-radius:20px;font-size:11px;font-weight:600;}
.cc-count{font-family:'Plus Jakarta Sans',sans-serif;font-size:28px;font-weight:800;margin-bottom:4px;}
.cc-sub{font-size:11.5px;opacity:.8;}
.cc-bar{height:4px;background:rgba(255,255,255,.2);border-radius:2px;margin-top:14px;overflow:hidden;}
.cc-bar-fill{height:100%;background:rgba(255,255,255,.7);border-radius:2px;transition:width .6s ease;}

/* table */
.table-wrap{background:var(--white);border-radius:20px;padding:20px 22px;box-shadow:var(--shadow);}
.tbl-filters{display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.filter-btn{padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;border:1.5px solid var(--border);background:transparent;color:var(--sub);cursor:pointer;transition:.15s;font-family:'Outfit',sans-serif;}
.filter-btn.active,.filter-btn:hover{background:var(--primary-light);color:var(--primary);border-color:var(--primary-light);}
table{width:100%;border-collapse:collapse;}
thead th{text-align:left;font-size:11px;font-weight:700;color:var(--sub);text-transform:uppercase;letter-spacing:.6px;padding:0 10px 12px;border-bottom:1px solid var(--border);}
tbody tr{transition:.15s;}
tbody tr:hover{background:#f8f9ff;}
tbody td{padding:12px 10px;font-size:13.5px;border-bottom:1px solid var(--border);}
tbody tr:last-child td{border-bottom:none;}
.s-name{display:flex;align-items:center;gap:10px;}
.av{width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:white;flex-shrink:0;}
.badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.b-blue{background:#eef1ff;color:var(--primary);}
.b-violet{background:#f3eeff;color:var(--violet);}
.b-rose{background:#fff0f2;color:var(--rose);}
.b-teal{background:#edfff8;color:var(--teal);}
.b-amber{background:#fff8ec;color:var(--amber);}
.status-dot{width:7px;height:7px;border-radius:50%;display:inline-block;margin-right:5px;}

/* ── RIGHT PANEL ── */
.right-panel{width:268px;flex-shrink:0;display:flex;flex-direction:column;gap:18px;}

.profile-card{background:var(--white);border-radius:20px;padding:22px 18px;text-align:center;box-shadow:var(--shadow);}
.profile-av{width:68px;height:68px;border-radius:50%;background:var(--g1);margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-family:'Plus Jakarta Sans',sans-serif;font-size:24px;font-weight:800;color:white;}
.profile-card h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;}
.profile-card .role{font-size:12px;color:var(--sub);margin:3px 0 14px;}
.btn-outline{border:1.5px solid var(--primary);color:var(--primary);background:transparent;padding:7px 20px;border-radius:10px;font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;font-size:12px;cursor:pointer;transition:.18s;}
.btn-outline:hover{background:var(--primary);color:white;}

/* mini donut chart */
.chart-card{background:var(--white);border-radius:20px;padding:20px 18px;box-shadow:var(--shadow);}
.chart-card h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;margin-bottom:14px;}
#donut-wrap{display:flex;justify-content:center;margin-bottom:14px;}
.legend{display:flex;flex-direction:column;gap:8px;}
.legend-item{display:flex;align-items:center;justify-content:space-between;font-size:12px;}
.legend-dot{width:9px;height:9px;border-radius:50%;margin-right:7px;flex-shrink:0;}
.legend-label{display:flex;align-items:center;color:var(--sub);}
.legend-val{font-weight:700;color:var(--text);}

/* calendar */
.cal-card{background:var(--white);border-radius:20px;padding:18px;box-shadow:var(--shadow);}
.cal-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.cal-hdr h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;}
.cal-nav{width:26px;height:26px;border-radius:8px;background:var(--bg);border:none;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;transition:.15s;}
.cal-nav:hover{background:var(--border);}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;}
.cal-lbl{font-size:9.5px;font-weight:700;color:var(--sub);text-align:center;padding:3px 0;}
.cal-d{aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:7px;font-size:11px;cursor:pointer;transition:.15s;color:var(--text);}
.cal-d:hover{background:var(--bg);}
.cal-d.today{background:var(--primary);color:white;font-weight:700;}
.cal-d.event{color:var(--primary);font-weight:600;position:relative;}
.cal-d.event::after{content:'';position:absolute;bottom:2px;left:50%;transform:translateX(-50%);width:3px;height:3px;background:var(--primary);border-radius:50%;}
.cal-d.dim{color:var(--border);}

/* reminders */
.rem-card{background:var(--white);border-radius:20px;padding:18px;box-shadow:var(--shadow);}
.rem-card h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;margin-bottom:12px;}
.rem-item{display:flex;align-items:center;gap:11px;padding:9px 0;border-bottom:1px solid var(--border);}
.rem-item:last-child{border-bottom:none;}
.rem-icon{width:32px;height:32px;border-radius:10px;background:var(--primary-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.rem-icon svg{width:14px;height:14px;fill:var(--primary);}
.rem-info h5{font-size:12.5px;font-weight:600;margin-bottom:2px;}
.rem-info p{font-size:11px;color:var(--sub);}

/* animations */
@keyframes fadeUp{from{opacity:0;transform:translateY(14px);}to{opacity:1;transform:translateY(0);}}
.fu{animation:fadeUp .38s ease both;}
.d1{animation-delay:.06s;}.d2{animation-delay:.12s;}.d3{animation-delay:.18s;}.d4{animation-delay:.24s;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark">
      <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
    </div>
    <span class="logo-name" style="color:blue">BOSNIA CHELSEA <span>MIS</span></span>
  </div>

  <div class="nav-section">Main Menu</div>
  <a href="#" class="nav-link active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </a>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Students
  </a>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
    Courses
  </a>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Schedule
  </a>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
    Live Classes
  </a>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Resources
  </a>

  <div class="nav-section" style="margin-top:12px;">Account</div>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
    Users
  </a>
  <a href="#" class="nav-link">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
    Settings
  </a>

  <div class="sidebar-help">
    <div class="help-ball">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
    </div>
    <h5>Need help?</h5>
    <p>Having trouble using the system?</p>
  </div>
</aside>

<!-- MAIN -->
<main class="main">
<div class="content">

  <!-- Topbar -->
  <div class="topbar fu">
    <div class="search">
      <svg viewBox="0 0 24 24" fill="none" stroke="#6b7199" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder="Search students, courses...">
    </div>
    <div class="topbar-right">
      <span class="date-badge" id="js-date"></span>
      <div class="notif-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <div class="notif-dot"></div>
      </div>
    </div>
  </div>

  <!-- Banner -->
  <div class="banner fu d1">
    <div class="banner-text">
      <h2>Welcome back, Admin 👋</h2>
      <p>You have <strong><?= $totalStudents ?> students</strong> enrolled across <strong><?= $totalCourses ?> courses</strong>. Here's your system overview for today.</p>
      <a href="#" class="btn-primary">Manage Students</a>
    </div>
    <div class="banner-art">
      <svg viewBox="0 0 130 90" xmlns="http://www.w3.org/2000/svg">
        <rect x="15" y="48" width="52" height="36" rx="4" fill="#4f6ef7" opacity=".12"/>
        <rect x="20" y="42" width="52" height="36" rx="4" fill="#7c5bf5" opacity=".22"/>
        <rect x="26" y="36" width="52" height="38" rx="4" fill="url(#bk)"/>
        <defs><linearGradient id="bk" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#4f6ef7"/><stop offset="100%" stop-color="#7c5bf5"/></linearGradient></defs>
        <rect x="32" y="43" width="32" height="3" rx="1.5" fill="white" opacity=".5"/>
        <rect x="32" y="50" width="25" height="3" rx="1.5" fill="white" opacity=".3"/>
        <rect x="32" y="57" width="28" height="3" rx="1.5" fill="white" opacity=".3"/>
        <circle cx="95" cy="28" r="20" fill="#f56b7c" opacity=".12"/>
        <circle cx="95" cy="28" r="14" fill="#f56b7c" opacity=".25"/>
        <text x="95" y="34" text-anchor="middle" font-size="15" fill="#f56b7c" font-weight="bold" font-family="Plus Jakarta Sans">A+</text>
        <circle cx="112" cy="55" r="9" fill="#34c9a0" opacity=".2"/>
        <circle cx="112" cy="55" r="6" fill="#34c9a0" opacity=".4"/>
        <text x="112" y="59" text-anchor="middle" font-size="8" fill="#34c9a0" font-weight="bold">✓</text>
      </svg>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats fu d2">
    <div class="stat">
      <div class="stat-icon si1">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <div class="stat-body">
        <h3><?= $totalStudents ?></h3>
        <p>Total Students</p>
        <span class="trend up">↑ Enrolled</span>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon si2">
        <svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg>
      </div>
      <div class="stat-body">
        <h3><?= $totalCourses ?></h3>
        <p>Active Courses</p>
        <span class="trend up">↑ Running</span>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon si3">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
      </div>
      <div class="stat-body">
        <h3><?= $totalUsers ?></h3>
        <p>System Users</p>
        <span class="trend neutral">→ Registered</span>
      </div>
    </div>
    <div class="stat">
      <div class="stat-icon si4">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      </div>
      <div class="stat-body">
        <h3><?= $avgAge ?></h3>
        <p>Avg. Student Age</p>
        <span class="trend neutral">→ Years</span>
      </div>
    </div>
  </div>

  <!-- Course Cards -->
  <div class="sec-hdr fu d2">
    <h3>Courses Overview</h3>
    <a href="#" class="view-all">View All ›</a>
  </div>
  <div class="courses-grid fu d2">
    <?php
    $total = max($totalStudents, 1);
    $gradClasses = ['g1','g2','g3'];
    foreach (array_slice($courses, 0, 3) as $i => $c):
      $pct = round(($c['total'] / $total) * 100);
    ?>
    <div class="course-card">
      <div class="cc-top">
        <h4><?= htmlspecialchars($c['course']) ?></h4>
        <span class="cc-badge">Active</span>
      </div>
      <div class="cc-count"><?= $c['total'] ?></div>
      <div class="cc-sub">students enrolled</div>
      <div class="cc-bar"><div class="cc-bar-fill" style="width:<?= $pct ?>%"></div></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Students Table -->
  <div class="sec-hdr fu d3">
    <h3>Recent Students</h3>
    <a href="#" class="view-all">View All ›</a>
  </div>
  <div class="table-wrap fu d3">
    <div class="tbl-filters">
      <button class="filter-btn active">All</button>
      <?php foreach (array_slice($courses, 0, 4) as $c): ?>
      <button class="filter-btn"><?= htmlspecialchars($c['course']) ?></button>
      <?php endforeach; ?>
    </div>
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Email</th>
          <th>Course</th>
          <th>Age</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $avColors = ['#4f6ef7','#7c5bf5','#f56b7c','#34c9a0','#f5a623','#6b7199','#e07038','#2d9fd8'];
        $badgeClass = ['b-blue','b-violet','b-rose','b-teal','b-amber'];
        $statuses = [
          ['Active','#34c9a0'],['Active','#34c9a0'],['Active','#34c9a0'],
          ['Pending','#f5a623'],['Active','#34c9a0'],['Active','#34c9a0'],
          ['Inactive','#f56b7c'],['Active','#34c9a0']
        ];
        foreach ($recentStudents as $idx => $s):
          $parts = explode(' ', trim($s['name']));
          $initials = strtoupper(substr($parts[0],0,1)) . (isset($parts[1]) ? strtoupper(substr($parts[1],0,1)) : '');
          $color = $avColors[$idx % count($avColors)];
          $bc = $badgeClass[$idx % count($badgeClass)];
          [$stLabel, $stColor] = $statuses[$idx % count($statuses)];
        ?>
        <tr>
          <td>
            <div class="s-name">
              <div class="av" style="background:<?= $color ?>"><?= $initials ?></div>
              <div>
                <div style="font-weight:600;font-size:13.5px;"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:11px;color:var(--sub);">ID #<?= str_pad($s['id'],4,'0',STR_PAD_LEFT) ?></div>
              </div>
            </div>
          </td>
          <td style="color:var(--sub);font-size:13px;"><?= htmlspecialchars($s['email']) ?></td>
          <td><span class="badge <?= $bc ?>"><?= htmlspecialchars($s['course']) ?></span></td>
          <td style="font-weight:600;"><?= $s['age'] ?></td>
          <td>
            <span style="display:inline-flex;align-items:center;font-size:12px;font-weight:600;color:<?= $stColor ?>">
              <span class="status-dot" style="background:<?= $stColor ?>"></span><?= $stLabel ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div><!-- /content -->

<!-- RIGHT PANEL -->
<aside class="right-panel">

  <div class="profile-card fu d1">
    <div class="profile-av">A</div>
    <h3>Administrator</h3>
    <p class="role">System Admin</p>
    <button class="btn-outline">Edit Profile</button>
  </div>

  <!-- Donut Chart -->
  <div class="chart-card fu d2">
    <h4>Enrollment by Course</h4>
    <div id="donut-wrap">
      <canvas id="donut" width="130" height="130"></canvas>
    </div>
    <div class="legend" id="legend"></div>
  </div>

  <!-- Calendar -->
  <div class="cal-card fu d3">
    <div class="cal-hdr">
      <button class="cal-nav" id="cal-prev">‹</button>
      <h4 id="cal-label"></h4>
      <button class="cal-nav" id="cal-next">›</button>
    </div>
    <div class="cal-grid" id="cal-grid"></div>
  </div>

  <!-- Reminders -->
  <div class="rem-card fu d4">
    <h4>Reminders</h4>
    <?php foreach (array_slice($recentStudents, 0, 3) as $s): ?>
    <div class="rem-item">
      <div class="rem-icon">
        <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
      </div>
      <div class="rem-info">
        <h5><?= htmlspecialchars($s['name']) ?></h5>
        <p><?= htmlspecialchars($s['course']) ?> · New enrollment</p>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

</aside>
</main>

<script>
// Date
const now = new Date();
document.getElementById('js-date').textContent = now.toLocaleDateString('en-US',{weekday:'long',day:'numeric',month:'long',year:'numeric'});

// Calendar
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS   = ['Mo','Tu','We','Th','Fr','Sa','Su'];
let cy = now.getFullYear(), cm = now.getMonth();
function buildCal(){
  document.getElementById('cal-label').textContent = MONTHS[cm]+' '+cy;
  const g = document.getElementById('cal-grid');
  g.innerHTML = '';
  DAYS.forEach(d=>{ const e=document.createElement('div');e.className='cal-lbl';e.textContent=d;g.appendChild(e); });
  const first = new Date(cy,cm,1).getDay();
  const off = first===0?6:first-1;
  const dim = new Date(cy,cm,0).getDate();
  const total = new Date(cy,cm+1,0).getDate();
  for(let i=off;i>0;i--){ const e=document.createElement('div');e.className='cal-d dim';e.textContent=dim-i+1;g.appendChild(e); }
  for(let d=1;d<=total;d++){
    const e=document.createElement('div');e.className='cal-d';
    if(d===now.getDate()&&cm===now.getMonth()&&cy===now.getFullYear()) e.classList.add('today');
    else if([3,8,15,22].includes(d)) e.classList.add('event');
    e.textContent=d; g.appendChild(e);
  }
}
buildCal();
document.getElementById('cal-prev').onclick=()=>{ cm--;if(cm<0){cm=11;cy--;} buildCal(); };
document.getElementById('cal-next').onclick=()=>{ cm++;if(cm>11){cm=0;cy++;} buildCal(); };

// Donut chart
const chartData = <?= $chartData ?>;
const colors = ['#4f6ef7','#7c5bf5','#f56b7c','#34c9a0','#f5a623','#2d9fd8'];
const canvas = document.getElementById('donut');
const ctx = canvas.getContext('2d');
const total = chartData.reduce((s,d)=>s+d.count,0);
let startAngle = -Math.PI/2;
const cx2=65,cy2=65,r=50,inner=30;
chartData.forEach((d,i)=>{
  const slice = (d.count/Math.max(total,1))*(2*Math.PI);
  ctx.beginPath();
  ctx.moveTo(cx2,cy2);
  ctx.arc(cx2,cy2,r,startAngle,startAngle+slice);
  ctx.closePath();
  ctx.fillStyle=colors[i%colors.length];
  ctx.fill();
  startAngle+=slice;
});
ctx.beginPath();
ctx.arc(cx2,cy2,inner,0,2*Math.PI);
ctx.fillStyle='white';
ctx.fill();
ctx.fillStyle='#1a1d2e';
ctx.font='bold 13px Plus Jakarta Sans';
ctx.textAlign='center';
ctx.textBaseline='middle';
ctx.fillText(total,cx2,cy2-6);
ctx.font='10px Outfit';
ctx.fillStyle='#6b7199';
ctx.fillText('students',cx2,cy2+8);

// Legend
const leg = document.getElementById('legend');
chartData.forEach((d,i)=>{
  const row=document.createElement('div');row.className='legend-item';
  row.innerHTML=`<span class="legend-label"><span class="legend-dot" style="background:${colors[i%colors.length]}"></span>${d.course}</span><span class="legend-val">${d.count}</span>`;
  leg.appendChild(row);
});

// Filter buttons
document.querySelectorAll('.filter-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{ document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); });
});
</script>
</body>
</html>