<?php
// Include database config
include_once '../../Database/db_config.php';

$errorMessages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $employeeNumber = $_POST['employeeNumber'];
    $position = $_POST['position'];
    $employeeStatus = $_POST['employeeStatus'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $dateJoined = $_POST['joinDate'];
    
    // The HTML5 date input returns Y-m-d format which MySQL DATE can use directly
    if (empty($dateJoined)) {
        $dateJoined = null;
    }

    // Validate employee number: must start with 09 and be 12 digits
    if (!preg_match('/^09\\d{10}$/', $employeeNumber)) {
        $errorMessages[] = 'Employee number must start with 09 and be 12 digits.';
    }
    // Validate password: at least 6 characters
    if (strlen($password) < 6) {
        $errorMessages[] = 'Password must be at least 6 characters.';
    }

    if (empty($errorMessages)) {
        // Use PASSWORD_DEFAULT for bcrypt, same as login.php
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO Employee (FirstName, LastName, EmployeePosition, EmployeeNumber, EmployeeStatus, Username, Password, DateJoined) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // For debugging - Check all values that will be inserted
            error_log("Inserting Employee with values: FN=$firstName, LN=$lastName, POS=$position, NUM=$employeeNumber, STAT=$employeeStatus, USER=$username, DATE=" . ($dateJoined ?? 'NULL'));
            
            $stmt->bind_param('ssssssss', $firstName, $lastName, $position, $employeeNumber, $employeeStatus, $username, $passwordHash, $dateJoined);            
            if ($stmt->execute()) {
                echo "<script>alert('Employee added successfully.'); window.location.href=window.location.href;</script>";
                exit();
            } else {
                $errorMessages[] = "Database error: " . $stmt->error . " (MySQL error code: " . $stmt->errno . ")";
                // Log the error
                error_log("MySQL error in employee.php: " . $stmt->error . " (Code: " . $stmt->errno . ")");
            }
            $stmt->close();
        } else {
            $errorMessages[] = "Database error: " . $conn->error;
        }
    }
    if (!empty($errorMessages)) {
        $alertMsg = implode("\n", $errorMessages);
        echo "<script>alert('" . addslashes($alertMsg) . "');</script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">    <title>Employees - RJane Water Refilling Station</title>
    <link rel="stylesheet" href="../../Css/employees.css">
    <link rel="stylesheet" href="../../Css/modal.css">
    <link rel="stylesheet" href="../../Css/password-toggle.css">
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
        <!-- Header Section -->
        <div class="employees-header">
            <h2>Employee Management</h2>
            <button class="add-employee-btn">
                <i class="fas fa-plus"></i> Add Employee
            </button>
        </div>

        <!-- Employee Statistics -->
        <div class="employee-stats">
            <div class="stat-card">
                <div class="stat-icon total-employees">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Employees</h3>
                    <p>12</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Today</h3>
                    <p>9</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon delivery-staff">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="stat-info">
                    <h3>Delivery Staff</h3>
                    <p>5</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon production">
                    <i class="fas fa-industry"></i>
                </div>
                <div class="stat-info">
                    <h3>Production Staff</h3>
                    <p>4</p>
                </div>
            </div>
        </div>

        <!-- Employee Tabs -->
        <div class="employee-tabs">
            <button class="tab-btn active" data-tab="all">All Employees</button>
            <button class="tab-btn" data-tab="drivers">Drivers</button>
            <button class="tab-btn" data-tab="Cashier">Cashier</button>
        </div>

        <!-- Employee Search and Filter -->
        <div class="employee-controls">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search employees...">
            </div>
            <div class="filters">
                <select class="status-filter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <!-- Employee List -->
        <div class="employee-list tab-content active" id="all">
            <div class="employee-grid">
                <!-- Employee Card -->
                <div class="employee-card">
                    <div class="employee-header">
                        <div class="employee-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="status-badge active">Active</span>
                    </div>
                    <div class="employee-details">
                        <h4>Juan Dela Cruz</h4>
                        <p class="employee-position">Senior Driver</p>
                        <div class="detail-item">
                            <i class="fas fa-phone"></i>
                            <span>+63 917 123 4567</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-envelope"></i>
                            <span>juan.delacruz@email.com</span>
                        </div>
                    </div>
                    <div class="employee-actions">
                        <button class="view-btn" title="View Details"><i class="fas fa-eye"></i></button>
                        <button class="delete-btn" title="Remove Employee"><i class="fas fa-trash"></i></button>
                    </div>
                </div>



                <!-- Driver Tab Content -->
                <div class="employee-list tab-content" id="drivers">
                    <div class="employee-grid">
                        <!-- Only driver cards would appear here -->
                        <div class="employee-card">
                            <div class="employee-header">
                                <div class="employee-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="status-badge active">Active</span>
                            </div>
                            <div class="employee-details">
                                <h4>Juan Dela Cruz</h4>
                                <p class="employee-position">Senior Driver</p>
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span>+63 917 123 4567</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-truck"></i>
                                    <span>Vehicle: Truck #001</span>
                                </div>
                            </div>
                            <div class="employee-actions">
                                <button class="view-btn" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="edit-btn" title="Edit Employee"><i class="fas fa-edit"></i></button>
                                <button class="schedule-btn" title="View Schedule"><i
                                        class="fas fa-calendar"></i></button>
                            </div>
                        </div>

                        <div class="employee-card">
                            <div class="employee-header">
                                <div class="employee-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span class="status-badge inactive">Inactive</span>
                            </div>
                            <div class="employee-details">
                                <h4>Robert Lee</h4>
                                <p class="employee-position">Driver</p>
                                <div class="detail-item">
                                    <i class="fas fa-phone"></i>
                                    <span>+63 919 876 5432</span>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-truck"></i>
                                    <span>Vehicle: Truck #002</span>
                                </div>
                            </div>
                            <div class="employee-actions">
                                <button class="view-btn" title="View Details"><i class="fas fa-eye"></i></button>
                                <button class="edit-btn" title="Edit Employee"><i class="fas fa-edit"></i></button>
                                <button class="schedule-btn" title="View Schedule"><i
                                        class="fas fa-calendar"></i></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Tab Content -->
                <div class="employee-list tab-content" id="attendance">
                    <div class="attendance-header">
                        <h3>Today's Attendance</h3>
                        <div class="date-picker">
                            <input type="date" id="attendanceDate">
                        </div>
                    </div>
                    <div class="attendance-table-container">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Position</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="employee-name">
                                            <div class="mini-avatar"><i class="fas fa-user"></i></div>
                                            <span>Juan Dela Cruz</span>
                                        </div>
                                    </td>
                                    <td>Senior Driver</td>
                                    <td>08:00 AM</td>
                                    <td>05:30 PM</td>
                                    <td>9.5</td>
                                    <td><span class="status-badge active">Present</span></td>
                                    <td>
                                        <button class="edit-btn" title="Edit Record"><i
                                                class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="employee-name">
                                            <div class="mini-avatar"><i class="fas fa-user"></i></div>
                                            <span>Maria Santos</span>
                                        </div>
                                    </td>
                                    <td>Production Supervisor</td>
                                    <td>07:45 AM</td>
                                    <td>05:00 PM</td>
                                    <td>9.25</td>
                                    <td><span class="status-badge active">Present</span></td>
                                    <td>
                                        <button class="edit-btn" title="Edit Record"><i
                                                class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="employee-name">
                                            <div class="mini-avatar"><i class="fas fa-user"></i></div>
                                            <span>Robert Lee</span>
                                        </div>
                                    </td>
                                    <td>Driver</td>
                                    <td>--</td>
                                    <td>--</td>
                                    <td>0</td>
                                    <td><span class="status-badge inactive">Absent</span></td>
                                    <td>
                                        <button class="edit-btn" title="Edit Record"><i
                                                class="fas fa-edit"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Employee Modal -->
            <div class="modal" id="addEmployeeModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-user-plus"></i> Add New Employee</h3>
                        <button class="close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($errorMessages)): ?>
                            <div class="error-messages" style="color: red; margin-bottom: 10px;">
                                <ul>
                                    <?php foreach ($errorMessages as $msg): ?>
                                        <li><?php echo htmlspecialchars($msg); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <form id="employeeForm" method="POST">
                            <div class="form-section">
                                <h4><i class="fas fa-user-circle"></i> Personal Information</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="firstName">First Name</label>
                                        <input type="text" id="firstName" name="firstName" placeholder="Enter first name" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="lastName">Last Name</label>
                                        <input type="text" id="lastName" name="lastName" placeholder="Enter last name" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="employeeNumber">Employee Number</label>
                                        <input type="tel" id="employeeNumber" name="employeeNumber" placeholder="09XXXXXXXXXX" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="address">Address</label>
                                    <textarea id="address" name="address" placeholder="Enter complete address"></textarea>
                                </div>
                            </div>

                            <div class="form-section">
                                <h4><i class="fas fa-briefcase"></i> Employment Details</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="position">Position</label>
                                        <select id="position" name="position" required>
                                            <option value="">Select Position</option>
                                            <option value="driver">Driver</option>
                                            <option value="senior_driver">Cashier</option>
                                        </select>
                                    </div>
                                </div>                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="joinDate">Date Joined</label>
                                        <input type="date" id="joinDate" name="joinDate" required>
                                        <!-- Using type="date" input which provides consistent YYYY-MM-DD format -->
                                    </div>
                                    <div class="form-group">
                                        <label for="employeeStatus">Status</label>
                                        <select id="employeeStatus" name="employeeStatus" required>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="probation">On Probation</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <h4><i class="fas fa-lock"></i> Account</h4>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" placeholder="Choose a username">
                                    </div>                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <div class="password-field" style="position:relative;">
                                            <input type="password" id="password" name="password" placeholder="••••••••" style="padding-right:35px; width:100%;">
                                            <button type="button" id="togglePassword" style="position:absolute; right:5px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer;">
                                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
                                <button type="submit" name="add_employee" class="submit-btn"><i class="fas fa-save"></i> Save
                                    Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- View Employee Modal -->
            <div class="modal" id="viewEmployeeModal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Employee Details</h3>
                        <button class="close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="employee-profile">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="profile-info">
                                    <h2>Juan Dela Cruz</h2>
                                    <p>Senior Driver</p>
                                    <span class="status-badge active">Active</span>
                                </div>
                            </div>

                            <div class="profile-section">
                                <h4>Personal Information</h4>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">Contact Number</span>
                                        <span class="value">+63 917 123 4567</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Email</span>
                                        <span class="value">juan.delacruz@email.com</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Address</span>
                                        <span class="value">123 Main St, Manila</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Date of Birth</span>
                                        <span class="value">June 12, 1985</span>
                                    </div>
                                </div>
                            </div>

                            <div class="profile-section">
                                <h4>Employment Details</h4>
                                <div class="detail-grid">
                                    <div class="detail-item">
                                        <span class="label">Employee ID</span>
                                        <span class="value">EMP001</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Department</span>
                                        <span class="value">Delivery</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Date Joined</span>
                                        <span class="value">January 15, 2020</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="label">Years of Service</span>
                                        <span class="value">3.5 years</span>
                                    </div>
                                </div>
                            </div>




                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="edit-profile-btn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>


            <!-- JavaScript for functionality -->
            <script>
                // DOM Elements
                const addEmployeeBtn = document.querySelector('.add-employee-btn');
                const addEmployeeModal = document.getElementById('addEmployeeModal');
                const viewEmployeeModal = document.getElementById('viewEmployeeModal');
                const closeButtons = document.querySelectorAll('.close-btn');
                const cancelButtons = document.querySelectorAll('.cancel-btn');
                const viewButtons = document.querySelectorAll('.view-btn');
                const editButtons = document.querySelectorAll('.edit-btn');
                const deleteButtons = document.querySelectorAll('.delete-btn');
                const tabButtons = document.querySelectorAll('.tab-btn');
                const tabContents = document.querySelectorAll('.tab-content');
                const positionSelect = document.getElementById('position');
                const driverDetails = document.getElementById('driverDetails');

                // Open Add Employee Modal
                addEmployeeBtn.addEventListener('click', function () {
                    addEmployeeModal.classList.add('show');
                });

                // Open View Employee Modal
                viewButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        viewEmployeeModal.classList.add('show');
                    });
                });

                // Close Modals
                closeButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        addEmployeeModal.classList.remove('show');
                        viewEmployeeModal.classList.remove('show');
                    });
                });

                cancelButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        addEmployeeModal.classList.remove('show');
                        viewEmployeeModal.classList.remove('show');
                    });
                });

                // Close on outside click
                window.addEventListener('click', function (e) {
                    if (e.target === addEmployeeModal) {
                        addEmployeeModal.classList.remove('show');
                    }
                    if (e.target === viewEmployeeModal) {
                        viewEmployeeModal.classList.remove('show');
                    }
                });

                // Tab functionality
                tabButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        // Remove active class from all buttons
                        tabButtons.forEach(btn => btn.classList.remove('active'));

                        // Add active class to clicked button
                        this.classList.add('active');

                        // Get the tab to show
                        const tabId = this.getAttribute('data-tab');

                        // Hide all tab contents
                        tabContents.forEach(content => content.classList.remove('active'));

                        // Show the selected tab content
                        document.getElementById(tabId).classList.add('active');
                    });
                });

                // Show/hide driver details based on position selection
                positionSelect.addEventListener('change', function () {
                    // Only show driver details if position is exactly 'driver'
                    if (this.value === 'driver') {
                        driverDetails.style.display = 'block';
                    } else {
                        driverDetails.style.display = 'none';
                    }
                });

                // Delete employee confirmation
                deleteButtons.forEach(button => {
                    button.addEventListener('click', function () {
                        if (confirm('Are you sure you want to remove this employee?')) {
                            // Get the employee card and remove it
                            const employeeCard = this.closest('.employee-card');
                            employeeCard.remove();

                            // Update employee count
                            const totalEmployeesCount = document.querySelector('.stat-card:first-child .stat-info p');
                            totalEmployeesCount.textContent = parseInt(totalEmployeesCount.textContent) - 1;

                            // Show notification
                            showNotification('Employee removed successfully');
                        }
                    });                });                // Toggle password visibility
                if (document.getElementById('togglePassword')) {
                    document.getElementById('togglePassword').addEventListener('click', function(e) {
                        // Prevent the button from submitting the form
                        e.preventDefault();
                        
                        const passwordInput = document.getElementById('password');
                        const toggleIcon = document.getElementById('togglePasswordIcon');
                        
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            toggleIcon.classList.remove('fa-eye');
                            toggleIcon.classList.add('fa-eye-slash');
                        } else {
                            passwordInput.type = 'password';
                            toggleIcon.classList.remove('fa-eye-slash');
                            toggleIcon.classList.add('fa-eye');
                        }
                    });
                }
                
                // Form pre-submission validation (but allow PHP form submission)
                const employeeForm = document.getElementById('employeeForm');
                employeeForm.addEventListener('submit', function (e) {
                    // Perform client-side validation if needed
                    const firstName = document.getElementById('firstName').value;
                    const lastName = document.getElementById('lastName').value;
                    const position = document.getElementById('position');
                    const positionText = position.options[position.selectedIndex].text;
                    const employeeNumber = document.getElementById('employeeNumber').value;
                    const username = document.getElementById('username').value;
                    const password = document.getElementById('password').value;
                    
                    // Validation
                    let isValid = true;
                    
                    if (!employeeNumber.match(/^09\d{10}$/)) {
                        isValid = false;
                        alert('Employee number must start with 09 and be 12 digits.');
                    }
                    
                    if (password.length < 6) {
                        isValid = false;
                        alert('Password must be at least 6 characters.');
                    }
                    
                    if (!username || !firstName || !lastName) {
                        isValid = false;
                        alert('Please fill in all required fields.');
                    }
                    
                    // If validation fails, prevent form submission
                    if (!isValid) {
                        e.preventDefault();
                    }
                    
                    // If validation passes, the form will submit to PHP

                    // Create new employee card
                    const newEmployee = document.createElement('div');
                    newEmployee.className = 'employee-card';
                    newEmployee.innerHTML = `
                <div class="employee-header">
                    <div class="employee-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="status-badge ${status}">${status === 'active' ? 'Active' : 'Inactive'}</span>
                </div>
                <div class="employee-details">
                    <h4>${firstName} ${lastName}</h4>
                    <p class="employee-position">${positionText}</p>
                    <div class="detail-item">
                        <i class="fas fa-phone"></i>
                        <span>${employeeNumber}</span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i>
                        <span>${email}</span>
                    </div>
                </div>
                <div class="employee-actions">
                    <button class="view-btn" title="View Details"><i class="fas fa-eye"></i></button>
                    <button class="edit-btn" title="Edit Employee"><i class="fas fa-edit"></i></button>
                    <button class="delete-btn" title="Remove Employee"><i class="fas fa-trash"></i></button>
                </div>
            `;

                    // Add the new employee card to the grid
                    document.querySelector('.employee-grid').prepend(newEmployee);

                    // Update the employee count
                    const totalEmployeesCount = document.querySelector('.stat-card:first-child .stat-info p');
                    totalEmployeesCount.textContent = parseInt(totalEmployeesCount.textContent) + 1;

                    // Add event listeners to new buttons
                    const newViewBtn = newEmployee.querySelector('.view-btn');
                    const newEditBtn = newEmployee.querySelector('.edit-btn');
                    const newDeleteBtn = newEmployee.querySelector('.delete-btn');

                    newViewBtn.addEventListener('click', function () {
                        viewEmployeeModal.classList.add('show');
                    });

                    newEditBtn.addEventListener('click', function () {
                        // Edit functionality would go here
                    });

                    newDeleteBtn.addEventListener('click', function () {
                        if (confirm('Are you sure you want to remove this employee?')) {
                            newEmployee.remove();

                            // Update employee count
                            totalEmployeesCount.textContent = parseInt(totalEmployeesCount.textContent) - 1;

                            // Show notification
                            showNotification('Employee removed successfully');
                        }
                    });

                    // Close modal and reset form
                    addEmployeeModal.classList.remove('show');
                    employeeForm.reset();

                    // Show success notification
                    showNotification('Employee added successfully');
                });

                // Hide modal on PHP form submit (page reload)
                if (window.location.search.includes('add_employee')) {
                    document.addEventListener('DOMContentLoaded', function () {
                        addEmployeeModal.classList.remove('show');
                    });
                }

                // Set date picker to today's date
                document.getElementById('attendanceDate').valueAsDate = new Date();

                // Function to show notification
                function showNotification(message) {
                    const notification = document.createElement('div');
                    notification.className = 'notification';
                    notification.innerHTML = `
                <div class="notification-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="notification-message">${message}</div>
                <button class="notification-close"><i class="fas fa-times"></i></button>
            `;

                    document.body.appendChild(notification);

                    // Auto remove after 5 seconds
                    setTimeout(() => {
                        notification.classList.add('fade-out');
                        setTimeout(() => {
                            document.body.removeChild(notification);
                        }, 300);
                    }, 5000);

                    // Close button
                    notification.querySelector('.notification-close').addEventListener('click', () => {
                        notification.classList.add('fade-out');
                        setTimeout(() => {
                            document.body.removeChild(notification);
                        }, 300);
                    });
                }

                // Edit Profile functionality
                document.addEventListener('DOMContentLoaded', function () {
                    const editProfileBtn = document.querySelector('.edit-profile-btn');

                    if (editProfileBtn) {
                        editProfileBtn.addEventListener('click', function () {
                            // Get current employee data from the view modal
                            const employeeName = document.querySelector('.profile-info h2').textContent;
                            const nameParts = employeeName.split(' ');
                            const firstName = nameParts[0] || '';
                            const lastName = nameParts.length > 1 ? nameParts.slice(1).join(' ') : '';
                            const position = document.querySelector('.profile-info p').textContent;
                            const employeeNumber = document.querySelector('.detail-grid .detail-item:nth-child(1) .value').textContent;
                            const status = document.querySelector('.profile-info .status-badge').textContent;

                            // Create a simple edit form modal
                            const editModal = document.createElement('div');
                            editModal.className = 'modal show';
                            editModal.id = 'editProfileModal';
                            editModal.innerHTML = `
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3><i class="fas fa-user-edit"></i> Edit Employee</h3>
                                <button class="close-btn">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="editEmployeeForm">
                                    <div class="form-section">
                                        <h4><i class="fas fa-user-circle"></i> Basic Information</h4>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="editFirstName">First Name</label>
                                                <input type="text" id="editFirstName" value="${firstName}" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="editLastName">Last Name</label>
                                                <input type="text" id="editLastName" value="${lastName}" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="editPosition">Position</label>
                                                <select id="editPosition" required>
                                                    <option value="driver" ${position === 'Driver' ? 'selected' : ''}>Driver</option>
                                                    <option value="senior_driver" ${position === 'Senior Driver' ? 'selected' : ''}>Senior Driver</option>
                                                    <option value="cashier" ${position === 'Cashier' ? 'selected' : ''}>Cashier</option>
                                                    <option value="production" ${position === 'Production Staff' ? 'selected' : ''}>Production Staff</option>
                                                    <option value="admin" ${position === 'Administrative Staff' ? 'selected' : ''}>Administrative Staff</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="editEmployeeNumber">Employee Number</label>
                                                <input type="tel" id="editEmployeeNumber" value="${employeeNumber}" required>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="editStatus">Status</label>
                                                <select id="editStatus" required>
                                                    <option value="active" ${status.toLowerCase() === 'active' ? 'selected' : ''}>Active</option>
                                                    <option value="inactive" ${status.toLowerCase() === 'inactive' ? 'selected' : ''}>Inactive</option>
                                                    <option value="probation" ${status.toLowerCase() === 'on probation' ? 'selected' : ''}>On Probation</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="editDateJoined">Date Joined</label>
                                                <input type="date" id="editDateJoined" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section">
                                        <h4><i class="fas fa-lock"></i> Account Details</h4>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="editUsername">Username</label>
                                                <input type="text" id="editUsername">
                                            </div>
                                            <div class="form-group">
                                                <label for="editPassword">Password</label>
                                                <input type="password" id="editPassword" placeholder="Leave blank to keep current password">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <button type="button" class="cancel-btn"><i class="fas fa-times"></i> Cancel</button>
                                        <button type="submit" class="submit-btn"><i class="fas fa-save"></i> Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    `;

                            // Add modal to the body
                            document.body.appendChild(editModal);

                            // Set up event listeners for the new modal
                            const closeEditBtn = editModal.querySelector('.close-btn');
                            const cancelEditBtn = editModal.querySelector('.cancel-btn');
                            const editEmployeeForm = editModal.querySelector('#editEmployeeForm');

                            // Close modal functions
                            const closeEditModal = () => {
                                editModal.classList.remove('show');
                                setTimeout(() => {
                                    document.body.removeChild(editModal);
                                }, 300);
                            };

                            closeEditBtn.addEventListener('click', closeEditModal);
                            cancelEditBtn.addEventListener('click', closeEditModal);

                            // Close on outside click
                            editModal.addEventListener('click', function (e) {
                                if (e.target === editModal) {
                                    closeEditModal();
                                }
                            });

                            // Form submission
                            editEmployeeForm.addEventListener('submit', function (e) {
                                e.preventDefault();

                                // Get form values
                                const newFirstName = document.getElementById('editFirstName').value;
                                const newLastName = document.getElementById('editLastName').value;
                                const newPosition = document.getElementById('editPosition');
                                const newPositionText = newPosition.options[newPosition.selectedIndex].text;
                                const newStatus = document.getElementById('editStatus').value;

                                // Update the view modal with new values
                                document.querySelector('.profile-info h2').textContent = `${newFirstName} ${newLastName}`;
                                document.querySelector('.profile-info p').textContent = newPositionText;
                                document.querySelector('.detail-grid .detail-item:nth-child(1) .value').textContent = document.getElementById('editEmployeeNumber').value;

                                // Update status badge
                                const statusBadge = document.querySelector('.profile-info .status-badge');
                                statusBadge.className = `status-badge ${newStatus}`;
                                statusBadge.textContent = newStatus === 'active' ? 'Active' : newStatus === 'inactive' ? 'Inactive' : 'On Probation';

                                // Show success notification
                                showNotification('Employee profile updated successfully');

                                // Close the edit modal
                                closeEditModal();
                            });

                            // Try to set the date joined field if available in the view modal
                            try {
                                const dateJoinedText = document.querySelector('.detail-grid .detail-item:nth-child(3) .value').textContent;
                                const dateParts = dateJoinedText.split(' ');
                                const month = new Date(Date.parse(`${dateParts[0]} 1, 2000`)).getMonth() + 1;
                                const day = parseInt(dateParts[1].replace(',', ''));
                                const year = parseInt(dateParts[2]);

                                if (!isNaN(month) && !isNaN(day) && !isNaN(year)) {
                                    document.getElementById('editDateJoined').value = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                                }
                            } catch (error) {
                                // If the date extraction fails, leave the field empty
                                console.log('Could not set date joined');
                            }
                        });
                    }
                });

                // Password show/hide toggle
                const passwordInput = document.getElementById('password');
                const togglePasswordBtn = document.getElementById('togglePassword');
                const togglePasswordIcon = document.getElementById('togglePasswordIcon');
                if (togglePasswordBtn) {
                    togglePasswordBtn.addEventListener('click', function () {
                        if (passwordInput.type === 'password') {
                            passwordInput.type = 'text';
                            togglePasswordIcon.classList.remove('fa-eye');
                            togglePasswordIcon.classList.add('fa-eye-slash');
                        } else {
                            passwordInput.type = 'password';
                            togglePasswordIcon.classList.remove('fa-eye-slash');
                            togglePasswordIcon.classList.add('fa-eye');
                        }
                    });
                }
            </script>
</body>

</html>
<php?

