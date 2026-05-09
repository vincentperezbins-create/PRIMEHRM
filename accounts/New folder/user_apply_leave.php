<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);

?>

<form id="leaveForm">
  <select name="leave_type_id" required>
    <?php
    // filter by personnel_type
    $stmt=$pdo->prepare("SELECT * FROM leave_types 
      WHERE personnel_type IN (?, 'both') AND is_active=1");
    $stmt->execute([$_SESSION['personnel_type']]);
    foreach($stmt as $lt){
      echo "<option value='{$lt['leave_type_id']}'>{$lt['leave_name']}</option>";
    }
    ?>
  </select>

  <input type="date" name="date_from" required>
  <input type="date" name="date_to" required>
  <textarea name="reason"></textarea>
  <button type="submit">Submit</button>
</form>

<script>
$('#leaveForm').submit(function(e){
  e.preventDefault();
  $.post('apply_leave_save.php', $(this).serialize(), ()=>alert('Submitted'));
});
</script>
