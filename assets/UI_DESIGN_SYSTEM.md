# PRIMEHR UI Design System

Global includes are already added in `accounts/partials/head.php`:

```html
<link rel="stylesheet" href="../assets/css/custom-ui.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/ui-helpers.js"></script>
```

Use these shared button classes when editing pages:

- `btn-add` / `btn-create`
- `btn-view`
- `btn-update`
- `btn-delete`
- `btn-save` / `btn-submit`
- `btn-back` / `btn-cancel`
- `btn-download` / `btn-print`
- `btn-approve`
- `btn-return` / `btn-reject`

Use these JavaScript helpers for consistent popups:

```js
PrimeUI.success('Saved successfully.');
PrimeUI.error('Unable to save.');
PrimeUI.confirmDelete('This record will be deleted.').then(result => {});
PrimeUI.confirmSave('Save these changes?').then(result => {});
PrimeUI.confirmReturn('Return this submission?').then(result => {});
PrimeUI.loading('Saving', 'Please wait.');
```

Use these render helpers in DataTables or AJAX rows:

```js
PrimeUI.badge('Approved')
PrimeUI.remarks(row.remarks)
```

Status and remarks cells in normal tables are also enhanced automatically when the page loads.
