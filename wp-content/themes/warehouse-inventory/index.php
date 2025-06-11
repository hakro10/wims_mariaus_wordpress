<?php
/**
 * The main template file for Warehouse Inventory Management
 */

get_header(); 


?>

<div class="warehouse-main">
    <?php 
    // Get current user and check permissions
    if (!is_user_logged_in()) {
        echo '<div class="container"><p>Please log in to access the warehouse management system.</p></div>';
        get_footer();
        return;
    }
    
    // Get active tab from URL parameter
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
    

    ?>
    
    <!-- Navigation Tabs -->
    <nav class="main-nav">
        <div class="container">
            <div class="nav-tabs">
                <a href="?tab=dashboard" class="nav-tab <?php echo $active_tab === 'dashboard' ? 'active' : ''; ?>">
                    📊 Dashboard
                </a>
                <a href="?tab=inventory" class="nav-tab <?php echo $active_tab === 'inventory' ? 'active' : ''; ?>">
                    📦 Inventory
                </a>
                <a href="?tab=categories" class="nav-tab <?php echo $active_tab === 'categories' ? 'active' : ''; ?>">
                    🏷️ Categories
                </a>
                <a href="?tab=locations" class="nav-tab <?php echo $active_tab === 'locations' ? 'active' : ''; ?>">
                    📍 Locations
                </a>
                <a href="?tab=sales" class="nav-tab <?php echo $active_tab === 'sales' ? 'active' : ''; ?>">
                    💰 Sales
                </a>
                <a href="?tab=team" class="nav-tab <?php echo $active_tab === 'team' ? 'active' : ''; ?>">
                    👥 Team
                </a>
                <a href="?tab=tasks" class="nav-tab <?php echo $active_tab === 'tasks' ? 'active' : ''; ?>">
                    ✅ Tasks
                </a>
                <a href="?tab=qr-codes" class="nav-tab <?php echo $active_tab === 'qr-codes' ? 'active' : ''; ?>">
                    📱 QR Codes
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php
        // Load the appropriate content based on active tab
        switch ($active_tab) {
            case 'dashboard':
                include(get_template_directory() . '/template-parts/dashboard.php');
                break;
            case 'inventory':
                include(get_template_directory() . '/template-parts/inventory.php');
                break;
            case 'categories':
                include(get_template_directory() . '/template-parts/categories.php');
                break;
            case 'locations':
                include(get_template_directory() . '/template-parts/locations.php');
                break;
            case 'sales':
                include(get_template_directory() . '/template-parts/sales.php');
                break;
            case 'team':
                include(get_template_directory() . '/template-parts/team.php');
                break;
            case 'tasks':
                include(get_template_directory() . '/template-parts/tasks.php');
                break;
            case 'qr-codes':
                include(get_template_directory() . '/template-parts/qr-codes.php');
                break;
            default:
                include(get_template_directory() . '/template-parts/dashboard.php');
        }
        ?>
    </div>
</div>

<?php get_footer(); ?> 