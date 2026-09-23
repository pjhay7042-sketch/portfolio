<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gaylord A. Pascual — Personal Biodata</title>
  <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Rajdhani:wght@400;500;600;700&family=Exo+2:ital,wght@0,300;0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div id="loginGate" class="login-gate">
    <div class="login-gate-box">
      <div class="login-gate-header">
        <div class="login-gate-icon">🔒</div>
        <div class="login-gate-title">GAYLORD A. PASCUAL</div>
        <div class="login-gate-sub">// SECURE ACCESS REQUIRED</div>
      </div>

      <!-- ── Tab bar (hidden when OTP panel is active) ── -->
      <div class="login-gate-tabs" id="gateTabs">
        <button class="login-gate-tab active" id="gateTabLoginBtn"    onclick="showGateTab('login')">Login</button>
        <button class="login-gate-tab"        id="gateTabRegisterBtn" onclick="showGateTab('register')">Register</button>
      </div>

      <!-- ── Login panel ── -->
      <div id="gateLoginPanel" class="login-gate-panel">
        <div class="crud-field">
          <label class="crud-label" for="gateLoginUsername">Username</label>
          <input class="crud-input" type="text" id="gateLoginUsername" placeholder="Username" autocomplete="off">
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateLoginPassword">Password</label>
          <input class="crud-input" type="password" id="gateLoginPassword" placeholder="Password" autocomplete="off">
        </div>
        <button class="crud-btn crud-btn-add login-gate-submit" onclick="handleGateLogin()">→ Login</button>
        <p id="gateLoginResult" class="login-result"></p>
      </div>

      <!-- ── Register panel ── -->
      <div id="gateRegisterPanel" class="login-gate-panel" style="display:none;">
        <div class="crud-field">
          <label class="crud-label" for="gateRegisterFirstName">First Name</label>
          <input class="crud-input" type="text" id="gateRegisterFirstName" placeholder="e.g. Gaylord" autocomplete="off">
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateRegisterMiddleName">Middle Name <span style="color:var(--text-muted);font-size:9px;">(optional)</span></label>
          <input class="crud-input" type="text" id="gateRegisterMiddleName" placeholder="e.g. Andani" autocomplete="off">
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateRegisterLastName">Surname</label>
          <input class="crud-input" type="text" id="gateRegisterLastName" placeholder="e.g. Pascual" autocomplete="off">
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateRegisterUsername">Username</label>
          <input class="crud-input" type="text" id="gateRegisterUsername" placeholder="e.g. gaylord_p" autocomplete="off">
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateRegisterEmail">Email Address</label>
          <input class="crud-input" type="email" id="gateRegisterEmail" placeholder="e.g. you@gmail.com" autocomplete="off">
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateRegisterPassword">Password</label>
          <input class="crud-input" type="password" id="gateRegisterPassword" placeholder="Enter a password" autocomplete="new-password">
        </div>
        <button class="crud-btn crud-btn-add login-gate-submit" onclick="handleGateRegister()">+ Create Account &amp; Enter</button>
        <p id="gateRegisterResult" class="login-result"></p>
      </div>

      <!-- ── OTP panel (shown after password accepted) ── -->
      <div id="gateOtpPanel" class="login-gate-panel" style="display:none;">
        <div class="otp-info">
          <div class="otp-icon">✉</div>
          <p class="otp-desc">A 6-digit code was sent to<br>
            <strong id="otpMaskedEmail" style="color:var(--accent-cyan);">your email</strong>.<br>
            Enter it below. The code expires in <span style="color:var(--accent-amber);">10 minutes</span>.
          </p>
        </div>
        <div class="crud-field">
          <label class="crud-label" for="gateOtpInput">One-Time Password</label>
          <input class="crud-input otp-input" type="text" id="gateOtpInput"
                 placeholder="_ _ _ _ _ _" maxlength="6" autocomplete="one-time-code"
                 oninput="this.value=this.value.replace(/\D/g,'')">
        </div>
        <button class="crud-btn crud-btn-add login-gate-submit" onclick="handleOtpVerify()">→ Verify &amp; Enter</button>
        <p id="gateOtpResult" class="login-result"></p>
        <div style="text-align:center;margin-top:8px;">
          <button class="otp-resend-btn" id="otpResendBtn" onclick="handleOtpResend()">↺ Resend code</button>
          <span id="otpResendTimer" class="otp-resend-timer" style="display:none;"></span>
        </div>
        <button class="otp-back-btn" onclick="showGateTab('login')">← Back to Login</button>
      </div>

      <!-- <p class="login-gate-note">Accounts are stored securely in MySQL via phpMyAdmin.</p> -->
    </div>
  </div>

  <div id="siteContent" style="display:none;">

    <div class="topbar">
      <div class="topbar-center">
        <span class="topbar-dot"></span>
        <span class="topbar-name">GAYLORD A. PASCUAL</span>
        <span class="topbar-sep">|</span>
        <span>BSIT 2-2 NETSEC</span>
        <span class="topbar-sep">|</span>
        <span>ISU Echague Campus</span>
        <span class="topbar-sep">|</span>
        <span id="topbarUserLabel"></span>
      </div>
      <button class="topbar-logout" onclick="handleLogout()">⏏ Logout</button>
    </div>

    <nav>
      <a href="#home">Home</a>

      <!-- Biodata dropdown -->
      <div class="nav-group">
        <button class="nav-group-btn" aria-haspopup="true" aria-expanded="false">
          Biodata <span class="nav-caret" aria-hidden="true">&#9660;</span>
        </button>
        <div class="nav-group-menu" role="menu">
          <a href="#about"    role="menuitem">About Me</a>
          <a href="#education" role="menuitem">Education</a>
          <a href="#skills"   role="menuitem">Skills</a>
          <a href="#manage"   role="menuitem">Manage Skills</a>
        </div>
      </div>

      <!-- Account dropdown -->
      <div class="nav-group">
        <button class="nav-group-btn" aria-haspopup="true" aria-expanded="false">
          Account <span class="nav-caret" aria-hidden="true">&#9660;</span>
        </button>
        <div class="nav-group-menu" role="menu">
          <a href="#accounts"     role="menuitem">Manage Accounts</a>
          <a href="#viewaccounts" role="menuitem">View Accounts</a>
        </div>
      </div>

      <a href="#iot" id="navIotLink">Sensor Dashboard</a>
      <a href="#contact">Contact</a>
    </nav>

    <div class="wrapper">

      <a name="home"></a>
      <div class="hero fade-in">
        <div class="hero-photo-wrap">
          <img src="top_body.png">
        </div>
        <div class="hero-text">
          <h1>Hello, I'm <span>Gaylord A. Pascual</span></h1>
          <div class="hero-tagline">// BSIT · NETWORK SECURITY · ISU ECHAGUE · MIDYEAR 2026</div>
          <p>
            2nd year irregular student at Isabela State University, passionate about cybersecurity,
            network systems, and building safer digital infrastructures.
          </p>
        </div>
      </div>

      <div class="card">
        <h2>Current Date and Time</h2>
        <p id="datetime" style="font-family:var(--font-mono);color:var(--accent-cyan);font-size:1rem;"></p>
      </div>

      <div class="info-grid fade-in">
        <div class="info-item">
          <span class="info-label">Full Name</span>
          <span class="info-value">Gaylord Andani Pascual</span>
        </div>
        <div class="info-item">
          <span class="info-label">Age</span>
          <span class="info-value">26 years old</span>
        </div>
        <div class="info-item">
          <span class="info-label">Birthday</span>
          <span class="info-value">November 13, 1999</span>
        </div>
        <div class="info-item">
          <span class="info-label">Course</span>
          <span class="info-value">BSIT – Network Security (NETSEC)</span>
        </div>
        <div class="info-item">
          <span class="info-label">School Year</span>
          <span class="info-value">Midyear 2026</span>
        </div>
        <div class="info-item">
          <span class="info-label">Location</span>
          <span class="info-value">Brgy. Tuguegarao, Echague, Isabela 3309</span>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="about"></a>
      <div class="section">
        <div class="section-title">About Me</div>
        <div class="section-hr"></div>

        <div class="card">
          <h2>Course and Major Selection</h2>
          <label class="crud-label" for="courseSelect">Course</label>
          <select class="crud-input" id="courseSelect" onchange="updateMajors()" style="max-width:260px;margin-bottom:16px;">
            <option value="">Select Course</option>
            <option value="BSIT">BSIT</option>
          </select>
          <br>
          <label class="crud-label" for="majorSelect">Major</label>
          <select class="crud-input" id="majorSelect" style="max-width:260px;margin-bottom:16px;">
            <option value="">Select Major</option>
          </select>
          <p id="selectionResult" style="margin-top:8px;font-family:var(--font-mono);font-size:12px;color:var(--accent-green);"></p>
        </div>

        <div class="card">
          <h2>Personal Information</h2>
          <table class="data-table">
            <tr><td>Student Number</td><td>18-0831</td></tr>
            <tr><td>Full Name</td><td>Pascual, Gaylord A.</td></tr>
            <tr><td>Age</td><td>26</td></tr>
            <tr><td>Date of Birth</td><td>November 14, 1999</td></tr>
            <tr><td>Gender</td><td>Male</td></tr>
            <tr><td>Mother's Name</td><td>Amelia Pascual</td></tr>
            <tr><td>Father's Name</td><td>Celestino Nicolas</td></tr>
            <tr><td>Nationality</td><td>Filipino</td></tr>
            <tr><td>Religion</td><td>Roman Catholic</td></tr>
            <tr><td>Address</td><td>Brgy. Tuguegarao, Echague, Isabela 3309</td></tr>
          </table>
        </div>

        <div class="card prose">
          <h2>Short Biography</h2>
          <p>I am Gaylord A. Pascual, a driven and tech-enthusiastic individual from Echague, Isabela.
            Born on November 13, 1999, I grew up with a deep curiosity for how digital systems work,
            which naturally led me to pursue a career in Information Technology. Currently, I am in my
            2nd year as an irregular student at Isabela State University – Echague Campus, specializing
            in Network Security.</p>
          <p>My academic journey has equipped me with foundational knowledge in networking protocols,
            firewall configuration, VPN setup, and basic programming using HTML, CSS, and Java.
            Beyond the classroom, I enjoy exploring open-source tools, tinkering with home lab setups,
            and staying updated with the latest developments in the cybersecurity landscape.</p>
          <p>I aspire to become a certified network security engineer, contributing to the protection of
            digital assets and critical infrastructure in the Philippines and beyond.</p>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="education"></a>
      <div class="section">
        <div class="section-title">Education</div>
        <div class="section-hr"></div>
        <div class="card">
          <h2>Educational Background</h2>
          <table class="data-table">
            <thead><tr><th>Level</th><th>School</th><th>Year</th></tr></thead>
            <tbody>
              <tr><td>College</td><td>Isabela State University – Echague Campus</td><td>2018 – Present</td></tr>
              <tr><td>Senior High School</td><td>Echague National High School</td><td>2016 – 2018</td></tr>
              <tr><td>Junior High School</td><td>Echague National High School</td><td>2012 – 2016</td></tr>
              <tr><td>Elementary</td><td>Tuguegarao Elementary School</td><td>2006 – 2012</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="skills"></a>
      <div class="section">
        <div class="section-title">Skills</div>
        <div class="section-hr"></div>
        <div class="card">
          <h2>Technical Skills <span class="crud-count" id="skillsDisplayCount"></span></h2>
          <div id="skillsDisplayContainer"></div>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="manage"></a>
      <div class="section">
        <div class="section-title">Manage Skills</div>
        <div class="section-hr"></div>

        <div class="card crud-form-card">
          <h2 id="formTitle">Add New Skill</h2>
          <div class="crud-form">
            <div class="crud-field">
              <label class="crud-label" for="skillNameInput">Skill Name</label>
              <input class="crud-input" type="text" id="skillNameInput" placeholder="e.g. Network Troubleshooting" autocomplete="off">
            </div>
            <div class="crud-field">
              <label class="crud-label" for="skillCategoryInput">Category</label>
              <select class="crud-input" id="skillCategoryInput">
                <option value="Networking &amp; Security">Networking &amp; Security</option>
                <option value="Programming &amp; Web Technologies">Programming &amp; Web Technologies</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="crud-field">
              <label class="crud-label" for="skillLevelInput">Level</label>
              <select class="crud-input" id="skillLevelInput">
                <option value="Beginner">Beginner</option>
                <option value="Basic" selected>Basic</option>
                <option value="Intermediate">Intermediate</option>
                <option value="Advanced">Advanced</option>
                <option value="Expert">Expert</option>
              </select>
            </div>
            <div class="crud-form-actions" style="align-self:flex-end;">
              <button class="crud-btn crud-btn-add" id="submitSkillBtn" onclick="submitSkill()">+ Add Skill</button>
              <button class="crud-btn crud-btn-cancel" id="cancelEditBtn" onclick="cancelEdit()" style="display:none;">✕ Cancel</button>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Skills Records <span class="crud-count" id="skillCount"></span></h2>
          <div id="noSkillsMsg" class="crud-empty" style="display:none;">No skills added yet. Use the form above to add one.</div>
          <table class="data-table crud-table" id="skillsTable">
            <thead>
              <tr>
                <th>#</th><th>Skill Name</th><th>Category</th><th>Level</th><th>Progress</th><th>Actions</th>
              </tr>
            </thead>
            <tbody id="skillsTableBody"></tbody>
          </table>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="accounts"></a>
      <div class="section">
        <div class="section-title">Manage Accounts</div>
        <div class="section-hr"></div>

        <div class="card" id="accountFormCard">
          <h2 id="accountFormTitle">Register New Account</h2>
          <div class="crud-form-names">
            <div class="crud-field">
              <label class="crud-label" for="accountFirstNameInput">First Name</label>
              <input class="crud-input" type="text" id="accountFirstNameInput" placeholder="e.g. Gaylord" autocomplete="off" />
            </div>
            <div class="crud-field">
              <label class="crud-label" for="accountMiddleNameInput">Middle Name <span style="color:var(--text-muted);font-size:9px;">(optional)</span></label>
              <input class="crud-input" type="text" id="accountMiddleNameInput" placeholder="e.g. Andani" autocomplete="off" />
            </div>
            <div class="crud-field">
              <label class="crud-label" for="accountLastNameInput">Surname</label>
              <input class="crud-input" type="text" id="accountLastNameInput" placeholder="e.g. Pascual" autocomplete="off" />
            </div>
          </div>
          <div style="height:14px;"></div>
          <div class="crud-form">
            <div class="crud-field">
              <label class="crud-label" for="accountUsernameInput">Username</label>
              <input class="crud-input" type="text" id="accountUsernameInput" placeholder="e.g. gaylord_p" autocomplete="off" />
            </div>
            <div class="crud-field">
              <label class="crud-label" for="accountEmailInput">Email Address</label>
              <input class="crud-input" type="email" id="accountEmailInput" placeholder="e.g. you@gmail.com" autocomplete="off" />
            </div>
            <div class="crud-field">
              <label class="crud-label" for="accountPasswordInput">Password</label>
              <input class="crud-input" type="password" id="accountPasswordInput" placeholder="Enter a password" autocomplete="new-password" />
            </div>
            <div class="crud-form-actions" style="align-self:flex-end;">
              <button class="crud-btn crud-btn-add" id="submitAccountBtn" onclick="submitAccount()">
                + Register
              </button>
              <button class="crud-btn crud-btn-cancel" id="cancelAccountEditBtn" onclick="cancelAccountEdit()" style="display:none;">
                ✕ Cancel
              </button>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Account Records <span class="crud-count" id="accountCount"></span></h2>
          <div id="noAccountsMsg" class="crud-empty" style="display:none;">
            No accounts registered yet. Use the form above to register one.
          </div>
          <table class="data-table crud-table" id="accountsTable">
            <thead>
              <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Registered</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="accountsTableBody"></tbody>
          </table>
        </div>

        <p class="crud-empty" style="text-align:left;padding:4px 0 0;"></p>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>
     
      <a name="viewaccounts"></a>
      <div class="section">
        <div class="section-title">View Accounts</div>
        <div class="section-hr"></div>

        <div class="card">
          <h2>Registered Accounts Directory <span class="crud-count" id="viewAccountCount"></span></h2>
          <p style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);margin-bottom:18px;letter-spacing:0.04em;">
            ⓘ Read-only view · Passwords are never displayed
          </p>
          <div id="noViewAccountsMsg" class="crud-empty" style="display:none;">
            No accounts registered yet.
          </div>
          <table class="data-table" id="viewAccountsTable">
            <thead>
              <tr>
                <th>#</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Surname</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Date Registered</th>
              </tr>
            </thead>
            <tbody id="viewAccountsTableBody"></tbody>
          </table>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="iot"></a>
      <div class="section">
        <div class="section-title">DHT22 Temperature & Humidity Sensor </div>
        <div class="section-hr"></div>

        <div class="card">
          <!-- <h2>
            DHT22 Temperature & Humidity Sensor 
             <span class="badge" id="iotStatusBadge" style="margin-left:8px;">— —</span>
            <span class="badge" id="iotLedIndicator" style="margin-left:4px;">— —</span> 
          </h2> -->
          <!-- <p style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);margin-bottom:6px;letter-spacing:0.04em;">
            ⓘ DHT22 Temperature &amp; Humidity Sensor · Arduino → Python bridge → this dashboard
          </p>
          <p style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);margin-bottom:18px;letter-spacing:0.04em;">
            🔴 Red LED + Buzzer / 🟡 Yellow LED — alert limit: <span id="iotThresholdLabel">— °C</span>
          </p> -->

          <div id="iotAlertBanner" class="iot-alert-banner" style="display:none;">
            <span class="iot-alert-banner-icon">⚠</span>
            <div class="iot-alert-banner-text">
              <strong>HIGH TEMPERATURE ALERT</strong>
              <span id="iotAlertBannerDetail">—</span>
            </div>
          </div>

          <form class="crud-form" id="iotFilterForm" style="grid-template-columns:1fr 1fr auto auto;" onsubmit="return false;">
            <div class="crud-field">
              <label class="crud-label" for="iotFromInput">From</label>
              <input class="crud-input" type="date" id="iotFromInput">
            </div>
            <div class="crud-field">
              <label class="crud-label" for="iotToInput">To</label>
              <input class="crud-input" type="date" id="iotToInput">
            </div>
            <div class="crud-form-actions">
              <button class="crud-btn crud-btn-add" onclick="applyIotFilter()">Filter</button>
              <button class="crud-btn crud-btn-cancel" onclick="clearIotFilter()">Clear</button>
            </div>
          </form>
        </div>

        <div class="info-grid fade-in" id="iotLatestGrid">
          <div class="info-item">
            <span class="info-label">Latest Temperature</span>
            <span class="info-value" id="iotLatestTemp">—</span>
          </div>
          <div class="info-item">
            <span class="info-label">Latest Humidity</span>
            <span class="info-value" id="iotLatestHum">—</span>
          </div>
          <div class="info-item">
            <span class="info-label">Last Updated</span>
            <span class="info-value" id="iotLatestTime">—</span>
          </div>
        </div>

        <div class="card">
          <h2>
            <span id="iotStatsTitle">All-Time Summary</span>
            <span class="crud-count" id="iotReadingCount"></span>
            <a class="back-top" id="iotExportLink" href="API/iot_export.php" style="margin:0 0 0 auto;float:right;">⤓ Export CSV</a>
          </h2>
          <div class="info-grid" style="margin-bottom:0;">
            <div class="info-item">
              <span class="info-label">Temperature (avg)</span>
              <span class="info-value" id="iotTempAvg">—</span>
            </div>
            <div class="info-item">
              <span class="info-label">Temperature (range)</span>
              <span class="info-value" id="iotTempRange">—</span>
            </div>
            <div class="info-item">
              <span class="info-label">Humidity (avg)</span>
              <span class="info-value" id="iotHumAvg">—</span>
            </div>
            <div class="info-item">
              <span class="info-label">Humidity (range)</span>
              <span class="info-value" id="iotHumRange">—</span>
            </div>
          </div>
        </div>

        <div class="card">
          <h2>Recent Readings</h2>
          <canvas id="iotChart" height="90"></canvas>
        </div>

        <div class="card" id="iotDailyCard" style="display:none;">
          <h2>Daily Averages</h2>
          <canvas id="iotDailyChart" height="80"></canvas>
        </div>

        <div class="card">
          <h2>Reading History <span class="crud-count" id="iotHistoryCount"></span></h2>
          <div id="noIotRowsMsg" class="crud-empty" style="display:none;">No readings yet.</div>
          <table class="data-table" id="iotHistoryTable">
            <thead>
              <tr><th>NO.</th><th>Temperature</th><th>Humidity</th><th>Alert</th><th>Recorded</th></tr>
            </thead>
            <tbody id="iotHistoryTableBody"></tbody>
          </table>
        </div>

        <div class="card" id="iotUsersCard" style="display:none;">
          <h2>Manage User Roles <span style="color:var(--text-muted);font-size:11px;font-family:var(--font-mono);font-weight:400;">(Admin only)</span></h2>
          <p style="font-family:var(--font-mono);font-size:11px;color:var(--text-muted);margin-bottom:18px;letter-spacing:0.04em;">
            ⓘ Promote a viewer to admin, or demote an admin back to viewer. The "Manage Accounts" section above still handles creating, editing, and deleting accounts.
          </p>
          <table class="data-table" id="iotUsersTable">
            <thead>
              <tr><th>NO.</th><th>Name</th><th>Username</th><th>Role</th><th>Action</th></tr>
            </thead>
            <tbody id="iotUsersTableBody"></tbody>
          </table>
        </div>
      </div>
      <a href="#home" class="back-top">↑ Back to Top</a>

      <a name="contact"></a>
      <div class="section">
        <div class="section-title">Contact</div>
        <div class="section-hr"></div>

        <div class="contact-grid">
          <div class="contact-item">
            <span class="contact-icon">✉</span>
            <div>
              <div class="contact-label">Email Address</div>
              <div class="contact-value"><a href="mailto:gaylord.pascual@isu.edu.ph">gaylord.pascual@isu.edu.ph</a></div>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">📞</span>
            <div>
              <div class="contact-label">Phone Number</div>
              <div class="contact-value">+63 912 345 6789</div>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">📍</span>
            <div>
              <div class="contact-label">Home Address</div>
              <div class="contact-value">Brgy. Tuguegarao, Echague, Isabela 3309, Philippines</div>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">🏫</span>
            <div>
              <div class="contact-label">School</div>
              <div class="contact-value">Isabela State University – Echague Campus</div>
            </div>
          </div>
          <div class="contact-item">
            <span class="contact-icon">💬</span>
            <div>
              <div class="contact-label">Facebook</div>
              <div class="contact-value">facebook.com/gaylord.pascual</div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-top:24px">
          <h2>Location Reference</h2>
          <table class="data-table">
            <tr><td>Barangay</td><td>Tuguegarao</td></tr>
            <tr><td>Municipality</td><td>Echague</td></tr>
            <tr><td>Province</td><td>Isabela</td></tr>
            <tr><td>ZIP Code</td><td>3309</td></tr>
            <tr><td>Region</td><td>Region II (Cagayan Valley), Philippines</td></tr>
          </table>
        </div>
      </div>

      <div class="card">
        <h2>Visit ISU Website</h2>
        <button class="crud-btn crud-btn-add" onclick="showPopup()">Open ISU Website</button>
      </div>

      <a href="#home" class="back-top">↑ Back to Top</a>

      <footer>
        <div class="footer-copy">
          © Midyear 2026 Gaylord A. Pascual · BSIT 2-2 NETSEC · ISU Echague Campus · Network Security — Activities 1–6
        </div>
      </footer>
    </div>
   
    <div id="deleteModal" class="modal-overlay" style="display:none;">
      <div class="modal-box">
        <div class="modal-icon">⚠</div>
        <div class="modal-title">Confirm Delete</div>
        <div class="modal-msg" id="deleteModalMsg"></div>
        <div class="modal-actions">
          <button class="crud-btn crud-btn-delete" id="confirmDeleteBtn">Delete</button>
          <button class="crud-btn crud-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div id="accountDeleteModal" class="modal-overlay" style="display:none;">
      <div class="modal-box">
        <div class="modal-icon">⚠</div>
        <div class="modal-title">Confirm Delete</div>
        <div class="modal-msg" id="accountDeleteModalMsg"></div>
        <div class="modal-actions">
          <button class="crud-btn crud-btn-delete" id="confirmAccountDeleteBtn">Delete</button>
          <button class="crud-btn crud-btn-cancel" onclick="closeAccountDeleteModal()">Cancel</button>
        </div>
      </div>
    </div>

    <div id="temperatureAlertModal" class="modal-overlay" style="display:none;">
      <div class="modal-box modal-box-danger">
        <div class="modal-icon modal-icon-danger">🔥</div>
        <div class="modal-title modal-title-danger">High Temperature Alert</div>
        <div class="modal-msg" id="temperatureAlertModalMsg"></div>
        <div class="modal-actions">
          <button class="crud-btn crud-btn-delete" onclick="closeTemperatureAlertModal()">Acknowledge</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="script.js"></script>
</body>
</html>