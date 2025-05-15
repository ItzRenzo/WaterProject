<?php
require_once '../../Database/db_config.php';

$tank_level = 500;
$res = $conn->query("SELECT tank_level FROM WaterTankLog ORDER BY last_refill DESC, id DESC LIMIT 1");
if ($res && $row = $res->fetch_assoc()) {
    $tank_level = $row['tank_level'];
}
$water_percent = round(($tank_level / 500) * 100);

// Handle refill action
if (isset($_GET['refill']) && $_GET['refill'] === '1') {
    $conn->query("INSERT INTO WaterTankLog (tank_level, last_refill) VALUES (500, NOW())");
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REssence Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="Sidebar.html">
</head>

<body>
    <div id="navbar"></div>

    <script>
        fetch('Sidebar.html')
            .then(response => response.text())
            .then(data => {
                document.getElementById('navbar').innerHTML = data;
            });
    </script>

    <div class="main-content">

        <div class="dashboard-header">
            <h2>Dashboard Overview</h2>
            <p class="date">Today: <span id="current-date">March 31, 2025</span></p>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon water-icon">
                    <i class="fas fa-droplet"></i>
                </div>
                <div class="stat-details">
                    <h3>46</h3>
                    <p>Gallons Sold</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon client-icon">
                    <i class="fas fa-droplet"></i>
                </div>
                <div class="stat-details">
                    <h3>5</h3>
                    <p>Water Bottles Sold</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon order-icon">
                    <i class="fas fa-list-check"></i>
                </div>
                <div class="stat-details">
                    <h3>2</h3>
                    <p>Pending Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revenue-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-details">
                    <h3>$240</h3>
                    <p>Today's Revenue</p>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Water level card - Fixed HTML -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-glass-water"></i> Water Tank Level</h3>
                    <div class="card-actions">
                        <button class="refresh-btn" id="refresh-tank"><i class="fas fa-sync-alt"></i></button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="water-level-container">
                        <div class="water-level">
                            <div class="water" id="water-fill" style="height: <?php echo $water_percent; ?>%;"></div>
                            <div class="level-indicators">
                                <div class="level critical"></div>
                                <div class="level warning"></div>
                                <div class="level good"></div>
                            </div>
                        </div>
                        <div class="water-info">
                            <div class="water-percentage" id="water-percentage"><?php echo $water_percent; ?>%</div>
                            <small style="display:block;color:#555;margin-top:2px;font-size:0.9em;">(<?php echo $tank_level; ?>L left)</small>
                            <div class="water-status <?php
                                if ($water_percent < 20) echo 'critical';
                                else if ($water_percent < 40) echo 'low';
                                else echo 'good';
                            ?>" id="water-status">
                                <?php if ($water_percent < 20): ?>
                                    <i class="fas fa-exclamation-triangle"></i> Critical Level
                                <?php elseif ($water_percent < 40): ?>
                                    <i class="fas fa-exclamation-circle"></i> Low Level
                                <?php else: ?>
                                    <i class="fas fa-check-circle"></i> Good Level
                                <?php endif; ?>
                            </div>
                            <button class="refill-btn" id="refill-tank" onclick="location.href='index.php?refill=1'">Refill Tank</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales summary card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Today's Sales</h3>
                    <div class="card-actions">
                        <div class="date-selector">
                            <button class="date-btn active">Day</button>
                            <button class="date-btn">Week</button>
                            <button class="date-btn">Month</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="sales-chart">
                        <div class="chart-bar" style="height: 30%;">
                            <div class="bar-label">Mineral</div>
                        </div>
                        <div class="chart-bar" style="height: 50%;">
                            <div class="bar-label">Purified</div>
                        </div>
                        <div class="chart-bar" style="height: 15%;">
                            <div class="bar-label">Alkaline</div>
                        </div>
                        <div class="chart-bar" style="height: 15%;">
                            <div class="bar-label">Kanjen</div>
                        </div>
                    </div>
                    <div class="sales-summary">
                        <div class="sales-item">
                            <div class="sales-label">Mineral Water</div>
                            <div class="sales-value">15 gallons</div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Purified Water</div>
                            <div class="sales-value">23 gallons</div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Alkaline Water</div>
                            <div class="sales-value">8 gallons</div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Kanjen Water</div>
                            <div class="sales-value">8 gallons</div>
                        </div>
                        <div class="sales-item total">
                            <div class="sales-label">Total</div>
                            <div class="sales-value">46 gallons</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <br>
        <div class="sales-overview-card card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Sales Overview</h3>
                <div class="card-actions">
                    <select id="sales-filter">
                        <option value="day">Day</option>
                        <option value="week">Week</option>
                        <option value="month">Month</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            let chart;
            function renderChart(type) {
                const dataSets = {
                    day: [12, 19, 3, 5, 2, 3],
                    week: [50, 60, 70, 80, 90, 100, 110],
                    month: [200, 180, 220, 210, 250, 230, 240, 260, 270, 280, 290, 300]
                };
                const labelsSets = {
                    day: ['Mineral', 'Purified', 'Alkaline', 'Distilled', 'Other'],
                    week: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    month: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
                };
                if (chart) chart.destroy();
                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labelsSets[type],
                        datasets: [{
                            label: 'Sales',
                            data: dataSets[type],
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });
            }
            const filter = document.getElementById('sales-filter');
            filter.addEventListener('change', function() {
                renderChart(this.value);
            });
            renderChart('day');
        });
        </script>
    </div>

    <script>
        // Set current date
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    </script>
</body>

</html>