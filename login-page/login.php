<?php

session_start();

include "db.php";

$email = $_POST['email'];
$password = $_POST['password'];

/* check user */

$sql = "SELECT * FROM user WHERE emailAddress='$email'";

$result = $conn->query($sql);

if($result->num_rows > 0){

    $user = $result->fetch_assoc();

    if($password == $user['password']){

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['firstName'] = $user['firstName'];

        header("Location: user.html");
        exit();

    }
}

echo "<script>
alert('Invalid Email or Password');
window.location.href='login.html';
</script>";

?>