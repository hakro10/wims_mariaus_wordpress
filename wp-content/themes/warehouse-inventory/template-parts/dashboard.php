<?php
// Get dashboard stats using the theme function
$stats = get_dashboard_stats();

// Get additional data for charts
global $wpdb;

// Get categories with item counts for chart
$categories_data = $wpdb->get_results("
    SELECT c.name, COUNT(i.id) as item_count
    FROM {$wpdb->prefix}wh_categories c
    LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON c.id = i.category_id
    WHERE c.is_active = 1
    GROUP BY c.id, c.name
    ORDER BY item_count DESC
    LIMIT 10
");

// Get locations with item counts for chart
$locations_data = $wpdb->get_results("
    SELECT l.name, COUNT(i.id) as item_count
    FROM {$wpdb->prefix}wh_locations l
    LEFT JOIN {$wpdb->prefix}wh_inventory_items i ON l.id = i.location_id
    GROUP BY l.id, l.name
    ORDER BY item_count DESC
    LIMIT 8
");

// Get recent sales data for trend chart (last 7 days)
$sales_trend = $wpdb->get_results("
    SELECT DATE(sale_date) as date, SUM(total_amount) as total_sales, COUNT(*) as sales_count
    FROM {$wpdb->prefix}wh_sales
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(sale_date)
    ORDER BY DATE(sale_date) ASC
");

// Get top selling items (last 30 days)
$top_items = $wpdb->get_results("
    SELECT i.name, SUM(s.quantity) as total_sold, SUM(s.total_amount) as total_revenue
    FROM {$wpdb->prefix}wh_sales s
    JOIN {$wpdb->prefix}wh_inventory_items i ON s.item_id = i.id
    WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY s.item_id, i.name
    ORDER BY total_sold DESC
    LIMIT 5
");
?>
<div class="dashboard-content">
    <div class="page-header">
        <h1>📊 Dashboard</h1>
        <p>Overview of your warehouse operations</p>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-value">$<?php echo isset($stats['total_value']) ? esc_html(number_format($stats['total_value'], 2)) : '0.00'; ?></div>
            <div class="stat-label">Total Inventory Value</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value"><?php echo isset($stats['in_stock']) ? esc_html($stats['in_stock']) : '0'; ?></div>
            <div class="stat-label">Items In Stock</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon yellow">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?php echo isset($stats['low_stock']) ? esc_html($stats['low_stock']) : '0'; ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon red">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value"><?php echo isset($stats['out_of_stock']) ? esc_html($stats['out_of_stock']) : '0'; ?></div>
            <div class="stat-label">Out of Stock Items</div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="dashboard-charts">
        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>📈 Stock Status Distribution</h3>
                    <p>Current inventory status overview</p>
                </div>
                <div class="chart-container">
                    <canvas id="stockStatusChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3>🛍️ Sales Trend (Last 7 Days)</h3>
                    <p>Daily sales performance</p>
                </div>
                <div class="chart-container">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3>📦 Items by Category</h3>
                    <p>Distribution of inventory by category</p>
                </div>
                <div class="chart-container">
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3>🏢 Items by Location</h3>
                    <p>Inventory distribution across locations</p>
                </div>
                <div class="chart-container">
                    <canvas id="locationsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="chart-row">
            <div class="chart-card full-width">
                <div class="chart-header">
                    <h3>🔥 Top Selling Items (Last 30 Days)</h3>
                    <p>Best performing products</p>
                </div>
                <div class="chart-container">
                    <canvas id="topItemsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Dashboard Charts JavaScript
jQuery(document).ready(function($) {
    // Stock Status Chart (Donut)
    const stockCtx = document.getElementById('stockStatusChart').getContext('2d');
    new Chart(stockCtx, {
        type: 'doughnut',
        data: {
            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: [
                    <?php echo isset($stats['in_stock']) ? $stats['in_stock'] : 0; ?>,
                    <?php echo isset($stats['low_stock']) ? $stats['low_stock'] : 0; ?>,
                    <?php echo isset($stats['out_of_stock']) ? $stats['out_of_stock'] : 0; ?>
                ],
                backgroundColor: [
                    '#10B981', // green
                    '#F59E0B', // yellow
                    '#EF4444'  // red
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Sales Trend Chart (Line)
    const salesCtx = document.getElementById('salesTrendChart').getContext('2d');
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [
                <?php 
                foreach ($sales_trend as $day) {
                    echo "'" . date('M j', strtotime($day->date)) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Sales ($)',
                data: [
                    <?php 
                    foreach ($sales_trend as $day) {
                        echo $day->total_sales . ',';
                    }
                    ?>
                ],
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });

    // Categories Chart (Bar)
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    new Chart(categoriesCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($categories_data as $category) {
                    echo "'" . esc_js($category->name) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Items',
                data: [
                    <?php 
                    foreach ($categories_data as $category) {
                        echo $category->item_count . ',';
                    }
                    ?>
                ],
                backgroundColor: '#8B5CF6',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Locations Chart (Pie)
    const locationsCtx = document.getElementById('locationsChart').getContext('2d');
    new Chart(locationsCtx, {
        type: 'pie',
        data: {
            labels: [
                <?php 
                foreach ($locations_data as $location) {
                    echo "'" . esc_js($location->name) . "',";
                }
                ?>
            ],
            datasets: [{
                data: [
                    <?php 
                    foreach ($locations_data as $location) {
                        echo $location->item_count . ',';
                    }
                    ?>
                ],
                backgroundColor: [
                    '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', 
                    '#EF4444', '#06B6D4', '#84CC16', '#F97316'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });

    // Top Items Chart (Horizontal Bar)
    const topItemsCtx = document.getElementById('topItemsChart').getContext('2d');
    new Chart(topItemsCtx, {
        type: 'bar',
        data: {
            labels: [
                <?php 
                foreach ($top_items as $item) {
                    echo "'" . esc_js($item->name) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Units Sold',
                data: [
                    <?php 
                    foreach ($top_items as $item) {
                        echo $item->total_sold . ',';
                    }
                    ?>
                ],
                backgroundColor: '#10B981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<style>
.dashboard-charts {
    margin-top: 2rem;
}

.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.chart-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border: 1px solid #f1f5f9;
}

.chart-card.full-width {
    grid-column: 1 / -1;
}

.chart-header {
    margin-bottom: 1rem;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 1rem;
}

.chart-header h3 {
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    font-size: 1.1rem;
    font-weight: 600;
}

.chart-header p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.chart-container {
    height: 300px;
    position: relative;
}

.chart-card.full-width .chart-container {
    height: 250px;
}

@media (max-width: 768px) {
    .chart-row {
        grid-template-columns: 1fr;
    }
    
    .chart-container {
        height: 250px;
    }
}
</style>