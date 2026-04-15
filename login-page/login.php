<?php

session_start();
include "../db.php";
$email = $_POST['email'];
$password = $_POST['password'];

$sql = "SELECT * FROM user WHERE emailAddress='$email'";
$result = $conn->query($sql);


if($result->num_rows == 0){
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}

$user = $result->fetch_assoc();


if(!password_verify($password, $user['password'])){
    echo "<script>
    alert('Invalid Email or Password');
    window.location.href='login.html';
    </script>";
    exit();
}


$_SESSION['user_id'] = $user['id'];
$_SESSION['firstName'] = $user['firstName'];
$_SESSION['user_type'] = $user['userType'];


if($user['userType'] == "admin"){
    header("Location: ../admin-page/admin.php");
} else {
    header("Location: ../user-page/user.php");
}

exit();

?>