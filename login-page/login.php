<?php
session_start();
require_once "../db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo "<script>
    alert('Please enter email and password.');
    window.location.href='login.html';
    </script>";
    exit();
}

// Check blocked users first
$stmt = $conn->prepare("SELECT id FROM blockeduser WHERE emailAddress = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$blockedResult = $stmt->get_result();

if ($blockedResult->num_rows > 0) {
    echo "<script>
    alert('Your account has been blocked.');
    window.location.href='login.html';
    </script>";
    exit();
}

// Check user table
$stmt = $conn->prepare("SELECT * FROM user WHERE emailAddress = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

$user = $result->fetch_assoc();

// Verify hashed password
if (!password_verify($password, $user['password'])) {
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

// Save session
$_SESSION['user_id'] = $user['id'];
$_SESSION['firstName'] = $user['firstName'];
$_SESSION['user_type'] = $user['userType'];

// Redirect by role
if ($user['userType'] === "admin") {
    header("Location: ../admin-page/admin.php");
} else {
    header("Location: ../user-page/user.php");
}
exit();
?>