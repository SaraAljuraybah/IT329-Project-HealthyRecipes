<?php
session_start();
require_once "../db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: sign-up.html");
    exit();
}

$first = trim($_POST['firstName'] ?? '');
$last = trim($_POST['lastName'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

$userType = "user";

if ($first === '' || $last === '' || $email === '' || $password === '') {
    echo "<script>
    alert('Please fill in all required fields.');
    window.location.href='sign-up.html';
    </script>";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
    alert('Please enter a valid email.');
    window.location.href='sign-up.html';
    </script>";
    exit();
}

if (strlen($password) < 6) {
    echo "<script>
    alert('Password must be at least 6 characters.');
    window.location.href='sign-up.html';
    </script>";
    exit();
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM user WHERE emailAddress = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<script>
    alert('Email already exists.');
    window.location.href='sign-up.html';
    </script>";
    exit();
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Handle profile image
$photo = "default-user.png";

if (isset($_FILES['photo']) && !empty($_FILES['photo']['name'])) {
    $uploadDir = "../uploads/profiles/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxFileSize = 2 * 1024 * 1024; // 2 MB

    $originalName = $_FILES['photo']['name'];
    $tmpName = $_FILES['photo']['tmp_name'];
    $fileSize = $_FILES['photo']['size'];
    $fileError = $_FILES['photo']['error'];

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($fileError !== 0) {
        echo "<script>
        alert('There was an error uploading the image.');
        window.location.href='sign-up.html';
        </script>";
        exit();
    }

    if (!in_array($extension, $allowedExtensions)) {
        echo "<script>
        alert('Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.');
        window.location.href='sign-up.html';
        </script>";
        exit();
    }

    if ($fileSize > $maxFileSize) {
        echo "<script>
        alert('Image size must be less than 2 MB.');
        window.location.href='sign-up.html';
        </script>";
        exit();
    }

    $imageInfo = getimagesize($tmpName);
    if ($imageInfo === false) {
        echo "<script>
        alert('Uploaded file is not a valid image.');
        window.location.href='sign-up.html';
        </script>";
        exit();
    }

    $photo = time() . "_" . basename($originalName);
    $targetPath = $uploadDir . $photo;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        echo "<script>
        alert('Failed to upload profile image.');
        window.location.href='sign-up.html';
        </script>";
        exit();
    }
}

// Insert user
$stmt = $conn->prepare("
    INSERT INTO user (userType, firstName, lastName, emailAddress, password, photoFileName)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssss", $userType, $first, $last, $email, $hashedPassword, $photo);

if ($stmt->execute()) {
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['firstName'] = $first;
    $_SESSION['user_type'] = $userType;

    header("Location: ../user-page/user.php");
    exit();
} else {
    echo "<script>
    alert('Signup failed.');
    window.location.href='sign-up.html';
    </script>";
    exit();
}
?>