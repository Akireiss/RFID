  <!-- ======= Sidebar ======= -->
  <aside id="sidebar" class="sidebar" style="background-color: white;">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item" style="background-color: white;">
        <a class="nav-link collapsed" style="background-color: white;" data-bs-target="#dashboard" href="../admin/dashboard.php">
          <i class="bi bi-grid"></i> 
          <span>Dashboard</span>
        </a><!-- End Dashboard Nav -->

<li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" href="../products/tbl_products.php">
          <i class="bi bi-basket"></i>
          <span>Products</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" href="../customerRecords/tbl_customers.php">
          <i class="bi bi-people"></i>
          <span>Customer Records</span>
        </a>
      </li>

     <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" href="../billing/main_bill.php">
          <i class="bi bi-cash"></i>
          <span>Billing</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" href="../billing/transaction.php">
          <i class="bi bi-arrow-left-right"></i>
          <span>Transactions</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" href="../loadManagement/loadMoney.php">
          <i class="bi bi-credit-card"></i>
          <span>Load Management</span>
        </a>
      </li>

       <li class="nav-heading">...</li>   <!--Ikaw bahala kung ano ilalagay mo dito -->

      <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" data-bs-target="#reports-nav" data-bs-toggle="collapse" href="" >
          <i class="bi bi-menu-button-wide"></i><span>Reports</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="reports-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="../reports/viewInventory.php" class="nav-item nav-link">
              <i class="bi bi-circle"></i><span>View Inventory</span>
            </a>
          </li>
          <li>
            <a href="../reports/listofProducts.php" class="nav-item nav-link">
              <i class="bi bi-circle"></i><span>View List of Products</span>
            </a>
          </li>
          <li>
            <a href="../reports/tbl_category.php" class="nav-item nav-link">
              <i class="bi bi-circle"></i><span>View Product Category</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" data-bs-target="#settings-nav" data-bs-toggle="collapse" href="" >
          <i class="bi bi-gear"></i><span>Settings</span><i class="bi bi-chevron-down ms-auto"></i>
        </a>
        <ul id="settings-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
          <li>
            <a href="../settings/tbl_staff.php" class="nav-item nav-link">
              <i class="bi bi-circle"></i><span>Manage User Account</span>
            </a>
          </li>
          <li>
            <a href="../admin/mysqldump.php" class="nav-item nav-link">
              <i class="bi bi-circle"></i><span>System Backup</span>
            </a>
          </li>
          <li>
            <a href="../settings/audit_trail.php" class="nav-item nav-link">
              <i class="bi bi-circle"></i><span>Audit Trail</span>
            </a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" style="background-color: white;" href="../php/logout.php">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Logout</span>
        </a>
      </li>

    </ul>

  </aside><!-- End Sidebar-->