<?php
$host = "localhost";
$dbname = "Students MIS";
$username = "root";
$password = "";

$conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Total students
$totalStudents = $conn->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Students per course (for class cards)
$coursesStmt = $conn->query("SELECT course, COUNT(*) as count FROM students GROUP BY course LIMIT 3");
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

// Recent students (lessons table)
$recentStmt = $conn->query("SELECT * FROM students ORDER BY id DESC LIMIT 5");
$recentStudents = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Age distribution (for stats)
$avgAge = $conn->query("SELECT AVG(age) FROM students")->fetchColumn();
$avgAge = round($avgAge, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LearnHub Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f0f2f8;
    --sidebar-bg: #ffffff;
    --card-bg: #ffffff;
    --primary: #5b7cfa;
    --primary-dark: #3a5bd9;
    --accent1: #7c5bf5;
    --accent2: #f56b7c;
    --accent3: #56cfb2;
    --text: #1a1d2e;
    --muted: #8b91a7;
    --border: #e8eaf2;
    --grad1: linear-gradient(135deg, #5b7cfa, #7c5bf5);
    --grad2: linear-gradient(135deg, #7c5bf5, #f56b7c);
    --grad3: linear-gradient(135deg, #f56b7c, #ffa07a);
    --shadow: 0 4px 24px rgba(91,124,250,0.10);
    --shadow-card: 0 2px 12px rgba(26,29,46,0.07);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: 220px;
    background: var(--sidebar-bg);
    display: flex;
    flex-direction: column;
    padding: 28px 18px;
    position: fixed;
    height: 100vh;
    border-right: 1px solid var(--border);
    z-index: 10;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 36px;
    padding-left: 6px;
  }

  .logo-icon {
    width: 34px; height: 34px;
    background: var(--grad1);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
  }

  .logo-icon svg { width: 18px; height: 18px; fill: white; }

  .logo-text {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: 17px;
    color: var(--text);
  }

  .nav-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 0 10px;
    margin-bottom: 8px;
    margin-top: 6px;
  }

  .nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 12px;
    cursor: pointer;
    transition: all .2s;
    margin-bottom: 2px;
    font-size: 14px;
    font-weight: 500;
    color: var(--muted);
    text-decoration: none;
  }

  .nav-item:hover { background: #f4f6ff; color: var(--primary); }
  .nav-item.active { background: #eef1ff; color: var(--primary); font-weight: 600; }

  .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }

  .sidebar-footer {
    margin-top: auto;
    background: linear-gradient(135deg, #eef1ff, #f3eeff);
    border-radius: 16px;
    padding: 18px 14px;
    text-align: center;
  }

  .sidebar-footer .help-icon {
    width: 42px; height: 42px;
    background: var(--grad1);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 10px;
  }

  .sidebar-footer .help-icon svg { width: 20px; height: 20px; fill: white; }
  .sidebar-footer h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 13px; font-weight: 700; margin-bottom: 4px; }
  .sidebar-footer p { font-size: 11px; color: var(--muted); line-height: 1.4; }

  /* ── Main ── */
  .main {
    margin-left: 220px;
    flex: 1;
    display: flex;
    padding: 28px 28px 28px 28px;
    gap: 24px;
  }

  .content { flex: 1; min-width: 0; }

  /* ── Topbar ── */
  .topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
  }

  .search-box {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 10px 16px;
    width: 300px;
  }

  .search-box svg { width: 16px; height: 16px; fill: var(--muted); flex-shrink: 0; }
  .search-box input { border: none; outline: none; font-family: 'DM Sans', sans-serif; font-size: 14px; color: var(--text); background: transparent; width: 100%; }
  .search-box input::placeholder { color: var(--muted); }

  .date-text { font-size: 13px; color: var(--muted); font-weight: 500; }

  /* ── Welcome Banner ── */
  .welcome-banner {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 28px;
    box-shadow: var(--shadow-card);
    overflow: hidden;
    position: relative;
  }

  .welcome-banner::before {
    content: '';
    position: absolute;
    right: 180px; top: -30px;
    width: 180px; height: 180px;
    background: radial-gradient(circle, #eef1ff 0%, transparent 70%);
    border-radius: 50%;
  }

  .welcome-banner h2 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 22px; font-weight: 800;
    margin-bottom: 6px;
  }

  .welcome-banner p { font-size: 13px; color: var(--muted); margin-bottom: 18px; max-width: 320px; line-height: 1.5; }

  .btn-primary {
    background: var(--grad1);
    color: white;
    border: none;
    padding: 11px 22px;
    border-radius: 12px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: opacity .2s, transform .2s;
    text-decoration: none;
    display: inline-block;
  }

  .btn-primary:hover { opacity: .88; transform: translateY(-1px); }

  .banner-illustration {
    width: 140px; height: 100px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .banner-illustration svg { width: 130px; height: 100px; }

  /* ── Stats Row ── */
  .stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }

  .stat-card {
    background: var(--card-bg);
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: var(--shadow-card);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform .2s;
  }

  .stat-card:hover { transform: translateY(-2px); }

  .stat-icon {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }

  .stat-icon svg { width: 22px; height: 22px; fill: white; }
  .stat-icon.blue { background: var(--grad1); }
  .stat-icon.purple { background: var(--grad2); }
  .stat-icon.pink { background: var(--grad3); }

  .stat-info h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 800; }
  .stat-info p { font-size: 12px; color: var(--muted); font-weight: 500; margin-top: 2px; }

  /* ── Section header ── */
  .section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
  }

  .section-header h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 16px; font-weight: 700;
  }

  .view-all {
    font-size: 13px; color: var(--primary);
    font-weight: 600; text-decoration: none;
    display: flex; align-items: center; gap: 4px;
  }

  /* ── Class Cards ── */
  .classes-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
  }

  .class-card {
    border-radius: 18px;
    padding: 22px;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: transform .2s;
    cursor: pointer;
  }

  .class-card:hover { transform: translateY(-3px); }
  .class-card:nth-child(1) { background: var(--grad1); }
  .class-card:nth-child(2) { background: var(--grad2); }
  .class-card:nth-child(3) { background: var(--grad3); }

  .class-card::after {
    content: '';
    position: absolute;
    bottom: -20px; right: -20px;
    width: 100px; height: 100px;
    background: rgba(255,255,255,.1);
    border-radius: 50%;
  }

  .class-card h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 15px; font-weight: 700; margin-bottom: 12px; }

  .avatar-stack { display: flex; margin-bottom: 14px; }
  .avatar-stack .av {
    width: 28px; height: 28px; border-radius: 50%;
    border: 2px solid rgba(255,255,255,.6);
    margin-left: -6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700;
    color: white;
  }

  .avatar-stack .av:first-child { margin-left: 0; }

  .av.av1 { background: rgba(255,255,255,.3); }
  .av.av2 { background: rgba(255,255,255,.2); }
  .av.av3 { background: rgba(255,255,255,.15); }
  .av.av-count { background: rgba(0,0,0,.2); font-size: 9px; }

  .class-meta { display: flex; flex-direction: column; gap: 5px; }
  .class-meta span { font-size: 12px; opacity: .85; display: flex; align-items: center; gap: 6px; }
  .class-meta span svg { width: 13px; height: 13px; fill: rgba(255,255,255,.85); }

  /* ── Students Table ── */
  .table-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 22px 24px;
    box-shadow: var(--shadow-card);
  }

  table { width: 100%; border-collapse: collapse; }
  thead th {
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .6px;
    padding: 0 12px 14px 12px;
    border-bottom: 1px solid var(--border);
  }

  tbody tr { transition: background .15s; }
  tbody tr:hover { background: #f8f9ff; }

  tbody td {
    padding: 13px 12px;
    font-size: 13.5px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
  }

  tbody tr:last-child td { border-bottom: none; }

  .student-name { display: flex; align-items: center; gap: 10px; }
  .name-avatar {
    width: 32px; height: 32px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 12px; color: white; flex-shrink: 0;
  }

  .badge {
    padding: 4px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
  }

  .badge-blue { background: #eef1ff; color: var(--primary); }
  .badge-purple { background: #f3eeff; color: var(--accent1); }
  .badge-pink { background: #fff0f2; color: var(--accent2); }
  .badge-green { background: #edfff8; color: #2ecb8a; }

  /* ── Right Panel ── */
  .right-panel {
    width: 280px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  /* Profile Card */
  .profile-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 24px 20px;
    text-align: center;
    box-shadow: var(--shadow-card);
  }

  .profile-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    margin: 0 auto 12px;
    background: var(--grad1);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 26px; font-weight: 800; color: white;
  }

  .profile-card h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 15px; font-weight: 700; }
  .profile-card p { font-size: 12px; color: var(--muted); margin: 3px 0 14px; }
  .btn-outline {
    border: 1.5px solid var(--primary); color: var(--primary);
    background: transparent; padding: 7px 20px; border-radius: 10px;
    font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; font-size: 12px;
    cursor: pointer; transition: all .2s;
  }
  .btn-outline:hover { background: var(--primary); color: white; }

  /* Calendar */
  .calendar-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 20px;
    box-shadow: var(--shadow-card);
  }

  .cal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 14px;
  }

  .cal-header h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; font-weight: 700; }
  .cal-nav {
    width: 26px; height: 26px; border-radius: 8px;
    background: var(--bg); border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 14px;
    transition: background .2s;
  }
  .cal-nav:hover { background: var(--border); }

  .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
  .cal-day-label { font-size: 10px; font-weight: 700; color: var(--muted); text-align: center; padding: 4px 0; }
  .cal-day {
    aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: 11.5px; cursor: pointer;
    transition: all .15s; color: var(--text);
  }
  .cal-day:hover { background: var(--bg); }
  .cal-day.today { background: var(--primary); color: white; font-weight: 700; }
  .cal-day.has-event { position: relative; font-weight: 600; color: var(--primary); }
  .cal-day.has-event::after { content: ''; position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; background: var(--primary); border-radius: 50%; }
  .cal-day.muted { color: var(--muted); }

  /* Reminders */
  .reminders-card {
    background: var(--card-bg);
    border-radius: 20px;
    padding: 20px;
    box-shadow: var(--shadow-card);
  }

  .reminders-card h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; font-weight: 700; margin-bottom: 14px; }

  .reminder-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 10px 0; border-bottom: 1px solid var(--border);
  }
  .reminder-item:last-child { border-bottom: none; }

  .reminder-bell {
    width: 32px; height: 32px; border-radius: 10px;
    background: #eef1ff;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  }
  .reminder-bell svg { width: 14px; height: 14px; fill: var(--primary); }

  .reminder-info h5 { font-size: 12.5px; font-weight: 600; }
  .reminder-info p { font-size: 11px; color: var(--muted); margin-top: 2px; }

  /* Scrollbar */
  ::-webkit-scrollbar { width: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

  /* Animations */
  @keyframes fadeUp { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform: translateY(0); } }
  .fade-up { animation: fadeUp .4s ease both; }
  .delay-1 { animation-delay: .08s; }
  .delay-2 { animation-delay: .16s; }
  .delay-3 { animation-delay: .24s; }
</style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="logo">
    <div class="logo-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <span class="logo-text">LearnHub</span>
  </div>

  <div class="nav-label">Main</div>
  <a href="#" class="nav-item active">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
    Dashboard
  </a>
  <a href="#" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
    Students
  </a>
  <a href="#" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    Schedule
  </a>
  <a href="#" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
    Live Lessons
  </a>
  <a href="#" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Resources
  </a>

  <div style="margin-top:16px;" class="nav-label">Account</div>
  <a href="#" class="nav-item">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
    Settings
  </a>

  <div class="sidebar-footer">
    <div class="help-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/></svg>
    </div>
    <h4>Need help?</h4>
    <p>Do you have any problem using LearnHub?</p>
  </div>
</aside>

<!-- Main Content -->
<main class="main">
  <div class="content">

    <!-- Topbar -->
    <div class="topbar fade-up">
      <div class="search-box">
        <svg viewBox="0 0 24 24"><path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" fill="none" stroke="#8b91a7" stroke-width="2"/></svg>
        <input type="text" placeholder="Search students, courses...">
      </div>
      <span class="date-text" id="current-date"></span>
    </div>

    <!-- Welcome Banner -->
    <div class="welcome-banner fade-up delay-1">
      <div>
        <h2>Welcome back, Admin!</h2>
        <p>You have <strong><?= $totalStudents ?> students</strong> enrolled across <?= count($courses) ?> active courses. Keep up the great work!</p>
        <a href="#" class="btn-primary">View All Students</a>
      </div>
      <div class="banner-illustration">
        <svg viewBox="0 0 130 100" xmlns="http://www.w3.org/2000/svg">
          <rect x="20" y="55" width="55" height="38" rx="4" fill="#5b7cfa" opacity=".15"/>
          <rect x="25" y="50" width="55" height="38" rx="4" fill="#7c5bf5" opacity=".25"/>
          <rect x="30" y="44" width="55" height="40" rx="4" fill="#5b7cfa" opacity=".6"/>
          <rect x="30" y="44" width="55" height="40" rx="4" fill="url(#book1)"/>
          <defs>
            <linearGradient id="book1" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0%" stop-color="#5b7cfa"/>
              <stop offset="100%" stop-color="#7c5bf5"/>
            </linearGradient>
          </defs>
          <rect x="35" y="50" width="35" height="3" rx="1.5" fill="white" opacity=".5"/>
          <rect x="35" y="57" width="28" height="3" rx="1.5" fill="white" opacity=".3"/>
          <rect x="35" y="64" width="32" height="3" rx="1.5" fill="white" opacity=".3"/>
          <circle cx="95" cy="30" r="18" fill="#f56b7c" opacity=".15"/>
          <circle cx="95" cy="30" r="13" fill="#f56b7c" opacity=".3"/>
          <text x="95" y="36" text-anchor="middle" font-size="16" fill="#f56b7c" font-weight="bold">A+</text>
        </svg>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-row fade-up delay-2">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
          <h3><?= $totalStudents ?></h3>
          <p>Total Students</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon purple">
          <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
        </div>
        <div class="stat-info">
          <h3><?= count($courses) ?></h3>
          <p>Active Courses</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon pink">
          <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </div>
        <div class="stat-info">
          <h3><?= $avgAge ?></h3>
          <p>Avg. Student Age</p>
        </div>
      </div>
    </div>

    <!-- Classes -->
    <div class="section-header fade-up delay-2">
      <h3>Classes</h3>
      <a href="#" class="view-all">View All ›</a>
    </div>

    <div class="classes-grid fade-up delay-2">
      <?php
      $gradients = ['var(--grad1)', 'var(--grad2)', 'var(--grad3)'];
      $colors = [['#5b7cfa','#4a6bde','#3a5bce'], ['#9c4ddd','#d44daa','#e04490'], ['#e06040','#e07830','#e09020']];
      $i = 0;
      foreach ($courses as $c):
        $initials = strtoupper(substr($c['course'], 0, 2));
      ?>
      <div class="class-card">
        <h4><?= htmlspecialchars($c['course']) ?></h4>
        <div class="avatar-stack">
          <div class="av av1"><?= $initials[0] ?></div>
          <div class="av av2"><?= $initials[1] ?? $initials[0] ?></div>
          <div class="av av3">+</div>
          <div class="av av-count"><?= $c['count'] ?></div>
        </div>
        <div class="class-meta">
          <span>
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <?= $c['count'] ?> Students
          </span>
          <span>
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            Active
          </span>
        </div>
      </div>
      <?php $i++; endforeach; ?>

      <?php if (count($courses) < 3): ?>
      <div class="class-card" style="background: linear-gradient(135deg,#56cfb2,#38b2ac); display:flex; align-items:center; justify-content:center; cursor:pointer; min-height:150px;">
        <div style="text-align:center;">
          <div style="font-size:32px; opacity:.7; margin-bottom:8px;">+</div>
          <span style="font-size:13px; font-weight:600; opacity:.8;">Add Course</span>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Students Table -->
    <div class="section-header fade-up delay-3">
      <h3>Recent Students</h3>
      <a href="#" class="view-all">View All ›</a>
    </div>

    <div class="table-card fade-up delay-3">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Course</th>
            <th>Age</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $avatarColors = ['#5b7cfa','#7c5bf5','#f56b7c','#56cfb2','#ffa07a'];
          $badges = ['badge-blue','badge-purple','badge-pink','badge-green'];
          $statuses = ['Active', 'Active', 'Pending', 'Active', 'Active'];
          foreach ($recentStudents as $idx => $s):
            $initials = strtoupper(substr($s['name'], 0, 1)) . strtoupper(substr(strstr($s['name'], ' '), 1, 1) ?: substr($s['name'], 1, 1));
            $color = $avatarColors[$idx % count($avatarColors)];
            $badge = $badges[$idx % count($badges)];
            $status = $statuses[$idx % count($statuses)];
          ?>
          <tr>
            <td>
              <div class="student-name">
                <div class="name-avatar" style="background:<?= $color ?>;"><?= $initials ?></div>
                <?= htmlspecialchars($s['name']) ?>
              </div>
            </td>
            <td style="color:var(--muted); font-size:13px;"><?= htmlspecialchars($s['email']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($s['course']) ?></span></td>
            <td><?= $s['age'] ?></td>
            <td><span class="badge <?= $status === 'Active' ? 'badge-green' : 'badge-pink' ?>"><?= $status ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Right Panel -->
  <aside class="right-panel">

    <!-- Profile -->
    <div class="profile-card fade-up delay-1">
      <div class="profile-avatar">A</div>
      <h3>Admin</h3>
      <p>Administrator</p>
      <button class="btn-outline">Edit Profile</button>
    </div>

    <!-- Calendar -->
    <div class="calendar-card fade-up delay-2">
      <div class="cal-header">
        <button class="cal-nav">‹</button>
        <h4 id="cal-month-label"></h4>
        <button class="cal-nav">›</button>
      </div>
      <div class="cal-grid" id="cal-grid"></div>
    </div>

    <!-- Reminders -->
    <div class="reminders-card fade-up delay-3">
      <h4>Reminders</h4>
      <?php foreach ($recentStudents as $idx => $s): if($idx >= 3) break; ?>
      <div class="reminder-item">
        <div class="reminder-bell">
          <svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <div class="reminder-info">
          <h5><?= htmlspecialchars($s['course']) ?> – <?= htmlspecialchars($s['name']) ?></h5>
          <p>Student enrolled</p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </aside>
</main>

<script>
// Date
const now = new Date();
document.getElementById('current-date').textContent = now.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

// Calendar
const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
let cYear = now.getFullYear(), cMonth = now.getMonth();

function renderCal() {
  document.getElementById('cal-month-label').textContent = months[cMonth] + ' ' + cYear;
  const grid = document.getElementById('cal-grid');
  grid.innerHTML = '';

  days.forEach(d => {
    const el = document.createElement('div');
    el.className = 'cal-day-label';
    el.textContent = d;
    grid.appendChild(el);
  });

  const first = new Date(cYear, cMonth, 1).getDay();
  const offset = first === 0 ? 6 : first - 1;
  const daysInMonth = new Date(cYear, cMonth + 1, 0).getDate();
  const prevDays = new Date(cYear, cMonth, 0).getDate();

  for (let i = offset; i > 0; i--) {
    const el = document.createElement('div');
    el.className = 'cal-day muted';
    el.textContent = prevDays - i + 1;
    grid.appendChild(el);
  }

  for (let d = 1; d <= daysInMonth; d++) {
    const el = document.createElement('div');
    el.className = 'cal-day';
    if (d === now.getDate() && cMonth === now.getMonth() && cYear === now.getFullYear()) el.classList.add('today');
    if ([5,12,19,26].includes(d)) el.classList.add('has-event');
    el.textContent = d;
    grid.appendChild(el);
  }
}

renderCal();

document.querySelectorAll('.cal-nav')[0].addEventListener('click', () => {
  cMonth--; if (cMonth < 0) { cMonth = 11; cYear--; } renderCal();
});
document.querySelectorAll('.cal-nav')[1].addEventListener('click', () => {
  cMonth++; if (cMonth > 11) { cMonth = 0; cYear++; } renderCal();
});
</script>
</body>
</html>