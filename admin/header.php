<?php
/**
 * Radhe Fashion — Admin Layout Header
 * File: admin/header.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// Protect all admin pages
require_admin();

$currentPage = basename($_SERVER['PHP_SELF']);
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' — ' : ''; ?>Radhe Fashion Admin</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
  <div class="admin-layout">
    
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
      <div class="sidebar-header">
        <h1 class="sidebar-title">Radhe Fashion</h1>
        <p class="sidebar-subtitle">Boutique Management</p>
      </div>

      <nav class="sidebar-nav">
        <a href="index.php" class="sidebar-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
          <span>&#9632; Dashboard</span>
        </a>
        <a href="products.php" class="sidebar-link <?php echo in_array($currentPage, ['products.php', 'product-form.php']) ? 'active' : ''; ?>">
          <span>&#9733; Products</span>
        </a>
        <a href="categories.php" class="sidebar-link <?php echo $currentPage === 'categories.php' ? 'active' : ''; ?>">
          <span>&#9776; Categories</span>
        </a>
        <a href="settings.php" class="sidebar-link <?php echo $currentPage === 'settings.php' ? 'active' : ''; ?>">
          <span>&#9881; Settings</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <p class="user-tag">Logged in as: <strong><?php echo e($adminUsername); ?></strong></p>
        <a href="../index.php" target="_blank" class="sidebar-link" style="padding-left: 0; color: #DFBF8B;">
          &#8599; View Public Website
        </a>
        <a href="logout.php" class="btn-logout">&#8618; Sign Out</a>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
      <header class="admin-topbar">
        <h2 class="page-title"><?php echo isset($pageTitle) ? e($pageTitle) : 'Admin Dashboard'; ?></h2>
        <div class="topbar-actions">
          <?php if (isset($topbarAction)): ?>
            <?php echo $topbarAction; ?>
          <?php endif; ?>
        </div>
      </header>
      
      <div class="admin-body">
        <?php if (!empty($_SESSION['flash_success'])): ?>
          <div class="alert alert-success">
            &#10003; <?php echo e($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
          <div class="alert alert-danger">
            &#9888; <?php echo e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
          </div>
        <?php endif; ?>
