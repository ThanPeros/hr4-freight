<?php
// Database connection settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables with default values to prevent undefined variable errors
$total_payroll = 0;
$total_employees = 0;
$new_hires = 0;
$turnover_rate = 0;
$payroll_labels = [];
$payroll_data = [];
$dept_labels = [];
$dept_data = [];

// Fetch total payroll data
$payroll_sql = "SELECT SUM(salary + bonus) as total_payroll FROM employees";
$payroll_result = $conn->query($payroll_sql);
if ($payroll_result && $payroll_result->num_rows > 0) {
    $total_payroll = $payroll_result->fetch_assoc()['total_payroll'] ?? 0;
}

// Fetch employee count
$employee_sql = "SELECT COUNT(*) as total_employees FROM employees";
$employee_result = $conn->query($employee_sql);
if ($employee_result && $employee_result->num_rows > 0) {
    $total_employees = $employee_result->fetch_assoc()['total_employees'] ?? 0;
}

// Fetch new hires this month
$new_hires_sql = "SELECT COUNT(*) as new_hires FROM employees WHERE hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$new_hires_result = $conn->query($new_hires_sql);
if ($new_hires_result && $new_hires_result->num_rows > 0) {
    $new_hires = $new_hires_result->fetch_assoc()['new_hires'] ?? 0;
}

// Fetch turnover rate
$turnover_sql = "SELECT 
    (SELECT COUNT(*) FROM employees WHERE termination_date IS NOT NULL AND termination_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) * 100.0 / 
    NULLIF((SELECT COUNT(*) FROM employees), 0) as turnover_rate";
$turnover_result = $conn->query($turnover_sql);
if ($turnover_result && $turnover_result->num_rows > 0) {
    $turnover_rate = $turnover_result->fetch_assoc()['turnover_rate'] ?? 0;
}

// Fetch payroll trend data
$payroll_trend_sql = "SELECT 
    YEAR(pay_date) as year, 
    MONTH(pay_date) as month, 
    SUM(amount) as total_payroll 
    FROM payroll 
    GROUP BY YEAR(pay_date), MONTH(pay_date) 
    ORDER BY year, month 
    LIMIT 6";
$payroll_trend_result = $conn->query($payroll_trend_sql);
if ($payroll_trend_result && $payroll_trend_result->num_rows > 0) {
    while ($row = $payroll_trend_result->fetch_assoc()) {
        $payroll_labels[] = date('M Y', mktime(0, 0, 0, $row['month'], 1, $row['year']));
        $payroll_data[] = $row['total_payroll'];
    }
}

// Fetch department distribution
$dept_sql = "SELECT d.name, COUNT(e.id) as employee_count 
             FROM departments d 
             LEFT JOIN employees e ON d.id = e.department_id 
             GROUP BY d.id, d.name";
$dept_result = $conn->query($dept_sql);
if ($dept_result && $dept_result->num_rows > 0) {
    while ($row = $dept_result->fetch_assoc()) {
        $dept_labels[] = $row['name'];
        $dept_data[] = $row['employee_count'];
    }
}

// Fetch recent new hires
$new_hires_list_sql = "SELECT e.first_name, e.last_name, d.name as department, e.hire_date, e.salary 
                       FROM employees e 
                       JOIN departments d ON e.department_id = d.id 
                       WHERE e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                       ORDER BY e.hire_date DESC 
                       LIMIT 5";
$new_hires_list_result = $conn->query($new_hires_list_sql);

// Fetch department budget data
$dept_budget_sql = "SELECT d.name, d.budget, COALESCE(SUM(e.salary), 0) as spent 
                    FROM departments d 
                    LEFT JOIN employees e ON d.id = e.department_id 
                    GROUP BY d.id, d.name, d.budget";
$dept_budget_result = $conn->query($dept_budget_sql);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-blue: #4e73df;
            --dark-blue: #2c3e50;
            --light-gray: #f8f9fc;
            --dark-text: #212529;
            --medium-gray: #6e707e;
            --light-text: rgba(255, 255, 255, 0.8);
            --success-green: #1cc88a;
            --info-blue: #36b9cc;
            --border-color: #e3e6f0;
            --shadow-color: rgba(58, 59, 69, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-text);
        }

        .header {
            background-color: white;
            padding: 20px;
            box-shadow: 0 4px 6px var(--shadow-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            color: var(--dark-blue);
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary {
            background-color: var(--primary-blue);
            color: white;
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--medium-gray);
        }

        .dashboard {
            padding: 20px;
        }

        .kpi-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .kpi-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px var(--shadow-color);
            border-left: 4px solid var(--primary-blue);
        }

        .kpi-card.payroll {
            border-left-color: var(--primary-blue);
        }

        .kpi-card.employees {
            border-left-color: var(--success-green);
        }

        .kpi-card.new-hires {
            border-left-color: var(--info-blue);
        }

        .kpi-card.turnover {
            border-left-color: #f6c23e;
        }

        .kpi-card h3 {
            font-size: 0.9rem;
            color: var(--medium-gray);
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .kpi-card .value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .kpi-card .change {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
        }

        .positive {
            color: var(--success-green);
        }

        .negative {
            color: #e74a3b;
        }

        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .chart-header h2 {
            font-size: 1.2rem;
            color: var(--dark-blue);
        }

        .chart-actions select {
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            color: var(--medium-gray);
        }

        .chart-placeholder {
            height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: rgba(78, 115, 223, 0.05);
            border-radius: 4px;
        }

        .tables-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .table-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px var(--shadow-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        table th {
            color: var(--medium-gray);
            font-weight: 600;
            font-size: 0.9rem;
        }

        .employee-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            background-color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .employee-info {
            display: flex;
            align-items: center;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(28, 200, 138, 0.2);
            color: var(--success-green);
        }

        .status-new {
            background-color: rgba(54, 185, 204, 0.2);
            color: var(--info-blue);
        }

        @media (max-width: 992px) {

            .charts-row,
            .tables-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>HR Analytics Dashboard</h1>
        <div class="header-actions">
            <button class="btn btn-outline"><i class="fas fa-calendar"></i> <?php echo date('M j, Y'); ?></button>
            <button class="btn btn-primary"><i class="fas fa-download"></i> Generate Report</button>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard">
        <!-- KPI Cards -->
        <div class="kpi-cards">
            <div class="kpi-card payroll">
                <h3>Total Payroll</h3>
                <div class="value">$<?php echo number_format($total_payroll); ?></div>
                <div class="change positive">
                    <i class="fas fa-arrow-up"></i> 4.3% since last month
                </div>
            </div>
            <div class="kpi-card employees">
                <h3>Total Employees</h3>
                <div class="value"><?php echo number_format($total_employees); ?></div>
                <div class="change positive">
                    <i class="fas fa-arrow-up"></i> 2.1% since last month
                </div>
            </div>
            <div class="kpi-card new-hires">
                <h3>New Hires</h3>
                <div class="value"><?php echo $new_hires; ?></div>
                <div class="change positive">
                    <i class="fas fa-arrow-up"></i> 8.7% since last month
                </div>
            </div>
            <div class="kpi-card turnover">
                <h3>Turnover Rate</h3>
                <div class="value"><?php echo number_format($turnover_rate, 1); ?>%</div>
                <div class="change negative">
                    <i class="fas fa-arrow-down"></i> 1.2% since last month
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="charts-row">
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Payroll & Compensation Trend</h2>
                    <div class="chart-actions">
                        <select>
                            <option>Last 6 Months</option>
                            <option>Last 12 Months</option>
                            <option>Year to Date</option>
                        </select>
                    </div>
                </div>
                <div class="chart-placeholder">
                    <canvas id="payrollChart"></canvas>
                </div>
            </div>
            <div class="chart-container">
                <div class="chart-header">
                    <h2>Employee Distribution</h2>
                    <div class="chart-actions">
                        <select>
                            <option>By Department</option>
                            <option>By Location</option>
                            <option>By Level</option>
                        </select>
                    </div>
                </div>
                <div class="chart-placeholder">
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tables Row -->
        <div class="tables-row">
            <div class="table-container">
                <div class="chart-header">
                    <h2>Recent New Hires</h2>
                    <div class="chart-actions">
                        <select>
                            <option>Last 30 Days</option>
                            <option>Last 90 Days</option>
                            <option>Year to Date</option>
                        </select>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Start Date</th>
                            <th>Salary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($new_hires_list_result && $new_hires_list_result->num_rows > 0) {
                            while ($row = $new_hires_list_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="employee-info">
                                            <div class="employee-avatar"><?php echo substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1); ?></div>
                                            <div><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo $row['department']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($row['hire_date'])); ?></td>
                                    <td>$<?php echo number_format($row['salary']); ?></td>
                                    <td><span class="status-badge status-new">New</span></td>
                                </tr>
                        <?php endwhile;
                        } else {
                            echo '<tr><td colspan="5" style="text-align: center;">No new hires in the last 30 days</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="table-container">
                <div class="chart-header">
                    <h2>Department Budget Overview</h2>
                    <div class="chart-actions">
                        <select>
                            <option>Q3 2023</option>
                            <option>Q4 2023</option>
                            <option>2023 Total</option>
                        </select>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Budget</th>
                            <th>Spent</th>
                            <th>Variation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($dept_budget_result && $dept_budget_result->num_rows > 0) {
                            while ($row = $dept_budget_result->fetch_assoc()):
                                $variation = (($row['spent'] - $row['budget']) / $row['budget']) * 100;
                                $variation_class = $variation <= 0 ? 'positive' : 'negative';
                                $variation_symbol = $variation <= 0 ? '' : '+';
                        ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td>$<?php echo number_format($row['budget']); ?></td>
                                    <td>$<?php echo number_format($row['spent']); ?></td>
                                    <td class="<?php echo $variation_class; ?>"><?php echo $variation_symbol . number_format($variation, 1); ?>%</td>
                                </tr>
                        <?php endwhile;
                        } else {
                            echo '<tr><td colspan="4" style="text-align: center;">No department budget data available</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Initialize charts when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Payroll Trend Chart
            const payrollCtx = document.getElementById('payrollChart').getContext('2d');
            if (payrollCtx) {
                new Chart(payrollCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo !empty($payroll_labels) ? json_encode($payroll_labels) : '[]'; ?>,
                        datasets: [{
                            label: 'Total Payroll',
                            data: <?php echo !empty($payroll_data) ? json_encode($payroll_data) : '[]'; ?>,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }

            // Department Distribution Chart
            const departmentCtx = document.getElementById('departmentChart').getContext('2d');
            if (departmentCtx) {
                new Chart(departmentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo !empty($dept_labels) ? json_encode($dept_labels) : '[]'; ?>,
                        datasets: [{
                            data: <?php echo !empty($dept_data) ? json_encode($dept_data) : '[]'; ?>,
                            backgroundColor: [
                                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                            ],
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'right'
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>