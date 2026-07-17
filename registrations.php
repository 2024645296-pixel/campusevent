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

/* UPDATE REGISTRATION */
if (isset($_POST['update'])) {
    $registrationId = (int) $_POST['registration_id'];

    $allowedPayment = [
        'Pending Verification',
        'Paid',
        'Failed'
    ];

    $allowedAttendance = [
        'Registered',
        'Attended',
        'Absent'
    ];

    $paymentStatus = $_POST['payment_status'] ?? '';
    $attendanceStatus = $_POST['attendance_status'] ?? '';

    if (
        in_array($paymentStatus, $allowedPayment, true) &&
        in_array($attendanceStatus, $allowedAttendance, true)
    ) {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE registrations
             SET payment_status = ?, attendance_status = ?
             WHERE registration_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "ssi",
            $paymentStatus,
            $attendanceStatus,
            $registrationId
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        header("Location: registrations.php?success=updated");
        exit();
    }
}

/* GET REGISTRATIONS */
$registrations = mysqli_query($conn, "
    SELECT registrations.*, events.event_name
    FROM registrations
    INNER JOIN events
        ON registrations.event_id = events.event_id
    ORDER BY registrations.registration_id DESC
");

if (!$registrations) {
    die("Database query failed: " . mysqli_error($conn));
}

$totalRegistrations = mysqli_num_rows($registrations);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Event Registrations | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=160">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main">

        <div class="page-header">

            <div>
                <h1>Event Registrations</h1>

                <p>
                    View participants, verify payments and update attendance status.
                </p>
            </div>

            <div class="page-summary">
                <span><?php echo $totalRegistrations; ?></span>

                <small>
                    Participant<?php echo $totalRegistrations !== 1 ? 's' : ''; ?>
                </small>
            </div>

        </div>

        <?php if (isset($_GET['success'])) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Registration status updated successfully.
            </div>

        <?php } ?>

        <div class="card registrations-card">

            <div class="section-header">

                <div>
                    <h2>Participant List</h2>

                    <p>
                        Manage payment verification and attendance for every participant.
                    </p>
                </div>

            </div>

            <div class="table-responsive">

                <table class="registrations-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Event</th>
                            <th>Participant</th>
                            <th>Matric No.</th>
                            <th>Contact</th>
                            <th>Payment</th>
                            <th>Receipt</th>
                            <th>Attendance</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalRegistrations > 0) { ?>

                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($registrations)) {
                                $formId = "updateForm" . (int) $row['registration_id'];
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>
                                        <div class="registration-event">
                                            <?php echo htmlspecialchars($row['event_name']); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="participant-info">

                                            <strong>
                                                <?php echo htmlspecialchars($row['full_name']); ?>
                                            </strong>

                                            <small>
                                                <?php echo htmlspecialchars($row['email']); ?>
                                            </small>

                                        </div>
                                    </td>

                                    <td class="nowrap-cell">
                                        <?php echo htmlspecialchars($row['matric_no']); ?>
                                    </td>

                                    <td class="contact-cell">
                                        <?php echo htmlspecialchars($row['phone']); ?>
                                    </td>

                                    <td>
                                        <div class="status-control">

                                            <select name="payment_status" form="<?php echo $formId; ?>" required>
                                                <option value="Pending Verification" <?php
                                                echo $row['payment_status'] ===
                                                    'Pending Verification'
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                    Pending
                                                </option>

                                                <option value="Paid" <?php
                                                echo $row['payment_status'] === 'Paid'
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                    Paid
                                                </option>

                                                <option value="Failed" <?php
                                                echo $row['payment_status'] === 'Failed'
                                                    ? 'selected'
                                                    : '';
                                                ?>>
                                                    Failed
                                                </option>
                                            </select>

                                            <small>
                                                <?php
                                                echo !empty($row['payment_method'])
                                                    ? htmlspecialchars($row['payment_method'])
                                                    : 'Not stated';
                                                ?>
                                            </small>

                                        </div>
                                    </td>

                                    <td>

                                        <?php if (!empty($row['receipt'])) { ?>

                                            <a class="btn-receipt" href="assets/receipts/<?php
                                            echo rawurlencode($row['receipt']);
                                            ?>" target="_blank" rel="noopener">
                                                <i class="fa-regular fa-file-lines"></i>
                                                View
                                            </a>

                                        <?php } else { ?>

                                            <span class="no-receipt">
                                                No receipt
                                            </span>

                                        <?php } ?>

                                    </td>

                                    <td>

                                        <select class="attendance-select" name="attendance_status" form="<?php echo $formId; ?>"
                                            required>
                                            <option value="Registered" <?php
                                            echo $row['attendance_status'] === 'Registered'
                                                ? 'selected'
                                                : '';
                                            ?>>
                                                Registered
                                            </option>

                                            <option value="Attended" <?php
                                            echo $row['attendance_status'] === 'Attended'
                                                ? 'selected'
                                                : '';
                                            ?>>
                                                Attended
                                            </option>

                                            <option value="Absent" <?php
                                            echo $row['attendance_status'] === 'Absent'
                                                ? 'selected'
                                                : '';
                                            ?>>
                                                Absent
                                            </option>
                                        </select>

                                    </td>

                                    <td>

                                        <form id="<?php echo $formId; ?>" method="POST" class="registration-update-form">
                                            <input type="hidden" name="registration_id" value="<?php
                                            echo (int) $row['registration_id'];
                                            ?>">

                                            <button type="submit" name="update" class="btn-update">
                                                <i class="fa-solid fa-check"></i>
                                                Update
                                            </button>
                                        </form>

                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="9">

                                    <div class="empty-state">

                                        <div class="empty-state-icon">
                                            <i class="fa-regular fa-user"></i>
                                        </div>

                                        <h3>No registrations found</h3>

                                        <p>
                                            Participant registrations will appear here.
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalRegistrations > 0) { ?>

                <div class="table-footer">
                    Showing <?php echo $totalRegistrations; ?>
                    participant<?php echo $totalRegistrations !== 1 ? 's' : ''; ?>
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