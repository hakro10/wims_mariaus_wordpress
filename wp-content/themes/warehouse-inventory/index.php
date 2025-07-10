<?php
// Get active tab first
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

get_header(); ?>

<div class="warehouse-app">
    <!-- Navigation Tabs -->
    <nav class="nav-tabs">
        <div class="container">
            <div class="nav-list">
                <a href="?tab=dashboard" class="nav-tab <?php echo ($active_tab === 'dashboard') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="?tab=inventory" class="nav-tab <?php echo ($active_tab === 'inventory') ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i> Inventory
                </a>
                <a href="?tab=categories" class="nav-tab <?php echo ($active_tab === 'categories') ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="?tab=locations" class="nav-tab <?php echo ($active_tab === 'locations') ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt"></i> Locations
                </a>
                <a href="?tab=sales" class="nav-tab <?php echo ($active_tab === 'sales') ? 'active' : ''; ?>">
                    <i class="fas fa-dollar-sign"></i> Sales
                </a>
                <a href="?tab=tasks" class="nav-tab <?php echo ($active_tab === 'tasks') ? 'active' : ''; ?>">
                    <i class="fas fa-tasks"></i> Tasks
                </a>
                <a href="?tab=team" class="nav-tab <?php echo ($active_tab === 'team') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> Team
                </a>
                <a href="?tab=qr-codes" class="nav-tab <?php echo ($active_tab === 'qr-codes') ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i> QR Codes
                </a>
            </div>
        </div>
    </nav>

    <!-- Content Area -->
    <main class="main-content">
        <div class="container">
            <?php
            // Load appropriate template part based on active tab
            switch ($active_tab) {
                case 'dashboard':
                    get_template_part('template-parts/dashboard');
                    break;
                case 'inventory':
                    get_template_part('template-parts/inventory');
                    break;
                case 'categories':
                    get_template_part('template-parts/categories');
                    break;
                case 'locations':
                    get_template_part('template-parts/locations');
                    break;
                case 'sales':
                    get_template_part('template-parts/sales');
                    break;
                case 'tasks':
                    get_template_part('template-parts/tasks');
                    break;
                case 'team':
                    get_template_part('template-parts/team');
                    break;
                case 'qr-codes':
                    get_template_part('template-parts/qr-codes');
                    break;
                default:
                    get_template_part('template-parts/dashboard');
                    break;
            }
            ?>
        </div>
    </main>
</div>

<?php get_footer(); ?>