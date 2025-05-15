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

// --- Dashboard Stats Queries ---
$today = date('Y-m-d');

// Gallons Sold (Products 1-4, sum Quantity for today)
$gallonsSold = 0;
$res = $conn->query("SELECT SUM(Quantity) as total FROM Transaction WHERE ProductID BETWEEN 1 AND 4 AND DATE(TransactionDate) = '$today'");
if ($res && $row = $res->fetch_assoc()) $gallonsSold = (int)$row['total'];

// Water Bottles Sold (Products 5-8, sum Quantity for today)
$bottlesSold = 0;
$res = $conn->query("SELECT SUM(Quantity) as total FROM Transaction WHERE ProductID BETWEEN 5 AND 8 AND DATE(TransactionDate) = '$today'");
if ($res && $row = $res->fetch_assoc()) $bottlesSold = (int)$row['total'];

// Pending Orders (DeliveryStatus = 'pending' for today)
$pendingOrders = 0;
$res = $conn->query("SELECT COUNT(*) as total FROM Transaction WHERE DeliveryStatus = 'pending' AND DATE(TransactionDate) = '$today'");
if ($res && $row = $res->fetch_assoc()) $pendingOrders = (int)$row['total'];

// Today's Revenue (sum Price for today)
$revenue = 0;
$res = $conn->query("SELECT SUM(Price) as total FROM Transaction WHERE DATE(TransactionDate) = '$today'");
if ($res && $row = $res->fetch_assoc()) $revenue = (float)$row['total'];

// --- Sales Filter (Day/Week/Month) ---
$salesFilter = isset($_GET['sales_filter']) ? $_GET['sales_filter'] : 'day';

if ($salesFilter === 'week') {
    $dateCondition = "YEARWEEK(TransactionDate, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($salesFilter === 'month') {
    $dateCondition = "YEAR(TransactionDate) = YEAR(CURDATE()) AND MONTH(TransactionDate) = MONTH(CURDATE())";
} else {
    $dateCondition = "DATE(TransactionDate) = CURDATE()";
}

// --- Dynamic Sales Summary (by ProductID and filter) ---
$salesSummary = [
    1 => 0, // Mineral Water
    2 => 0, // Purified Water
    3 => 0, // Alkaline Water
    4 => 0, // Distilled Water
    5 => 0, // Mineral Water Bottle
    6 => 0, // Purified Water Bottle
    7 => 0, // Alkaline Water Bottle
    8 => 0  // Distilled Water Bottle
];
$res = $conn->query("SELECT ProductID, SUM(Quantity) as qty FROM Transaction WHERE $dateCondition GROUP BY ProductID");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pid = (int)$row['ProductID'];
        if (isset($salesSummary[$pid])) {
            $salesSummary[$pid] = (int)$row['qty'];
        }
    }
}

// --- Prepare Chart Data for Sales Chart ---
$chartLabels = ['Mineral Water', 'Purified Water', 'Alkaline Water', 'Distilled Water', 'Mineral Water Bottle', 'Purified Water Bottle', 'Alkaline Water Bottle', 'Distilled Water Bottle'];
$chartData = [];
foreach ([1,2,3,4,5,6,7,8] as $pid) {
    $chartData[] = $salesSummary[$pid];
}

// --- Sales Overview Filter (Day/Week/Month) ---
$salesOverviewFilter = isset($_GET['sales_filter']) ? $_GET['sales_filter'] : 'day';

$overviewChartLabels = [];
$overviewChartData = [];
if ($salesOverviewFilter === 'month') {
    // Show sales per month for the current year
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    $overviewChartLabels = array_values($months);
    $overviewChartData = array_fill(0, 12, 0);
    $res = $conn->query("SELECT MONTH(TransactionDate) as month, SUM(Quantity) as qty FROM Transaction WHERE YEAR(TransactionDate) = YEAR(CURDATE()) GROUP BY month");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $m = (int)$row['month'];
            $overviewChartData[$m-1] = (int)$row['qty'];
        }
    }
} else {
    // Day or week: show per product
    $overviewChartLabels = ['Mineral Water', 'Purified Water', 'Alkaline Water', 'Distilled Water', 'Mineral Water Bottle', 'Purified Water Bottle', 'Alkaline Water Bottle', 'Distilled Water Bottle'];
    $overviewChartData = [0,0,0,0,0,0,0,0];
    $res = $conn->query("SELECT ProductID, SUM(Quantity) as qty FROM Transaction WHERE $dateCondition GROUP BY ProductID");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $pid = (int)$row['ProductID'];
            if ($pid >= 1 && $pid <= 8) {
                $overviewChartData[$pid-1] = (int)$row['qty'];
            }
        }
    }
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
                    <h3><?php echo $gallonsSold; ?></h3>
                    <p>Gallons Sold</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon client-icon">
                    <i class="fas fa-droplet"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $bottlesSold; ?></h3>
                    <p>Water Bottles Sold</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon order-icon">
                    <i class="fas fa-list-check"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $pendingOrders; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon revenue-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-details">
                    <h3>₱<?php echo number_format($revenue, 2); ?></h3>
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
                </div>
                <div class="card-body">
                    <div class="sales-summary">
                        <div class="sales-item">
                            <div class="sales-label">Mineral Water</div>
                            <div class="sales-value"><?php echo $salesSummary[1]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Purified Water</div>
                            <div class="sales-value"><?php echo $salesSummary[2]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Alkaline Water</div>
                            <div class="sales-value"><?php echo $salesSummary[3]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Distilled Water</div>
                            <div class="sales-value"><?php echo $salesSummary[4]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Mineral Water Bottle</div>
                            <div class="sales-value"><?php echo $salesSummary[5]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Purified Water Bottle</div>
                            <div class="sales-value"><?php echo $salesSummary[6]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Alkaline Water Bottle</div>
                            <div class="sales-value"><?php echo $salesSummary[7]; ?></div>
                        </div>
                        <div class="sales-item">
                            <div class="sales-label">Distilled Water Bottle</div>
                            <div class="sales-value"><?php echo $salesSummary[8]; ?></div>
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
                        <option value="day" <?php if($salesOverviewFilter==='day') echo 'selected'; ?>>Day</option>
                        <option value="week" <?php if($salesOverviewFilter==='week') echo 'selected'; ?>>Week</option>
                        <option value="month" <?php if($salesOverviewFilter==='month') echo 'selected'; ?>>Month</option>
                    </select>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="100"></canvas>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>

        const overviewChartLabels = <?php echo json_encode($overviewChartLabels); ?>;
        const overviewChartData = <?php echo json_encode($overviewChartData); ?>;

        const ctx = document.getElementById('salesChart').getContext('2d');
        let chart;
        function renderChart() {
            if (chart) chart.destroy();
            chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: overviewChartLabels,
                    datasets: [{
                        label: <?php echo $salesOverviewFilter === 'month' ? "'Units Sold (All Products)'" : "'Units Sold'"; ?>,
                        data: overviewChartData,
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

        renderChart();

        document.getElementById('sales-filter').addEventListener('change', function() {
            const val = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('sales_filter', val);
            window.location.href = url.toString();
        });

        // Set current date
        document.getElementById('current-date').textContent = new Date().toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        </script>
    </div>
</body>

</html>