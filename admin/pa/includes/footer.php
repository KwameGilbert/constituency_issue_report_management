    <!-- Footer JS scripts -->
    <script>
// Add any global scripts here
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip-target]');
    tooltips.forEach(tooltip => {
        const target = document.getElementById(tooltip.dataset.tooltipTarget);
        if (target) {
            tooltip.addEventListener('mouseenter', () => {
                target.classList.remove('hidden');
            });
            tooltip.addEventListener('mouseleave', () => {
                target.classList.add('hidden');
            });
        }
    });
});
    </script>
    </body>

    </html>