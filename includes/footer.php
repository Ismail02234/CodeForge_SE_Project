<footer class="mt-5 py-4 bg-white border-top">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; 2026 CodeForge Platform | DBMS Project Presentation</p>
            <small class="text-info">Connected to MySQL Instance on Port 3307</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        setTimeout(function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                let bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 3000);
    </script>
</body>
</html>