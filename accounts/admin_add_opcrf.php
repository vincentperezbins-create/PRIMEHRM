<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

$offices = $pdo->query("SELECT office_id, office_name FROM sdopang1_offices WHERE status='Active' ORDER BY office_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<form id="opcrfForm" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add">
    <div class="row">
        <div class="col-md-6 mb-2">
            <label>Office / Unit</label>
            <select name="office_id" class="form-control" required>
                <option value="">Select office or unit</option>
                <?php foreach ($offices as $office): ?>
                    <option value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($office['office_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-2">
            <label>Title</label>
            <input name="title" class="form-control" required placeholder="Office Performance Commitment and Review Form">
        </div>
        <div class="col-md-4 mb-2">
            <label>School Year</label>
            <input name="school_year" class="form-control" required placeholder="2025-2026">
        </div>
        <div class="col-md-4 mb-2">
            <label>Quarter</label>
            <select name="quarter" class="form-control" required>
                <option value="Q1">Q1</option>
                <option value="Q2">Q2</option>
                <option value="Q3">Q3</option>
                <option value="Q4">Q4</option>
                <option value="Annual">Annual</option>
            </select>
        </div>
        <div class="col-md-4 mb-2">
            <label>Date Prepared</label>
            <input type="date" name="date_prepared" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-md-6 mb-2">
            <label>Upload PDF</label>
            <input type="file" name="uploaded_pdf" class="form-control" accept=".pdf">
        </div>
        <div class="col-md-6 mb-2">
            <label>Upload Excel</label>
            <input type="file" name="uploaded_excel" class="form-control" accept=".xls,.xlsx">
        </div>
        <div class="col-12 mb-3">
            <label>Remarks</label>
            <textarea name="remarks" class="form-control" rows="3"></textarea>
        </div>
    </div>
    <button class="btn btn-primary w-100">Create Office/Unit OPCRF</button>
</form>
