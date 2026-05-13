<?php
require_once __DIR__ . '/../core/notification_helpers.php';

$notificationItems = [];

if (isset($pdo, $currentUser, $_SESSION['role_id'])) {
    $roleId = (int) $_SESSION['role_id'];
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    foreach (notification_unread_for_user($pdo, $userId, $roleId, 10) as $dbNotification) {
        $notificationItems[] = [
            'title' => $dbNotification['title'],
            'message' => $dbNotification['message'],
            'href' => 'notification_open.php?id=' . urlencode((string) $dbNotification['notification_id']),
            'count' => 1,
            'icon' => 'dw dw-notification',
        ];
    }

    $addNotification = function (string $title, string $message, string $href, int $count, string $icon = 'dw dw-notification') use (&$notificationItems): void {
        if ($count <= 0) {
            return;
        }

        $notificationItems[] = [
            'title' => $title,
            'message' => $message,
            'href' => $href,
            'count' => $count,
            'icon' => $icon,
        ];
    };

    $addEventNotification = function (string $title, string $message, string $href, string $icon = 'dw dw-notification') use (&$notificationItems): void {
        $notificationItems[] = [
            'title' => $title,
            'message' => $message,
            'href' => $href,
            'count' => 1,
            'icon' => $icon,
        ];
    };

    try {
        if ($roleId === 1) {
            $stmt = $pdo->query("
                SELECT d.document_id, d.uploaded_at, t.doc_name,
                       TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS employee_name
                FROM sdopang1_documents d
                JOIN sdopang1_user u ON u.user_id = d.user_id
                JOIN sdopang1_document_types t ON t.doc_type_id = d.doc_type_id
                WHERE d.status = 'Pending'
                ORDER BY d.uploaded_at DESC
                LIMIT 5
            ");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $document) {
                $addEventNotification(
                    '201 File Uploaded',
                    ($document['employee_name'] ?: 'Employee') . ' uploaded ' . $document['doc_name'] . '.',
                    'admin_view_201_file.php?id=' . urlencode((string) $document['document_id']),
                    'dw dw-file'
                );
            }
            $addNotification(
                'Leave Applications',
                'Pending leave application(s) for review.',
                'admin_leave_applications.php',
                (int) $pdo->query("SELECT COUNT(*) FROM leave_applications WHERE status = 'pending'")->fetchColumn(),
                'dw dw-calendar1'
            );
            $addNotification(
                'Office/Unit OPCRF',
                'Office/unit OPCRF record(s) waiting for review.',
                'admin_opcrf_list.php',
                (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_opcrf WHERE status = 'For Review'")->fetchColumn(),
                'dw dw-analytics-21'
            );
            $addNotification(
                'Employee IPCRF',
                'Employee IPCRF submission(s) waiting for review.',
                'admin_ipcrf_list.php',
                (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_ipcrf WHERE status = 'For Review'")->fetchColumn(),
                'dw dw-analytics-21'
            );
        } elseif ($roleId === 3) {
            $schoolId = $currentUser['school_id'] ?? null;

            if ($schoolId !== null && $schoolId !== '') {
                $stmt = $pdo->prepare("
                    SELECT d.document_id, d.uploaded_at, t.doc_name,
                           TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS employee_name
                    FROM sdopang1_documents d
                    JOIN sdopang1_user u ON u.user_id = d.user_id
                    JOIN sdopang1_document_types t ON t.doc_type_id = d.doc_type_id
                    WHERE u.school_id = ?
                      AND u.role_id = 4
                      AND u.user_id <> ?
                      AND d.status = 'Pending'
                    ORDER BY d.uploaded_at DESC
                    LIMIT 5
                ");
                $stmt->execute([$schoolId, $userId]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $document) {
                    $addEventNotification(
                        'School 201 File Uploaded',
                        ($document['employee_name'] ?: 'Employee') . ' uploaded ' . $document['doc_name'] . '.',
                        'school_view_201_file.php?id=' . urlencode((string) $document['document_id']),
                        'dw dw-file'
                    );
                }

                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM leave_applications la
                    JOIN sdopang1_user u ON u.user_id = la.user_id
                    WHERE u.school_id = ?
                      AND la.status = 'pending'
                ");
                $stmt->execute([$schoolId]);
                $addNotification('School Leave', 'School leave application(s) pending review.', 'school_leave_applications.php', (int) $stmt->fetchColumn(), 'dw dw-calendar1');
            }
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_documents WHERE user_id = ? AND status = 'Returned'");
        $stmt->execute([$userId]);
        $addNotification('My 201 Files', 'Returned 201 file(s) need your action.', 'user_201_tables.php', (int) $stmt->fetchColumn(), 'dw dw-file');

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM leave_applications WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        $addNotification('My Leave', 'Your leave application(s) are still pending.', 'user_leave_history.php', (int) $stmt->fetchColumn(), 'dw dw-calendar1');

        $officeId = $currentUser['office_id'] ?? null;
        if (!$officeId) {
            $stmt = $pdo->prepare("SELECT office_id FROM sdopang1_offices WHERE office_head = ? ORDER BY office_id LIMIT 1");
            $stmt->execute([$userId]);
            $officeId = $stmt->fetchColumn() ?: null;
        }

        if ($officeId) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_opcrf WHERE office_id = ? AND status IN ('Returned','For Review')");
            $stmt->execute([$officeId]);
            $addNotification('Office/Unit OPCRF', 'Office/unit OPCRF item(s) need monitoring or action.', 'user_opcrf_list.php', (int) $stmt->fetchColumn(), 'dw dw-analytics-21');
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_ipcrf WHERE user_id = ? AND status IN ('Returned','For Review')");
        $stmt->execute([$userId]);
        $addNotification('My IPCRF', 'Your IPCRF item(s) need monitoring or action.', 'user_ipcrf_list.php', (int) $stmt->fetchColumn(), 'dw dw-analytics-21');
    } catch (Throwable $e) {
        $notificationItems = [];
    }
}

$notificationTotal = array_sum(array_column($notificationItems, 'count'));
$navbarFullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['middle_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
$navbarFullName = $navbarFullName !== '' ? $navbarFullName : 'User';
$navbarRoleName = trim((string) ($role['role_name'] ?? $currentUser['role_name'] ?? 'User'));
$navbarInitials = strtoupper(substr((string) ($currentUser['first_name'] ?? 'U'), 0, 1) . substr((string) ($currentUser['last_name'] ?? ''), 0, 1));
$navbarImage = trim((string) ($currentUser['user_image'] ?? ''));

$quickLinks = [];
$moreLinks = [];
if (isset($_SESSION['role_id'])) {
    $roleId = (int) $_SESSION['role_id'];
    if ($roleId === 1) {
        $quickLinks = [
            ['label' => 'Dashboard', 'href' => 'admin_dashboard.php', 'icon' => 'bi bi-grid-1x2'],
            ['label' => 'Users', 'href' => 'admin_users_list.php', 'icon' => 'bi bi-people'],
            ['label' => 'Schools', 'href' => 'admin_school_list.php', 'icon' => 'bi bi-building'],
            ['label' => '201 Files', 'href' => 'admin_201_tables.php', 'icon' => 'bi bi-folder-check'],
        ];
        $moreLinks = [
            ['label' => 'Leave', 'href' => 'admin_leave_applications.php', 'icon' => 'bi bi-calendar-check'],
            ['label' => 'L&D', 'href' => 'ld_dashboard.php', 'icon' => 'bi bi-mortarboard'],
            ['label' => 'Rewards', 'href' => 'rewards_dashboard.php', 'icon' => 'bi bi-trophy'],
            ['label' => 'Settings', 'href' => 'admin_notifications.php', 'icon' => 'bi bi-gear'],
            ['label' => 'Audit Logs', 'href' => 'admin_audit_logs.php', 'icon' => 'bi bi-shield-check'],
        ];
    } elseif ($roleId === 3) {
        $quickLinks = [
            ['label' => 'Dashboard', 'href' => 'school_dashboard.php', 'icon' => 'bi bi-grid-1x2'],
            ['label' => '201 Files', 'href' => 'school_201_tables.php', 'icon' => 'bi bi-folder-check'],
            ['label' => 'Leave', 'href' => 'school_leave_applications.php', 'icon' => 'bi bi-calendar-check'],
            ['label' => 'L&D', 'href' => 'ld_dashboard.php', 'icon' => 'bi bi-mortarboard'],
        ];
        $moreLinks = [
            ['label' => 'My Trainings', 'href' => 'ld_my_trainings.php', 'icon' => 'bi bi-award'],
            ['label' => 'Profile', 'href' => 'profile.php', 'icon' => 'bi bi-person'],
        ];
    } else {
        $quickLinks = [
            ['label' => 'Dashboard', 'href' => 'user_dashboard.php', 'icon' => 'bi bi-grid-1x2'],
            ['label' => 'My 201', 'href' => 'user_201_tables.php', 'icon' => 'bi bi-folder-check'],
            ['label' => 'Leave', 'href' => 'user_leave_apply.php', 'icon' => 'bi bi-calendar-plus'],
            ['label' => 'Trainings', 'href' => 'ld_my_trainings.php', 'icon' => 'bi bi-mortarboard'],
        ];
        $moreLinks = [
            ['label' => 'Certificates', 'href' => 'ld_my_trainings.php', 'icon' => 'bi bi-award'],
            ['label' => 'Recognitions', 'href' => 'rewards_my_recognitions.php', 'icon' => 'bi bi-trophy'],
            ['label' => 'Profile', 'href' => 'profile.php', 'icon' => 'bi bi-person'],
        ];
    }
}
?>
    <style>
      .header {
        background: rgba(255, 255, 255, .96) !important;
        border-bottom: 1px solid #eef2f7;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        backdrop-filter: blur(10px);
      }
      .prime-navbar-left {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
      }
      .prime-navbar-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 160px;
        padding-right: 10px;
        border-right: 1px solid #e5e7eb;
      }
      .prime-navbar-logo {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        object-fit: contain;
        background: #fff;
        box-shadow: 0 6px 16px rgba(15, 23, 42, .10);
      }
      .prime-navbar-title {
        display: flex;
        flex-direction: column;
        line-height: 1.1;
      }
      .prime-navbar-title strong {
        color: #111827;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .01em;
      }
      .prime-navbar-title span {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
      }
      .prime-quick-links {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
      }
      .prime-quick-link,
      .prime-more-toggle {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        padding: 8px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        color: #334155;
        background: #fff;
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, color .18s ease;
      }
      .prime-quick-link:hover,
      .prime-more-toggle:hover {
        color: #2563eb;
        text-decoration: none;
        border-color: #bfdbfe;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .10);
        transform: translateY(-1px);
      }
      .prime-quick-link i,
      .prime-more-toggle i {
        color: #2563eb;
        font-size: 15px;
      }
      .prime-more-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 38px;
        font-weight: 700;
      }
      .prime-more-menu .dropdown-item i {
        color: #2563eb;
      }
      .prime-mobile-shortcuts {
        display: none;
      }
      .prime-mobile-shortcut-toggle {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 36px;
        padding: 8px 12px;
        border: 1px solid #bfdbfe;
        border-radius: 12px;
        color: #2563eb;
        background: #eff6ff;
        font-size: 13px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .10);
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
      }
      .prime-mobile-shortcut-toggle:hover {
        color: #1d4ed8;
        background: #dbeafe;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 10px 22px rgba(37, 99, 235, .14);
      }
      .prime-mobile-shortcut-menu {
        min-width: 230px;
        padding: 8px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, .14);
      }
      .prime-mobile-shortcut-menu .dropdown-header {
        color: #94a3b8;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
      }
      .prime-mobile-shortcut-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 40px;
        border-radius: 10px;
        color: #334155;
        font-weight: 800;
      }
      .prime-mobile-shortcut-menu .dropdown-item i {
        color: #2563eb;
        font-size: 15px;
      }
      .prime-mobile-shortcut-menu .dropdown-item:hover {
        color: #2563eb;
        background: #eff6ff;
      }
      .header-right {
        gap: 6px;
      }
      .github-link {
        display: none !important;
      }
      .navbar-user-initials {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        color: #fff;
        background: linear-gradient(135deg, #2563eb, #7c3aed);
        font-size: 13px;
        font-weight: 800;
      }
      .navbar-user-meta {
        display: inline-flex;
        flex-direction: column;
        line-height: 1.15;
      }
      .navbar-user-meta .user-role {
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
      }
      @media (max-width: 1199.98px) {
        .prime-navbar-title,
        .prime-quick-link span {
          display: none;
        }
        .prime-navbar-brand {
          min-width: auto;
        }
        .prime-quick-link {
          width: 38px;
          justify-content: center;
          padding: 8px;
        }
      }
      @media (max-width: 767.98px) {
        .prime-quick-links {
          display: none;
        }
        .prime-mobile-shortcuts {
          display: block;
        }
        .prime-navbar-left {
          gap: 8px;
        }
      }
    </style>
    <div class="header">
      <div class="header-left prime-navbar-left">
        <div class="menu-icon bi bi-list"></div>
        <a class="prime-navbar-brand" href="index.php">
          <img class="prime-navbar-logo" src="../assets_pang1/logo.png" alt="SDO 1 Pangasinan">
          <span class="prime-navbar-title">
            <strong>PRIMEHR</strong>
            <span>SDO 1 Pangasinan</span>
          </span>
        </a>
        <div class="prime-quick-links">
          <?php foreach ($quickLinks as $link): ?>
            <a class="prime-quick-link" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
              <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
              <span><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php endforeach; ?>
          <?php if ($moreLinks): ?>
            <div class="dropdown">
              <a class="prime-more-toggle dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                <i class="bi bi-lightning-charge"></i><span>More</span>
              </a>
              <div class="dropdown-menu prime-more-menu">
                <?php foreach ($moreLinks as $link): ?>
                  <a class="dropdown-item" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($quickLinks): ?>
          <div class="dropdown prime-mobile-shortcuts">
            <a class="prime-mobile-shortcut-toggle dropdown-toggle" href="#" role="button" data-toggle="dropdown">
              <i class="bi bi-lightning-charge"></i>
              <span>Quick</span>
            </a>
            <div class="dropdown-menu prime-mobile-shortcut-menu">
              <div class="dropdown-header">Quick Links</div>
              <?php foreach ($quickLinks as $link): ?>
                <a class="dropdown-item" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
                  <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                  <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                </a>
              <?php endforeach; ?>
              <?php if ($moreLinks): ?>
                <div class="dropdown-divider"></div>
                <div class="dropdown-header">More</div>
                <?php foreach ($moreLinks as $link): ?>
                  <a class="dropdown-item" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="<?= htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    <?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="header-right">
        <!-- <div class="dashboard-setting user-notification">
          <div class="dropdown">
            <a
              class="dropdown-toggle no-arrow"
              href="javascript:;"
              data-toggle="right-sidebar"
            >
              <i class="dw dw-settings2"></i>
            </a>
          </div>
        </div> -->
        <div class="user-notification">
          <div class="dropdown">
            <a
              class="dropdown-toggle no-arrow"
              href="#"
              role="button"
              data-toggle="dropdown"
            >
              <i class="icon-copy dw dw-notification"></i>
              <?php if ($notificationTotal > 0): ?>
                <span class="badge notification-active"></span>
              <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
              <div class="notification-list mx-h-350 customscroll">
                <ul>
                  <?php foreach ($notificationItems as $item): ?>
                    <li>
                      <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="mr-2"><i class="<?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                        <h3><?= htmlspecialchars($item['title']) ?> (<?= htmlspecialchars((string) $item['count']) ?>)</h3>
                        <p><?= htmlspecialchars($item['message']) ?></p>
                      </a>
                    </li>
                  <?php endforeach; ?>
                  <?php if (!$notificationItems): ?>
                    <li>
                      <a href="javascript:;">
                        <span class="mr-2"><i class="dw dw-check"></i></span>
                        <h3>No notifications</h3>
                        <p>You are all caught up.</p>
                      </a>
                    </li>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
        <div class="user-info-dropdown">
          <div class="dropdown">
            <a
              class="dropdown-toggle"
              href="#"
              role="button"
              data-toggle="dropdown"
            >
              <span class="user-icon">
                <?php if ($navbarImage !== ''): ?>
                  <img src="<?= htmlspecialchars($navbarImage, ENT_QUOTES, 'UTF-8') ?>" alt="" />
                <?php else: ?>
                  <span class="navbar-user-initials"><?= htmlspecialchars($navbarInitials, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
              </span>
              <span class="navbar-user-meta">
                <span class="user-name"><?= htmlspecialchars($navbarFullName, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-role"><?= htmlspecialchars($navbarRoleName, ENT_QUOTES, 'UTF-8') ?></span>
              </span>
            </a>
            <div
              class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list"
            >
              <a class="dropdown-item" href="profile.php"
                ><i class="dw dw-user1"></i> Profile</a
              >
              <a class="dropdown-item" href="profile.php"
                ><i class="dw dw-settings2"></i> Setting</a
              >
              <a class="dropdown-item" href="faq.html"
                ><i class="dw dw-help"></i> Help</a
              >
              <a class="dropdown-item" href="logout.php"
                ><i class="dw dw-logout"></i> Log Out</a
              >
            </div>
          </div>
        </div>
        <div class="github-link">
          <a href="https://github.com/dropways/deskapp" target="_blank"
            ><img src="vendors/images/github.svg" alt=""
          /></a>
        </div>
      </div>
    </div>
    <script>
      window.auditTrack = window.auditTrack || function(actionType, moduleName, recordId, description) {
        if (!window.fetch) return;
        const body = new URLSearchParams();
        body.append('action_type', actionType || '');
        body.append('module_name', moduleName || '');
        body.append('record_id', recordId || '');
        body.append('description', description || '');
        fetch('audit_track.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: body.toString(),
          credentials: 'same-origin'
        }).catch(function() {});
      };
    </script>
