<?php

session_start();
include "../db.php";

$email = $_POST['email'];
$password = $_POST['password'];

// 1) Check if email exists in blockeduser table first
$blockedSql = "SELECT * FROM blockeduser WHERE emailAddress='$email'";
$blockedResult = $conn->query($blockedSql);

if ($blockedResult->num_rows > 0) {
    echo "<script>
    alert('Your account has been blocked.');
    window.location.href='login.html';
    </script>";
    exit();
}

// 2) Check if email exists in user table
$sql = "SELECT * FROM user WHERE emailAddress='$email'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

// 3) Get user data
$user = $result->fetch_assoc();

// 4) Verify password
if (!password_verify($password, $user['password'])) {
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

// 5) Save session
$_SESSION['user_id'] = $user['id'];
$_SESSION['firstName'] = $user['firstName'];
$_SESSION['user_type'] = $user['userType'];

// 6) Redirect based on role
if ($user['userType'] == "admin") {
    header("Location: ../admin-page/admin.php");
} else {
    header("Location: ../user-page/user.php");
}

exit();

?>
