<?php

session_start();
include "../db.php";

$email = $_POST['email'];
$password = $_POST['password'];

// 1) Check user in user table
$sql = "SELECT * FROM user WHERE emailAddress='$email'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

$user = $result->fetch_assoc();

// 2) Verify password
if (!password_verify($password, $user['password'])) {
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

// 3) Check if user is blocked
$blockedSql = "SELECT * FROM blockeduser WHERE emailAddress='$email'";
$blockedResult = $conn->query($blockedSql);

if ($blockedResult->num_rows > 0) {
    echo "<script>
    alert('Your account has been blocked.');
    window.location.href='login.html';
    </script>";
    exit();
}

// 4) Start session
$_SESSION['user_id'] = $user['id'];
$_SESSION['firstName'] = $user['firstName'];
$_SESSION['user_type'] = $user['userType'];

// 5) Redirect
if ($user['userType'] == "admin") {
    header("Location: ../admin-page/admin.php");
} else {
    header("Location: ../user-page/user.php");
}

exit();

?>
