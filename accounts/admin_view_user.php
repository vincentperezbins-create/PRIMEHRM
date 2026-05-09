<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);


if (!$id) {
    die("Invalid request");
}

$stmt = $pdo->prepare("
    SELECT 
            u.*,
            s.*,
            d.*,
            c.*,
            p.*,
            o.office_name,
            du.unit_name AS division_unit_name,
            ou.unit_name AS office_unit_name

        FROM sdopang1_user u

        LEFT JOIN sdopang1schoollist s 
            ON s.schoolID = u.school_id

        LEFT JOIN sdopang1_district d 
            ON d.districtID = s.district

        LEFT JOIN sdopang1_cong c 
            ON c.cong_name = s.cong

        LEFT JOIN sdopang1_position p
            ON p.position_id = u.position_id

        LEFT JOIN sdopang1_offices o
            ON o.office_id = u.office_id

        LEFT JOIN division_units du
            ON du.division_unit_id = u.division_unit_id

        LEFT JOIN office_units ou
            ON ou.office_unit_id = u.office_unit_id
    WHERE u.user_id = ?
");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die("Access denied");
}
?>
<h5 style="color: red;">Personal Info</h5>
<p><strong>Fullname:</strong> <?= htmlspecialchars(trim(
    ($user['first_name'] ?? '') . ' ' .
    ($user['middle_name'] ?? '') . ' ' .
    ($user['last_name'] ?? '')
)) ?></p>
<p><strong>Age:</strong> <?= htmlspecialchars($user['age'])  ?></p>
<p><strong>Sex:</strong> <?= htmlspecialchars($user['sex'])  ?></p>
<p><strong>Email:</strong> <?= htmlspecialchars($user['email'])  ?></p>
<p><strong>Civil status:</strong> <?= htmlspecialchars($user['civil_status'])  ?></p>
<p><strong>Religion:</strong> <?= htmlspecialchars($user['religion'])  ?></p>
<p><strong>Person with disability:</strong> <?= htmlspecialchars($user['person_with_disability'])  ?></p>
<p><strong>Indigenous group:</strong> <?= htmlspecialchars($user['indigenous_group'])  ?></p>
<p><strong>Solo parent:</strong> <?= htmlspecialchars($user['solo_parent'])  ?></p>
<hr>
<h5 style="color: red;">School Info</h5>
<p><strong>Region:</strong> <?= htmlspecialchars($user['region'])  ?></p>
<p><strong>Position id:</strong> <?= htmlspecialchars($user['position_title'])  ?></p>
<p><strong>Form 6 Unit:</strong> <?= htmlspecialchars($user['division_unit_name'] ?? ($user['division_unit'] ?? 'School'))  ?></p>
<p><strong>Office Unit:</strong> <?= htmlspecialchars($user['office_unit_name'] ?? '')  ?></p>
<p><strong>Legacy Office:</strong> <?= htmlspecialchars($user['office_name'] ?? '')  ?></p>
<p><strong>Office Role:</strong> <?= htmlspecialchars($user['office_role'] ?? '')  ?></p>
<p><strong>School id:</strong> <?= htmlspecialchars($user['school_id'])  ?></p>
<p><strong>School Name:</strong> <?= htmlspecialchars($user['schoolname'])  ?></p>
<p><strong>District:</strong> <?= htmlspecialchars($user['district_name'])  ?></p>
<hr>
<h5 style="color: red;">Additional Info</h5>
<p><strong>Educational background:</strong> <?= htmlspecialchars($user['educational_background'])  ?></p>
<p><strong>Grade level taught:</strong> <?= htmlspecialchars($user['grade_level_taught'])  ?></p>
<p><strong>Specialization:</strong> <?= htmlspecialchars($user['specialization'])  ?></p>
<p><strong>Actual subjects taught:</strong> <?= htmlspecialchars($user['actual_subjects_taught'])  ?></p>
<p><strong>Years in current position:</strong> <?= htmlspecialchars($user['years_in_current_position'])  ?></p>
<p><strong>Specify educational background:</strong> <?= htmlspecialchars($user['specify_educational_background'])  ?></p>
<hr>
<h5 style="color: red;">ID Info</h5>
<p><strong>Employee ID:</strong> <?= htmlspecialchars($user['employeeID'])  ?></p>
<p><strong>Tin:</strong> <?= htmlspecialchars($user['tin'])  ?></p>
<p><strong>Prc license number:</strong> <?= htmlspecialchars($user['prc_license_number'])  ?></p>
<p><strong>Date Added:</strong> <?= htmlspecialchars($user['date_added'])  ?></p>


























