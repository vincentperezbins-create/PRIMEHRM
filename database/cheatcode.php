<?php

//SAVE IN CLASSES/USER.PHP
// 📄 GET ALL USERS WITH SCHOOL, DISTRICT, CONG
// public function getAllUserswithschoolinfo() {
//      $sql = "
//         SELECT 
//             *

//         FROM sdopang1_user u

//         LEFT JOIN sdopang1schoollist s 
//             ON s.schoolID = u.school_id

//         LEFT JOIN sdopang1_district d 
//             ON d.district_name = s.district

//         LEFT JOIN sdopang1_cong c 
//             ON c.cong_name = s.cong

//         LEFT JOIN sdopang1_position p
//             ON p.position_id = u.position_id

//         ORDER BY u.first_name ASC
//     ";

//     $stmt = $this->pdo->prepare($sql);
//     $stmt->execute();

//     return $stmt->fetchAll(PDO::FETCH_ASSOC);
// }
?>




<!-- MODAL DYNAMIC
<button 
                        class="btn btn-sm btn-primary openModal" 
                        data-id="<?= htmlspecialchars((string) $u['user_id']) ?>"
                        data-type="<?= htmlspecialchars((string) $u['user_id']) ?>"
                        data-action="View">
                        View
                    </button>
                      <button 
                        class="btn btn-sm btn-warning openModal" 
                        data-id="<?= htmlspecialchars((string) $u['user_id']) ?>"
                        data-type="<?= htmlspecialchars((string) $u['user_id']) ?>"
                        data-action="Update">
                        Update
                    </button>
                      <button 
                        class="btn btn-sm btn-danger openModal" 
                        data-id="<?= htmlspecialchars((string) $u['user_id']) ?>"
                        data-type="<?= htmlspecialchars((string) $u['user_id']) ?>"
                        data-action="Delete">
                        Delete
                    </button>

<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-primary">
        <h5 class="modal-title text-white" id="modalTitle"></h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="modalContent">
        Loading...
      </div>

    </div>
  </div>
</div>



<script>
document.addEventListener("click", function (e) {

    if (e.target.classList.contains("openModal")) {

        let btn = e.target;

        let id = btn.dataset.id;
        let type = btn.dataset.type;
        let action = btn.dataset.action;

        let url = "";

        if (action === "View") {
            url = "admin_view_user.php?id=" + id;
        } else if (action === "Replace" || action === "Update") {
            url = "admin_update_user.php?id=" + id;
        } else {
            url = "admin_delete_user.php?id=" + type;
        }

        document.getElementById("modalTitle").innerText = action + " User";

        fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;

            let modal = new bootstrap.Modal(document.getElementById('actionModal'));
            modal.show();
        });

    }

});
</script>




<?= htmlspecialchars($user['middle_name'] ?? '') ?>
 -->
