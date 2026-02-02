<!-- Sidebar Component -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <img src="../img/colleg.jpg" alt="شعار جامعة تعز فرع التربة" class="sidebar-logo"
             onerror="this.src='https://via.placeholder.com/80x80/1a365d/f59e0b?text=TU'">
        <h3>لوحة التحكم</h3>
        <p class="sidebar-subtitle">جامعة تعز - فرع التربة</p>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="admin_panel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_panel.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>لوحة التحكم</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'students.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>إدارة الطلاب</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="departments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'departments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-building"></i>
                    <span>الأقسام</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="schedules.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>الجداول الدراسية</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>التقارير</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="admins.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>المشرفين</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>الإعدادات</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../home.html" class="nav-link external-link">
            <i class="fas fa-external-link-alt"></i>
            <span>الموقع الرئيسي</span>
        </a>
        <a href="logout.php" class="nav-link logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>تسجيل الخروج</span>
        </a>
    </div>
</aside>

<style>
/* Premium Sidebar Styles */
.sidebar {
    position: fixed;
    right: 0;
    top: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(180deg, #1a365d 0%, #0a1628 100%);
    display: flex;
    flex-direction: column;
    z-index: 1000;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 25px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
}

.sidebar-logo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    border: 3px solid #f59e0b;
    box-shadow: 0 0 30px rgba(245, 158, 11, 0.3);
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.sidebar-logo:hover {
    transform: scale(1.1) rotate(5deg);
}

.sidebar-header h3 {
    color: #f8fafc;
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 5px;
}

.sidebar-subtitle {
    color: #94a3b8;
    font-size: 0.85rem;
    margin: 0;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 15px;
    overflow-y: auto;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    color: #94a3b8;
    text-decoration: none;
    border-radius: 12px;
    transition: all 0.3s ease;
    font-weight: 600;
}

.nav-link i {
    width: 22px;
    text-align: center;
    font-size: 1.1rem;
}

.nav-link:hover {
    background: rgba(245, 158, 11, 0.15);
    color: #f59e0b;
    transform: translateX(-5px);
}

.nav-link.active {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(245, 158, 11, 0.1));
    color: #f59e0b;
    border-right: 4px solid #f59e0b;
}

.sidebar-footer {
    padding: 20px 15px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.15);
}

.external-link {
    background: rgba(37, 99, 235, 0.15);
    margin-bottom: 10px;
}

.external-link:hover {
    background: rgba(37, 99, 235, 0.3);
    color: #93c5fd;
}

.logout-link {
    background: rgba(239, 68, 68, 0.1);
}

.logout-link:hover {
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

/* Mobile Toggle */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1001;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
    color: #0a1628;
    border: none;
    border-radius: 12px;
    font-size: 1.3rem;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
}

@media (max-width: 992px) {
    .sidebar {
        transform: translateX(100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>