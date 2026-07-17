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

/* Validate registration ID */
if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    header("Location: my_registrations.php?error=invalid");
    exit();
}

$registrationId = (int) $_GET['id'];

/* Get registration and event information */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        registrations.*,
        events.event_name,
        events.club_name,
        events.event_date,
        events.event_time,
        events.location,
        events.description,
        events.poster,
        events.event_fee,
        events.status AS event_status
     FROM registrations
     INNER JOIN events
        ON registrations.event_id = events.event_id
     WHERE registrations.registration_id = ?
     AND registrations.user_id = ?
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
    "ii",
    $registrationId,
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$registration = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$registration) {
    header("Location: my_registrations.php?error=notfound");
    exit();
}

/* Status classes */
$paymentClass = strtolower(
    str_replace(
        ' ',
        '-',
        $registration['payment_status']
    )
);

$attendanceClass = strtolower(
    str_replace(
        ' ',
        '-',
        $registration['attendance_status']
    )
);

$eventStatusClass = strtolower(
    str_replace(
        ' ',
        '-',
        $registration['event_status']
    )
);

/* Merit calculation */
$meritPoints =
    $registration['attendance_status'] === 'Attended'
    ? 10
    : 0;

/* Poster */
$posterPath = '';

if (!empty($registration['poster'])) {
    $posterPath =
        'assets/posters/' .
        rawurlencode($registration['poster']);
}

/* Receipt */
$receiptPath = '';

if (!empty($registration['receipt'])) {
    $receiptPath =
        'assets/receipts/' .
        rawurlencode($registration['receipt']);
}

$eventFee = (float) (
    $registration['event_fee'] ?? 0
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Registration Details | CampusEvent
    </title>

    <link rel="stylesheet" href="assets/css/style.css?v=92000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main registration-detail-page">

        <div class="detail-hero">

            <div>

                <a href="my_registrations.php" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to My Registrations
                </a>

                <h1>Registration Details</h1>

                <p>
                    Review your event registration, payment and attendance information.
                </p>

            </div>

            <span class="attendance-badge attendance-<?php
            echo htmlspecialchars($attendanceClass);
            ?>">
                <?php
                echo htmlspecialchars(
                    $registration['attendance_status']
                );
                ?>
            </span>

        </div>

        <div class="registration-detail-layout">

            <aside class="card registration-event-card">

                <?php if ($posterPath !== '') { ?>

                    <img src="<?php echo $posterPath; ?>" class="registration-detail-poster" alt="<?php
                       echo htmlspecialchars(
                           $registration['event_name']
                       );
                       ?>">

                <?php } else { ?>

                    <div class="registration-detail-poster-empty">

                        <i class="fa-regular fa-image"></i>

                        <span>No poster uploaded</span>

                    </div>

                <?php } ?>

                <div class="registration-event-body">

                    <span class="badge badge-<?php
                    echo htmlspecialchars($eventStatusClass);
                    ?>">
                        <?php
                        echo htmlspecialchars(
                            $registration['event_status']
                        );
                        ?>
                    </span>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $registration['event_name']
                        );
                        ?>
                    </h2>

                    <?php if (
                        !empty($registration['club_name'])
                    ) { ?>

                        <p class="registration-organizer">

                            <i class="fa-solid fa-users"></i>

                            <?php
                            echo htmlspecialchars(
                                $registration['club_name']
                            );
                            ?>

                        </p>

                    <?php } ?>

                    <div class="registration-event-meta">

                        <div>
                            <i class="fa-regular fa-calendar"></i>

                            <span>
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $registration['event_date']
                                    )
                                );
                                ?>
                            </span>
                        </div>

                        <div>
                            <i class="fa-regular fa-clock"></i>

                            <span>
                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $registration['event_time']
                                    )
                                );
                                ?>
                            </span>
                        </div>

                        <div>
                            <i class="fa-solid fa-location-dot"></i>

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $registration['location']
                                );
                                ?>
                            </span>
                        </div>

                        <div>
                            <i class="fa-solid fa-ticket"></i>

                            <span>
                                <?php
                                echo $eventFee > 0
                                    ? 'RM ' . number_format(
                                        $eventFee,
                                        2
                                    )
                                    : 'Free Event';
                                ?>
                            </span>
                        </div>

                    </div>

                    <a href="event_detail.php?id=<?php
                    echo (int) $registration['event_id'];
                    ?>" class="btn-secondary registration-view-event">
                        <i class="fa-regular fa-eye"></i>
                        View Event
                    </a>

                </div>

            </aside>

            <div class="registration-detail-content">

                <div class="card registration-detail-card">

                    <div class="section-header">

                        <div>
                            <h2>Student Information</h2>

                            <p>
                                Personal details submitted during registration.
                            </p>
                        </div>

                        <span class="registration-section-icon">
                            <i class="fa-regular fa-id-card"></i>
                        </span>

                    </div>

                    <div class="registration-info-grid">

                        <div class="registration-info-item">

                            <span>Full Name</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $registration['full_name']
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="registration-info-item">

                            <span>Matric Number</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $registration['matric_no']
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="registration-info-item">

                            <span>Email Address</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $registration['email']
                                );
                                ?>
                            </strong>

                        </div>

                        <div class="registration-info-item">

                            <span>Phone Number</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $registration['phone']
                                );
                                ?>
                            </strong>

                        </div>

                    </div>

                </div>

                <div class="card registration-detail-card">

                    <div class="section-header">

                        <div>
                            <h2>Payment Information</h2>

                            <p>
                                Payment method, verification status and receipt.
                            </p>
                        </div>

                        <span class="registration-section-icon">
                            <i class="fa-solid fa-credit-card"></i>
                        </span>

                    </div>

                    <div class="registration-payment-layout">

                        <div class="registration-payment-details">

                            <div class="registration-info-item">

                                <span>Event Fee</span>

                                <strong>
                                    <?php
                                    echo $eventFee > 0
                                        ? 'RM ' . number_format(
                                            $eventFee,
                                            2
                                        )
                                        : 'Free';
                                    ?>
                                </strong>

                            </div>

                            <div class="registration-info-item">

                                <span>Payment Method</span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $registration['payment_method']
                                    );
                                    ?>
                                </strong>

                            </div>

                            <div class="registration-info-item">

                                <span>Payment Status</span>

                                <span class="badge badge-<?php
                                echo htmlspecialchars($paymentClass);
                                ?>">
                                    <?php
                                    echo htmlspecialchars(
                                        $registration['payment_status']
                                    );
                                    ?>
                                </span>

                            </div>

                        </div>

                        <div class="registration-receipt-box">

                            <span>Payment Receipt</span>

                            <?php if ($receiptPath !== '') { ?>

                                <a href="<?php echo $receiptPath; ?>" target="_blank" class="registration-receipt-button">
                                    <i class="fa-regular fa-file-lines"></i>

                                    <div>
                                        <strong>View Receipt</strong>

                                        <small>
                                            Open uploaded payment proof
                                        </small>
                                    </div>
                                </a>

                            <?php } else { ?>

                                <div class="registration-no-receipt">

                                    <i class="fa-regular fa-file-excel"></i>

                                    <span>
                                        No receipt required or uploaded.
                                    </span>

                                </div>

                            <?php } ?>

                        </div>

                    </div>

                </div>

                <div class="card registration-detail-card">

                    <div class="section-header">

                        <div>
                            <h2>Attendance & Merit</h2>

                            <p>
                                Attendance confirmation and merit points earned.
                            </p>
                        </div>

                        <span class="registration-section-icon">
                            <i class="fa-solid fa-award"></i>
                        </span>

                    </div>

                    <div class="attendance-merit-grid">

                        <div class="attendance-result-card">

                            <span>Attendance Status</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $registration['attendance_status']
                                );
                                ?>
                            </strong>

                            <div class="attendance-result-icon attendance-<?php
                            echo htmlspecialchars($attendanceClass);
                            ?>">
                                <?php if (
                                    $registration['attendance_status'] ===
                                    'Attended'
                                ) { ?>

                                    <i class="fa-solid fa-circle-check"></i>

                                <?php } elseif (
                                    $registration['attendance_status'] ===
                                    'Absent'
                                ) { ?>

                                    <i class="fa-solid fa-circle-xmark"></i>

                                <?php } else { ?>

                                    <i class="fa-solid fa-clock"></i>

                                <?php } ?>
                            </div>

                        </div>

                        <div class="merit-result-card">

                            <span>Merit Points Earned</span>

                            <strong>
                                <?php echo $meritPoints; ?>
                            </strong>

                            <p>
                                <?php if ($meritPoints > 0) { ?>

                                    Merit points awarded for attending the event.

                                <?php } else { ?>

                                    Merit points will be awarded after attendance is confirmed.

                                <?php } ?>
                            </p>

                        </div>

                    </div>

                </div>

                <?php if (
                    !empty($registration['description'])
                ) { ?>

                    <div class="card registration-detail-card">

                        <div class="section-header">

                            <div>
                                <h2>Event Description</h2>

                                <p>
                                    Additional information about the event.
                                </p>
                            </div>

                        </div>

                        <div class="registration-description">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $registration['description']
                                )
                            );
                            ?>

                        </div>

                    </div>

                <?php } ?>

                <div class="registration-detail-actions">

                    <a href="my_registrations.php" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to My Registrations
                    </a>

                    <a href="event_detail.php?id=<?php
                    echo (int) $registration['event_id'];
                    ?>" class="btn-primary">
                        <i class="fa-regular fa-eye"></i>
                        View Event
                    </a>

                </div>

            </div>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

</body>

</html>