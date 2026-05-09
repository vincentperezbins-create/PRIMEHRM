    <div class="left-side-bar">
      <div class="brand-logo">
        <a href="index.php">
          <img src="../assets_pang1/2463e139-dbb8-4805-ae1e-af942924cd69.png" alt="" class="dark-logo" />
          <img
            src="../assets_pang/12463e139-dbb8-4805-ae1e-af942924cd69.png.svg"
            alt=""
            class="light-logo"
          />
        </a>
        <div class="close-sidebar" data-toggle="left-sidebar-close">
          <i class="ion-close-round"></i>
        </div>
      </div>
      <div class="menu-block customscroll">
        <div class="sidebar-menu">


        <?php
        $canValidate201 = isset($pdo) && user_can_validate($pdo, '201');
        $canValidateOpcrf = isset($pdo) && user_can_validate($pdo, 'opcrf');
        $canValidateIpcrf = isset($pdo) && user_can_validate($pdo, 'ipcrf');
        $canValidateLeave = isset($pdo) && user_can_validate($pdo, 'leave');
        $hasValidatorTasks = $canValidate201 || $canValidateOpcrf || $canValidateIpcrf || $canValidateLeave;
        ?>

        <?php if ($_SESSION['role_id'] == 1): ?>
<ul id="accordion-menu">

    <!-- HOME -->
    <li class="dropdown">
        <a href="javascript:;" class="dropdown-toggle">
            <span class="micon bi bi-house"></span>
            <span class="mtext">Home</span>
        </a>
        <ul class="submenu">
            <li><a href="index.php">Welcome</a></li>
            <li><a href="admin_dashboard.php">Dashboard</a></li>
            <li><a href="admin_leave_dashboard.php">Dashboard Leave Credits</a></li>
        </ul>
    </li>

    <!-- MY SUBMISSION -->
    <li class="dropdown">
        <a href="javascript:;" class="dropdown-toggle">
            <span class="micon bi bi-inbox"></span>
            <span class="mtext">My Submission</span>
        </a>
        <ul class="submenu">
            <li><a href="user_201_tables.php">My 201 Files</a></li>
            <li><a href="user_leave_balance.php">My Leave Credits</a></li>
            <li><a href="user_opcrf_list.php">Office/Unit OPCRF</a></li>
            <li><a href="user_ipcrf_list.php">My IPCRF</a></li>
        </ul>
    </li>

    <!-- SETTINGS / MASTER DATA -->
    <li class="dropdown">
        <a href="javascript:;" class="dropdown-toggle">
            <span class="micon bi bi-gear"></span>
            <span class="mtext">Settings</span>
        </a>
        <ul class="submenu">
            <li><a href="admin_users_list.php">User List</a></li>
            <li><a href="admin_school_list.php">School List</a></li>
            <li><a href="admin_district_list.php">District List</a></li>
            <li><a href="admin_document_types_list.php">Document Types (201 Files)</a></li>
            <li><a href="admin_notifications.php">Notifications</a></li>
            <li><a href="admin_manual_generator.php">User Manual Generator</a></li>
        </ul>
    </li>

    <!-- 201 FILES -->
    <li class="dropdown">
        <a href="javascript:;" class="dropdown-toggle">
            <span class="micon bi bi-folder-check"></span>
            <span class="mtext">201 Files</span>
        </a>
        <ul class="submenu">
            <li><a href="admin_201_tables.php">Validate 201 Files</a></li>
            <li><a href="admin_document_types_list.php">Document Types</a></li>
        </ul>
    </li>

    <!-- LEAVE MANAGEMENT -->
    <li class="dropdown">
        <a href="javascript:;" class="dropdown-toggle">
            <span class="micon bi bi-calendar-check"></span>
            <span class="mtext">Leave Management</span>
        </a>
        <ul class="submenu">
            <li><a href="admin_leave_applications.php">Leave Applications</a></li>
            <li><a href="admin_approved_leave_applications.php">Approved Leave Report</a></li>
            <li><a href="admin_leave_balances.php">Leave Balances</a></li>
            <li><a href="admin_leave_ledger.php">Leave Ledger</a></li>
            <li><a href="admin_leave_transactions.php">Transactions</a></li>
        </ul>
    </li>

    <!-- PERFORMANCE FORMS -->
    <li class="dropdown">
        <a href="javascript:;" class="dropdown-toggle">
            <span class="micon bi bi-clipboard-data"></span>
            <span class="mtext">Performance Forms</span>
        </a>
        <ul class="submenu">
            <li><a href="admin_opcrf_list.php">Office/Unit OPCRF</a></li>
            <li><a href="admin_ipcrf_list.php">Employee IPCRF</a></li>
            <li><a href="admin_opcrf_offices.php">Offices / Units</a></li>
        </ul>
    </li>

    <!-- CALENDAR -->
    <li>
        <a href="calendar.html" class="dropdown-toggle no-arrow">
            <span class="micon bi bi-calendar4-week"></span>
            <span class="mtext">Calendar</span>
        </a>
    </li>

    <li>
        <a href="profile.php" class="dropdown-toggle no-arrow">
            <span class="micon bi bi-person"></span>
            <span class="mtext">Profile</span>
        </a>
    </li>

</ul>
<?php endif; ?>

            <?php if ($_SESSION['role_id'] != 1 && $hasValidatorTasks): ?>
              <ul id="accordion-menu">
                <li>
                  <a href="index.php" class="dropdown-toggle no-arrow">
                    <span class="micon bi bi-house"></span>
                    <span class="mtext">Welcome</span>
                  </a>
                </li>
                <li class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle">
                    <span class="micon bi bi-check2-square"></span>
                    <span class="mtext">Validation Tasks</span>
                  </a>
                  <ul class="submenu">
                    <?php if ($canValidate201): ?><li><a href="admin_201_tables.php">Validate 201 Files</a></li><?php endif; ?>
                    <?php if ($canValidateOpcrf): ?><li><a href="admin_opcrf_list.php">Validate OPCRF</a></li><?php endif; ?>
                    <?php if ($canValidateIpcrf): ?><li><a href="admin_ipcrf_list.php">Validate IPCRF</a></li><?php endif; ?>
                    <?php if ($canValidateLeave): ?><li><a href="admin_leave_applications.php">Validate Leave</a></li><?php endif; ?>
                  </ul>
                </li>
                <li class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle">
                    <span class="micon bi bi-inbox"></span>
                    <span class="mtext">My Submission</span>
                  </a>
                  <ul class="submenu">
                    <li><a href="user_201_tables.php">My 201 Files</a></li>
                    <li><a href="user_leave_balance.php">My Leave Credits</a></li>
                    <li><a href="user_opcrf_list.php">Office/Unit OPCRF</a></li>
                    <li><a href="user_ipcrf_list.php">My IPCRF</a></li>
                  </ul>
                </li>
                <li class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle">
                    <span class="micon bi bi-person-check"></span>
                    <span class="mtext">My Leave</span>
                  </a>
                  <ul class="submenu">
                    <li><a href="user_leave_apply.php">Apply Leave</a></li>
                    <li><a href="user_leave_history.php">Leave History</a></li>
                    <li><a href="user_leave_balance.php">My Balance</a></li>
                  </ul>
                </li>
                <li>
                  <a href="profile.php" class="dropdown-toggle no-arrow">
                    <span class="micon bi bi-person"></span>
                    <span class="mtext">Profile</span>
                  </a>
                </li>
              </ul>
            <?php endif; ?>

            <?php if (!$hasValidatorTasks && in_array((int) $_SESSION['role_id'], [2, 5, 6, 7], true)): ?>
              <ul id="accordion-menu">
                <li>
                  <a href="index.php" class="dropdown-toggle no-arrow">
                    <span class="micon bi bi-house"></span>
                    <span class="mtext">Welcome</span>
                  </a>
                </li>
                <li class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle">
                    <span class="micon bi bi-inbox"></span>
                    <span class="mtext">My Submission</span>
                  </a>
                  <ul class="submenu">
                    <li><a href="user_201_tables.php">My 201 Files</a></li>
                    <li><a href="user_leave_balance.php">My Leave Credits</a></li>
                    <li><a href="user_opcrf_list.php">Office/Unit OPCRF</a></li>
                    <li><a href="user_ipcrf_list.php">My IPCRF</a></li>
                  </ul>
                </li>
                <li class="dropdown">
                  <a href="javascript:;" class="dropdown-toggle">
                    <span class="micon bi bi-person-check"></span>
                    <span class="mtext">My Leave</span>
                  </a>
                  <ul class="submenu">
                    <li><a href="user_leave_apply.php">Apply Leave</a></li>
                    <li><a href="user_leave_history.php">Leave History</a></li>
                    <li><a href="user_leave_balance.php">My Balance</a></li>
                  </ul>
                </li>
                <li>
                  <a href="profile.php" class="dropdown-toggle no-arrow">
                    <span class="micon bi bi-person"></span>
                    <span class="mtext">Profile</span>
                  </a>
                </li>
              </ul>
            <?php endif; ?>

            <?php if (!$hasValidatorTasks && $_SESSION['role_id'] == 3): ?>
              <ul id="accordion-menu">
                <li>
                   <a href="index.php" class="dropdown-toggle no-arrow">
                    <span class="micon bi bi-calendar4-week"></span
                    ><span class="mtext">Welcome</span>
                  </a>
               </li>
              <li>
                <a href="school_dashboard.php" class="dropdown-toggle no-arrow">
                 <span class="micon bi bi-calendar4-week"></span>
                 <span class="mtext">School Dashboard</span>
                </a>
              </li>
              <li>
                <a href="school_201_tables.php" class="dropdown-toggle no-arrow">
                 <span class="micon bi bi-calendar4-week"></span>
                 <span class="mtext">School Employee <br>201 Files Uploaded</span>
                </a>
              </li>
              <li class="dropdown">
                <a href="javascript:;" class="dropdown-toggle">
                 <span class="micon bi bi-inbox"></span>
                 <span class="mtext">My Submission</span>
                </a>
                <ul class="submenu">
                  <li><a href="user_201_tables.php">My 201 Files</a></li>
                  <li><a href="user_leave_balance.php">My Leave Credits</a></li>
                  <li><a href="user_opcrf_list.php">Office/Unit OPCRF</a></li>
                  <li><a href="user_ipcrf_list.php">My IPCRF</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="javascript:;" class="dropdown-toggle">
                 <span class="micon bi bi-calendar-check"></span>
                 <span class="mtext">School Leave</span>
                </a>
                <ul class="submenu">
                  <li><a href="school_leave_applications.php">Leave Applications</a></li>
                  <li><a href="school_leave_balances.php">Leave Balances</a></li>
                  <li><a href="school_leave_ledger.php">Leave Ledger</a></li>
                </ul>
              </li>
              <li class="dropdown">
                <a href="javascript:;" class="dropdown-toggle">
                 <span class="micon bi bi-person-check"></span>
                 <span class="mtext">My Leave</span>
                </a>
                <ul class="submenu">
                  <li><a href="user_leave_apply.php">Apply Leave</a></li>
                  <li><a href="user_leave_history.php">Leave History</a></li>
                  <li><a href="user_leave_balance.php">My Balance</a></li>
                </ul>
              </li>
              <li>
                <a href="profile.php" class="dropdown-toggle no-arrow">
                 <span class="micon bi bi-person"></span>
                 <span class="mtext">Profile</span>
                </a>
              </li>
            </ul>
            <?php endif; ?>

            <?php if (!$hasValidatorTasks && $_SESSION['role_id'] == 4): ?>
              <ul id="accordion-menu">

                  <!-- HOME -->
                  <li class="dropdown">
                      <a href="javascript:;" class="dropdown-toggle">
                          <span class="micon bi bi-house"></span>
                          <span class="mtext">Home</span>
                      </a>
                      <ul class="submenu">
                          <li><a href="index.php">Welcome</a></li>
                          <li><a href="user_dashboard.php">My Dashboard</a></li>
                      </ul>
                  </li>

                  <!-- MY SUBMISSION -->
                  <li class="dropdown">
                      <a href="javascript:;" class="dropdown-toggle">
                          <span class="micon bi bi-inbox"></span>
                          <span class="mtext">My Submission</span>
                      </a>
                      <ul class="submenu">
                          <li><a href="user_201_tables.php">My 201 Files</a></li>
                          <li><a href="user_leave_balance.php">My Leave Credits</a></li>
                          <li><a href="user_opcrf_list.php">Office/Unit OPCRF</a></li>
                          <li><a href="user_ipcrf_list.php">My IPCRF</a></li>
                      </ul>
                  </li>

                  <!-- MY LEAVE -->
                  <li class="dropdown">
                      <a href="javascript:;" class="dropdown-toggle">
                          <span class="micon bi bi-calendar-check"></span>
                          <span class="mtext">My Leave</span>
                      </a>
                      <ul class="submenu">
                          <li><a href="user_leave_apply.php">Apply Leave</a></li>
                          <li><a href="user_leave_history.php">Leave History</a></li>
                          <li><a href="user_leave_balance.php">My Balance</a></li>
                      </ul>
                  </li>

                  <!-- PROFILE -->
                  <li>
                      <a href="profile.php" class="dropdown-toggle no-arrow">
                          <span class="micon bi bi-person"></span>
                          <span class="mtext">Profile</span>
                      </a>
                  </li>

              </ul>
              <?php endif; ?>
            
            
            
            
         
        </div>
      </div>
    </div>
