<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <title><?php echo $page_title ?? 'لوحة التحكم - جامعة تعز فرع التربة'; ?></title>
    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Admin Styles -->
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="تبديل القائمة">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Include Sidebar -->
    <?php include('sidebar.php'); ?>
    
    <!-- Main Content Wrapper -->
    <div class="main-content">
        <!-- Page Header -->
        <header class="page-header">
            <div class="page-title">
                <i class="<?php echo $page_icon ?? 'fas fa-tachometer-alt'; ?>"></i>
                <h1><?php echo $page_heading ?? 'لوحة التحكم'; ?></h1>
            </div>
            <div class="user-menu">
                <span class="text-muted d-none d-md-inline">
                    مرحباً، <?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'المدير'); ?>
                </span>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'م', 0, 1)); ?>
                </div>
            </div>
        </header>
