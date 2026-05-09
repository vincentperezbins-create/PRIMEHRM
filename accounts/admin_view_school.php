<?php
$id = $_GET['id'] ?? '';
?>

<div id="viewContent">Loading...</div>

<script>
fetch('admin_query_school.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'action=get&schoolID=<?= $id ?>'
})
.then(res => res.json())
.then(res => {

    if(res.status !== 'success'){
        document.getElementById('viewContent').innerHTML = "Error loading data";
        return;
    }

    let d = res.data;

    document.getElementById('viewContent').innerHTML = `
        <table class="table table-bordered">
            <tr><th>School ID</th><td>${d.schoolID}</td></tr>
            <tr><th>School Name</th><td>${d.schoolname}</td></tr>
            <tr><th>District</th><td>${d.district_name ?? ''}</td></tr>
            <tr><th>Address</th><td>${d.schooladdress ?? ''}</td></tr>
            <tr><th>Principal</th><td>${d.principal_name ?? ''}</td></tr>
        </table>
    `;
})
.catch(err => {
    console.error(err);
    document.getElementById('viewContent').innerHTML = "Request failed";
});
</script>