<?php
// Temporary test script to trigger profit table creation
require_once('wp-config.php');
require_once('wp-load.php');

// Manually trigger the functions
create_profit_tracking_table();
populate_sample_profit_data();

echo "Profit tracking table created and sample data populated!\n";
echo "You can now delete this file.\n";
?> 