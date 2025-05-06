<?php
session_start();

require_once '../../Database/db_config.php';

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['position'])) {
    $position = $_SESSION['position'];
    if ($position === 'Admin') {
        header("Location: ../admin/Admin_dashboard.html");
    } elseif ($position === 'Driver') {
        header("Location: ../html/Drivers/Driver-dashboard.html");
    } elseif ($position === 'Cashier') {
        header("Location: ../html/Home.html");
    } else {
        header("Location: ../html/dashboard.html");
    }
    exit();
}

$error = null; // Initialize error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Query to fetch user details based on the Username column
    $stmt = $conn->prepare("SELECT EmployeeID, EmployeeName, EmployeePosition, EmployeeStatus, Password FROM Employee WHERE Username = ? AND EmployeeStatus = 'Active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // For testing purposes - always log in with the user 'walton'
        if ($username === 'walton') {
            // Set session variables
            $_SESSION['user_id'] = $user['EmployeeID'];
            $_SESSION['username'] = $user['EmployeeName'];
            $_SESSION['position'] = $user['EmployeePosition'];
            
            // Redirect based on position
            if ($user['EmployeePosition'] === 'Admin') {
                header("Location: ../admin/Admin_dashboard.html");
            } elseif ($user['EmployeePosition'] === 'Driver') {
                header("Location: ../html/Drivers/Driver-dashboard.html");
            } elseif ($user['EmployeePosition'] === 'Cashier') {
                header("Location: ../html/Home.html");
            } else {
                header("Location: ../html/dashboard.html");
            }
            exit(); // Important: make sure script execution stops after redirect
        }
        // Normal password verification
        else if (password_verify($password, $user['Password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['EmployeeID'];
            $_SESSION['username'] = $user['EmployeeName'];
            $_SESSION['position'] = $user['EmployeePosition'];
            
            // Redirect based on position
            if ($user['EmployeePosition'] === 'Admin') {
                header("Location: ../admin/Admin_dashboard.html");
            } elseif ($user['EmployeePosition'] === 'Driver') {
                header("Location: ../html/Drivers/Driver-dashboard.html");
            } elseif ($user['EmployeePosition'] === 'Cashier') {
                header("Location: ../html/Home.html");
            } else {
                header("Location: ../html/dashboard.html");
            }
            exit(); // Important: make sure script execution stops after redirect
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }

    $stmt->close();
}
$conn->close();

// Display error if there is one
if (isset($error)) {
    echo "<div style='color: red; text-align: center; margin-bottom: 20px;'>$error</div>";
}

// Always include the login form at the end
include '../html/login.html';
?>
