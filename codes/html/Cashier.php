<?php
session_start();
include_once '../../Database/db_config.php';
include_once '../../Database/db_check.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RJane Water </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../Css/Cashier.css">
</head>
<body>
    <div class="kiosk-container">
        <!-- Water flowing animation -->
        <div class="water-animation"></div>
        
        <!-- Header with logo -->
        <header class="kiosk-header">
            <div class="kiosk-logo">
                <i class="fas fa-water"></i>
            </div>
            <h1>RJane Water Refilling</h1>
            <p>Pure Water, Healthier Life</p>
            <!-- Cashier info display -->
            <div class="cashier-info">
                <span>Cashier: <span id="header-cashier-name"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Not logged in'; ?></span></span>
            </div>
        </header>
        <!-- Main Content -->
        <main class="kiosk-main">
            <div class="kiosk-options">
                <form action="order.php" method="post">
                    <input type="hidden" name="from_cashier" value="1">
                    <button type="submit" class="kiosk-btn" id="order-water">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Order Water</span>
                    </button>
                </form>
            </div>
        </main>
        <!-- Footer with time -->
        <footer class="kiosk-footer">
            <p>&copy; 2025 RJane Water Refilling</p>
            <div class="time-display" id="time-display">10:30 AM</div>
            <p>Touch to begin</p>
        </footer>
    </div>
    <script>
        // Set cashier name if available from PHP session (if rendered via PHP)
        document.addEventListener('DOMContentLoaded', function () {
            // Only keep the time display functionality
            const timeDisplay = document.getElementById('time-display');
            // Update time
            function updateTime() {
                const now = new Date();
                const hours = now.getHours();
                const minutes = now.getMinutes().toString().padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                const formattedHours = hours % 12 || 12;
                timeDisplay.textContent = `${formattedHours}:${minutes} ${ampm}`;
            }
            updateTime();
            setInterval(updateTime, 60000);
        });
    </script>
</body>
</html>