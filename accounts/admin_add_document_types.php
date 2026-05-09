<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);
?>

<form id="addDocForm">
    <input type="hidden" name="action" value="add">

    <div class="mb-2">
        <label>Document Name</label>
        <input name="doc_name" class="form-control" required placeholder="e.g. Service Record">
    </div>

    <div class="mb-3">
        <label>Required</label>
        <select name="is_required" class="form-control">
            <option value="1">Required</option>
            <option value="0">Optional</option>
        </select>
    </div>

    <button class="btn btn-primary w-100">Save Document Type</button>
</form>
