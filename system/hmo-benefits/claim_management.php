<?php
include '../sidebar.php';
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "claim_management";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    $conn->select_db($dbname);

    // Create claims table
    $sql = "CREATE TABLE IF NOT EXISTS claims (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(30) NOT NULL,
        employee_name VARCHAR(100) NOT NULL,
        claim_type VARCHAR(50) NOT NULL,
        provider VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        treatment_date DATE NOT NULL,
        diagnosis_code VARCHAR(20),
        description TEXT,
        status VARCHAR(20) DEFAULT 'Submitted',
        documents VARCHAR(255),
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        validated_at TIMESTAMP NULL,
        validated_by VARCHAR(100) NULL
    )";

    if ($conn->query($sql) !== TRUE) {
        echo "Error creating table: " . $conn->error;
    }

    // Create providers table
    $sql = "CREATE TABLE IF NOT EXISTS providers (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        contact_email VARCHAR(100),
        contact_phone VARCHAR(20),
        is_active BOOLEAN DEFAULT TRUE
    )";

    if ($conn->query($sql) !== TRUE) {
        echo "Error creating table: " . $conn->error;
    }

    // Insert sample providers if table is empty
    $result = $conn->query("SELECT COUNT(*) as count FROM providers");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $conn->query("INSERT INTO providers (name, category, contact_email, contact_phone, is_active) VALUES 
            ('MedHealth HMO', 'Health Insurance', 'info@medhealth.com', '800-123-4567', TRUE),
            ('DentalCare Plus', 'Dental Insurance', 'support@dentalcare.com', '800-987-6543', TRUE),
            ('VisionOne', 'Vision Insurance', 'help@visionone.com', '800-555-1234', FALSE),
            ('Wellness Solutions', 'Wellness Program', 'admin@wellness.com', '800-222-9876', TRUE)");
    }
} else {
    echo "Error creating database: " . $conn->error;
}

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['submit_claim'])) {
        // Claim submission handling
        $employee_id = $_POST['employee_id'];
        $employee_name = $_POST['employee_name'];
        $claim_type = $_POST['claim_type'];
        $provider = $_POST['provider'];
        $amount = $_POST['amount'];
        $treatment_date = $_POST['treatment_date'];
        $diagnosis_code = $_POST['diagnosis_code'];
        $description = $_POST['description'];

        // Basic validation
        $valid = true;
        if (
            empty($employee_id) || empty($employee_name) || empty($claim_type) ||
            empty($provider) || empty($amount) || empty($treatment_date)
        ) {
            $valid = false;
            $error_message = "Please fill in all required fields.";
        }

        if ($valid) {
            // Handle file upload
            $target_dir = "uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $documents = "";
            if (!empty($_FILES["documents"]["name"])) {
                $target_file = $target_dir . time() . "_" . basename($_FILES["documents"]["name"]);
                if (move_uploaded_file($_FILES["documents"]["tmp_name"], $target_file)) {
                    $documents = $target_file;
                }
            }

            // Insert into database
            $stmt = $conn->prepare("INSERT INTO claims (employee_id, employee_name, claim_type, provider, amount, treatment_date, diagnosis_code, description, documents) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssdssss", $employee_id, $employee_name, $claim_type, $provider, $amount, $treatment_date, $diagnosis_code, $description, $documents);

            if ($stmt->execute()) {
                $success_message = "Claim submitted successfully!";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['validate_claim'])) {
        // Claim validation handling
        $claim_id = $_POST['claim_id'];
        $action = $_POST['validation_action'];
        $validated_by = "Admin User"; // In a real system, this would be the logged-in user

        if ($action == 'approve') {
            $stmt = $conn->prepare("UPDATE claims SET status = 'Approved', validated_at = NOW(), validated_by = ? WHERE id = ?");
            $stmt->bind_param("si", $validated_by, $claim_id);
            if ($stmt->execute()) {
                $success_message = "Claim #$claim_id has been approved.";
            } else {
                $error_message = "Error updating claim: " . $stmt->error;
            }
        } elseif ($action == 'reject') {
            $stmt = $conn->prepare("UPDATE claims SET status = 'Rejected', validated_at = NOW(), validated_by = ? WHERE id = ?");
            $stmt->bind_param("si", $validated_by, $claim_id);
            if ($stmt->execute()) {
                $success_message = "Claim #$claim_id has been rejected.";
            } else {
                $error_message = "Error updating claim: " . $stmt->error;
            }
        }
        $stmt->close();
    }
}

// Fetch claims for display
$claims = [];
$result = $conn->query("SELECT * FROM claims ORDER BY submitted_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $claims[] = $row;
    }
}

// Fetch providers for dropdown (only active ones)
$providers = [];
$result = $conn->query("SELECT * FROM providers WHERE is_active = TRUE");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Management System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: linear-gradient(135deg, #0066cc, #004799);
            color: white;
            padding: 20px 0;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        header p {
            text-align: center;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        }

        .card h2 {
            color: #0066cc;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px 15px;
        }

        .form-group {
            flex: 1 0 calc(50% - 20px);
            margin: 0 10px 15px;
            min-width: 250px;
        }

        .form-group.full-width {
            flex: 1 0 calc(100% - 20px);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #0066cc;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
        }

        button {
            background: #0066cc;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
        }

        button:hover {
            background: #0052a3;
        }

        .btn-approve {
            background: #0c9d61;
        }

        .btn-approve:hover {
            background: #0a7a4a;
        }

        .btn-reject {
            background: #f44336;
        }

        .btn-reject:hover {
            background: #d32f2f;
        }

        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            background-color: #f8f9fa;
            font-weight: 600;
            color: #444;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status.submitted {
            background-color: #e8f4ff;
            color: #0066cc;
        }

        .status.approved {
            background-color: #e6f7ee;
            color: #0c9d61;
        }

        .status.rejected {
            background-color: #feeaea;
            color: #f44336;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .tab {
            padding: 12px 24px;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            color: #0066cc;
            border-bottom: 3px solid #0066cc;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .validation-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .form-group {
                flex: 1 0 calc(100% - 20px);
            }

            .container {
                padding: 15px;
            }

            .validation-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header>
            <h1>Claim Management System</h1>
            <p>Submit, track, and validate employee HMO/benefit claims</p>
        </header>

        <div class="tabs">
            <div class="tab active" onclick="switchTab('submit')">Submit Claim</div>
            <div class="tab" onclick="switchTab('track')">Track Claims</div>
            <div class="tab" onclick="switchTab('validate')">Validate Claims</div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div id="submit" class="tab-content active">
            <div class="card">
                <h2>Claim Intake Form</h2>
                <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="employee_id">Employee ID *</label>
                            <input type="text" id="employee_id" name="employee_id" required>
                        </div>
                        <div class="form-group">
                            <label for="employee_name">Employee Name *</label>
                            <input type="text" id="employee_name" name="employee_name" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="claim_type">Claim Type *</label>
                            <select id="claim_type" name="claim_type" required>
                                <option value="">Select Claim Type</option>
                                <option value="Medical">Medical</option>
                                <option value="Dental">Dental</option>
                                <option value="Vision">Vision</option>
                                <option value="Prescription">Prescription</option>
                                <option value="Wellness">Wellness</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="provider">Provider *</label>
                            <select id="provider" name="provider" required>
                                <option value="">Select Provider</option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?php echo $provider['name']; ?>"><?php echo $provider['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="amount">Amount ($) *</label>
                            <input type="number" id="amount" name="amount" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="treatment_date">Treatment Date *</label>
                            <input type="date" id="treatment_date" name="treatment_date" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="diagnosis_code">Diagnosis Code</label>
                            <input type="text" id="diagnosis_code" name="diagnosis_code" placeholder="e.g., ICD-10 code">
                        </div>
                        <div class="form-group">
                            <label for="documents">Supporting Documents</label>
                            <input type="file" id="documents" name="documents">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" placeholder="Please describe the treatment or service received"></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <button type="submit" name="submit_claim">Submit Claim</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="track" class="tab-content">
            <div class="card">
                <h2>Claim Status Tracking</h2>
                <?php if (count($claims) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($claims as $claim): ?>
                                <tr>
                                    <td>#<?php echo $claim['id']; ?></td>
                                    <td><?php echo $claim['employee_name']; ?></td>
                                    <td><?php echo $claim['claim_type']; ?></td>
                                    <td><?php echo $claim['provider']; ?></td>
                                    <td>$<?php echo number_format($claim['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($claim['treatment_date'])); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($claim['status']); ?>">
                                            <?php echo $claim['status']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No claims found. Submit a claim to see it here.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="validate" class="tab-content">
            <div class="card">
                <h2>Claim Validation</h2>
                <p>Validate submitted claims by approving or rejecting them.</p>

                <?php if (count($claims) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Claim ID</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Provider</th>
                                <th>Amount</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($claims as $claim): ?>
                                <tr>
                                    <td>#<?php echo $claim['id']; ?></td>
                                    <td><?php echo $claim['employee_name']; ?></td>
                                    <td><?php echo $claim['claim_type']; ?></td>
                                    <td><?php echo $claim['provider']; ?></td>
                                    <td>$<?php echo number_format($claim['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($claim['submitted_at'])); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($claim['status']); ?>">
                                            <?php echo $claim['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($claim['status'] == 'Submitted'): ?>
                                            <form method="POST">
                                                <input type="hidden" name="claim_id" value="<?php echo $claim['id']; ?>">
                                                <div class="validation-actions">
                                                    <button type="submit" name="validate_claim" value="approve" class="btn-approve">Approve</button>
                                                    <button type="submit" name="validate_claim" value="reject" class="btn-reject">Reject</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <em>Processed</em>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No claims to validate.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }

            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Update active tab
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
                if (tabs[i].textContent.toLowerCase().includes(tabName.toLowerCase())) {
                    tabs[i].classList.add('active');
                }
            }
        }

        function validateForm() {
            const employeeId = document.getElementById('employee_id').value;
            const employeeName = document.getElementById('employee_name').value;
            const claimType = document.getElementById('claim_type').value;
            const provider = document.getElementById('provider').value;
            const amount = document.getElementById('amount').value;
            const treatmentDate = document.getElementById('treatment_date').value;

            if (!employeeId || !employeeName || !claimType || !provider || !amount || !treatmentDate) {
                alert('Please fill in all required fields.');
                return false;
            }

            if (amount <= 0) {
                alert('Please enter a valid amount greater than zero.');
                return false;
            }

            const today = new Date().toISOString().split('T')[0];
            if (treatmentDate > today) {
                alert('Treatment date cannot be in the future.');
                return false;
            }

            return true;
        }

        // Set maximum date to today for treatment date
        document.getElementById('treatment_date').max = new Date().toISOString().split('T')[0];
    </script>
</body>

</html>