        </div><!-- /content-area -->
        
        <!-- Footer -->
        <footer class="text-center py-3 text-muted small">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
        </footer>
    </main>
    
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle d-md-none">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Leaflet JS for Maps -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
    
    <!-- Load Notifications on Dropdown -->
    <script>
    document.querySelector('.notification-btn')?.addEventListener('click', function() {
        const list = document.getElementById('notification-list');
        App.ajax('<?php echo APP_URL; ?>/ajax/notifications.php', { action: 'list' })
            .then(response => {
                if (response.success) {
                    if (response.notifications.length === 0) {
                        list.innerHTML = '<div class="text-center py-3 text-muted"><small>No notifications</small></div>';
                    } else {
                        list.innerHTML = response.notifications.map(n => `
                            <a href="${n.link || '#'}" class="dropdown-item py-2 ${n.is_read ? '' : 'bg-light'}">
                                <div class="d-flex align-items-start">
                                    <span class="badge bg-${n.type} me-2 mt-1">&nbsp;</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small">${n.title}</div>
                                        <div class="text-muted small text-truncate" style="max-width: 250px;">${n.message}</div>
                                    </div>
                                </div>
                            </a>
                        `).join('');
                    }
                }
            });
    });
    </script>
    
    <?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
