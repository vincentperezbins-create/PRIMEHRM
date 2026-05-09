<div id="viewDistrict">Loading...</div>

<script>
fetch('admin_query_district.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:'action=get&districtID=<?= $_GET['id'] ?>'
})
.then(r=>r.json())
.then(res=>{
let d=res.data;
document.getElementById('viewDistrict').innerHTML=`
<table class="table">
<tr><th>ID</th><td>${d.districtID}</td></tr>
<tr><th>Name</th><td>${d.district_name}</td></tr>
<tr><th>Congress</th><td>${d.cong_name}</td></tr>
</table>`;
});
</script>