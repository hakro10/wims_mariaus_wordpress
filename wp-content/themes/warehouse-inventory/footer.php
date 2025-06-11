<footer class="warehouse-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-left">
                <p>&copy; <?php echo date('Y'); ?> Warehouse Management System. All rights reserved.</p>
            </div>
            <div class="footer-right">
                <span class="version">Version <?php echo get_option('wh_inventory_version', '1.0.0'); ?></span>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>

<script>
// Basic JavaScript for modal functionality
document.addEventListener('DOMContentLoaded', function() {
    // Modal close functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.style.display = 'none';
        }
        if (e.target.classList.contains('btn-close-modal')) {
            e.target.closest('.modal-overlay').style.display = 'none';
        }
    });
    
    // Status indicator animation
    const statusIndicators = document.querySelectorAll('.status-indicator.online');
    statusIndicators.forEach(indicator => {
        setInterval(() => {
            indicator.style.opacity = indicator.style.opacity === '0.5' ? '1' : '0.5';
        }, 1000);
    });
});
</script>

</body>
</html> 