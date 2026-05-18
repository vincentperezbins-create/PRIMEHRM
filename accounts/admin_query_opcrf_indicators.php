<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

require_login();

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $opcrfId = filter_input(INPUT_POST, 'opcrf_id', FILTER_VALIDATE_INT);
        $objective = trim($_POST['objective'] ?? '');

        if (!$opcrfId || $objective === '') {
            opcrf_json(['status' => 'error', 'message' => 'OPCRF and objective are required'], 422);
        }

        if (!opcrf_user_can_manage_content($pdo, $opcrfId)) {
            opcrf_json(['status' => 'error', 'message' => 'Only the owner office/unit can add indicators.'], 403);
        }

        $stmt = $pdo->prepare("
            INSERT INTO sdopang1_opcrf_indicators
                (opcrf_id, kra, objective, success_indicator, actual_accomplishment, quality, efficiency, timeliness, rating, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $opcrfId,
            trim($_POST['kra'] ?? '') ?: null,
            $objective,
            trim($_POST['success_indicator'] ?? '') ?: null,
            trim($_POST['actual_accomplishment'] ?? '') ?: null,
            trim($_POST['quality'] ?? '') ?: null,
            trim($_POST['efficiency'] ?? '') ?: null,
            trim($_POST['timeliness'] ?? '') ?: null,
            $_POST['rating'] !== '' ? (float) $_POST['rating'] : null,
            trim($_POST['remarks'] ?? '') ?: null,
        ]);

        opcrf_log($pdo, $opcrfId, 'Added indicator', $objective);
        opcrf_json(['status' => 'success']);
    }

    if ($action === 'delete') {
        $indicatorId = filter_input(INPUT_POST, 'indicator_id', FILTER_VALIDATE_INT);
        if (!$indicatorId) {
            opcrf_json(['status' => 'error', 'message' => 'Indicator is required'], 422);
        }

        $stmt = $pdo->prepare("SELECT opcrf_id FROM sdopang1_opcrf_indicators WHERE indicator_id = ?");
        $stmt->execute([$indicatorId]);
        $opcrfId = (int) $stmt->fetchColumn();

        if (!$opcrfId || !opcrf_user_can_manage_content($pdo, $opcrfId)) {
            opcrf_json(['status' => 'error', 'message' => 'Only the owner office/unit can delete indicators.'], 403);
        }

        $stmt = $pdo->prepare("DELETE FROM sdopang1_opcrf_indicators WHERE indicator_id = ?");
        $stmt->execute([$indicatorId]);

        if ($opcrfId) {
            opcrf_log($pdo, $opcrfId, 'Deleted indicator');
        }

        opcrf_json(['status' => 'success']);
    }

    opcrf_json(['status' => 'error', 'message' => 'Invalid action'], 422);
} catch (Throwable $e) {
    opcrf_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}
