(function (window, document) {
  const palette = {
    primary: '#155eef',
    primaryDark: '#1048b8',
    danger: '#b42318',
    warning: '#b54708',
    muted: '#667085'
  };

  const normalize = (value) => String(value || '').trim();
  const statusKey = (value) => normalize(value).toLowerCase().replace(/\s+/g, '-');

  function swalAvailable() {
    return typeof window.Swal !== 'undefined';
  }

  const PrimeUI = {
    statusClass(status) {
      const key = statusKey(status);
      const map = {
        pending: 'status-pending',
        'for-review': 'status-for-review',
        approved: 'status-approved',
        returned: 'status-returned',
        rejected: 'status-rejected',
        completed: 'status-completed',
        ongoing: 'status-ongoing',
        draft: 'status-draft',
        active: 'status-active',
        inactive: 'status-inactive',
        reviewed: 'status-reviewed',
        missing: 'status-missing'
      };
      return map[key] || 'status-missing';
    },

    badge(status) {
      const label = normalize(status) || 'Missing';
      return `<span class="prime-badge ${PrimeUI.statusClass(label)}">${PrimeUI.escape(label)}</span>`;
    },

    remarks(value) {
      const text = normalize(value);
      if (!text || text === '-') {
        return '<span class="prime-remarks is-empty">No remarks</span>';
      }
      return `<span class="prime-remarks">${PrimeUI.escape(text).replace(/\n/g, '<br>')}</span>`;
    },

    escape(value) {
      const div = document.createElement('div');
      div.textContent = String(value ?? '');
      return div.innerHTML;
    },

    buttonIcon(text) {
      const value = normalize(text).toLowerCase();
      if (value.includes('add') || value.includes('create')) return 'bi bi-plus-lg';
      if (value.includes('update') || value.includes('edit')) return 'bi bi-pencil-square';
      if (value.includes('view') || value.includes('open')) return 'bi bi-eye';
      if (value.includes('delete')) return 'bi bi-trash';
      if (value.includes('save') || value.includes('submit')) return 'bi bi-check2-circle';
      if (value.includes('upload') || value.includes('re-upload') || value.includes('replace')) return 'bi bi-upload';
      if (value.includes('back') || value.includes('cancel')) return 'bi bi-arrow-left';
      if (value.includes('print')) return 'bi bi-printer';
      if (value.includes('download')) return 'bi bi-download';
      if (value.includes('approve')) return 'bi bi-check2';
      if (value.includes('return') || value.includes('reject')) return 'bi bi-arrow-counterclockwise';
      return '';
    },

    exportDom() {
      return '<"prime-dt-top"lf><"prime-export-actions"B>rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3"ip>';
    },

    exportButtons(options = {}) {
      const title = options.title || document.title || 'PRIMEHR Export';
      const columns = options.columns || ':visible:not(.datatable-nosort)';
      const extra = options.exportOptions || {};
      const exportOptions = Object.assign({ columns }, extra);

      return [
        {
          extend: 'excelHtml5',
          text: '<i class="bi bi-file-earmark-excel"></i><span class="export-btn-label">Export Excel</span>',
          title,
          className: 'btn prime-export-btn prime-export-btn--excel',
          exportOptions
        },
        {
          extend: 'pdfHtml5',
          text: '<i class="bi bi-file-earmark-pdf"></i><span class="export-btn-label">Export PDF</span>',
          title,
          orientation: options.orientation || 'landscape',
          pageSize: options.pageSize || 'A4',
          className: 'btn prime-export-btn prime-export-btn--pdf',
          exportOptions
        },
        {
          extend: 'print',
          text: '<i class="bi bi-printer"></i><span class="export-btn-label">Print</span>',
          title,
          className: 'btn prime-export-btn prime-export-btn--print',
          exportOptions
        }
      ];
    },

    attachExportToolbar(settings) {
      if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable || !jQuery.fn.dataTable.Buttons || !settings) {
        return;
      }

      const api = new jQuery.fn.dataTable.Api(settings);
      const wrapper = jQuery(api.table().container());
      if (!wrapper.length || wrapper.find('.dt-buttons').length) {
        return;
      }

      const headers = jQuery(api.table().header()).find('th').toArray();
      const exportColumns = headers
        .map((th, index) => ({ index, label: normalize(th.textContent).toLowerCase(), th }))
        .filter((column) => {
          if (column.th.classList.contains('datatable-nosort')) return false;
          if (column.label === 'action' || column.label === 'actions' || column.label === '') return false;
          return true;
        })
        .map((column) => column.index);

      if (!exportColumns.length) {
        return;
      }

      try {
        new jQuery.fn.dataTable.Buttons(api, {
          buttons: PrimeUI.exportButtons({
            title: document.title || 'PRIMEHR Export',
            columns: exportColumns
          })
        });

        const toolbar = jQuery('<div class="prime-export-actions mb-2"></div>');
        toolbar.append(api.buttons().container());
        wrapper.find('.dataTables_filter').first().after(toolbar);
      } catch (error) {
        if (window.console) console.warn('Export toolbar skipped:', error);
      }
    },

    success(message, title = 'Saved') {
      if (!swalAvailable()) return alert(message || title);
      return Swal.fire({
        icon: 'success',
        title,
        text: message || '',
        confirmButtonColor: palette.primary,
        timer: 1800,
        showConfirmButton: false
      });
    },

    error(message, title = 'Something went wrong') {
      if (!swalAvailable()) return alert(message || title);
      return Swal.fire({
        icon: 'error',
        title,
        text: message || 'Please try again.',
        confirmButtonColor: palette.primary
      });
    },

    confirm(options = {}) {
      const defaults = {
        icon: 'warning',
        title: 'Are you sure?',
        text: 'Please confirm before continuing.',
        confirmButtonText: 'Yes, continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: palette.primary,
        cancelButtonColor: palette.muted,
        reverseButtons: true,
        showCancelButton: true
      };
      if (!swalAvailable()) {
        return Promise.resolve({ isConfirmed: window.confirm(options.text || defaults.text) });
      }
      return Swal.fire(Object.assign(defaults, options));
    },

    confirmDelete(text = 'This record will be deleted.') {
      return PrimeUI.confirm({
        title: 'Delete record?',
        text,
        confirmButtonText: 'Delete',
        confirmButtonColor: palette.danger
      });
    },

    confirmSave(text = 'Save these changes?') {
      return PrimeUI.confirm({
        title: 'Save changes?',
        text,
        confirmButtonText: 'Save',
        confirmButtonColor: palette.primary
      });
    },

    confirmReturn(text = 'Return this submission with remarks?') {
      return PrimeUI.confirm({
        title: 'Return submission?',
        text,
        confirmButtonText: 'Return',
        confirmButtonColor: palette.warning
      });
    },

    loading(title = 'Working...', text = 'Please wait.') {
      if (!swalAvailable()) return;
      return Swal.fire({
        title,
        text,
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
      });
    },

    enhanceTables(root = document) {
      root.querySelectorAll('table').forEach((table) => {
        table.classList.add('prime-table');
        const headers = Array.from(table.querySelectorAll('thead th')).map((th) => th.textContent.trim().toLowerCase());
        const statusIndex = headers.findIndex((h) => h === 'status');
        const remarksIndex = headers.findIndex((h) => h === 'remarks');

        table.querySelectorAll('tbody tr').forEach((row) => {
          const cells = row.children;
          if (statusIndex >= 0 && cells[statusIndex] && !cells[statusIndex].querySelector('.prime-badge, .badge')) {
            cells[statusIndex].innerHTML = PrimeUI.badge(cells[statusIndex].textContent);
          }
          if (remarksIndex >= 0 && cells[remarksIndex] && !cells[remarksIndex].querySelector('.prime-remarks')) {
            cells[remarksIndex].innerHTML = PrimeUI.remarks(cells[remarksIndex].textContent);
          }
        });
      });
    },

    enhanceButtons(root = document) {
      root.querySelectorAll('.btn').forEach((btn) => {
        const text = normalize(btn.textContent).toLowerCase();
        if (text.includes('add') || text.includes('create')) btn.classList.add('btn-add');
        if (text.includes('update') || text.includes('edit')) btn.classList.add('btn-update');
        if (text.includes('view') || text.includes('open')) btn.classList.add('btn-view');
        if (text.includes('delete')) btn.classList.add('btn-delete');
        if (text.includes('save') || text.includes('submit')) btn.classList.add('btn-save');
        if (text.includes('back') || text.includes('cancel')) btn.classList.add('btn-back');
        if (text.includes('print') || text.includes('download')) btn.classList.add('btn-download');
        if (text.includes('approve')) btn.classList.add('btn-approve');
        if (text.includes('return') || text.includes('reject')) btn.classList.add('btn-return');
        if (!btn.querySelector('i, svg, .micon, .icon-copy, [class*=" bi-"], [class*=" dw-"], [class^="dw "]')) {
          const icon = PrimeUI.buttonIcon(text);
          if (icon) {
            btn.insertAdjacentHTML('afterbegin', `<i class="${icon}" aria-hidden="true"></i>`);
          }
        }
      });
    },

    enhance(root = document) {
      PrimeUI.enhanceButtons(root);
      PrimeUI.enhanceTables(root);
    }
  };

  window.PrimeUI = PrimeUI;
  document.addEventListener('DOMContentLoaded', () => {
    PrimeUI.enhance();
    if (window.jQuery) {
      window.jQuery(document).on('draw.dt', () => PrimeUI.enhance());
      window.jQuery(document).on('init.dt', function (_event, settings) {
        PrimeUI.attachExportToolbar(settings);
      });
      window.jQuery('table').each(function () {
        if (jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(this)) {
          PrimeUI.attachExportToolbar(jQuery(this).DataTable().settings()[0]);
        }
      });
    }
  });
})(window, document);
