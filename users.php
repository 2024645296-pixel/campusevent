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

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : "";

$safeSearch = mysqli_real_escape_string(
    $conn,
    $search
);

if ($search !== "") {
    $users = mysqli_query(
        $conn,
        "
        SELECT *
        FROM users
        WHERE name LIKE '%$safeSearch%'
        OR matric_no LIKE '%$safeSearch%'
        OR email LIKE '%$safeSearch%'
        OR role LIKE '%$safeSearch%'
        OR faculty LIKE '%$safeSearch%'
        ORDER BY user_id DESC
        "
    );
} else {
    $users = mysqli_query(
        $conn,
        "SELECT * FROM users ORDER BY user_id DESC"
    );
}

if (!$users) {
    die(
        "Database query failed: " .
        mysqli_error($conn)
    );
}

$totalUsers = mysqli_num_rows($users);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=240">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main">

        <div class="page-header">

            <div>
                <h1>Users</h1>

                <p>
                    View all registered users and manage their accounts.
                </p>
            </div>

            <div class="page-summary">

                <span>
                    <?php echo $totalUsers; ?>
                </span>

                <small>
                    User<?php echo $totalUsers !== 1 ? 's' : ''; ?>
                </small>

            </div>

        </div>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'updated'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                User information updated successfully.
            </div>

        <?php } ?>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'deleted'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                User deleted successfully.
            </div>

        <?php } ?>

        <?php if (isset($_GET['error'])) { ?>

            <div class="error users-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php if ($_GET['error'] === 'self') { ?>

                    You cannot delete your own account.

                <?php } elseif ($_GET['error'] === 'lastadmin') { ?>

                    The final Admin account cannot be deleted.

                <?php } elseif ($_GET['error'] === 'related') { ?>

                    This user cannot be deleted because they have related proposals or registrations.

                <?php } elseif ($_GET['error'] === 'notfound') { ?>

                    The selected user was not found.

                <?php } elseif ($_GET['error'] === 'invalid') { ?>

                    Invalid user selected.

                <?php } else { ?>

                    Unable to complete the requested action.

                <?php } ?>

            </div>

        <?php } ?>

        <div class="card users-card">

            <div class="section-header">

                <div>
                    <h2>User List</h2>

                    <p>
                        Search users by name, matric number, email, role or faculty.
                    </p>
                </div>

            </div>

            <form method="GET" class="users-search-form">

                <div class="search-input-wrap">

                    <i class="fa-solid fa-magnifying-glass search-icon"></i>

                    <input type="text" name="search" placeholder="Search name, matric no, email, role or faculty..."
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search
                </button>

                <a href="users.php" class="btn-secondary">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>

            </form>

            <div class="table-responsive">

                <table class="users-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Matric No.</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Faculty</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalUsers > 0) { ?>

                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($users)) {
                                $roleClass = strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $row['role']
                                    )
                                );
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>

                                        <div class="user-name-cell">

                                            <div class="user-avatar">
                                                <?php
                                                echo strtoupper(
                                                    substr(
                                                        $row['name'],
                                                        0,
                                                        1
                                                    )
                                                );
                                                ?>
                                            </div>

                                            <span>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['name']
                                                );
                                                ?>
                                            </span>

                                        </div>

                                    </td>

                                    <td class="users-nowrap">
                                        <?php
                                        echo !empty($row['matric_no'])
                                            ? htmlspecialchars(
                                                $row['matric_no']
                                            )
                                            : '-';
                                        ?>
                                    </td>

                                    <td>

                                        <a class="user-email" href="mailto:<?php
                                        echo htmlspecialchars(
                                            $row['email']
                                        );
                                        ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['email']
                                            );
                                            ?>
                                        </a>

                                    </td>

                                    <td class="users-nowrap">
                                        <?php
                                        echo !empty($row['phone'])
                                            ? htmlspecialchars(
                                                $row['phone']
                                            )
                                            : '-';
                                        ?>
                                    </td>

                                    <td>

                                        <span class="role-badge role-<?php
                                        echo htmlspecialchars(
                                            $roleClass
                                        );
                                        ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['role']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                    <td>
                                        <?php
                                        echo !empty($row['faculty'])
                                            ? htmlspecialchars(
                                                $row['faculty']
                                            )
                                            : '-';
                                        ?>
                                    </td>

                                    <td>

                                        <div class="user-action-buttons">

                                            <a href="edit_user.php?id=<?php
                                            echo (int) $row['user_id'];
                                            ?>" class="btn-edit-user">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                                Edit
                                            </a>

                                            <?php if (
                                                (int) $row['user_id'] !==
                                                (int) $_SESSION['user_id']
                                            ) { ?>

                                                <a href="delete_user.php?id=<?php
                                                echo (int) $row['user_id'];
                                                ?>" class="btn-delete-user"
                                                    onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                                    <i class="fa-solid fa-trash"></i>
                                                    Delete
                                                </a>

                                            <?php } else { ?>

                                                <span class="current-user-label">
                                                    Current Account
                                                </span>

                                            <?php } ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="8">

                                    <div class="empty-state">

                                        <div class="empty-state-icon">
                                            <i class="fa-regular fa-user"></i>
                                        </div>

                                        <h3>No users found</h3>

                                        <p>
                                            Try another search keyword or reset the search.
                                        </p>

                                        <a href="users.php" class="btn-secondary">
                                            Reset Search
                                        </a>

                                    </div>

                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalUsers > 0) { ?>

                <div class="table-footer">

                    Showing <?php echo $totalUsers; ?>
                    user<?php echo $totalUsers !== 1 ? 's' : ''; ?>

                </div>

            <?php } ?>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

    <script>
        window.setTimeout(function () {
            const toast = document.getElementById("successMsg");

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