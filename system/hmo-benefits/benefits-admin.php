<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "systems";
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee_benefit'])) {
        $employee_id = $_POST['employee_id'];
        $benefit_id = $_POST['benefit_id'];
        $coverage_details = $_POST['coverage_details'];
        $enrollment_date = $_POST['enrollment_date'];

        $stmt = $conn->prepare("INSERT INTO employee_coverage (employee_id, benefit_id, coverage_details, enrollment_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $employee_id, $benefit_id, $coverage_details, $enrollment_date);
        $stmt->execute();
    }

    if (isset($_POST['add_allowance'])) {
        $employee_id = $_POST['employee_id'];
        $allowance_type = $_POST['allowance_type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date_given = $_POST['date_given'];

        $stmt = $conn->prepare("INSERT INTO allowances (employee_id, type, amount, description, date_given) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $employee_id, $allowance_type, $amount, $description, $date_given);
        $stmt->execute();
    }

    if (isset($_POST['add_incentive'])) {
        $employee_id = $_POST['employee_id'];
        $incentive_type = $_POST['incentive_type'];
        $amount = $_POST['amount'];
        $description = $_POST['description'];
        $date_given = $_POST['date_given'];

        $stmt = $conn->prepare("INSERT INTO incentives (employee_id, type, amount, description, date_given) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $employee_id, $incentive_type, $amount, $description, $date_given);
        $stmt->execute();
    }
}

// Fetch data
$employees = $conn->query("SELECT * FROM employees");
$benefits = $conn->query("SELECT * FROM benefits");
$employee_benefits = $conn->query("
    SELECT ec.*, e.first_name, e.last_name, b.employee_id as benefit_name 
    FROM employee_coverage ec 
    JOIN employees e ON ec.employee_id = e.id 
    JOIN benefits b ON ec.benefit_id = b.id
");
$employee_allowances = $conn->query("
    SELECT a.*, e.first_name, e.last_name
    FROM allowances a 
    JOIN employees e ON a.employee_id = e.id
");
$employee_incentives = $conn->query("
    SELECT i.*, e.first_name, e.last_name
    FROM incentives i 
    JOIN employees e ON i.employee_id = e.id
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benefits Administration System</title>
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #34495e;
            --success: #2ecc71;
            --warning: #f39c12;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: var(--primary);
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2,
        h3 {
            margin-bottom: 15px;
            color: var(--dark);
        }

        .tab-container {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            flex-wrap: wrap;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background: #eee;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .tab.active {
            background: white;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            font-weight: bold;
        }

        .tab-content {
            display: none;
            background: white;
            padding: 20px;
            border-radius: 0 5px 5px 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        button {
            background: var(--secondary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: var(--light);
            font-weight: bold;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--secondary);
        }

        .stat-label {
            color: var(--dark);
            font-size: 0.9rem;
        }

        .employee-name {
            font-weight: bold;
            color: var(--dark);
        }

        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Benefits Administration System</h1>
            <p>Manage employee benefits, allowances, and compliance</p>
        </header>

        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-number"><?php echo $employees->num_rows; ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $benefits->num_rows; ?></div>
                <div class="stat-label">Available Benefits</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $employee_benefits->num_rows; ?></div>
                <div class="stat-label">Active Benefit Enrollments</div>
            </div>
        </div>

        <div class="tab-container">
            <div class="tab active" onclick="openTab('health')">Health & Insurance</div>
            <div class="tab" onclick="openTab('travel')">Travel & Allowances</div>
            <div class="tab" onclick="openTab('leave')">Leave Management</div>
            <div class="tab" onclick="openTab('retirement')">Retirement & Savings</div>
            <div class="tab" onclick="openTab('incentives')">Incentives & Rewards</div>
            <div class="tab" onclick="openTab('compliance')">Compliance & Reports</div>
        </div>

        <!-- Health & Insurance Tab -->
        <div id="health" class="tab-content active">
            <h2>Health & Insurance Benefits</h2>

            <div class="card">
                <h3>Enroll Employee in Health Plan</h3>
                <form method="POST">
                    <input type="hidden" name="add_employee_benefit" value="1">
                    <div class="form-group">
                        <label for="employee_id">Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php
                            $employees_result = $conn->query("SELECT * FROM employees");
                            while ($employee = $employees_result->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="benefit_id">Benefit Plan</label>
                        <select name="benefit_id" required>
                            <option value="">Select Benefit</option>
                            <?php
                            $benefits_result = $conn->query("SELECT * FROM benefits WHERE type = 'health'");
                            while ($benefit = $benefits_result->fetch_assoc()): ?>
                                <option value="<?php echo $benefit['id']; ?>"><?php echo $benefit['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="coverage_details">Coverage Details</label>
                        <textarea name="coverage_details" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="enrollment_date">Enrollment Date</label>
                        <input type="date" name="enrollment_date" required>
                    </div>
                    <button type="submit">Enroll Employee</button>
                </form>
            </div>

            <div class="card">
                <h3>Employee Health Benefits</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Benefit</th>
                            <th>Coverage Details</th>
                            <th>Enrollment Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $employee_benefits->fetch_assoc()): ?>
                            <tr>
                                <td><span class="employee-name"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></span></td>
                                <td><?php echo $row['benefit_name']; ?></td>
                                <td><?php echo $row['coverage_details']; ?></td>
                                <td><?php echo $row['enrollment_date']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Travel & Allowances Tab -->
        <div id="travel" class="tab-content">
            <h2>Travel & Allowances</h2>

            <div class="card">
                <h3>Add Allowance</h3>
                <form method="POST">
                    <input type="hidden" name="add_allowance" value="1">
                    <div class="form-group">
                        <label for="employee_id">Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php
                            $employees_result = $conn->query("SELECT * FROM employees");
                            while ($employee = $employees_result->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="allowance_type">Allowance Type</label>
                        <select name="allowance_type" required>
                            <option value="per_diem">Per Diem</option>
                            <option value="fuel">Fuel/Transportation</option>
                            <option value="hazard">Hazard Pay</option>
                            <option value="overtime">Overtime</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="date_given">Date Given</label>
                        <input type="date" name['date_given'] required>
                    </div>
                    <button type="submit">Add Allowance</button>
                </form>
            </div>

            <div class="card">
                <h3>Employee Allowances</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date Given</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $employee_allowances->fetch_assoc()): ?>
                            <tr>
                                <td><span class="employee-name"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></span></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $row['type'])); ?></td>
                                <td><?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td><?php echo $row['date_given']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Incentives & Rewards Tab -->
        <div id="incentives" class="tab-content">
            <h2>Incentives & Rewards</h2>

            <div class="card">
                <h3>Add Incentive</h3>
                <form method="POST">
                    <input type="hidden" name="add_incentive" value="1">
                    <div class="form-group">
                        <label for="employee_id">Employee</label>
                        <select name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php
                            $employees_result = $conn->query("SELECT * FROM employees");
                            while ($employee = $employees_result->fetch_assoc()): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="incentive_type">Incentive Type</label>
                        <select name="incentive_type" required>
                            <option value="performance">Performance Bonus</option>
                            <option value="referral">Referral Bonus</option>
                            <option value="profit_sharing">Profit Sharing</option>
                            <option value="recognition">Recognition Award</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input type="number" name="amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="date_given">Date Given</label>
                        <input type="date" name="date_given" required>
                    </div>
                    <button type="submit">Add Incentive</button>
                </form>
            </div>

            <div class="card">
                <h3>Employee Incentives</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Date Given</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $employee_incentives->fetch_assoc()): ?>
                            <tr>
                                <td><span class="employee-name"><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></span></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $row['type'])); ?></td>
                                <td><?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td><?php echo $row['date_given']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Other tabs would be implemented similarly -->
        <div id="leave" class="tab-content">
            <h2>Leave & Time-off Management</h2>
            <div class="card">
                <h3>Manage Employee Leave</h3>
                <p>This section would integrate with your attendance and time_attendance tables to manage leave policies and tracking.</p>
                <p>Features would include:</p>
                <ul>
                    <li>Paid time off (sick leave, vacation leave) tracking</li>
                    <li>Emergency leave policies for on-duty staff</li>
                    <li>Special leave (maternity/paternity, bereavement) management</li>
                    <li>Leave credits tracking & carry-over rules</li>
                </ul>
            </div>
        </div>

        <div id="retirement" class="tab-content">
            <h2>Retirement & Savings Plans</h2>
            <div class="card">
                <h3>Retirement Benefits Management</h3>
                <p>This section would manage government contributions (SSS, Pag-IBIG, PhilHealth) and retirement plans.</p>
                <p>Features would include:</p>
                <ul>
                    <li>Government contributions tracking</li>
                    <li>401k/Pension management</li>
                    <li>Company retirement plans for long-term employees</li>
                    <li>Contribution history and statements</li>
                </ul>
            </div>
        </div>

        <div id="compliance" class="tab-content">
            <h2>Compliance & Documentation</h2>
            <div class="card">
                <h3>Compliance Management</h3>
                <p>This section would generate reports and ensure compliance with benefit regulations.</p>
                <p>Features would include:</p>
                <ul>
                    <li>Digital records of benefits per employee</li>
                    <li>Benefits eligibility rules management</li>
                    <li>Integration with Payroll & Compensation</li>
                    <li>Reporting for HR & Finance</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }

            // Remove active class from all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }

            // Show the selected tab content and mark tab as active
            document.getElementById(tabName).classList.add("active");
            event.currentTarget.classList.add("active");
        }
    </script>
</body>

</html>
<?php
$conn->close();
?>