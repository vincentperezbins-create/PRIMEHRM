<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/manual_generator.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$token = generateToken();
$roles = manual_role_options();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['token'] ?? '')) {
        $error = 'Invalid request token. Please reload the page and try again.';
    } else {
        $role = $_POST['role'] ?? 'staff_user';
        if (!isset($roles[$role])) {
            $role = 'staff_user';
        }

        try {
            $path = manual_generate_docx($role);
            if (!is_file($path)) {
                throw new RuntimeException('The manual file was not created.');
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment; filename="' . manual_output_filename($role) . '"');
            header('Content-Length: ' . filesize($path));
            header('Cache-Control: private, max-age=0, must-revalidate');
            readfile($path);
            exit;
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<?php require_once __DIR__ . '/partials/head.php'; ?>
<body>
<?php require_once __DIR__ . '/partials/preloader.php'; ?>
<?php require_once __DIR__ . '/partials/navbar.php'; ?>
<?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
<?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
<div class="mobile-menu-overlay"></div>

<div class="main-container">
  <div class="xs-pd-20-10 pd-ltr-20">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 pb-20">
      <div>
        <h2 class="mb-1">User Manual Generator</h2>
        <p class="text-700 mb-0">Generate role-based Microsoft Word manuals with screenshots, steps, notes, captions, and page numbers.</p>
      </div>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
      <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-7 mb-20">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-3">Generate DOCX Manual</h5>
            <form method="post">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
              <div class="form-group">
                <label for="role">User role <span class="text-danger">*</span></label>
                <select name="role" id="role" class="form-control" required>
                  <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Download Manual</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-5 mb-20">
        <div class="card">
          <div class="card-body">
            <h5 class="mb-3">Screenshot Folders</h5>
            <p class="text-700">Place screenshots in these folders before generating the manual:</p>
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Role</th>
                    <th>Folder</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($roles as $key => $label): ?>
                    <tr>
                      <td><?= htmlspecialchars($label) ?></td>
                      <td><code>manual_images/<?= htmlspecialchars($key) ?>/</code></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <p class="text-700 mb-0">Recommended file names: <code>admin-dashboard.png</code>, <code>user-list.png</code>, <code>apply-leave.png</code>. The generator also accepts numbered files like <code>01-login-page.png</code>.</p>
          </div>
        </div>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>
  </div>
</div>

<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<button class="welcome-modal-btn">
  <i class="fa fa-download"></i> Download
</button>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>
