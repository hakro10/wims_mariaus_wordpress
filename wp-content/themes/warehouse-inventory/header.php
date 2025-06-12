<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo home_url('/manifest.json'); ?>">
    
    <!-- Mobile App Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Warehouse">
    <meta name="application-name" content="Warehouse">
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo get_template_directory_uri(); ?>/assets/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo get_template_directory_uri(); ?>/assets/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo get_template_directory_uri(); ?>/assets/icons/favicon-16x16.png">
    
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileImage" content="<?php echo get_template_directory_uri(); ?>/assets/icons/mstile-144x144.png">
    <meta name="msapplication-config" content="<?php echo get_template_directory_uri(); ?>/assets/icons/browserconfig.xml">
    
    <?php wp_head(); ?>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- QR Code Scanner Library (jsQR) -->
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
</head>

<body <?php body_class(); ?>>

<header class="warehouse-header">
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-warehouse"></i>
                Warehouse Management System
            </div>
            
            <div class="user-menu">
                <?php if (is_user_logged_in()) : ?>
                    <?php $current_user = wp_get_current_user(); ?>
                    <span class="user-greeting">
                        Welcome, <?php echo esc_html($current_user->display_name); ?>
                    </span>
                    <div class="online-status">
                        <span class="status-indicator online" title="Online"></span>
                    </div>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else : ?>
                    <a href="<?php echo wp_login_url(); ?>" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header> 