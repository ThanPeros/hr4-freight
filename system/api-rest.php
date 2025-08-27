<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee API Integration</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        h1,
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        input[type="password"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        button:hover {
            background-color: #2980b9;
        }

        .submit-btn {
            background-color: #2ecc71;
        }

        .submit-btn:hover {
            background-color: #27ae60;
        }

        .delete-btn {
            background-color: #e74c3c;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .employee-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f9f9f9;
        }

        .endpoint {
            background-color: #2c3e50;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-family: monospace;
        }

        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin-bottom: 20px;
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 14px;
        }

        .tab {
            display: inline-block;
            padding: 10px 20px;
            cursor: pointer;
            background-color: #eee;
            border-radius: 4px 4px 0 0;
            margin-right: 5px;
        }

        .tab.active {
            background-color: #3498db;
            color: white;
        }

        .tab-content {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>

<body>
    <h1>Employee REST API Integration Example</h1>

    <div class="container">
        <div>
            <div class="section">
                <h2>API Testing Interface</h2>

                <div class="tabs">
                    <div class="tab active" onclick="switchTab('createTab')">Create Employee</div>
                    <div class="tab" onclick="switchTab('readTab')">Get Employees</div>
                    <div class="tab" onclick="switchTab('updateTab')">Update Employee</div>
                    <div class="tab" onclick="switchTab('deleteTab')">Delete Employee</div>
                </div>

                <div id="createTab" class="tab-content active">
                    <h3>Create New Employee</h3>
                    <form id="createForm">
                        <div class="form-group">
                            <label for="first_name">First Name:</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name:</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone:</label>
                            <input type="tel" id="phone" name="phone">
                        </div>

                        <div class="form-group">
                            <label for="hire_date">Hire Date:</label>
                            <input type="date" id="hire_date" name="hire_date" required>
                        </div>

                        <button type="submit" class="submit-btn">Create Employee</button>
                    </form>
                </div>

                <div id="readTab" class="tab-content">
                    <h3>Get All Employees</h3>
                    <button onclick="getEmployees()">Fetch Employees</button>
                    <div id="employeesList"></div>
                </div>

                <div id="updateTab" class="tab-content">
                    <h3>Update Employee</h3>
                    <div class="form-group">
                        <label for="employee_id">Employee ID:</label>
                        <input type="text" id="employee_id" name="employee_id" placeholder="Enter employee ID">
                    </div>
                    <button onclick="getEmployee()">Get Employee Details</button>

                    <form id="updateForm" style="display: none; margin-top: 20px;">
                        <div class="form-group">
                            <label for="update_first_name">First Name:</label>
                            <input type="text" id="update_first_name" name="first_name" required>
                        </div>

                        <div class="form-group">
                            <label for="update_last_name">Last Name:</label>
                            <input type="text" id="update_last_name" name="last_name" required>
                        </div>

                        <div class="form-group">
                            <label for="update_email">Email:</label>
                            <input type="email" id="update_email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label for="update_phone">Phone:</label>
                            <input type="tel" id="update_phone" name="phone">
                        </div>

                        <button type="submit" class="submit-btn">Update Employee</button>
                    </form>
                </div>

                <div id="deleteTab" class="tab-content">
                    <h3>Delete Employee</h3>
                    <div class="form-group">
                        <label for="delete_id">Employee ID:</label>
                        <input type="text" id="delete_id" name="delete_id" placeholder="Enter employee ID">
                    </div>
                    <button onclick="deleteEmployee()" class="delete-btn">Delete Employee</button>
                </div>

                <div id="apiResponse" class="message" style="display: none;"></div>
            </div>
        </div>

        <div>
            <div class="section">
                <h2>REST API Documentation</h2>

                <h3>API Endpoints</h3>

                <div class="endpoint">POST /api/employees</div>
                <p>Create a new employee</p>
                <pre><code>{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john.doe@example.com",
  "phone": "123-456-7890",
  "hire_date": "2023-01-15"
}</code></pre>

                <div class="endpoint">GET /api/employees</div>
                <p>Retrieve all employees</p>

                <div class="endpoint">GET /api/employees/{id}</div>
                <p>Retrieve a specific employee by ID</p>

                <div class="endpoint">PUT /api/employees/{id}</div>
                <p>Update an existing employee</p>
                <pre><code>{
  "first_name": "John",
  "last_name": "Smith",
  "email": "john.smith@example.com",
  "phone": "098-765-4321"
}</code></pre>

                <div class="endpoint">DELETE /api/employees/{id}</div>
                <p>Delete an employee</p>

                <h3>Example API Implementation (PHP)</h3>
                <pre><code>// api/employees.php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "systems";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        if (isset($request[1])) {
            // Get specific employee
            $id = $request[1];
            $result = $conn->query("SELECT * FROM employees WHERE id = $id");
        } else {
            // Get all employees
            $result = $conn->query("SELECT * FROM employees");
        }
        
        $employees = [];
        while($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        echo json_encode($employees);
        break;
        
    case 'POST':
        // Create new employee
        $first_name = $input['first_name'];
        $last_name = $input['last_name'];
        $email = $input['email'];
        $phone = $input['phone'];
        $hire_date = $input['hire_date'];
        
        $stmt = $conn->prepare("INSERT INTO employees (first_name, last_name, email, phone, hire_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $hire_date);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Employee created successfully", "id" => $stmt->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error creating employee: " . $stmt->error]);
        }
        break;
        
    case 'PUT':
        // Update employee
        $id = $request[1];
        $first_name = $input['first_name'];
        $last_name = $input['last_name'];
        $email = $input['email'];
        $phone = $input['phone'];
        
        $stmt = $conn->prepare("UPDATE employees SET first_name=?, last_name=?, email=?, phone=? WHERE id=?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $phone, $id);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Employee updated successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error updating employee: " . $stmt->error]);
        }
        break;
        
    case 'DELETE':
        // Delete employee
        $id = $request[1];
        $stmt = $conn->prepare("DELETE FROM employees WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(["message" => "Employee deleted successfully"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Error deleting employee: " . $stmt->error]);
        }
        break;
}

$conn->close();
</code></pre>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        function switchTab(tabId) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabId).classList.add('active');

            // Update tab styles
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.currentTarget.classList.add('active');

            // Clear response message
            document.getElementById('apiResponse').style.display = 'none';
        }

        // Handle create form submission
        document.getElementById('createForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = {
                first_name: document.getElementById('first_name').value,
                last_name: document.getElementById('last_name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                hire_date: document.getElementById('hire_date').value
            };

            fetch('https://jsonplaceholder.typicode.com/users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    showMessage('Employee created successfully! ID: ' + data.id, 'success');
                    document.getElementById('createForm').reset();
                })
                .catch(error => {
                    showMessage('Error creating employee: ' + error, 'error');
                });
        });

        // Get all employees
        function getEmployees() {
            fetch('https://jsonplaceholder.typicode.com/users')
                .then(response => response.json())
                .then(data => {
                    const employeesList = document.getElementById('employeesList');
                    employeesList.innerHTML = '';

                    if (data.length === 0) {
                        employeesList.innerHTML = '<p>No employees found.</p>';
                        return;
                    }

                    data.forEach(employee => {
                        const employeeCard = document.createElement('div');
                        employeeCard.className = 'employee-card';
                        employeeCard.innerHTML = `
                        <strong>${employee.name}</strong> (ID: ${employee.id})<br>
                        Email: ${employee.email}<br>
                        Phone: ${employee.phone}
                    `;
                        employeesList.appendChild(employeeCard);
                    });
                })
                .catch(error => {
                    showMessage('Error fetching employees: ' + error, 'error');
                });
        }

        // Get specific employee for update
        function getEmployee() {
            const employeeId = document.getElementById('employee_id').value;

            if (!employeeId) {
                showMessage('Please enter an employee ID', 'error');
                return;
            }

            fetch(`https://jsonplaceholder.typicode.com/users/${employeeId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Employee not found');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('update_first_name').value = data.name.split(' ')[0];
                    document.getElementById('update_last_name').value = data.name.split(' ')[1];
                    document.getElementById('update_email').value = data.email;
                    document.getElementById('update_phone').value = data.phone;
                    document.getElementById('updateForm').style.display = 'block';
                })
                .catch(error => {
                    showMessage('Error fetching employee: ' + error, 'error');
                });
        }

        // Handle update form submission
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const employeeId = document.getElementById('employee_id').value;

            const formData = {
                first_name: document.getElementById('update_first_name').value,
                last_name: document.getElementById('update_last_name').value,
                email: document.getElementById('update_email').value,
                phone: document.getElementById('update_phone').value
            };

            fetch(`https://jsonplaceholder.typicode.com/users/${employeeId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    showMessage('Employee updated successfully!', 'success');
                })
                .catch(error => {
                    showMessage('Error updating employee: ' + error, 'error');
                });
        });

        // Delete employee
        function deleteEmployee() {
            const employeeId = document.getElementById('delete_id').value;

            if (!employeeId) {
                showMessage('Please enter an employee ID', 'error');
                return;
            }

            if (!confirm('Are you sure you want to delete this employee?')) {
                return;
            }

            fetch(`https://jsonplaceholder.typicode.com/users/${employeeId}`, {
                    method: 'DELETE'
                })
                .then(response => {
                    if (response.ok) {
                        showMessage('Employee deleted successfully!', 'success');
                        document.getElementById('delete_id').value = '';
                    } else {
                        throw new Error('Failed to delete employee');
                    }
                })
                .catch(error => {
                    showMessage('Error deleting employee: ' + error, 'error');
                });
        }

        // Show API response message
        function showMessage(message, type) {
            const responseDiv = document.getElementById('apiResponse');
            responseDiv.textContent = message;
            responseDiv.className = `message ${type}`;
            responseDiv.style.display = 'block';

            // Scroll to message
            responseDiv.scrollIntoView({
                behavior: 'smooth'
            });
        }
    </script>
</body>

</html>