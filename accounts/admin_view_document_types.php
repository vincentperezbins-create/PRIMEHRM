<div id="viewDocContent">Loading...</div>

<script>
fetch('admin_query_document_types.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: 'action=get&doc_type_id=<?= $_GET['id'] ?>'
})
.then(res => res.json())
.then(res => {

    if(res.status !== 'success'){
        document.getElementById('viewDocContent').innerHTML = "Error loading data";
        return;
    }

    let d = res.data;

    document.getElementById('viewDocContent').innerHTML = `
        <table class="table table-bordered">
            <tr>
                <th>ID</th>
                <td>${d.doc_type_id}</td>
            </tr>
            <tr>
                <th>Document Name</th>
                <td>${d.doc_name}</td>
            </tr>
            <tr>
                <th>Required</th>
                <td>${d.is_required == 1 ? 'Yes' : 'No'}</td>
            </tr>
        </table>
    `;
})
.catch(err => {
    console.error(err);
    document.getElementById('viewDocContent').innerHTML = "Request failed";
});
</script>