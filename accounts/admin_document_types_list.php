<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$total = $pdo->query("SELECT COUNT(*) FROM sdopang1_document_types")->fetchColumn();
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

<div class="d-flex justify-content-between pb-20">
    <h2>Document Types (<?= $total ?>)</h2>

    <button class="btn btn-primary openModal" data-action="Add">
        Add Document Type
    </button>
</div>

<div class="card-box pd-20">
<table id="docTable" class="table table-bordered">
<thead>
<tr>
<th>ID</th>
<th>Document Name</th>
<th>Required</th>
<th>Action</th>
</tr>
</thead>
</table>
</div>

<div class="modal fade" id="actionModal">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header bg-primary">
<h5 class="modal-title text-white" id="modalTitle"></h5>
<button class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body" id="modalContent"></div>
</div>
</div>
</div>

</div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>

<script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
<script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
<script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
<script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
<script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
<script src="src/plugins/datatables/js/buttons.print.min.js"></script>
<script src="src/plugins/datatables/js/jszip.min.js"></script>
<script src="src/plugins/datatables/js/pdfmake.min.js"></script>
<script src="src/plugins/datatables/js/vfs_fonts.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const table = $('#docTable').DataTable({
processing:true,
serverSide:true,
ajax:{ url:'admin_ajax_document_types_list.php', type:'POST' },
columns:[
{data:'doc_type_id'},
{data:'doc_name'},
{data:'is_required'},
{data:'action', orderable:false}
]
});

// MODAL
document.addEventListener("click", function(e){
let btn=e.target.closest('.openModal');
if(!btn) return;

let id=btn.dataset.id;
let action=btn.dataset.action;

let url="";
if(action==="Add") url="admin_add_document_types.php";
else if(action==="View") url="admin_view_document_types.php?id="+id;
else if(action==="Update") url="admin_update_document_types.php?id="+id;

document.getElementById("modalTitle").innerText=action+" Document Type";

fetch(url)
.then(r=>r.text())
.then(html=>{
document.getElementById("modalContent").innerHTML=html;
let scripts=document.getElementById("modalContent").querySelectorAll("script");
scripts.forEach(s=>eval(s.innerText));
new bootstrap.Modal(document.getElementById('actionModal')).show();
});
});

// DELETE
document.addEventListener("click", function(e){
let btn=e.target.closest('.btnDelete');
if(!btn) return;

Swal.fire({
icon:'warning',
title:'Delete document type?',
text:'This document type will be removed from the 201 file requirements.',
showCancelButton:true,
confirmButtonText:'Yes, delete',
cancelButtonText:'Cancel',
confirmButtonColor:'#d33'
}).then(result=>{
if(!result.isConfirmed) return;

fetch('admin_query_document_types.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:'action=delete&doc_type_id='+encodeURIComponent(btn.dataset.id)
})
.then(r=>r.json())
.then(res=>{
if(res.status==='success'){
Swal.fire({icon:'success',title:'Deleted!',timer:1500,showConfirmButton:false});
table.ajax.reload(null,false);
}else{
Swal.fire({icon:'error',title:'Delete failed',text:res.message || 'Unable to delete document type.'});
}
})
.catch(()=>{
Swal.fire({icon:'error',title:'Delete failed',text:'Please check the connection or server logs.'});
});
});
});

// ADD + UPDATE
document.addEventListener('submit', function(e){

if(e.target.id==='addDocForm' || e.target.id==='updateDocForm'){
e.preventDefault();

fetch('admin_query_document_types.php',{
method:'POST',
body:new FormData(e.target)
})
.then(r=>r.json())
.then(res=>{
if(res.status==='success'){
Swal.fire({icon:'success',title:'Saved!',timer:1500,showConfirmButton:false});
$('#actionModal').modal('hide');
table.ajax.reload(null,false);
}else{
alert(res.message);
}
});
}

});
</script>

</body>
</html>



