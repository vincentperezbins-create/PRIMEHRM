<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/ld_helpers.php';

$userModel = new User($pdo);
require_login();
require_once __DIR__ . '/partials/session.php';
ld_ensure_schema($pdo);

$stmt = $pdo->prepare("
    SELECT
        u.*,
        r.role_name,
        p.position_title,
        p.position_category,
        p.salary_grade,
        s.schoolname,
        s.schooladdress,
        d.district_name,
        o.office_name,
        o.office_type,
        du.unit_name AS division_unit_name,
        ou.unit_name AS office_unit_name
    FROM sdopang1_user u
    LEFT JOIN sdopang1_roles r ON r.role_id = u.role_id
    LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_district d ON d.districtID = s.district
    LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
    LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
    LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id
    WHERE u.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$profile) {
    die('Profile not found');
}

$token = generateToken();

$h = static function ($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$dateValue = static function ($value): string {
    if (!$value || $value === '0000-00-00') {
        return '';
    }

    $time = strtotime((string) $value);
    return $time ? date('Y-m-d', $time) : '';
};

$displayDate = static function ($value): string {
    if (!$value || $value === '0000-00-00') {
        return '-';
    }

    $time = strtotime((string) $value);
    return $time ? date('m/d/Y', $time) : '-';
};

$yesNo = static function ($value): string {
    return ((int) $value === 1 || strtoupper((string) $value) === 'YES') ? 'YES' : 'NO';
};

$fullName = trim(implode(' ', array_filter([
    $profile['first_name'] ?? '',
    $profile['middle_name'] ?? '',
    $profile['last_name'] ?? '',
    $profile['name_extension'] ?? '',
])));
$displayName = $fullName !== '' ? $fullName : 'My Profile';
$initials = strtoupper(substr((string) ($profile['first_name'] ?? 'U'), 0, 1) . substr((string) ($profile['last_name'] ?? 'P'), 0, 1));
$completionFields = [
    'first_name', 'middle_name', 'last_name', 'name_extension', 'email', 'employeeID',
    'tin', 'prc_license_number', 'sex', 'age', 'civil_status', 'religion', 'region',
    'person_with_disability', 'indigenous_group', 'solo_parent', 'educational_background',
    'specify_educational_background', 'grade_level_taught', 'specialization',
    'actual_subjects_taught', 'years_in_current_position', 'appointmentdate',
    'assumptiontoduty', 'position_title', 'position_category', 'school_id', 'schoolname',
    'district_name', 'office_name', 'division_unit_name', 'office_unit_name', 'role_name',
    'status', 'username', 'ucode', 'sdo',
];
$completedFields = 0;
foreach ($completionFields as $field) {
    if (trim((string) ($profile[$field] ?? '')) !== '') {
        $completedFields++;
    }
}
$completionPercent = (int) round(($completedFields / count($completionFields)) * 100);

$serviceStart = $profile['assumptiontoduty'] ?: ($profile['appointmentdate'] ?? null);
$lengthOfService = '-';
if ($serviceStart && $serviceStart !== '0000-00-00') {
    try {
        $interval = (new DateTimeImmutable((string) $serviceStart))->diff(new DateTimeImmutable('today'));
        $lengthOfService = $interval->y . ' years, ' . $interval->m . ' months, ' . $interval->d . ' days';
    } catch (Exception $e) {
        $lengthOfService = '-';
    }
}

$avatarSrc = trim((string) ($profile['user_image'] ?? ''));
$profileSuccessMessage = $_SESSION['success_message'] ?? '';
$profileErrorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
$profileCertificates = ld_certificate_submissions($pdo, $currentUser, null, 50, true);
$profileGeneratedCertificates = ld_generated_certificates($pdo, $currentUser, null, 50, true);
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <style>
    .profile-page-body { background: #f4f7fb; }
    .profile-sheet {
      color: #0f172a;
      background: transparent !important;
      border: 0 !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      padding: 0 0 24px !important;
    }
    .profile-top-card {
      margin-bottom: 22px;
      padding: 24px;
      background: #fff;
      border: 1px solid #dbe5f1;
      border-radius: 22px;
      box-shadow: 0 18px 46px rgba(15, 23, 42, 0.08);
    }
    .profile-hero-card {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 260px;
      gap: 24px;
      align-items: center;
      margin-bottom: 18px;
    }
    .profile-eyebrow {
      display: inline-flex;
      margin-bottom: 8px;
      color: #2563eb;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }
    .profile-hero-content h2 {
      color: #0f172a;
      font-size: 30px;
      line-height: 1.2;
      margin-bottom: 8px;
    }
    .profile-hero-content > p {
      color: #64748b;
      font-size: 15px;
      margin-bottom: 18px;
    }
    .profile-completion-card {
      max-width: 680px;
      padding: 18px;
      background: #f8fbff;
      border: 1px solid #dbe5f1;
      border-radius: 18px;
    }
    .profile-completion-topline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 6px;
    }
    .profile-completion-topline span {
      color: #0f172a !important;
      font-size: 14px !important;
      font-weight: 800 !important;
      text-transform: none !important;
    }
    .profile-completion-topline strong {
      margin: 0 !important;
      color: #2563eb !important;
      font-size: 18px !important;
      line-height: 1 !important;
    }
    .profile-completion-card p {
      color: #64748b !important;
      font-size: 13px !important;
      margin: 0 0 12px !important;
    }
    .profile-completion-progress {
      height: 24px !important;
      overflow: hidden;
      background: #eaf1fb;
      border: 1px solid #dbeafe;
      border-radius: 999px;
    }
    .profile-completion-progress i {
      display: flex !important;
      align-items: center;
      justify-content: flex-end;
      width: var(--progress);
      min-width: 48px;
      height: 100%;
      padding-right: 10px;
      color: #fff;
      font-style: normal;
      font-size: 12px;
      font-weight: 800;
      border-radius: inherit;
      background: linear-gradient(90deg, #2563eb, #22c55e);
    }
    .profile-completion-progress span {
      color: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      text-transform: none !important;
    }
    .profile-photo-panel {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      width: 100%;
      padding: 0 !important;
    }
    .profile-photo-frame {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 148px;
      height: 148px;
      border-radius: 50%;
      background: #fff;
      box-shadow: 0 18px 38px rgba(15, 23, 42, 0.14);
    }
    .profile-photo {
      width: 130px !important;
      height: 130px !important;
      border: 5px solid #fff;
      border-radius: 50% !important;
      overflow: hidden;
      background: linear-gradient(135deg, #dbeafe, #f8fafc);
      color: #1d4ed8;
      font-size: 34px;
    }
    .profile-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .profile-photo-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px;
      width: 100%;
    }
    .profile-photo-panel .btn,
    .profile-form-actions .btn {
      min-height: 40px;
      border-radius: 999px !important;
      padding: 9px 16px;
      font-size: 13px;
      font-weight: 800;
    }
    .profile-summary-row {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }
    .profile-summary-card {
      min-height: 96px;
      padding: 18px;
      background: #f8fbff;
      border: 1px solid #dbe5f1;
      border-radius: 16px;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
    }
    .profile-summary-card span,
    .profile-service-card span {
      display: block;
      color: #64748b;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }
    .profile-summary-card strong {
      display: block;
      margin-top: 8px;
      color: #0f172a;
      font-size: 16px;
      line-height: 1.35;
    }
    .profile-form-section {
      display: block;
      margin: 0 0 18px !important;
      padding: 0 !important;
      overflow: hidden;
      background: #f8fbff !important;
      border: 1px solid #dbe5f1 !important;
      border-radius: 18px !important;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06) !important;
    }
    .profile-form-heading {
      margin: 0 !important;
      padding: 16px 18px !important;
      background: #fff !important;
      border-bottom: 1px solid #dbe5f1;
    }
    .profile-form-heading h5 {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #0f172a !important;
      font-size: 17px !important;
      margin: 0 !important;
    }
    .profile-form-heading h5 span {
      min-width: 40px !important;
      height: 40px !important;
      border-radius: 12px !important;
      color: #2563eb !important;
      background: #eaf1ff !important;
    }
    .profile-form-section > .row { padding: 18px 18px 2px; }
    .profile-sheet .profile-field { margin-bottom: 16px !important; }
    .profile-sheet .profile-field label {
      color: #334155 !important;
      font-size: 12px !important;
      font-weight: 800 !important;
      margin-bottom: 7px !important;
    }
    .profile-sheet .form-control {
      min-height: 46px !important;
      height: 46px !important;
      color: #0f172a !important;
      border-color: #dbe5f1 !important;
      border-radius: 12px !important;
      background: #fff !important;
      font-size: 14px !important;
    }
    .profile-sheet .form-control[readonly],
    .profile-sheet .form-control:disabled {
      color: #64748b !important;
      background: #f1f5f9 !important;
    }
    .profile-form-actions {
      position: sticky;
      bottom: 12px;
      z-index: 4;
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 22px;
      padding: 16px !important;
      background: rgba(255, 255, 255, 0.94) !important;
      border: 1px solid #dbe5f1 !important;
      border-radius: 18px !important;
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.12) !important;
      backdrop-filter: blur(12px);
    }
    .profile-reminder {
      max-width: 460px;
      margin-right: auto;
      padding: 10px 12px;
      border: 1px solid #dbeafe;
      border-radius: 14px;
      background: #eff6ff;
    }
    .profile-form-actions .btn-primary {
      color: #fff !important;
      border-color: #2563eb !important;
      background: #2563eb !important;
    }
    @media (max-width: 991.98px) {
      .profile-hero-card { grid-template-columns: 1fr; }
      .profile-summary-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .profile-form-actions { align-items: stretch; flex-direction: column; }
      .profile-form-actions .btn, .profile-reminder { width: 100%; max-width: none; }
    }
    @media (max-width: 575.98px) {
      .profile-top-card { padding: 16px; border-radius: 18px; }
      .profile-summary-row { grid-template-columns: 1fr; }
      .profile-photo-actions { flex-direction: column; }
      .profile-photo-panel .btn { width: 100%; }
    }
  </style>
  <body class="profile-page-body">
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20">
        <form method="POST" action="profile_update.php" class="profile-sheet" enctype="multipart/form-data">
          <input type="hidden" name="token" value="<?= $h($token) ?>">
          <input type="hidden" name="remove_profile_photo" id="removeProfilePhoto" value="0">

          <div class="profile-top-card">
            <div class="profile-hero-card">
              <div class="profile-hero-content">
                <span class="profile-eyebrow">PRIMEHR / SDO 1 Pangasinan</span>
                <h2>My Profile</h2>
                <p>Update and manage your personal, employment, and government information.</p>

                <div class="profile-completion-card">
                  <div class="profile-completion-topline">
                    <span>Profile Completion</span>
                    <strong><?= $completionPercent ?>%</strong>
                  </div>
                  <p>Complete your profile to keep your information up to date.</p>
                  <div class="profile-completion-progress" aria-label="Profile completion <?= $completionPercent ?>%">
                    <i style="--progress: <?= $completionPercent ?>%;"><span><?= $completionPercent ?>%</span></i>
                  </div>
                </div>
              </div>

              <aside class="profile-photo-panel">
                <div class="profile-photo-frame">
                  <div class="profile-photo" id="profilePhotoPreview">
                    <?php if ($avatarSrc !== ''): ?>
                      <img src="<?= $h($avatarSrc) ?>" alt="">
                    <?php else: ?>
                      <span><?= $h($initials) ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="profile-photo-actions">
                  <label class="btn btn-outline-primary btn-sm mb-0" for="profilePhotoInput"><i class="dw dw-upload1"></i> Change Photo</label>
                  <input id="profilePhotoInput" class="profile-photo-input" type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp,image/gif">
                  <button type="button" class="btn btn-outline-danger btn-sm" id="removeProfilePhotoButton"><i class="dw dw-delete-3"></i> Remove Photo</button>
                </div>
              </aside>
            </div>

            <div class="profile-summary-row">
              <div class="profile-summary-card">
                <span>Full Name</span>
                <strong><?= $h($displayName) ?></strong>
              </div>
              <div class="profile-summary-card">
                <span>Position</span>
                <strong><?= $h($profile['position_title'] ?: '-') ?></strong>
              </div>
              <div class="profile-summary-card">
                <span>School / Office</span>
                <strong><?= $h($profile['schoolname'] ?: ($profile['office_name'] ?: '-')) ?></strong>
              </div>
            </div>
          </div>

          <section class="profile-form-section profile-personal-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-user1"></i> 1. </span>Personal Information</h5>
            </div>
            <div class="row">
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>First Name *</label><input name="first_name" class="form-control" required value="<?= $h($profile['first_name']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Middle Name</label><input name="middle_name" class="form-control" value="<?= $h($profile['middle_name']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Last Name *</label><input name="last_name" class="form-control" required value="<?= $h($profile['last_name']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Name Extension</label><input name="name_extension" class="form-control" value="<?= $h($profile['name_extension']) ?>"></div>
              <div class="col-lg-2 col-md-4 mb-3 profile-field"><label>Age</label><input name="age" type="number" min="0" class="form-control" value="<?= $h($profile['age']) ?>"></div>
              <div class="col-lg-3 col-md-4 mb-3 profile-field">
                <label>Sex</label>
                <select name="sex" class="form-control">
                  <option value="">Select</option>
                  <?php foreach (['Male', 'Female', 'Other'] as $sex): ?>
                    <option value="<?= $h($sex) ?>" <?= ($profile['sex'] ?? '') === $sex ? 'selected' : '' ?>><?= $h($sex) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-lg-3 col-md-4 mb-3 profile-field"><label>Civil Status</label><input name="civil_status" class="form-control" value="<?= $h($profile['civil_status']) ?>"></div>
              <div class="col-lg-2 col-md-4 mb-3 profile-field"><label>Religion</label><input name="religion" class="form-control" value="<?= $h($profile['religion']) ?>"></div>
              <div class="col-lg-2 col-md-4 mb-3 profile-field"><label>Region</label><input name="region" class="form-control" value="<?= $h($profile['region']) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-email1"></i> 2. </span>Account & Contact Information</h5>
            </div>
            <div class="row">
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Email Address</label><input name="email" type="email" class="form-control" value="<?= $h($profile['email']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Username</label><input class="form-control" readonly value="<?= $h($profile['username']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>User Code</label><input class="form-control" readonly value="<?= $h($profile['ucode']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>SDO</label><input class="form-control" readonly value="<?= $h($profile['sdo']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Date Added</label><input class="form-control" readonly value="<?= $h($displayDate($profile['date_added'])) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Profile Image</label><input class="form-control" readonly value="<?= $h($profile['user_image']) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-briefcase"></i> 3. </span>Employment Details</h5>
            </div>
            <div class="row align-items-end">
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Job Title</label><input class="form-control" readonly value="<?= $h($profile['position_title']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Employee Status</label><input class="form-control" readonly value="<?= $h(ucfirst((string) $profile['status'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Employment Status</label><input class="form-control" readonly value="<?= $h($profile['position_category']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Date Hired / Appointment</label><input name="appointmentdate" type="date" class="form-control" value="<?= $h($dateValue($profile['appointmentdate'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Assumption to Duty</label><input name="assumptiontoduty" type="date" class="form-control" value="<?= $h($dateValue($profile['assumptiontoduty'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Salary Grade</label><input class="form-control" readonly value="<?= $h($profile['salary_grade']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Office Role</label><input class="form-control" readonly value="<?= $h($profile['office_role'] ?: 'Staff') ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3">
                <div class="profile-service-card">
                  <span>Length of Service</span>
                  <strong><?= $h($lengthOfService) ?></strong>
                  <small>As of today</small>
                </div>
              </div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-building"></i> 4. </span>School Information</h5>
            </div>
            <div class="row">
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>School ID</label><input class="form-control" readonly value="<?= $h($profile['school_id']) ?>"></div>
              <div class="col-lg-5 col-md-6 mb-3 profile-field"><label>School Name</label><input class="form-control" readonly value="<?= $h($profile['schoolname']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>District</label><input class="form-control" readonly value="<?= $h($profile['district_name']) ?>"></div>
              <div class="col-lg-6 col-md-6 mb-3 profile-field"><label>School Address</label><input class="form-control" readonly value="<?= $h($profile['schooladdress']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Division Unit</label><input class="form-control" readonly value="<?= $h($profile['division_unit_name'] ?: $profile['division_unit']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Office Unit</label><input class="form-control" readonly value="<?= $h($profile['office_unit_name']) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-library"></i> 5. </span>Educational Background</h5>
            </div>
            <div class="row">
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Educational Background</label><input name="educational_background" class="form-control" value="<?= $h($profile['educational_background']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Specify Educational Background</label><input name="specify_educational_background" class="form-control" value="<?= $h($profile['specify_educational_background']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Years in Current Position</label><input name="years_in_current_position" type="number" min="0" class="form-control" value="<?= $h($profile['years_in_current_position']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Grade Level Taught</label><input name="grade_level_taught" class="form-control" value="<?= $h($profile['grade_level_taught']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Specialization</label><input name="specialization" class="form-control" value="<?= $h($profile['specialization']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Actual Subjects Taught</label><input name="actual_subjects_taught" class="form-control" value="<?= $h($profile['actual_subjects_taught']) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-group"></i> 6. </span>Family & Personal Declarations</h5>
            </div>
            <div class="row">
              <div class="col-lg-4 col-md-6 mb-3 profile-field">
                <label>Person with Disability</label>
                <select name="person_with_disability" class="form-control">
                  <option value="">Select</option>
                  <?php foreach (['YES', 'NO'] as $value): ?>
                    <option value="<?= $value ?>" <?= ($profile['person_with_disability'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field">
                <label>Indigenous Group</label>
                <select name="indigenous_group" class="form-control">
                  <option value="">Select</option>
                  <?php foreach (['YES', 'NO'] as $value): ?>
                    <option value="<?= $value ?>" <?= ($profile['indigenous_group'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field">
                <label>Solo Parent</label>
                <select name="solo_parent" class="form-control">
                  <option value="">Select</option>
                  <?php foreach (['YES', 'NO'] as $value): ?>
                    <option value="<?= $value ?>" <?= ($profile['solo_parent'] ?? '') === $value ? 'selected' : '' ?>><?= $value ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-bank"></i> 7. </span>Government Benefit Details</h5>
            </div>
            <div class="row">
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Employee ID *</label><input name="employeeID" class="form-control" value="<?= $h($profile['employeeID']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>TIN Number</label><input name="tin" class="form-control" value="<?= $h($profile['tin']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>SDO</label><input class="form-control" readonly value="<?= $h($profile['sdo']) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-shield"></i> 8. </span>Valid IDs</h5>
            </div>
            <div class="row">
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>PRC License Number</label><input name="prc_license_number" class="form-control" value="<?= $h($profile['prc_license_number']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>User ID</label><input class="form-control" readonly value="<?= $h($profile['user_id']) ?>"></div>
              <div class="col-lg-4 col-md-6 mb-3 profile-field"><label>Position ID</label><input class="form-control" readonly value="<?= $h($profile['position_id']) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-settings2"></i> 9. </span>System Access & Validation</h5>
            </div>
            <div class="row">
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Role</label><input class="form-control" readonly value="<?= $h($profile['role_name']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Office</label><input class="form-control" readonly value="<?= $h($profile['office_name']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Office Type</label><input class="form-control" readonly value="<?= $h($profile['office_type']) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Office Head</label><input class="form-control" readonly value="<?= $h($yesNo($profile['is_office_head'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Validate 201 Files</label><input class="form-control" readonly value="<?= $h($yesNo($profile['can_validate_201'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Validate OPCRF</label><input class="form-control" readonly value="<?= $h($yesNo($profile['can_validate_opcrf'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Validate IPCRF</label><input class="form-control" readonly value="<?= $h($yesNo($profile['can_validate_ipcrf'])) ?>"></div>
              <div class="col-lg-3 col-md-6 mb-3 profile-field"><label>Validate Leave</label><input class="form-control" readonly value="<?= $h($yesNo($profile['can_validate_leave'])) ?>"></div>
            </div>
          </section>

          <section class="profile-form-section">
            <div class="profile-form-heading">
              <h5><span><i class="dw dw-certificate"></i> 10. </span>L&amp;D Certificates</h5>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <p class="mb-0 text-muted">Approved certificates become part of your profile record. Pending certificates are waiting for validation.</p>
                  <a class="btn btn-outline-primary btn-sm" href="ld_certificate_submit.php">Add Certificate</a>
                </div>
                <div class="table-responsive">
                  <table class="table table-bordered mb-0">
                    <thead><tr><th>Certificate / Training</th><th>Status</th><th>Submitted</th><th>File</th></tr></thead>
                    <tbody>
                      <?php foreach ($profileGeneratedCertificates as $certificate): ?>
                        <tr>
                          <td><?= $h($certificate['training_title']) ?></td>
                          <td><?= ld_status_badge('Approved') ?></td>
                          <td><?= $h($displayDate($certificate['generated_at'])) ?></td>
                          <td><a class="btn btn-sm btn-outline-primary" href="ld_certificate_view.php?id=<?= (int) $certificate['generated_certificate_id'] ?>" target="_blank">View E-Certificate</a><?php if (!empty($certificate['pdf_path'])): ?> <a class="btn btn-sm btn-primary" href="<?= $h($certificate['pdf_path']) ?>" target="_blank">PDF</a><?php endif; ?></td>
                        </tr>
                      <?php endforeach; ?>
                      <?php foreach ($profileCertificates as $certificate): ?>
                        <tr>
                          <td><?= $h($certificate['training_title']) ?></td>
                          <td><?= ld_status_badge($certificate['status']) ?></td>
                          <td><?= $h($displayDate($certificate['submitted_at'])) ?></td>
                          <td><a class="btn btn-sm btn-outline-primary" href="<?= $h($certificate['certificate_path']) ?>" target="_blank">View</a></td>
                        </tr>
                      <?php endforeach; ?>
                      <?php if (!$profileCertificates && !$profileGeneratedCertificates): ?><tr><td colspan="4" class="text-center text-muted">No certificate submissions yet.</td></tr><?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>

          <div class="profile-form-actions">
            <div class="profile-reminder">
              <strong>Important Reminder</strong>
              <span>Ensure that all editable information is true and correct. System-controlled fields are shown for reference.</span>
            </div>
            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" name="save_draft" value="1" class="btn btn-outline-primary">Save as Draft</button>
            <button type="submit" class="btn btn-primary" id="saveProfileButton">Save Profile</button>
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
    <script>
      (function() {
        const profileSuccessMessage = <?= json_encode((string) $profileSuccessMessage) ?>;
        const profileErrorMessage = <?= json_encode((string) $profileErrorMessage) ?>;
        const form = document.querySelector('.profile-sheet');
        const input = document.getElementById('profilePhotoInput');
        const preview = document.getElementById('profilePhotoPreview');
        const removeInput = document.getElementById('removeProfilePhoto');
        const removeButton = document.getElementById('removeProfilePhotoButton');
        const initials = <?= json_encode($initials) ?>;

        const swalBase = {
          customClass: {
            popup: 'primehr-swal',
            confirmButton: 'btn btn-primary primehr-swal-confirm',
            cancelButton: 'btn btn-outline-secondary primehr-swal-cancel'
          },
          buttonsStyling: false
        };

        if (window.Swal) {
          if (profileErrorMessage) {
            Swal.fire({
              ...swalBase,
              title: 'Save Failed',
              text: profileErrorMessage,
              icon: 'error'
            });
          } else if (profileSuccessMessage) {
            const failed = /fail|error|invalid|unable|required/i.test(profileSuccessMessage);
            Swal.fire({
              ...swalBase,
              title: failed ? 'Save Failed' : 'Profile Saved',
              text: failed ? profileSuccessMessage : 'Your profile information has been updated successfully.',
              icon: failed ? 'error' : 'success'
            });
          }
        }

        if (form && window.Swal) {
          form.addEventListener('submit', function(event) {
            if (form.dataset.confirmedSubmit === '1') {
              delete form.dataset.confirmedSubmit;
              return;
            }

            const submitter = event.submitter;
            const isDraft = submitter && submitter.name === 'save_draft';

            event.preventDefault();

            Swal.fire({
              ...swalBase,
              title: isDraft ? 'Save as Draft?' : 'Save Profile?',
              text: isDraft
                ? 'Your changes will be saved but may still need completion later.'
                : 'Please confirm that all editable information is true and correct.',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: isDraft ? 'Yes, Save Draft' : 'Yes, Save',
              cancelButtonText: isDraft ? 'Review Again' : 'Review Again'
            }).then(function(result) {
              if (!result.isConfirmed) {
                return;
              }

              form.dataset.confirmedSubmit = '1';
              if (submitter && typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter);
                return;
              }

              if (submitter && submitter.name) {
                const hiddenSubmitter = document.createElement('input');
                hiddenSubmitter.type = 'hidden';
                hiddenSubmitter.name = submitter.name;
                hiddenSubmitter.value = submitter.value || '1';
                form.appendChild(hiddenSubmitter);
              }
              form.submit();
            });
          });
        }

        if (input && preview && removeInput && removeButton) {
          input.addEventListener('change', function() {
            const file = input.files && input.files[0];
            if (!file) {
              return;
            }

            removeInput.value = '0';
            const reader = new FileReader();
            reader.onload = function(event) {
              preview.innerHTML = '<img src="' + event.target.result + '" alt="">';
            };
            reader.readAsDataURL(file);
          });

          removeButton.addEventListener('click', function() {
            input.value = '';
            removeInput.value = '1';
            preview.innerHTML = '<span>' + initials + '</span>';
          });
        }
      })();
    </script>
  </body>
</html>
