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
?>
    <div class="header">
      <div class="header-left">
        <div class="menu-icon bi bi-list"></div>
        <div
          class="search-toggle-icon bi bi-search"
          data-toggle="header_search"
        ></div>
        <div class="header-search">
          <form>
            <div class="form-group mb-0">
              <i class="dw dw-search2 search-icon"></i>
              <input
                type="text"
                class="form-control search-input"
                placeholder="Search Here"
              />
              <div class="dropdown">
                <a
                  class="dropdown-toggle no-arrow"
                  href="#"
                  role="button"
                  data-toggle="dropdown"
                >
                  <i class="ion-arrow-down-c"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                  <div class="form-group row">
                    <label class="col-sm-12 col-md-2 col-form-label"
                      >From</label
                    >
                    <div class="col-sm-12 col-md-10">
                      <input
                        class="form-control form-control-sm form-control-line"
                        type="text"
                      />
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-12 col-md-2 col-form-label">To</label>
                    <div class="col-sm-12 col-md-10">
                      <input
                        class="form-control form-control-sm form-control-line"
                        type="text"
                      />
                    </div>
                  </div>
                  <div class="form-group row">
                    <label class="col-sm-12 col-md-2 col-form-label"
                      >Subject</label
                    >
                    <div class="col-sm-12 col-md-10">
                      <input
                        class="form-control form-control-sm form-control-line"
                        type="text"
                      />
                    </div>
                  </div>
                  <div class="text-right">
                    <button class="btn btn-primary">Search</button>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
      <div class="header-right">
        <div class="dashboard-setting user-notification">
          <div class="dropdown">
            <a
              class="dropdown-toggle no-arrow"
              href="javascript:;"
              data-toggle="right-sidebar"
            >
              <i class="dw dw-settings2"></i>
            </a>
          </div>
        </div>
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
