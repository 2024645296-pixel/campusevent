<?php
session_start();
include 'includes/db.php';

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    $safeEmail = mysqli_real_escape_string($conn, $email);
    $safePassword = mysqli_real_escape_string($conn, $password);
    $safeRole = mysqli_real_escape_string($conn, $role);

    $query = mysqli_query($conn, "
        SELECT *
        FROM users
        WHERE email = '$safeEmail'
        AND password = '$safePassword'
        AND role = '$safeRole'
        LIMIT 1
    ");

    if (!$query) {
        die("Login query failed: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email, password or selected role.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=350">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="login-body">

    <div class="login-card">

        <div class="login-left">

            <div class="login-brand">

                <img src="assets/images/logo uitm.png" class="login-uitm-logo" alt="UiTM Logo">

                <div>
                    <h2>Campus<span>Event</span></h2>
                    <p>Student Club Management System</p>
                </div>

            </div>

            <div class="login-left-content">

                <div class="login-badge">
                    <i class="fa-solid fa-calendar-check"></i>
                    Campus Management Platform
                </div>

                <h1>
                    Student Club Event & Proposal Management System
                </h1>

                <p>
                    Manage club proposals, events, participant registrations
                    and reports in one secure and organized platform.
                </p>

                <div class="login-features">

                    <div>
                        <i class="fa-solid fa-circle-check"></i>
                        Proposal Management
                    </div>

                    <div>
                        <i class="fa-solid fa-circle-check"></i>
                        Event Registration
                    </div>

                    <div>
                        <i class="fa-solid fa-circle-check"></i>
                        Report Monitoring
                    </div>

                </div>

            </div>

        </div>

        <div class="login-right">

            <div class="login-heading">

                <span class="login-welcome-icon">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </span>

                <h2>Welcome Back</h2>

                <p class="login-subtitle">
                    Sign in to access the CampusEvent Management System.
                </p>

            </div>

            <?php if (isset($error)) { ?>

                <div class="error login-error">
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php } ?>

            <form method="POST" class="login-form">

                <div class="form-group">

                    <label for="role">
                        Login As
                    </label>

                    <div class="login-field">

                        <i class="fa-solid fa-user-shield"></i>

                        <select id="role" name="role" required>
                            <option value="">
                                Select Role
                            </option>

                            <option value="Admin" <?php
                            echo isset($role) && $role === 'Admin'
                                ? 'selected'
                                : '';
                            ?>>
                                Admin
                            </option>

                            <option value="Club Leader" <?php
                            echo isset($role) && $role === 'Club Leader'
                                ? 'selected'
                                : '';
                            ?>>
                                Club Leader
                            </option>

                            <option value="Student" <?php
                            echo isset($role) && $role === 'Student'
                                ? 'selected'
                                : '';
                            ?>>
                                Student
                            </option>
                        </select>

                    </div>

                </div>

                <div class="form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="login-field">

                        <i class="fa-regular fa-envelope"></i>

                        <input type="email" id="email" name="email" placeholder="example@student.uitm.edu.my" value="<?php
                        echo isset($email)
                            ? htmlspecialchars($email)
                            : '';
                        ?>" required>

                    </div>

                </div>

                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="login-field password-field">

                        <i class="fa-solid fa-lock"></i>

                        <input type="password" id="password" name="password" placeholder="Enter your password" required>

                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
                            <i class="fa-regular fa-eye" id="passwordIcon"></i>
                        </button>

                    </div>

                </div>

                <button type="submit" name="login" class="login-btn">
                    <span>Login</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>

            </form>

            <div class="login-help">
                <p>
                    Need an account?
                    <a href="register.php">Register as Student</a>
                </p>
            </div>

        </div>

    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const passwordToggle = document.getElementById("passwordToggle");
        const passwordIcon = document.getElementById("passwordIcon");

        passwordToggle.addEventListener("click", function () {
            const isPassword = passwordInput.type === "password";

            passwordInput.type = isPassword ? "text" : "password";

            passwordIcon.classList.toggle("fa-eye", !isPassword);
            passwordIcon.classList.toggle("fa-eye-slash", isPassword);

            passwordToggle.setAttribute(
                "aria-label",
                isPassword ? "Hide password" : "Show password"
            );
        });
    </script>

</body>

</html>