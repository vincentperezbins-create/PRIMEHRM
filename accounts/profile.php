<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

$userModel = new User($pdo);
require_login();
require_once __DIR__ . '/partials/session.php';

$stmt = $pdo->prepare("
    SELECT
        u.*,
        r.role_name,
        p.position_title,
        p.position_category,
        s.schoolname,
        o.office_name
    FROM sdopang1_user u
    LEFT JOIN sdopang1_roles r ON r.role_id = u.role_id
    LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die('Profile not found');
}

$token = generateToken();
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <body>
   <?php if (!empty($_SESSION['success_message'])): ?>
      <script>alert(<?= json_encode($_SESSION['success_message']) ?>);</script>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

   <?php require_once __DIR__ . '/partials/preloader.php'; ?>
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 pb-20">
          <div>
            <h2 class="mb-1">My Profile</h2>
            <p class="text-700 mb-0">Update your personal information and contact details.</p>
          </div>
        </div>

        <form method="POST" action="profile_update.php">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

          <div class="row">
            <div class="col-lg-8 mb-20">
              <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">Personal Information</h5>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label>First Name</label>
                    <input name="first_name" class="form-control" required value="<?= htmlspecialchars($profile['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>Middle Name</label>
                    <input name="middle_name" class="form-control" value="<?= htmlspecialchars($profile['middle_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>Last Name</label>
                    <input name="last_name" class="form-control" required value="<?= htmlspecialchars($profile['last_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label>Name Extension</label>
                    <input name="name_extension" class="form-control" value="<?= htmlspecialchars($profile['name_extension'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label>Age</label>
                    <input name="age" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) ($profile['age'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label>Sex</label>
                    <select name="sex" class="form-control">
                      <option value="">Select</option>
                      <?php foreach (['Male', 'Female', 'Other'] as $sex): ?>
                        <option value="<?= htmlspecialchars($sex, ENT_QUOTES, 'UTF-8') ?>" <?= ($profile['sex'] ?? '') === $sex ? 'selected' : '' ?>><?= htmlspecialchars($sex) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-3">
                    <label>Civil Status</label>
                    <input name="civil_status" class="form-control" value="<?= htmlspecialchars($profile['civil_status'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label>Email</label>
                    <input name="email" type="email" class="form-control" value="<?= htmlspecialchars($profile['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label>Religion</label>
                    <input name="religion" class="form-control" value="<?= htmlspecialchars($profile['religion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-3 mb-3">
                    <label>Region</label>
                    <input name="region" class="form-control" value="<?= htmlspecialchars($profile['region'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>Person with Disability</label>
                    <select name="person_with_disability" class="form-control">
                      <option value="">Select</option>
                      <?php foreach (['YES', 'NO'] as $value): ?>
                        <option value="<?= $value ?>" <?= ($profile['person_with_disability'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>Indigenous Group</label>
                    <select name="indigenous_group" class="form-control">
                      <option value="">Select</option>
                      <?php foreach (['YES', 'NO'] as $value): ?>
                        <option value="<?= $value ?>" <?= ($profile['indigenous_group'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>Solo Parent</label>
                    <select name="solo_parent" class="form-control">
                      <option value="">Select</option>
                      <?php foreach (['YES', 'NO'] as $value): ?>
                        <option value="<?= $value ?>" <?= ($profile['solo_parent'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">Professional Information</h5>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label>Educational Background</label>
                    <input name="educational_background" class="form-control" value="<?= htmlspecialchars($profile['educational_background'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label>Specify Educational Background</label>
                    <input name="specify_educational_background" class="form-control" value="<?= htmlspecialchars($profile['specify_educational_background'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label>Grade Level Taught</label>
                    <input name="grade_level_taught" class="form-control" value="<?= htmlspecialchars($profile['grade_level_taught'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label>Specialization</label>
                    <input name="specialization" class="form-control" value="<?= htmlspecialchars($profile['specialization'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label>Actual Subjects Taught</label>
                    <input name="actual_subjects_taught" class="form-control" value="<?= htmlspecialchars($profile['actual_subjects_taught'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label>Years in Current Position</label>
                    <input name="years_in_current_position" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) ($profile['years_in_current_position'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                </div>
              </div>

              <div class="card-box pd-20">
                <h5 class="mb-3">ID Information</h5>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label>Employee ID</label>
                    <input name="employeeID" class="form-control" value="<?= htmlspecialchars($profile['employeeID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>TIN</label>
                    <input name="tin" class="form-control" value="<?= htmlspecialchars($profile['tin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="col-md-4 mb-3">
                    <label>PRC License Number</label>
                    <input name="prc_license_number" class="form-control" value="<?= htmlspecialchars($profile['prc_license_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-4 mb-20">
              <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">Assignment</h5>
                <p class="text-700 mb-1">Role</p>
                <h6 class="mb-3"><?= htmlspecialchars($profile['role_name'] ?? '-') ?></h6>
                <p class="text-700 mb-1">Position</p>
                <h6 class="mb-3"><?= htmlspecialchars($profile['position_title'] ?? '-') ?></h6>
                <p class="text-700 mb-1">Position Category</p>
                <h6 class="mb-3"><?= htmlspecialchars($profile['position_category'] ?? '-') ?></h6>
                <p class="text-700 mb-1">School</p>
                <h6 class="mb-3"><?= htmlspecialchars($profile['schoolname'] ?? '-') ?></h6>
                <p class="text-700 mb-1">Office</p>
                <h6 class="mb-3"><?= htmlspecialchars($profile['office_name'] ?? '-') ?></h6>
                <p class="text-700 mb-1">Office Role</p>
                <h6 class="mb-0"><?= htmlspecialchars($profile['office_role'] ?? 'Staff') ?></h6>
              </div>

              <div class="card-box pd-20">
                <button type="submit" class="btn btn-primary btn-block">Save Profile</button>
                <a href="index.php" class="btn btn-outline-secondary btn-block mt-2">Cancel</a>
              </div>
            </div>
          </div>
        </form>

        <?php require_once __DIR__ . '/partials/footer.php'; ?>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
  </body>
</html>
