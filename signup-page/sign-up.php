<?php



session_start();
include "../db.php";


$first = $_POST['firstName'];
$last = $_POST['lastName'];
$email = $_POST['email'];
$password = $_POST['password'];


if(isset($_POST['adminCode']) && $_POST['adminCode'] == "1445"){
    $userType = "admin";
} else {
    $userType = "user";
}

# check email exists
$sql = "SELECT * FROM user WHERE emailAddress='$email'";
$result = $conn->query($sql);

if($result->num_rows > 0){
    echo "Email already exists";
    exit();
}


# hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);



if(isset($_FILES['photo']) && $_FILES['photo']['name'] != ""){
    $photo = time() . "_" . $_FILES['photo']['name'];
    
    move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/profiles/" . $photo);
} else {
    
    $photo = "default-user.png";
}
# insert user
$sql = "INSERT INTO user 
(userType,firstName,lastName,emailAddress,password,photoFileName)
VALUES 
('$userType','$first','$last','$email','$hashedPassword','$photo')";

if($conn->query($sql)){

    # تسجيل دخول مباشرة
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['firstName'] = $first;
    $_SESSION['user_type'] = $userType;

    # توجيه حسب النوع
    if($userType == "admin"){
        header("Location: ../admin-page/admin.php");
    } else {
        header("Location: ../user-page/user.php");
    }

    exit();

} else {
    echo "ERROR: " . $conn->error;
}

?>