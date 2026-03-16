<?php

include "db.php";

$firstName = $_POST['firstName'];
$lastName  = $_POST['lastName'];
$email     = $_POST['email'];
$password  = $_POST['password'];

$imageName = "default-user.png";

/* upload image */

if(isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] == 0){

    $imageName = time() . "_" . $_FILES['profileImage']['name'];

    move_uploaded_file(
        $_FILES['profileImage']['tmp_name'],
        "uploads/" . $imageName
    );
}

/* insert user */

$sql = "INSERT INTO user 
(firstName,lastName,emailAddress,password,photoFileName)

VALUES

('$firstName','$lastName','$email','$password','$imageName')";

if($conn->query($sql)){

    header("Location: login.html");
    exit();

}
else{

    echo "Error: " . $conn->error;

}

?>