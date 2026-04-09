<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../db.php");

/* check login */
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header("Location: ../login-page/login.html?error=Please login first");
    exit();
}

/* check admin */
if ($_SESSION['user_type'] != 'admin') {
    header("Location: ../login-page/login.html?error=Access denied");
    exit();
}

$adminID = $_SESSION['user_id'];

/* get admin info */
$sqlAdmin = "SELECT * FROM user WHERE id = ? AND userType = 'admin'";
$stmtAdmin = mysqli_prepare($conn, $sqlAdmin);
mysqli_stmt_bind_param($stmtAdmin, "i", $adminID);
mysqli_stmt_execute($stmtAdmin);
$resultAdmin = mysqli_stmt_get_result($stmtAdmin);

if (mysqli_num_rows($resultAdmin) == 0) {
    echo "Admin not found.";
    exit();
}

$admin = mysqli_fetch_assoc($resultAdmin);

/* get all reports with recipe + user info */
$sqlReports = "SELECT report.id AS reportID,
                      report.recipeID,
                      recipe.name AS recipeName,
                      recipe.userID AS recipeOwnerID,
                      user.firstName,
                      user.lastName,
                      user.emailAddress
               FROM report
               JOIN recipe ON report.recipeID = recipe.id
               JOIN user ON recipe.userID = user.id
               ORDER BY report.id DESC";

$resultReports = mysqli_query($conn, $sqlReports);

/* get blocked users */
$sqlBlocked = "SELECT * FROM blockeduser ORDER BY id DESC";
$resultBlocked = mysqli_query($conn, $sqlBlocked);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8faf8;
            margin: 0;
        }

        .page {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }

        .top-box {
            background: white;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .logout-link {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 16px;
            background: #6dda69;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
        }

        .section {
            background: white;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        h1, h2 {
            color: #2f6f4f;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table th, table td {
            border: 1px solid #d9e3d9;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }

        table th {
            background: #eef7ee;
        }

        select, button {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        button {
            background: #6dda69;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }

        button:hover {
            opacity: 0.9;
        }

        .recipe-link {
            color: #2f6f4f;
            font-weight: bold;
            text-decoration: none;
        }

        .empty-msg {
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div class="page">

    <div class="top-box">
        <h1>Welcome, <?php echo htmlspecialchars($admin['firstName']); ?></h1>
        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($admin['firstName'] . " " . $admin['lastName']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($admin['emailAddress']); ?></p>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($admin['userType']); ?></p>

        <a class="logout-link" href="../logout.php">Sign Out</a>
    </div>

    <div class="section">
        <h2>Reported Recipes</h2>

        <?php if ($resultReports && mysqli_num_rows($resultReports) > 0) { ?>
            <table>
                <tr>
                    <th>Recipe</th>
                    <th>Reported User</th>
                    <th>Action</th>
                </tr>

                <?php while ($report = mysqli_fetch_assoc($resultReports)) { ?>
                    <tr>
                        <td>
                            <a class="recipe-link" href="../view_recipe-page/view_recipe.php?id=<?php echo $report['recipeID']; ?>">
                                <?php echo htmlspecialchars($report['recipeName']); ?>
                            </a>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($report['firstName'] . " " . $report['lastName']); ?><br>
                            <?php echo htmlspecialchars($report['emailAddress']); ?>
                        </td>

                        <td>
                            <form action="handle_report.php" method="post">
                                <input type="hidden" name="reportID" value="<?php echo $report['reportID']; ?>">
                                <input type="hidden" name="recipeID" value="<?php echo $report['recipeID']; ?>">

                                <select name="action" required>
                                    <option value="">Select Action</option>
                                    <option value="block">Block User</option>
                                    <option value="dismiss">Dismiss Report</option>
                                </select>

                                <button type="submit">Submit</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="empty-msg">No reports found.</p>
        <?php } ?>
    </div>

    <div class="section">
        <h2>Blocked Users</h2>

        <?php if ($resultBlocked && mysqli_num_rows($resultBlocked) > 0) { ?>
            <table>
                <tr>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                </tr>

                <?php while ($blocked = mysqli_fetch_assoc($resultBlocked)) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($blocked['firstName']); ?></td>
                        <td><?php echo htmlspecialchars($blocked['lastName']); ?></td>
                        <td><?php echo htmlspecialchars($blocked['emailAddress']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } else { ?>
            <p class="empty-msg">No blocked users.</p>
        <?php } ?>
    </div>

</div>

</body>
</html>