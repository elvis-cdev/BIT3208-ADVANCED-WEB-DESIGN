<?php
session_start();
if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit; }
if($_SESSION['role'] === 'admin'){ header("Location: dashboard.php"); exit; }
require 'db.php';

// Get this student's record from students table (matched by username)
$stmt = $conn->prepare("SELECT * FROM students WHERE name LIKE ?");
$stmt->execute(['%'.$_SESSION['username'].'%']);
$myRecord = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all available courses
$allCourses = $conn->query("SELECT * FROM courses WHERE status='Active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$totalCourses = count($allCourses);

// Get my results (if student record exists)
$myResults = [];
if($myRecord){
    $stmt = $conn->prepare("SELECT r.*, c.name as course_name, c.teacher
        FROM results r
        JOIN courses c ON c.id = r.course_id
        WHERE r.student_id = ?
        ORDER BY r.uploaded_at DESC");
    $stmt->execute([$myRecord['id']]);
    $myResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();
$avgScore = empty($myResults) ? null : round(array_sum(array_column($myResults,'score')) / count($myResults), 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Student Portal - SchoolMS</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#eef0f7;--white:#fff;
  --primary:#34c9a0;--pl:#edfff8;
  --blue:#4f6ef7;--bluel:#eef1ff;
  --violet:#7c5bf5;--rose:#f56b7c;--amber:#f5a623;
  --text:#1a1d2e;--sub:#6b7199;--border:#e4e7f2;
  --g1:linear-gradient(135deg,#34c9a0,#4f6ef7);
  --shadow:0 2px 16px rgba(52,201,160,.10);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Outfit',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}

/* ── SIDEBAR ── */
.sidebar{width:220px;background:var(--white);position:fixed;height:100vh;display:flex;flex-direction:column;padding:22px 14px;border-right:1px solid var(--border);z-index:20;overflow-y:auto;}
.logo{display:flex;align-items:center;gap:10px;padding:0 6px;margin-bottom:10px;}
.logo-mark{width:34px;height:34px;background:var(--g1);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.logo-mark svg{width:18px;height:18px;fill:white;}
.logo-name{font-family:'Plus Jakarta Sans',sans-serif;font-weight:800;font-size:14px;color:var(--text);}
.logo-name span{color:var(--primary);}
.role-pill{display:inline-block;background:var(--pl);color:var(--primary);font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;margin:4px 6px 18px;letter-spacing:.05em;}
.nav-sec{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--sub);padding:0 10px;margin:6px 0 4px;}
.nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;font-size:13px;font-weight:500;color:var(--sub);text-decoration:none;cursor:pointer;transition:.15s;margin-bottom:2px;border:none;background:none;width:100%;text-align:left;font-family:'Outfit',sans-serif;}
.nav-link:hover{background:#f0fffc;color:var(--primary);}
.nav-link.active{background:var(--pl);color:var(--primary);font-weight:600;}
.nav-link svg{width:16px;height:16px;flex-shrink:0;}
.sb-profile{background:var(--g1);border-radius:14px;padding:14px 12px;text-align:center;margin-top:auto;}
.sb-av{width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:16px;margin:0 auto 8px;}
.sb-profile h5{color:white;font-size:13px;font-weight:700;font-family:'Plus Jakarta Sans',sans-serif;}
.sb-profile p{color:rgba(255,255,255,.75);font-size:11px;margin-top:2px;}

/* ── MAIN ── */
.main{margin-left:220px;flex:1;padding:22px 26px;}

/* ── TOPBAR ── */
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;}
.topbar h1{font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:800;}
.topbar p{font-size:13px;color:var(--sub);margin-top:2px;}
.topbar-right{display:flex;align-items:center;gap:10px;}
.av-top{width:38px;height:38px;border-radius:11px;background:var(--g1);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:14px;}

/* ── PAGES ── */
.page{display:none;animation:fadeUp .3s ease both;}
.page.active{display:block;}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}

/* ── BANNER ── */
.banner{background:var(--g1);border-radius:18px;padding:22px 26px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;}
.banner-left h2{color:white;font-family:'Plus Jakarta Sans',sans-serif;font-size:17px;font-weight:800;margin-bottom:4px;}
.banner-left p{color:rgba(255,255,255,.82);font-size:13px;}
.banner-icons{display:flex;gap:8px;}
.bic{width:42px;height:54px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;}
.bi1{background:rgba(255,255,255,.2)}.bi2{background:rgba(255,255,255,.15)}.bi3{background:rgba(255,255,255,.1)}

/* ── STAT GRID ── */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin-bottom:20px;}
.stat-card{background:var(--white);border-radius:16px;padding:16px 18px;box-shadow:var(--shadow);display:flex;align-items:center;gap:13px;}
.sicon{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sicon svg{width:19px;height:19px;fill:white;}
.si1{background:var(--g1)}.si2{background:linear-gradient(135deg,#7c5bf5,#f56b7c)}.si3{background:linear-gradient(135deg,#f5a623,#f56b7c)}.si4{background:linear-gradient(135deg,#4f6ef7,#34c9a0)}
.sbody h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:20px;font-weight:800;}
.sbody p{font-size:11px;color:var(--sub);margin-top:2px;}

/* ── SECTION HEADER ── */
.sec-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.sec-hdr h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;}
.sec-hdr span{font-size:12px;color:var(--sub);}

/* ── COURSE CARDS ── */
.course-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:22px;}
.cc{border-radius:14px;padding:18px;color:white;position:relative;}
.cc1{background:linear-gradient(135deg,#34c9a0,#4f6ef7)}
.cc2{background:linear-gradient(135deg,#7c5bf5,#f56b7c)}
.cc3{background:linear-gradient(135deg,#f5a623,#f56b7c)}
.cc4{background:linear-gradient(135deg,#4f6ef7,#7c5bf5)}
.cc5{background:linear-gradient(135deg,#f56b7c,#f5a623)}
.cc6{background:linear-gradient(135deg,#34c9a0,#7c5bf5)}
.cc h4{font-family:'Plus Jakarta Sans',sans-serif;font-size:13px;font-weight:700;margin-bottom:6px;}
.cc-meta{font-size:11px;color:rgba(255,255,255,.82);margin-top:4px;display:flex;align-items:center;gap:4px;}
.cc-badge{background:rgba(255,255,255,.22);color:white;font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;display:inline-block;margin-top:10px;}
.cc-badge.active-badge{background:rgba(255,255,255,.3);}
.empty-state{grid-column:1/-1;text-align:center;padding:32px;color:var(--sub);font-size:13px;}

/* ── RESULTS TABLE ── */
.tbl-wrap{background:var(--white);border-radius:18px;padding:18px 20px;box-shadow:var(--shadow);}
table{width:100%;border-collapse:collapse;}
thead tr{background:#f7f8ff;}
th{padding:10px 13px;text-align:left;font-size:11px;color:var(--sub);font-weight:700;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--border);}
td{padding:11px 13px;font-size:13px;border-bottom:1px solid var(--border);}
tbody tr:last-child td{border-bottom:none;}
tbody tr:hover{background:#fafbff;}
.grade-pill{padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;display:inline-block;}
.gA{background:#edfff8;color:#0F6E56}.gB{background:#eef1ff;color:#4f6ef7}
.gC{background:#fff8ec;color:#854F0B}.gD{background:#fff0f2;color:#A32D2D}.gF{background:#fff0f2;color:#A32D2D}
.score-bar-wrap{display:flex;align-items:center;gap:8px;}
.score-bar{height:6px;border-radius:3px;background:#eee;flex:1;overflow:hidden;}
.score-fill{height:100%;border-radius:3px;}
.no-results{text-align:center;padding:32px;color:var(--sub);}
.no-results p{font-size:13px;margin-top:8px;}

/* ── PROFILE CARD ── */
.profile-card{background:var(--white);border-radius:18px;padding:24px;box-shadow:var(--shadow);display:flex;align-items:center;gap:20px;margin-bottom:20px;}
.profile-av{width:64px;height:64px;border-radius:18px;background:var(--g1);display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:24px;flex-shrink:0;}
.profile-info h3{font-family:'Plus Jakarta Sans',sans-serif;font-size:17px;font-weight:800;}
.profile-info p{font-size:13px;color:var(--sub);margin-top:3px;}
.profile-tags{display:flex;gap:8px;margin-top:8px;}
.ptag{font-size:11px;font-weight:600;padding:3px 12px;border-radius:20px;}
.ptag-green{background:var(--pl);color:var(--primary);}
.ptag-blue{background:var(--bluel);color:var(--blue);}
.not-found{background:#fff8ec;border:1px solid var(--amber);border-radius:14px;padding:16px 18px;font-size:13px;color:#854F0B;margin-bottom:20px;}

/* ── MOBILE ── */
@media(max-width:768px){
  .sidebar{width:100%;height:auto;position:relative;flex-direction:row;flex-wrap:wrap;padding:12px;}
  .main{margin-left:0;}
  .stat-grid{grid-template-columns:repeat(2,1fr);}
  .course-grid{grid-template-columns:1fr;}
  .banner{flex-direction:column;gap:12px;}
  .banner-icons{display:none;}
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <div class="logo-mark"><svg viewBox="0 0 24 24"><path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/></svg></div>
    <span class="logo-name">School<span>MS</span></span>
  </div>
  <span class="role-pill">&#127891; STUDENT PORTAL</span>

  <div class="nav-sec">My Portal</div>
  <button class="nav-link active" id="nav-home" onclick="showTab('home')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </button>
  <button class="nav-link" id="nav-courses" onclick="showTab('courses')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
    Available Courses
  </button>
  <button class="nav-link" id="nav-results" onclick="showTab('results')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
    My Results
    <?php if(!empty($myResults)): ?>
    <span style="margin-left:auto;background:var(--primary);color:white;font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;"><?= count($myResults) ?></span>
    <?php endif; ?>
  </button>
  <button class="nav-link" id="nav-profile" onclick="showTab('profile')">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    My Profile
  </button>

  <div class="nav-sec">Account</div>
  <a class="nav-link" href="logout.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    Logout
  </a>

  <div class="sb-profile">
    <div class="sb-av"><?= strtoupper(substr($_SESSION['username'],0,2)) ?></div>
    <h5><?= htmlspecialchars($_SESSION['username']) ?></h5>
    <p>Student Account</p>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div>
      <h1>Student Portal</h1>
      <p><?= date('l, d F Y') ?></p>
    </div>
    <div class="topbar-right">
      <div class="av-top"><?= strtoupper(substr($_SESSION['username'],0,2)) ?></div>
    </div>
  </div>

  <!-- ══ HOME TAB ══ -->
  <div class="page active" id="tab-home">
    <div class="banner">
      <div class="banner-left">
        <h2>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>! &#128075;</h2>
        <p>Check your courses, results and profile from the sidebar.</p>
      </div>
      <div class="banner-icons">
        <div class="bic bi1">&#127891;</div>
        <div class="bic bi2">&#128218;</div>
        <div class="bic bi3">&#128196;</div>
      </div>
    </div>

    <div class="stat-grid">
      <div class="stat-card">
        <div class="sicon si1"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/></svg></div>
        <div class="sbody"><h3><?= $totalCourses ?></h3><p>Available Courses</p></div>
      </div>
      <div class="stat-card">
        <div class="sicon si2"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div>
        <div class="sbody"><h3><?= count($myResults) ?></h3><p>My Results</p></div>
      </div>
      <div class="stat-card">
        <div class="sicon si3"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
        <div class="sbody"><h3><?= $avgScore !== null ? $avgScore.'%' : 'N/A' ?></h3><p>Avg Score</p></div>
      </div>
      <div class="stat-card">
        <div class="sicon si4"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        <div class="sbody"><h3><?= $totalStudents ?></h3><p>Total Enrolled</p></div>
      </div>
    </div>

    <!-- Recent Results Preview -->
    <?php if(!empty($myResults)): ?>
    <div class="sec-hdr"><h3>Recent Results</h3><button onclick="showTab('results')" style="font-size:12px;color:var(--primary);background:none;border:none;cursor:pointer;font-family:'Outfit',sans-serif;">View all &rsaquo;</button></div>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>Course</th><th>Score</th><th>Grade</th><th>Remarks</th></tr></thead>
        <tbody>
          <?php foreach(array_slice($myResults,0,4) as $r):
            $sc = (float)$r['score'];
            $fillColor = $sc>=70?'#34c9a0':($sc>=50?'#f5a623':'#f56b7c');
          ?>
          <tr>
            <td style="font-weight:600;"><?= htmlspecialchars($r['course_name']) ?></td>
            <td>
              <div class="score-bar-wrap">
                <div class="score-bar"><div class="score-fill" style="width:<?= $sc ?>%;background:<?= $fillColor ?>;"></div></div>
                <span style="font-weight:700;font-size:13px;width:38px;flex-shrink:0;"><?= $sc ?>%</span>
              </div>
            </td>
            <td><span class="grade-pill g<?= $r['grade'] ?>"><?= $r['grade'] ?></span></td>
            <td style="color:var(--sub);"><?= htmlspecialchars($r['remarks']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div style="background:var(--white);border-radius:16px;padding:28px;text-align:center;box-shadow:var(--shadow);">
      <div style="font-size:36px;margin-bottom:10px;">&#128203;</div>
      <p style="font-size:14px;font-weight:600;color:var(--text);">No results yet</p>
      <p style="font-size:13px;color:var(--sub);margin-top:4px;">Your results will appear here once uploaded by admin.</p>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ COURSES TAB ══ -->
  <div class="page" id="tab-courses">
    <div class="sec-hdr"><h3>Available Courses</h3><span><?= $totalCourses ?> courses</span></div>
    <div class="course-grid">
      <?php if(empty($allCourses)): ?>
      <div class="empty-state">&#128218; No courses available yet. Check back later.</div>
      <?php else:
      $grads = ['cc1','cc2','cc3','cc4','cc5','cc6'];
      foreach($allCourses as $i => $c): $g = $grads[$i % count($grads)]; ?>
      <div class="cc <?= $g ?>">
        <h4><?= htmlspecialchars($c['name']) ?></h4>
        <?php if($c['description']): ?>
        <div class="cc-meta">&#128218; <?= htmlspecialchars($c['description']) ?></div>
        <?php endif; ?>
        <?php if($c['teacher']): ?>
        <div class="cc-meta">&#127979; <?= htmlspecialchars($c['teacher']) ?></div>
        <?php endif; ?>
        <?php if($c['file_count']): ?>
        <div class="cc-meta">&#128196; <?= $c['file_count'] ?> files</div>
        <?php endif; ?>
        <span class="cc-badge active-badge">&#9679; <?= $c['status'] ?></span>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- ══ RESULTS TAB ══ -->
  <div class="page" id="tab-results">
    <div class="sec-hdr"><h3>My Results</h3><span><?= count($myResults) ?> record(s)</span></div>

    <?php if(!$myRecord): ?>
    <div class="not-found">
      &#9888; Your student record was not found in the system. Your username (<strong><?= htmlspecialchars($_SESSION['username']) ?></strong>) doesn't match any student name. Please contact your admin to register your record.
    </div>
    <?php endif; ?>

    <?php if(empty($myResults)): ?>
    <div class="tbl-wrap">
      <div class="no-results">
        <div style="font-size:40px;">&#128203;</div>
        <p>No results uploaded for your account yet.</p>
        <p style="margin-top:4px;font-size:12px;">Results will appear here once your lecturer uploads them.</p>
      </div>
    </div>
    <?php else: ?>
    <!-- Summary cards -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin-bottom:20px;">
      <?php
      $scores = array_column($myResults,'score');
      $best = max($scores); $worst = min($scores);
      $avg = round(array_sum($scores)/count($scores),1);
      ?>
      <div class="stat-card"><div class="sicon si1"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12" fill="white"/></svg></div><div class="sbody"><h3><?= $avg ?>%</h3><p>Average Score</p></div></div>
      <div class="stat-card"><div class="sicon si4" style="background:linear-gradient(135deg,#34c9a0,#4f6ef7)"><svg viewBox="0 0 24 24" fill="white"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div><div class="sbody"><h3><?= $best ?>%</h3><p>Best Score</p></div></div>
      <div class="stat-card"><div class="sicon si2"><svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="sbody"><h3><?= $worst ?>%</h3><p>Lowest Score</p></div></div>
    </div>

    <div class="tbl-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Course</th><th>Teacher</th><th>Score</th><th>Grade</th><th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($myResults as $i => $r):
            $sc = (float)$r['score'];
            $fillColor = $sc>=70?'#34c9a0':($sc>=60?'#4f6ef7':($sc>=50?'#f5a623':'#f56b7c'));
          ?>
          <tr>
            <td style="color:var(--sub);font-size:12px;"><?= $i+1 ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($r['course_name']) ?></td>
            <td style="color:var(--sub);"><?= htmlspecialchars($r['teacher'] ?? '—') ?></td>
            <td>
              <div class="score-bar-wrap">
                <div class="score-bar"><div class="score-fill" style="width:<?= $sc ?>%;background:<?= $fillColor ?>;"></div></div>
                <span style="font-weight:700;font-size:13px;width:40px;flex-shrink:0;"><?= $sc ?>%</span>
              </div>
            </td>
            <td><span class="grade-pill g<?= $r['grade'] ?>"><?= $r['grade'] ?></span></td>
            <td style="color:var(--sub);font-size:12px;"><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══ PROFILE TAB ══ -->
  <div class="page" id="tab-profile">
    <div class="sec-hdr"><h3>My Profile</h3></div>
    <?php if($myRecord): ?>
    <div class="profile-card">
      <div class="profile-av"><?= strtoupper(substr($myRecord['name'],0,2)) ?></div>
      <div class="profile-info">
        <h3><?= htmlspecialchars($myRecord['name']) ?></h3>
        <p>&#128231; <?= htmlspecialchars($myRecord['email']) ?></p>
        <div class="profile-tags">
          <span class="ptag ptag-green">&#127891; <?= htmlspecialchars($myRecord['course']) ?></span>
          <span class="ptag ptag-blue">&#128100; Age <?= $myRecord['age'] ?></span>
          <span class="ptag ptag-green">ID #<?= str_pad($myRecord['id'],4,'0',STR_PAD_LEFT) ?></span>
        </div>
      </div>
    </div>
    <div style="background:var(--white);border-radius:18px;padding:20px 24px;box-shadow:var(--shadow);">
      <h4 style="font-family:'Plus Jakarta Sans',sans-serif;font-size:14px;font-weight:700;margin-bottom:14px;">Student Details</h4>
      <?php
      $fields = ['Name'=>$myRecord['name'],'Email'=>$myRecord['email'],'Course'=>$myRecord['course'],'Age'=>$myRecord['age'],'Student ID'=>'#'.str_pad($myRecord['id'],4,'0',STR_PAD_LEFT)];
      foreach($fields as $k=>$v): ?>
      <div style="display:flex;align-items:center;padding:11px 0;border-bottom:1px solid var(--border);">
        <span style="font-size:12px;color:var(--sub);width:110px;flex-shrink:0;"><?= $k ?></span>
        <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($v) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="not-found">
      &#9888; No student record found matching your username <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>.<br>
      Please ask your admin to register your name in the Students section — your registered name must match your username.
    </div>
    <?php endif; ?>
  </div>

</main>

<script>
function showTab(tab){
  document.querySelectorAll('.page').forEach(function(p){ p.classList.remove('active'); });
  document.querySelectorAll('.nav-link').forEach(function(n){ n.classList.remove('active'); });
  document.getElementById('tab-'+tab).classList.add('active');
  document.getElementById('nav-'+tab).classList.add('active');
}
</script>
</body>
</html>