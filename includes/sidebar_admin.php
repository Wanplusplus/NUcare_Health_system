<!-- Admin Sidebar UI (NUCARE) -->
<!-- Required by assets/js/app.js: #hamburgerBtn, #sidebar, #sidebarOverlay -->
<style>
  .sidebar.sidebar-admin {
    background: linear-gradient(180deg, #8b0000 0%, #4b0000 100%);
  }

  .sidebar-admin .brand-mark {
    background: #ffd24d;
    color: #8b0000;
  }

  .sidebar-admin .nav-item {
    background: rgba(255, 255, 255, 0.08);
  }

  .sidebar-admin .nav-item:hover,
  .sidebar-admin .nav-item.active {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.22);
  }

  .sidebar-admin .nav-dot {
    background: #ffd24d;
    box-shadow: 0 0 0 6px rgba(255, 210, 77, 0.16);
  }

  .sidebar-admin .status-pill {
    background: #5c0000;
    color: #ffe0e0;
  }
</style>

<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar sidebar-admin" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">AD</div>
    <div>
      <h1>NUCARE Admin</h1>
      <p>System Management</p>
    </div>
  </div>

  <nav class="nav-menu">
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>Dashboard
    </a>
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>User Management
    </a>
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>RBAC Management
    </a>
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>Reports
    </a>
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>Audit Logs
    </a>
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>Medicine Inventory
    </a>
    <a class="nav-item" href="#">
      <span class="nav-dot"></span>Schedule Monitoring
    </a>
  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">System Status</p>
    <div class="status-pill">Operational</div>
  </div>
</aside>
