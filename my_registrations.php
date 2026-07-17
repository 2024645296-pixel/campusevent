<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'Student') {
    header("Location: dashboard.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

/* Get registrations */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        registrations.*,
        events.event_name,
        events.event_date,
        events.event_time,
        events.location,
        events.status AS event_status,
        events.event_fee
     FROM registrations
     INNER JOIN events
        ON registrations.event_id = events.event_id
     WHERE registrations.user_id = ?
     ORDER BY registrations.registration_id DESC"
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

$registrations = [];

while ($registration = mysqli_fetch_assoc($result)) {
    $registrations[] = $registration;
}

mysqli_stmt_close($stmt);

/* Summary statistics */
$totalRegistrations = count($registrations);
$pendingPayments = 0;
$totalAttended = 0;
$totalMerit = 0;

foreach ($registrations as $registration) {
    if (
        $registration['payment_status'] ===
        'Pending Verification'
    ) {
        $pendingPayments++;
    }

    if (
        $registration['attendance_status'] ===
        'Attended'
    ) {
        $totalAttended++;
        $totalMerit += 10;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Registrations | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=91000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main my-registrations-page">

        <div class="page-header">

            <div>
                <h1>My Registrations</h1>

                <p>
                    View your registered events, payment status,
                    attendance and merit points.
                </p>
            </div>

            <a href="events.php" class="btn-primary">
                <i class="fa-solid fa-calendar-plus"></i>
                Browse Events
            </a>

        </div>

        <div class="my-registration-summary">

            <div class="my-registration-summary-card">

                <div class="my-registration-summary-icon blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>

                <div>
                    <h2><?php echo $totalRegistrations; ?></h2>
                    <p>Total Registrations</p>
                </div>

            </div>

            <div class="my-registration-summary-card">

                <div class="my-registration-summary-icon orange">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>

                <div>
                    <h2><?php echo $pendingPayments; ?></h2>
                    <p>Pending Payments</p>
                </div>

            </div>

            <div class="my-registration-summary-card">

                <div class="my-registration-summary-icon green">
                    <i class="fa-solid fa-user-check"></i>
                </div>

                <div>
                    <h2><?php echo $totalAttended; ?></h2>
                    <p>Events Attended</p>
                </div>

            </div>

            <div class="my-registration-summary-card">

                <div class="my-registration-summary-icon purple">
                    <i class="fa-solid fa-star"></i>
                </div>

                <div>
                    <h2><?php echo $totalMerit; ?></h2>
                    <p>Total Merit Points</p>
                </div>

            </div>

        </div>

        <div class="card my-registrations-card">

            <div class="section-header">

                <div>
                    <h2>Registration List</h2>

                    <p>
                        Check payment, receipt, attendance and merit information.
                    </p>
                </div>

            </div>

            <div class="table-responsive">

                <table class="my-registrations-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Payment</th>
                            <th>Receipt</th>
                            <th>Attendance</th>
                            <th>Merit</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalRegistrations > 0) { ?>

                            <?php
                            $no = 1;

                            foreach ($registrations as $row) {
                                $paymentClass = strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $row['payment_status']
                                    )
                                );

                                $attendanceClass = strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $row['attendance_status']
                                    )
                                );

                                $merit =
                                    $row['attendance_status'] === 'Attended'
                                    ? 10
                                    : 0;
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>

                                        <div class="my-registration-event">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['event_name']
                                            );
                                            ?>
                                        </div>

                                        <small class="my-registration-event-status">

                                            <?php
                                            echo htmlspecialchars(
                                                $row['event_status']
                                            );
                                            ?>

                                        </small>

                                    </td>

                                    <td class="my-registration-date">

                                        <strong>
                                            <?php
                                            echo date(
                                                "d M Y",
                                                strtotime(
                                                    $row['event_date']
                                                )
                                            );
                                            ?>
                                        </strong>

                                        <small>
                                            <?php
                                            echo date(
                                                "h:i A",
                                                strtotime(
                                                    $row['event_time']
                                                )
                                            );
                                            ?>
                                        </small>

                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['location']
                                        );
                                        ?>
                                    </td>

                                    <td>

                                        <span class="badge badge-<?php
                                        echo htmlspecialchars(
                                            $paymentClass
                                        );
                                        ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['payment_status']
                                            );
                                            ?>
                                        </span>

                                        <small class="my-registration-payment-method">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['payment_method']
                                            );
                                            ?>
                                        </small>

                                    </td>

                                    <td>

                                        <?php if (!empty($row['receipt'])) { ?>

                                            <a class="btn-action btn-view-receipt" href="assets/receipts/<?php
                                            echo rawurlencode(
                                                $row['receipt']
                                            );
                                            ?>" target="_blank">
                                                <i class="fa-regular fa-file"></i>
                                                View
                                            </a>

                                        <?php } else { ?>

                                            <span class="no-receipt">
                                                No receipt
                                            </span>

                                        <?php } ?>

                                    </td>

                                    <td>

                                        <span class="attendance-badge attendance-<?php
                                        echo htmlspecialchars(
                                            $attendanceClass
                                        );
                                        ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['attendance_status']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                    <td>

                                        <span class="merit-badge">

                                            <i class="fa-solid fa-star"></i>

                                            <?php echo $merit; ?>

                                        </span>

                                    </td>

                                    <td>

                                        <a href="event_detail.php?id=<?php
                                        echo (int) $row['event_id'];
                                        ?>" class="btn-action btn-view">
                                            <i class="fa-regular fa-eye"></i>
                                            View
                                        </a>

                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="9">

                                    <div class="empty-state">

                                        <div class="empty-state-icon">
                                            <i class="fa-regular fa-calendar-xmark"></i>
                                        </div>

                                        <h3>No registrations yet</h3>

                                        <p>
                                            Browse available events and register for your first activity.
                                        </p>

                                        <a href="events.php" class="btn-primary">
                                            Browse Events
                                        </a>

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
                    registration<?php
                    echo $totalRegistrations !== 1
                        ? 's'
                        : '';
                    ?>

                </div>

            <?php } ?>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

</body>

</html>