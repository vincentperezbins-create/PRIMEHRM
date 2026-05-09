<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

require_login();
require_role([1]);

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add' || $action === 'update') {
        $officeName = trim($_POST['office_name'] ?? '');
        $officeType = trim($_POST['office_type'] ?? '');
        $officeCategory = in_array($_POST['office_category'] ?? 'Division Office', ['Division Office', 'School'], true) ? $_POST['office_category'] : 'Division Office';
        $schoolId = trim($_POST['school_id'] ?? '') ?: null;
        $parentOfficeId = filter_input(INPUT_POST, 'parent_office_id', FILTER_VALIDATE_INT) ?: null;
        $officeHead = filter_input(INPUT_POST, 'office_head', FILTER_VALIDATE_INT) ?: null;
        $unitHead = filter_input(INPUT_POST, 'unit_head', FILTER_VALIDATE_INT) ?: null;
        $status = in_array($_POST['status'] ?? 'Active', ['Active', 'Inactive'], true) ? $_POST['status'] : 'Active';

        if ($officeName === '') {
            opcrf_json(['status' => 'error', 'message' => 'Office name is required'], 422);
        }

        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO sdopang1_offices (office_name, office_type, parent_office_id, office_head, unit_head, office_category, school_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$officeName, $officeType ?: null, $parentOfficeId, $officeHead, $unitHead, $officeCategory, $schoolId, $status]);
        } else {
            $officeId = filter_input(INPUT_POST, 'office_id', FILTER_VALIDATE_INT);
            if (!$officeId) {
                opcrf_json(['status' => 'error', 'message' => 'Office is required'], 422);
            }

            $stmt = $pdo->prepare("
                UPDATE sdopang1_offices
                SET office_name = ?, office_type = ?, parent_office_id = ?, office_head = ?, unit_head = ?, office_category = ?, school_id = ?, status = ?
                WHERE office_id = ?
            ");
            $stmt->execute([$officeName, $officeType ?: null, $parentOfficeId, $officeHead, $unitHead, $officeCategory, $schoolId, $status, $officeId]);
        }

        opcrf_json(['status' => 'success']);
    }

    if ($action === 'delete') {
        $officeId = filter_input(INPUT_POST, 'office_id', FILTER_VALIDATE_INT);
        if (!$officeId) {
            opcrf_json(['status' => 'error', 'message' => 'Office is required'], 422);
        }

        $stmt = $pdo->prepare("DELETE FROM sdopang1_offices WHERE office_id = ?");
        $stmt->execute([$officeId]);
        opcrf_json(['status' => 'success']);
    }

    opcrf_json(['status' => 'error', 'message' => 'Invalid action'], 422);
} catch (Throwable $e) {
    opcrf_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}
