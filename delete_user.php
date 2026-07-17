<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    header("Location: users.php?error=invalid");
    exit();
}

$userId = (int) $_GET['id'];
$currentUserId = (int) $_SESSION['user_id'];

/* Admin cannot delete own account */
if ($userId === $currentUserId) {
    header("Location: users.php?error=self");
    exit();
}

/* Check selected user */
$checkStmt = mysqli_prepare(
    $conn,
    "SELECT user_id, name, role
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkStmt,
    "i",
    $userId
);

mysqli_stmt_execute($checkStmt);

$result = mysqli_stmt_get_result($checkStmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($checkStmt);

if (!$user) {
    header("Location: users.php?error=notfound");
    exit();
}

/* Do not delete final Admin */
if ($user['role'] === 'Admin') {
    $adminResult = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = 'Admin'"
    );

    $adminData = mysqli_fetch_assoc($adminResult);
    $adminCount = (int) $adminData['total'];

    if ($adminCount <= 1) {
        header("Location: users.php?error=lastadmin");
        exit();
    }
}

/* Delete selected user */
$deleteStmt = mysqli_prepare(
    $conn,
    "DELETE FROM users
     WHERE user_id = ?"
);

mysqli_stmt_bind_param(
    $deleteStmt,
    "i",
    $userId
);

if (mysqli_stmt_execute($deleteStmt)) {
    mysqli_stmt_close($deleteStmt);

    header("Location: users.php?success=deleted");
    exit();
}

$errorCode = mysqli_errno($conn);

mysqli_stmt_close($deleteStmt);

/* Foreign key relationship exists */
if ($errorCode === 1451) {
    header("Location: users.php?error=related");
    exit();
}

header("Location: users.php?error=delete");
exit();
?>