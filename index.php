<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PRIMEHR | SDO 1 Pangasinan</title>
  <link rel="icon" type="image/png" href="assets_pang1/logo.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="vendors/styles/icon-font.min.css">
  <style>
    :root {
      --primary: #155eef;
      --primary-dark: #1849a9;
      --ink: #101828;
      --muted: #667085;
      --line: #d0d5dd;
      --soft: #f5f8ff;
      --success: #067647;
      --warning: #b54708;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: Inter, Arial, sans-serif;
      color: var(--ink);
      background: #ffffff;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .site-header {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 20;
      border-bottom: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(16, 24, 40, 0.42);
      backdrop-filter: blur(14px);
    }

    .nav-inner {
      width: min(1180px, calc(100% - 32px));
      min-height: 76px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
      color: #ffffff;
    }

    .brand img {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      background: #ffffff;
      padding: 4px;
    }

    .brand strong {
      display: block;
      font-size: 17px;
      line-height: 1.1;
      letter-spacing: 0;
    }

    .brand span {
      display: block;
      margin-top: 3px;
      font-size: 12px;
      color: rgba(255, 255, 255, 0.78);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 22px;
      color: rgba(255, 255, 255, 0.86);
      font-size: 14px;
      font-weight: 700;
    }

    .nav-links a:hover {
      color: #ffffff;
    }

    .nav-login {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 42px;
      padding: 0 16px;
      border-radius: 10px;
      background: #ffffff;
      color: var(--primary-dark);
      font-weight: 800;
      box-shadow: 0 12px 26px rgba(16, 24, 40, 0.18);
    }

    .hero {
      min-height: 92vh;
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      color: #ffffff;
      background:
        linear-gradient(105deg, rgba(16, 24, 40, 0.86) 0%, rgba(24, 73, 169, 0.78) 48%, rgba(21, 94, 239, 0.42) 100%),
        url("assets_pang1/SDO1 Pang building.png") center/cover no-repeat;
    }

    .hero::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 96px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0), #ffffff);
      pointer-events: none;
    }

    .hero-inner {
      width: min(1180px, calc(100% - 32px));
      margin: 0 auto;
      padding: 128px 0 86px;
      position: relative;
      z-index: 1;
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 18px;
      padding: 8px 12px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.28);
      background: rgba(255, 255, 255, 0.12);
      color: rgba(255, 255, 255, 0.92);
      font-size: 13px;
      font-weight: 800;
    }

    .hero h1 {
      max-width: 760px;
      margin: 0;
      color: #ffffff;
      font-size: clamp(42px, 8vw, 78px);
      line-height: 0.98;
      font-weight: 800;
      letter-spacing: 0;
    }

    .hero-copy {
      max-width: 660px;
      margin: 22px 0 0;
      color: rgba(255, 255, 255, 0.9);
      font-size: 18px;
      line-height: 1.7;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-top: 32px;
    }

    .btn {
      min-height: 48px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;
      padding: 0 18px;
      border-radius: 10px;
      font-weight: 800;
      transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
    }

    .btn-primary {
      background: #ffffff;
      color: var(--primary-dark);
      box-shadow: 0 16px 34px rgba(16, 24, 40, 0.22);
    }

    .btn-ghost {
      border: 1px solid rgba(255, 255, 255, 0.32);
      background: rgba(255, 255, 255, 0.1);
      color: #ffffff;
    }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
      margin-top: 54px;
      max-width: 860px;
    }

    .stat {
      min-height: 116px;
      padding: 18px;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      background: rgba(255, 255, 255, 0.12);
      backdrop-filter: blur(12px);
    }

    .stat i {
      font-size: 22px;
      color: #ffffff;
    }

    .stat strong {
      display: block;
      margin-top: 12px;
      font-size: 15px;
      color: #ffffff;
    }

    .stat span {
      display: block;
      margin-top: 5px;
      font-size: 12px;
      line-height: 1.45;
      color: rgba(255, 255, 255, 0.76);
    }

    .section {
      width: min(1180px, calc(100% - 32px));
      margin: 0 auto;
      padding: 76px 0;
    }

    .section-heading {
      max-width: 720px;
      margin-bottom: 30px;
    }

    .section-heading p {
      margin: 0 0 8px;
      color: var(--primary);
      font-size: 13px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .section-heading h2 {
      margin: 0;
      color: var(--ink);
      font-size: clamp(28px, 4vw, 42px);
      line-height: 1.1;
      letter-spacing: 0;
    }

    .module-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
    }

    .module-card {
      min-height: 220px;
      padding: 22px;
      border: 1px solid #eaecf0;
      border-radius: 14px;
      background: #ffffff;
      box-shadow: 0 12px 30px rgba(16, 24, 40, 0.06);
    }

    .module-card i {
      width: 44px;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: #eff4ff;
      color: var(--primary);
      font-size: 22px;
    }

    .module-card h3 {
      margin: 18px 0 8px;
      font-size: 18px;
      color: var(--ink);
    }

    .module-card p {
      margin: 0;
      color: var(--muted);
      font-size: 14px;
      line-height: 1.65;
    }

    .workflow {
      background: var(--soft);
      border-top: 1px solid #eaecf0;
      border-bottom: 1px solid #eaecf0;
    }

    .workflow-grid {
      display: grid;
      grid-template-columns: 0.9fr 1.1fr;
      gap: 34px;
      align-items: center;
    }

    .workflow-image {
      min-height: 420px;
      border-radius: 18px;
      background:
        linear-gradient(180deg, rgba(21, 94, 239, 0.08), rgba(16, 24, 40, 0.16)),
        url("assets_pang1/header.png") center/cover no-repeat;
      box-shadow: 0 20px 48px rgba(16, 24, 40, 0.12);
    }

    .steps {
      display: grid;
      gap: 12px;
    }

    .step {
      display: grid;
      grid-template-columns: 44px 1fr;
      gap: 14px;
      padding: 18px;
      border: 1px solid #e4e7ec;
      border-radius: 14px;
      background: #ffffff;
    }

    .step-number {
      width: 44px;
      height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: var(--primary);
      color: #ffffff;
      font-weight: 800;
    }

    .step h3 {
      margin: 0 0 5px;
      font-size: 16px;
    }

    .step p {
      margin: 0;
      color: var(--muted);
      line-height: 1.6;
      font-size: 14px;
    }

    .cta {
      padding: 70px 0;
      color: #ffffff;
      background:
        linear-gradient(100deg, rgba(24, 73, 169, 0.95), rgba(21, 94, 239, 0.9)),
        url("assets_pang1/SDO1 Pang building - Copy.png") center/cover no-repeat;
    }

    .cta-inner {
      width: min(1180px, calc(100% - 32px));
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
    }

    .cta h2 {
      margin: 0 0 10px;
      color: #ffffff;
      font-size: clamp(26px, 4vw, 40px);
      letter-spacing: 0;
    }

    .cta p {
      max-width: 660px;
      margin: 0;
      color: rgba(255, 255, 255, 0.86);
      line-height: 1.65;
    }

    .site-footer {
      padding: 24px 0;
      background: #101828;
      color: rgba(255, 255, 255, 0.72);
      font-size: 13px;
    }

    .footer-inner {
      width: min(1180px, calc(100% - 32px));
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
    }

    @media (max-width: 980px) {
      .nav-links a:not(.nav-login) {
        display: none;
      }

      .hero {
        min-height: auto;
      }

      .hero-stats,
      .module-grid,
      .workflow-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .workflow-grid {
        align-items: stretch;
      }
    }

    @media (max-width: 680px) {
      .nav-inner {
        min-height: 68px;
      }

      .brand span {
        max-width: 160px;
      }

      .hero-inner {
        padding-top: 112px;
        padding-bottom: 54px;
      }

      .hero-copy {
        font-size: 16px;
      }

      .hero-stats,
      .module-grid,
      .workflow-grid {
        grid-template-columns: 1fr;
      }

      .stat {
        min-height: auto;
      }

      .section {
        padding: 52px 0;
      }

      .workflow-image {
        min-height: 260px;
      }

      .cta-inner {
        align-items: flex-start;
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <header class="site-header">
    <div class="nav-inner">
      <a class="brand" href="index.php">
        <img src="assets_pang1/logo.png" alt="SDO 1 Pangasinan Logo">
        <span>
          <strong>PRIMEHR</strong>
          <span>SDO 1 Pangasinan</span>
        </span>
      </a>
      <nav class="nav-links" aria-label="Main navigation">
        <a href="#modules">Modules</a>
        <a href="#workflow">Workflow</a>
        <a href="accounts/login.php" class="nav-login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
      </nav>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-inner">
        <div class="eyebrow"><i class="bi bi-shield-check"></i> Human Resource Management System</div>
        <h1>PRIMEHR</h1>
        <p class="hero-copy">A unified digital workspace for employee records, 201 files, leave credits, OPCRF, IPCRF, and school or division HR validation workflows.</p>
        <div class="hero-actions">
          <a href="accounts/login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Sign In</a>
          <a href="#modules" class="btn btn-ghost"><i class="bi bi-grid"></i> View Modules</a>
        </div>
        <div class="hero-stats" aria-label="System modules">
          <div class="stat">
            <i class="bi bi-folder-check"></i>
            <strong>201 Files</strong>
            <span>Submit, validate, return, and monitor employee document requirements.</span>
          </div>
          <div class="stat">
            <i class="bi bi-calendar-check"></i>
            <strong>Leave Credits</strong>
            <span>Track applications, balances, ledgers, transactions, and reports.</span>
          </div>
          <div class="stat">
            <i class="bi bi-clipboard-data"></i>
            <strong>OPCRF/IPCRF</strong>
            <span>Manage office-level and individual performance submissions.</span>
          </div>
          <div class="stat">
            <i class="bi bi-people"></i>
            <strong>Role Access</strong>
            <span>Admin, office head, school head, validator, and staff/user workflows.</span>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="modules">
      <div class="section-heading">
        <p>Core Modules</p>
        <h2>Everything HR needs in one consistent system.</h2>
      </div>
      <div class="module-grid">
        <article class="module-card">
          <i class="bi bi-person-badge"></i>
          <h3>User Management</h3>
          <p>Create users, assign roles, connect division units, office units, schools, and validator tasks.</p>
        </article>
        <article class="module-card">
          <i class="bi bi-folder2-open"></i>
          <h3>201 Files</h3>
          <p>Employees upload records while assigned validators review, approve, or return files with remarks.</p>
        </article>
        <article class="module-card">
          <i class="bi bi-calendar2-week"></i>
          <h3>Leave System</h3>
          <p>Process leave applications, balances, ledgers, Form 6 signatories, and credit computations.</p>
        </article>
        <article class="module-card">
          <i class="bi bi-bar-chart-line"></i>
          <h3>Performance Forms</h3>
          <p>Support Office/Unit OPCRF and employee IPCRF tracking with role-aware responsibilities.</p>
        </article>
      </div>
    </section>

    <section class="workflow" id="workflow">
      <div class="section">
        <div class="workflow-grid">
          <div class="workflow-image" aria-label="PRIMEHR interface preview"></div>
          <div>
            <div class="section-heading">
              <p>Workflow</p>
              <h2>Built for submission, validation, and accountability.</h2>
            </div>
            <div class="steps">
              <div class="step">
                <span class="step-number">1</span>
                <div>
                  <h3>Employee submits records</h3>
                  <p>Staff and administrators submit 201 files, leave forms, leave credits, OPCRF, and IPCRF requirements.</p>
                </div>
              </div>
              <div class="step">
                <span class="step-number">2</span>
                <div>
                  <h3>Assigned personnel validate</h3>
                  <p>Validators review submissions based on their assigned task area and provide clear remarks when returning records.</p>
                </div>
              </div>
              <div class="step">
                <span class="step-number">3</span>
                <div>
                  <h3>Reports and ledgers stay updated</h3>
                  <p>Approved actions update balances, statuses, reports, and employee records across the system.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="cta">
      <div class="cta-inner">
        <div>
          <h2>Access PRIMEHR securely.</h2>
          <p>Use your assigned account to continue to your dashboard and manage the records connected to your role.</p>
        </div>
        <a href="accounts/login.php" class="btn btn-primary"><i class="bi bi-box-arrow-in-right"></i> Continue to Login</a>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="footer-inner">
      <span>PRIMEHR Human Resource Management System</span>
      <span>Schools Division Office 1 Pangasinan</span>
    </div>
  </footer>
</body>
</html>
