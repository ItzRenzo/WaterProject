<?php
session_start();

require_once '../../Database/db_config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['position'])) {
    $position = strtolower($_SESSION['position']);
    if ($position === 'admin') {
        header('Location: ../admin/index.html');
    } elseif ($position === 'driver') {
        header('Location: ../html/Driver-dashboard.html');
    } elseif ($position === 'cashier') {
        header('Location: ../html/Cashier.php');
    } else {
        header('Location: ../html/index.html');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Only allow login by username (case-insensitive)
    $stmt = $conn->prepare('SELECT EmployeeID, CONCAT(FirstName, " ", LastName) AS FullName, EmployeePosition, EmployeeStatus, Username, Password FROM Employee WHERE LOWER(Username) = LOWER(?) AND EmployeeStatus = "Active"');
    $stmt->bind_param('s', $username);
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
            $pos = strtolower($user['EmployeePosition']);
            if ($pos === 'admin') {
                header('Location: ../admin/index.html');
            } elseif ($pos === 'driver') {
                header('Location: ../html/Driver-dashboard.php');
            } elseif ($pos === 'cashier') {
                header('Location: ../html/Cashier.php');
            } else {
                header('Location: ../html/index.html');
            }
            exit();
        } else {
            echo "<script>alert('Invalid username or password.');window.location.href='../html/login.html';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid username or password.');window.location.href='../html/login.html';</script>";
        exit();
    }

    $stmt->close();
}
$conn->close();



include '../html/login.html';
?>
