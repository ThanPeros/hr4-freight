<?php
// Database connection
$host = 'localhost';
$dbname = 'systems';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

// Fetch data for dashboard
function fetchEmployeeCount($pdo)
{
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees");
    return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

function fetchStatusStats($pdo)
{
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as count 
        FROM status 
        GROUP BY status
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchActiveEmployees($pdo)
{
    // Get the latest status for each employee
    $stmt = $pdo->query("
        SELECT s1.employee_id, s1.status, s1.changed_at, e.first_name, e.last_name
        FROM status s1
        INNER JOIN employees e ON s1.employee_id = e.id
        INNER JOIN (
            SELECT employee_id, MAX(changed_at) as max_date
            FROM status
            GROUP BY employee_id
        ) s2 ON s1.employee_id = s2.employee_id AND s1.changed_at = s2.max_date
        WHERE s1.status = 'active'
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchClaimsData($pdo)
{
    $stmt = $pdo->query("
        SELECT claim_type, COUNT(id) as count, SUM(amount) as total_amount 
        FROM claims 
        GROUP BY claim_type
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchSalaryData($pdo)
{
    // Fixed the query to use gross_pay instead of total_earnings
    $stmt = $pdo->query("
        SELECT e.first_name, e.last_name, ss.min_salary, ss.max_salary, ec.gross_pay as total_earnings 
        FROM employees e 
        JOIN salary_structures ss ON e.id = ss.employee_id 
        JOIN earnings_calculations ec ON e.id = ec.employee_id 
        ORDER BY ec.calculation_date DESC
        LIMIT 10
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchRecentStatusChanges($pdo)
{
    $stmt = $pdo->query("
        SELECT s.employee_id, CONCAT(e.first_name, ' ', e.last_name) as name, 
               s.status, s.changed_at, s.changed_by
        FROM status s
        JOIN employees e ON s.employee_id = e.id
        ORDER BY s.changed_at DESC
        LIMIT 5
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data
$employeeCount = fetchEmployeeCount($pdo);
$statusStats = fetchStatusStats($pdo);
$activeEmployees = fetchActiveEmployees($pdo);
$claimsData = fetchClaimsData($pdo);

// Handle potential errors with salary data
try {
    $salaryData = fetchSalaryData($pdo);
} catch (PDOException $e) {
    // If there's an error, create empty salary data
    $salaryData = [];
    error_log("Error fetching salary data: " . $e->getMessage());
}

$recentStatusChanges = fetchRecentStatusChanges($pdo);

// Calculate active employee count
$activeCount = 0;
foreach ($statusStats as $stat) {
    if ($stat['status'] == 'active') {
        $activeCount = $stat['count'];
        break;
    }
}

// Calculate average salary
$totalSalary = 0;
$count = 0;
foreach ($salaryData as $salary) {
    $totalSalary += ($salary['min_salary'] + $salary['max_salary']) / 2;
    $count++;
}
$avgSalary = $count > 0 ? $totalSalary / $count : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --light: #ecf0f1;
            --success: #2ecc71;
            --warning: #f39c12;
            --danger: #e74c3c;
            --dark: #2c3e50;
            --text: #333;
            --text-light: #777;
            --border: #ddd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(to bottom, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            position: fixed;
            width: 250px;
            height: 100%;
            overflow-y: auto;
        }

        .logo {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 30px;
        }

        .logo h2 {
            font-weight: 600;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 5px 0;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .nav-item i {
            margin-right: 10px;
            font-size: 18px;
        }

        .nav-item:hover,
        .nav-item.active {
            background-color: var(--accent);
        }

        .main-content {
            grid-column: 2;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            text-align: center;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-card h2 {
            font-size: 2.5rem;
            margin: 10px 0;
        }

        .stat-card.employees i {
            color: var(--accent);
        }

        .stat-card.active i {
            color: var(--success);
        }

        .stat-card.claims i {
            color: var(--warning);
        }

        .stat-card.salary i {
            color: var(--danger);
        }

        .stat-card p {
            color: var(--text-light);
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table,
        th,
        td {
            border: 1px solid var(--border);
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
        }

        th {
            background-color: var(--secondary);
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            min-width: 180px;
        }

        .filter-item label {
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        select,
        input {
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            background: white;
        }

        .btn {
            padding: 10px 15px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: var(--primary);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background-color: var(--success);
            color: white;
        }

        .status-inactive {
            background-color: var(--danger);
            color: white;
        }

        .recent-activity {
            margin-top: 30px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid var(--border);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }

            .main-content {
                grid-column: 1;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .filters {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="sidebar">
            <div class="logo">
                <h2><i class="fas fa-chart-line"></i> HR Analytics</h2>
            </div>
            <div class="nav-item active"><i class="fas fa-home"></i> Dashboard</div>
            <div class="nav-item"><i class="fas fa-users"></i> Employee Data</div>
            <div class="nav-item"><i class="fas fa-file-invoice-dollar"></i> Claims</div>
            <div class="nav-item"><i class="fas fa-money-bill-wave"></i> Salary Structures</div>
            <div class="nav-item"><i class="fas fa-calculator"></i> Earnings</div>
            <div class="nav-item"><i class="fas fa-chart-pie"></i> Reports</div>
            <div class="nav-item"><i class="fas fa-cog"></i> Settings</div>
        </div>

        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-chart-bar"></i> HR Analytics Dashboard</h1>
                <div class="user-info">
                    <span><i class="fas fa-user-circle"></i> Welcome, Admin</span>
                </div>
            </div>

            <div class="filters">
                <div class="filter-item">
                    <label for="date-range"><i class="fas fa-calendar"></i> Date Range</label>
                    <select id="date-range">
                        <option value="7">Last 7 Days</option>
                        <option value="30" selected>Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                        <option value="365">Last Year</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="department"><i class="fas fa-building"></i> Department</label>
                    <select id="department">
                        <option value="all">All Departments</option>
                        <option value="hr">Human Resources</option>
                        <option value="it">Information Technology</option>
                        <option value="finance">Finance</option>
                        <option value="sales">Sales</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label for="status"><i class="fas fa-user-check"></i> Employment Status</label>
                    <select id="status">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="filter-item">
                    <label>&nbsp;</label>
                    <button class="btn" id="apply-filters"><i class="fas fa-filter"></i> Apply Filters</button>
                </div>
            </div>

            <div class="dashboard-cards">
                <div class="card stat-card employees">
                    <i class="fas fa-users"></i>
                    <h3>Total Employees</h3>
                    <h2><?php echo $employeeCount; ?></h2>
                    <p>Across all departments</p>
                </div>

                <div class="card stat-card active">
                    <i class="fas fa-user-check"></i>
                    <h3>Active Employees</h3>
                    <h2><?php echo $activeCount; ?></h2>
                    <p>Currently employed</p>
                </div>

                <div class="card stat-card claims">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <h3>Total Claims</h3>
                    <h2>
                        <?php
                        $totalClaims = 0;
                        foreach ($claimsData as $claim) {
                            $totalClaims += $claim['count'];
                        }
                        echo $totalClaims;
                        ?>
                    </h2>
                    <p>All claim types</p>
                </div>

                <div class="card stat-card salary">
                    <i class="fas fa-money-bill-wave"></i>
                    <h3>Avg. Salary</h3>
                    <h2>$<?php echo number_format($avgSalary, 2); ?></h2>
                    <p>Average salary range</p>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-pie"></i> Employee Status Distribution</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-bar"></i> Claims by Type</h3>
                <div class="chart-container">
                    <canvas id="claimsChart"></canvas>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-money-bill-wave"></i> Salary Data</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee Name</th>
                            <th>Min Salary</th>
                            <th>Max Salary</th>
                            <th>Total Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($salaryData) > 0): ?>
                            <?php foreach ($salaryData as $row): ?>
                                <tr>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td>$<?php echo number_format($row['min_salary'], 2); ?></td>
                                    <td>$<?php echo number_format($row['max_salary'], 2); ?></td>
                                    <td>$<?php echo number_format($row['total_earnings'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No salary data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card recent-activity">
                <h3><i class="fas fa-history"></i> Recent Status Changes</h3>
                <?php foreach ($recentStatusChanges as $change): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div class="activity-details">
                            <p><strong><?php echo $change['name']; ?></strong> status changed to
                                <span class="status-badge status-<?php echo $change['status']; ?>"><?php echo $change['status']; ?></span>
                            </p>
                            <small>By <?php echo $change['changed_by'] ?: 'System'; ?> on <?php echo $change['changed_at']; ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Status Distribution Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: [
                        <?php
                        foreach ($statusStats as $stat) {
                            echo "'" . ucfirst($stat['status']) . "',";
                        }
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php
                            foreach ($statusStats as $stat) {
                                echo $stat['count'] . ",";
                            }
                            ?>
                        ],
                        backgroundColor: [
                            '#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Claims Chart
            const claimsCtx = document.getElementById('claimsChart').getContext('2d');
            const claimsChart = new Chart(claimsCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php
                        foreach ($claimsData as $claim) {
                            echo "'" . ucfirst($claim['claim_type']) . "',";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Number of Claims',
                        data: [
                            <?php
                            foreach ($claimsData as $claim) {
                                echo $claim['count'] . ",";
                            }
                            ?>
                        ],
                        backgroundColor: '#3498db'
                    }, {
                        label: 'Total Amount',
                        data: [
                            <?php
                            foreach ($claimsData as $claim) {
                                echo $claim['total_amount'] . ",";
                            }
                            ?>
                        ],
                        backgroundColor: '#2ecc71',
                        type: 'line',
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Claims'
                            }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Amount'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });

            // Filter functionality
            document.getElementById('apply-filters').addEventListener('click', updateDashboard);

            function updateDashboard() {
                // In a real application, this would make an AJAX request to update the dashboard
                alert('Filters updated. In a real application, this would refresh the data.');
                console.log('Date range:', document.getElementById('date-range').value);
                console.log('Department:', document.getElementById('department').value);
                console.log('Status:', document.getElementById('status').value);
            }
        });
    </script>
</body>

</html>