<?php
session_start();

require_once '../../Database/db_config.php';

// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['position'])) {
    $position = $_SESSION['position'];
    if ($position === 'Admin') {
        header("Location: ../admin/index.html");
    } elseif ($position === 'Driver') {
        header("Location: ../html/Driver-dashboard.html");
    } elseif ($position === 'Cashier') {
        header("Location: ../html/Cashier.html");
    } else {
        header("Location: ../html/index.html");
    }
    exit();
}

$error = null; // Initialize error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Query to fetch user details based on the Username column
    $stmt = $conn->prepare("SELECT EmployeeID, CONCAT(FirstName, ' ', LastName) AS FullName, 
                           EmployeePosition, EmployeeStatus, Password 
                           FROM Employee 
                           WHERE Username = ? AND EmployeeStatus = 'Active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Check password
        if (password_verify($password, $user['Password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['EmployeeID'];
            $_SESSION['username'] = $user['FullName'];
            $_SESSION['position'] = $user['EmployeePosition'];
            
            // Redirect based on position
            if ($user['EmployeePosition'] === 'Admin') {
                header("Location: ../admin/index.html");
            } elseif ($user['EmployeePosition'] === 'Driver') {
                header("Location: ../html/Driver-dashboard.html");
            } elseif ($user['EmployeePosition'] === 'Cashier') {
                header("Location: ../html/Cashier.html");
            } else {
                header("Location: ../html/index.html");
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

if ($error) {
    echo "<script>alert('$error');</script>";
}

include '../html/login.html';
?>
