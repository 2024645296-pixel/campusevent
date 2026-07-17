<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? '';

if (!in_array($role, ['Student', 'Club Leader'], true)) {
    header("Location: dashboard.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$error = "";

/* =========================================
   GET CURRENT USER
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die(
        "Database preparation failed: " .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    header("Location: logout.php");
    exit();
}

/* =========================================
   UPDATE PROFILE
========================================= */

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $matricNo = trim($_POST['matric_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if ($name === '') {
        $error = "Please enter your full name.";
    } elseif ($matricNo === '') {
        $error = "Please enter your matric number.";
    } elseif ($email === '') {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($phone === '') {
        $error = "Please enter your phone number.";
    } elseif (
        $newPassword !== '' &&
        strlen($newPassword) < 6
    ) {
        $error = "New password must contain at least 6 characters.";
    } elseif (
        $newPassword !== '' &&
        $newPassword !== $confirmPassword
    ) {
        $error = "New password and confirmation do not match.";
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

        if (!$emailStmt) {
            $error =
                "Unable to validate email: " .
                mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $emailStmt,
                "si",
                $email,
                $userId
            );

            mysqli_stmt_execute($emailStmt);

            $emailResult =
                mysqli_stmt_get_result($emailStmt);

            $emailExists =
                mysqli_fetch_assoc($emailResult);

            mysqli_stmt_close($emailStmt);

            if ($emailExists) {
                $error =
                    "This email address is already registered.";
            }
        }
    }

    /* Check duplicate matric number */
    if ($error === '') {
        $matricStmt = mysqli_prepare(
            $conn,
            "SELECT user_id
             FROM users
             WHERE matric_no = ?
             AND user_id != ?
             LIMIT 1"
        );

        if (!$matricStmt) {
            $error =
                "Unable to validate matric number: " .
                mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $matricStmt,
                "si",
                $matricNo,
                $userId
            );

            mysqli_stmt_execute($matricStmt);

            $matricResult =
                mysqli_stmt_get_result($matricStmt);

            $matricExists =
                mysqli_fetch_assoc($matricResult);

            mysqli_stmt_close($matricStmt);

            if ($matricExists) {
                $error =
                    "This matric number is already registered.";
            }
        }
    }

    /* Update user */
    if ($error === '') {
        if ($newPassword !== '') {
            $updateStmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET
                    name = ?,
                    matric_no = ?,
                    email = ?,
                    phone = ?,
                    faculty = ?,
                    password = ?
                 WHERE user_id = ?"
            );

            if (!$updateStmt) {
                $error =
                    "Database preparation failed: " .
                    mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $updateStmt,
                    "ssssssi",
                    $name,
                    $matricNo,
                    $email,
                    $phone,
                    $faculty,
                    $newPassword,
                    $userId
                );
            }
        } else {
            $updateStmt = mysqli_prepare(
                $conn,
                "UPDATE users
                 SET
                    name = ?,
                    matric_no = ?,
                    email = ?,
                    phone = ?,
                    faculty = ?
                 WHERE user_id = ?"
            );

            if (!$updateStmt) {
                $error =
                    "Database preparation failed: " .
                    mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param(
                    $updateStmt,
                    "sssssi",
                    $name,
                    $matricNo,
                    $email,
                    $phone,
                    $faculty,
                    $userId
                );
            }
        }

        if ($error === '' && isset($updateStmt)) {
            if (mysqli_stmt_execute($updateStmt)) {
                mysqli_stmt_close($updateStmt);

                $_SESSION['name'] = $name;

                header(
                    "Location: profile.php?success=updated"
                );
                exit();
            }

            $error =
                "Unable to update profile: " .
                mysqli_stmt_error($updateStmt);

            mysqli_stmt_close($updateStmt);
        }
    }

    /* Keep submitted values after validation error */
    $user['name'] = $name;
    $user['matric_no'] = $matricNo;
    $user['email'] = $email;
    $user['phone'] = $phone;
    $user['faculty'] = $faculty;
}

/* =========================================
   CLUB LEADER INFORMATION
========================================= */

$clubInformation = null;

if ($role === 'Club Leader') {
    $clubStmt = mysqli_prepare(
        $conn,
        "SELECT
            club_name,
            COUNT(*) AS total_proposals
         FROM program_proposals
         WHERE user_id = ?
         GROUP BY club_name
         ORDER BY total_proposals DESC
         LIMIT 1"
    );

    if ($clubStmt) {
        mysqli_stmt_bind_param(
            $clubStmt,
            "i",
            $userId
        );

        mysqli_stmt_execute($clubStmt);

        $clubResult =
            mysqli_stmt_get_result($clubStmt);

        $clubInformation =
            mysqli_fetch_assoc($clubResult);

        mysqli_stmt_close($clubStmt);
    }
}

/* =========================================
   DISPLAY VALUES
========================================= */

$profileInitials = strtoupper(
    substr(
        trim($user['name']),
        0,
        2
    )
);

$roleClass = strtolower(
    str_replace(
        ' ',
        '-',
        $role
    )
);

$pageRoleLabel = $role === 'Club Leader'
    ? 'Club Leader Account'
    : 'Student Account';

$profileDescription = $role === 'Club Leader'
    ? 'View and update your club leader account information.'
    : 'View and update your student account information.';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Profile | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=95000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main student-profile-page">

        <div class="page-header">

            <div>
                <h1>My Profile</h1>

                <p>
                    <?php
                    echo htmlspecialchars(
                        $profileDescription
                    );
                    ?>
                </p>
            </div>

            <div class="page-summary">

                <span>
                    <i class="fa-solid fa-user-pen"></i>
                </span>

                <small>
                    <?php
                    echo htmlspecialchars(
                        $pageRoleLabel
                    );
                    ?>
                </small>

            </div>

        </div>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'updated'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Profile updated successfully.
            </div>

        <?php } ?>

        <?php if ($error !== '') { ?>

            <div class="error student-profile-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>

        <div class="student-profile-layout">

            <aside class="card student-profile-summary">

                <div class="student-profile-avatar">
                    <?php
                    echo htmlspecialchars(
                        $profileInitials
                    );
                    ?>
                </div>

                <h2>
                    <?php
                    echo htmlspecialchars(
                        $user['name']
                    );
                    ?>
                </h2>

                <p>
                    <?php
                    echo !empty($user['matric_no'])
                        ? htmlspecialchars(
                            $user['matric_no']
                        )
                        : 'No matric number';
                    ?>
                </p>

                <span class="role-badge role-<?php
                echo htmlspecialchars($roleClass);
                ?>">
                    <?php echo htmlspecialchars($role); ?>
                </span>

                <div class="student-profile-contact">

                    <div>
                        <i class="fa-regular fa-envelope"></i>

                        <span>
                            <?php
                            echo htmlspecialchars(
                                $user['email']
                            );
                            ?>
                        </span>
                    </div>

                    <div>
                        <i class="fa-solid fa-phone"></i>

                        <span>
                            <?php
                            echo !empty($user['phone'])
                                ? htmlspecialchars(
                                    $user['phone']
                                )
                                : 'No phone number';
                            ?>
                        </span>
                    </div>

                    <div>
                        <i class="fa-solid fa-building-columns"></i>

                        <span>
                            <?php
                            echo !empty($user['faculty'])
                                ? htmlspecialchars(
                                    $user['faculty']
                                )
                                : 'No faculty information';
                            ?>
                        </span>
                    </div>

                </div>

                <?php if ($role === 'Club Leader') { ?>

                    <div class="club-profile-summary">

                        <h3>
                            <i class="fa-solid fa-people-group"></i>
                            Club Information
                        </h3>

                        <div>
                            <span>Club Name</span>

                            <strong>
                                <?php
                                echo !empty(
                                    $clubInformation['club_name']
                                )
                                    ? htmlspecialchars(
                                        $clubInformation['club_name']
                                    )
                                    : 'No club proposal submitted yet';
                                ?>
                            </strong>
                        </div>

                        <div>
                            <span>Position</span>

                            <strong>
                                Club Leader
                            </strong>
                        </div>

                        <div>
                            <span>Total Proposals</span>

                            <strong>
                                <?php
                                echo (int) (
                                    $clubInformation['total_proposals']
                                    ?? 0
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                <?php } ?>

            </aside>

            <div class="card student-profile-card">

                <div class="section-header">

                    <div>
                        <h2>Personal Information</h2>

                        <p>
                            Make sure your information is accurate before saving.
                        </p>
                    </div>

                    <span class="student-profile-section-icon">
                        <i class="fa-regular fa-id-card"></i>
                    </span>

                </div>

                <form method="POST" class="student-profile-form">

                    <div class="form-grid">

                        <div class="form-group form-full">

                            <label for="name">
                                Full Name
                                <span class="required-mark">*</span>
                            </label>

                            <div class="student-profile-input">

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
                                <span class="required-mark">*</span>
                            </label>

                            <div class="student-profile-input">

                                <i class="fa-solid fa-id-card"></i>

                                <input type="text" id="matric_no" name="matric_no" value="<?php
                                echo htmlspecialchars(
                                    $user['matric_no'] ?? ''
                                );
                                ?>" required>

                            </div>

                        </div>

                        <div class="form-group">

                            <label for="phone">
                                Phone Number
                                <span class="required-mark">*</span>
                            </label>

                            <div class="student-profile-input">

                                <i class="fa-solid fa-phone"></i>

                                <input type="text" id="phone" name="phone" value="<?php
                                echo htmlspecialchars(
                                    $user['phone'] ?? ''
                                );
                                ?>" placeholder="Example: 0123456789" required>

                            </div>

                        </div>

                        <div class="form-group form-full">

                            <label for="email">
                                Email Address
                                <span class="required-mark">*</span>
                            </label>

                            <div class="student-profile-input">

                                <i class="fa-regular fa-envelope"></i>

                                <input type="email" id="email" name="email" value="<?php
                                echo htmlspecialchars(
                                    $user['email']
                                );
                                ?>" required>

                            </div>

                        </div>

                        <div class="form-group form-full">

                            <label for="faculty">
                                Faculty
                            </label>

                            <div class="student-profile-input">

                                <i class="fa-solid fa-building-columns"></i>

                                <input type="text" id="faculty" name="faculty" value="<?php
                                echo htmlspecialchars(
                                    $user['faculty'] ?? ''
                                );
                                ?>" placeholder="Example: Faculty of Information Science">

                            </div>

                        </div>

                    </div>

                    <div class="student-password-section">

                        <div class="section-header">

                            <div>
                                <h2>Change Password</h2>

                                <p>
                                    Leave both fields blank to keep your current password.
                                </p>
                            </div>

                            <span class="student-profile-section-icon">
                                <i class="fa-solid fa-lock"></i>
                            </span>

                        </div>

                        <div class="form-grid">

                            <div class="form-group">

                                <label for="password">
                                    New Password
                                </label>

                                <div class="student-profile-input password-field">

                                    <i class="fa-solid fa-lock"></i>

                                    <input type="password" id="password" name="password"
                                        placeholder="Minimum 6 characters">

                                    <button type="button" class="student-password-toggle" data-target="password"
                                        aria-label="Show password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>

                                </div>

                            </div>

                            <div class="form-group">

                                <label for="confirm_password">
                                    Confirm New Password
                                </label>

                                <div class="student-profile-input password-field">

                                    <i class="fa-solid fa-lock"></i>

                                    <input type="password" id="confirm_password" name="confirm_password"
                                        placeholder="Enter the password again">

                                    <button type="button" class="student-password-toggle" data-target="confirm_password"
                                        aria-label="Show password">
                                        <i class="fa-regular fa-eye"></i>
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="student-profile-note">

                        <i class="fa-solid fa-circle-info"></i>

                        <p>
                            <?php if ($role === 'Club Leader') { ?>

                                Changes to your profile will be used for future proposals and account information.

                            <?php } else { ?>

                                Changes to your profile will be used for future event registrations.

                            <?php } ?>
                        </p>

                    </div>

                    <div class="student-profile-actions">

                        <button type="submit" name="update_profile" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Save Changes
                        </button>

                        <a href="dashboard.php" class="btn-secondary">
                            <i class="fa-solid fa-xmark"></i>
                            Cancel
                        </a>

                    </div>

                </form>

            </div>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

    <script>
        document.querySelectorAll(
            ".student-password-toggle"
        ).forEach(function (button) {
            button.addEventListener(
                "click",
                function () {
                    const targetId =
                        this.getAttribute("data-target");

                    const input =
                        document.getElementById(targetId);

                    const icon =
                        this.querySelector("i");

                    if (!input || !icon) {
                        return;
                    }

                    const isHidden =
                        input.type === "password";

                    input.type =
                        isHidden ? "text" : "password";

                    icon.className = isHidden
                        ? "fa-regular fa-eye-slash"
                        : "fa-regular fa-eye";

                    this.setAttribute(
                        "aria-label",
                        isHidden
                            ? "Hide password"
                            : "Show password"
                    );
                }
            );
        });

        window.setTimeout(function () {
            const toast =
                document.getElementById("successMsg");

            if (!toast) {
                return;
            }

            toast.classList.add("toast-hide");

            window.setTimeout(function () {
                toast.remove();
            }, 400);
        }, 3000);
    </script>

</body>

</html>