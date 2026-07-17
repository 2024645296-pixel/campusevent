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

/* Validate user ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php?error=invalid");
    exit();
}

$userId = (int) $_GET['id'];

/* Get selected user */
$selectStmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $selectStmt,
    "i",
    $userId
);

mysqli_stmt_execute($selectStmt);

$result = mysqli_stmt_get_result($selectStmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($selectStmt);

if (!$user) {
    header("Location: users.php?error=notfound");
    exit();
}

$error = "";

/* Update user */
if (isset($_POST['update_user'])) {
    $name = trim($_POST['name'] ?? '');
    $matricNo = trim($_POST['matric_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');

    $allowedRoles = [
        'Admin',
        'Club Leader',
        'Student'
    ];

    if ($name === '') {
        $error = "Please enter the user's name.";
    } elseif ($email === '') {
        $error = "Please enter the user's email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!in_array($role, $allowedRoles, true)) {
        $error = "Invalid user role selected.";
    }

    /* Check duplicate email */
    if ($error === '') {
        $emailStmt = mysqli_prepare(
            $conn,
            "SELECT user_id
             FROM users
             WHERE email = ?
             AND user_id != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $emailStmt,
            "si",
            $email,
            $userId
        );

        mysqli_stmt_execute($emailStmt);

        $emailResult = mysqli_stmt_get_result($emailStmt);
        $existingEmail = mysqli_fetch_assoc($emailResult);

        mysqli_stmt_close($emailStmt);

        if ($existingEmail) {
            $error = "This email address is already registered.";
        }
    }

    /* Check duplicate matric number */
    if (
        $error === '' &&
        $matricNo !== ''
    ) {
        $matricStmt = mysqli_prepare(
            $conn,
            "SELECT user_id
             FROM users
             WHERE matric_no = ?
             AND user_id != ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param(
            $matricStmt,
            "si",
            $matricNo,
            $userId
        );

        mysqli_stmt_execute($matricStmt);

        $matricResult = mysqli_stmt_get_result($matricStmt);
        $existingMatric = mysqli_fetch_assoc($matricResult);

        mysqli_stmt_close($matricStmt);

        if ($existingMatric) {
            $error = "This matric number is already registered.";
        }
    }

    /*
       Prevent the current logged-in Admin
       from changing their own role.
    */
    if (
        $error === '' &&
        $userId === (int) $_SESSION['user_id'] &&
        $role !== 'Admin'
    ) {
        $error = "You cannot change your own Admin role.";
    }

    /*
       Prevent removing the final Admin account.
    */
    if (
        $error === '' &&
        $user['role'] === 'Admin' &&
        $role !== 'Admin'
    ) {
        $adminResult = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total
             FROM users
             WHERE role = 'Admin'"
        );

        $adminData = mysqli_fetch_assoc($adminResult);
        $adminCount = (int) ($adminData['total'] ?? 0);

        if ($adminCount <= 1) {
            $error = "The final Admin account must remain as Admin.";
        }
    }

    if ($error === '') {
        /*
           Password is updated only when Admin
           enters a new password.
        */
        if ($newPassword !== '') {
            $updateStmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET name = ?,
                     matric_no = ?,
                     email = ?,
                     phone = ?,
                     faculty = ?,
                     role = ?,
                     password = ?
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $updateStmt,
                "sssssssi",
                $name,
                $matricNo,
                $email,
                $phone,
                $faculty,
                $role,
                $newPassword,
                $userId
            );
        } else {
            $updateStmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET name = ?,
                     matric_no = ?,
                     email = ?,
                     phone = ?,
                     faculty = ?,
                     role = ?
                 WHERE user_id = ?"
            );

            mysqli_stmt_bind_param(
                $updateStmt,
                "ssssssi",
                $name,
                $matricNo,
                $email,
                $phone,
                $faculty,
                $role,
                $userId
            );
        }

        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);

            /*
               Update session name if Admin edits
               their own account.
            */
            if ($userId === (int) $_SESSION['user_id']) {
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
            }

            header("Location: users.php?success=updated");
            exit();
        }

        $error = "Unable to update user: " .
            mysqli_stmt_error($updateStmt);

        mysqli_stmt_close($updateStmt);
    }

    /* Keep submitted values if validation fails */
    $user['name'] = $name;
    $user['matric_no'] = $matricNo;
    $user['email'] = $email;
    $user['phone'] = $phone;
    $user['faculty'] = $faculty;
    $user['role'] = $role;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit User | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=230">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main edit-user-page">

        <div class="page-header">

            <div>

                <a href="users.php" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Users
                </a>

                <h1>Edit User</h1>

                <p>
                    Update the user's personal information, role and login details.
                </p>

            </div>

            <div class="page-summary">

                <span>
                    <i class="fa-solid fa-user-pen"></i>
                </span>

                <small>
                    User ID #
                    <?php echo $userId; ?>
                </small>

            </div>

        </div>

        <?php if ($error !== '') { ?>

            <div class="error edit-user-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>

        <div class="card edit-user-card">

            <div class="section-header">

                <div>
                    <h2>User Information</h2>

                    <p>
                        Make sure all details are correct before saving the changes.
                    </p>
                </div>

                <span class="role-badge role-<?php
                echo strtolower(
                    str_replace(
                        ' ',
                        '-',
                        $user['role']
                    )
                );
                ?>">
                    <?php echo htmlspecialchars($user['role']); ?>
                </span>

            </div>

            <form method="POST" class="edit-user-form">

                <div class="form-grid">

                    <div class="form-group form-full">

                        <label for="name">
                            Full Name
                        </label>

                        <div class="edit-user-input">

                            <i class="fa-regular fa-user"></i>

                            <input type="text" id="name" name="name" value="<?php
                            echo htmlspecialchars(
                                $user['name']
                            );
                            ?>" required>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="matric_no">
                            Matric Number
                        </label>

                        <div class="edit-user-input">

                            <i class="fa-solid fa-id-card"></i>

                            <input type="text" id="matric_no" name="matric_no" value="<?php
                            echo htmlspecialchars(
                                $user['matric_no'] ?? ''
                            );
                            ?>" placeholder="Example: 2024645296">

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="role">
                            User Role
                        </label>

                        <div class="edit-user-input">

                            <i class="fa-solid fa-user-shield"></i>

                            <select id="role" name="role" required>

                                <option value="Admin" <?php
                                echo $user['role'] === 'Admin'
                                    ? 'selected'
                                    : '';
                                ?>
                        >
                                    Admin
                                </option>

                                <option value="Club Leader" <?php
                                echo $user['role'] === 'Club Leader'
                                    ? 'selected'
                                    : '';
                                ?>
                        >
                                    Club Leader
                                </option>

                                <option value="Student" <?php
                                echo $user['role'] === 'Student'
                                    ? 'selected'
                                    : '';
                                ?>
     >
                                    Student
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <div class="edit-user-input">

                            <i class="fa-regular fa-envelope"></i>

                            <input type="email" id="email" name="email" value="<?php
                            echo htmlspecialchars(
                                $user['email']
                            );
                            ?>" required>

                        </div>

                    </div>

                    <div class="form-group">

                        <label for="phone">
                            Phone Number
                        </label>

                        <div class="edit-user-input">

                            <i class="fa-solid fa-phone"></i>

                            <input type="text" id="phone" name="phone" value="<?php
                            echo htmlspecialchars(
                                $user['phone'] ?? ''
                            );
                            ?>" placeholder="Example: 0123456789">

                        </div>

                    </div>

                    <div class="form-group form-full">

                        <label for="faculty">
                            Faculty
                        </label>

                        <div class="edit-user-input">

                            <i class="fa-solid fa-building-columns"></i>

                            <input type="text" id="faculty" name="faculty" value="<?php
                            echo htmlspecialchars(
                                $user['faculty'] ?? ''
                            );
                            ?>" placeholder="Example: Faculty of Information Science">

                        </div>

                    </div>

                    <div class="form-group form-full">

                        <label for="password">
                            New Password
                        </label>

                        <div class="edit-user-input password-field">

                            <i class="fa-solid fa-lock"></i>

                            <input type="password" id="password" name="password"
                                placeholder="Leave blank to keep the current password">

                            <button type="button" class="edit-password-toggle" id="passwordToggle"
                                aria-label="Show password">
                                <i class="fa-regular fa-eye" id="passwordIcon"></i>
                            </button>

                        </div>

                        <small class="edit-user-note">
                            The password will only change if a new password is entered.
                        </small>

                    </div>

                </div>

                <div class="edit-user-notice">

                    <i class="fa-solid fa-circle-info"></i>

                    <div>
                        <strong>Account Management Notice</strong>

                        <p>
                            Changing a user's role will change the pages and functions
                            that the user can access after their next login.
                        </p>
                    </div>

                </div>

                <div class="edit-user-actions">

                    <button type="submit" name="update_user" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Changes
                    </button>

                    <a href="users.php" class="btn-secondary">
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                    </a>

                </div>

            </form>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("passwordToggle");
        const passwordIcon = document.getElementById("passwordIcon");

        passwordToggle.addEventListener("click", function () {
            const passwordIsHidden =
                passwordInput.type === "password";

            passwordInput.type =
                passwordIsHidden ? "text" : "password";

            passwordIcon.classList.toggle(
                "fa-eye",
                !passwordIsHidden
            );

            passwordIcon.classList.toggle(
                "fa-eye-slash",
                passwordIsHidden
            );

            passwordToggle.setAttribute(
                "aria-label",
                passwordIsHidden
                    ? "Hide password"
                    : "Show password"
            );
        });
    </script>

</body>

</html>