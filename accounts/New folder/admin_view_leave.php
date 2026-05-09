<div id="viewLeave">Loading...</div>
<script>
fetch('admin_query_leave.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=get&application_id=<?= $_GET['id'] ?>'
})
.then(r=>r.json())
.then(res=>{
    if(res.status!=='success'){
        document.getElementById('viewLeave').innerHTML = 'Error';
        return;
    }
    let d = res.data;
    document.getElementById('viewLeave').innerHTML = `
        <table class="table table-bordered">
            <tr><th>Employee</th><td>${d.employee}</td></tr>
            <tr><th>Type</th><td>${d.leave_name}</td></tr>
            <tr><th>Date From</th><td>${d.date_from}</td></tr>
            <tr><th>Date To</th><td>${d.date_to}</td></tr>
            <tr><th>Days</th><td>${d.days}</td></tr>
            <tr><th>Reason</th><td>${d.reason||''}</td></tr>
            <tr><th>Status</th><td>${d.status}</td></tr>
        </table>`;
});
</script>