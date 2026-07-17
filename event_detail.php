<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

/* Validate event ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php?error=invalid");
    exit();
}

$eventId = (int) $_GET['id'];

/* Get event */
$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM events
     WHERE event_id = ?
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
    $eventId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$event) {
    header("Location: events.php?error=notfound");
    exit();
}

/* Students should not access cancelled events */
if (
    $role === 'Student' &&
    $event['status'] === 'Cancelled'
) {
    header("Location: events.php?error=notfound");
    exit();
}

/* Check student registration */
$isRegistered = false;

if ($role === 'Student') {
    $registrationStmt = mysqli_prepare(
        $conn,
        "SELECT registration_id
         FROM registrations
         WHERE user_id = ?
         AND event_id = ?
         LIMIT 1"
    );

    if ($registrationStmt) {
        mysqli_stmt_bind_param(
            $registrationStmt,
            "ii",
            $userId,
            $eventId
        );

        mysqli_stmt_execute($registrationStmt);

        $registrationResult =
            mysqli_stmt_get_result($registrationStmt);

        $existingRegistration =
            mysqli_fetch_assoc($registrationResult);

        $isRegistered =
            !empty($existingRegistration);

        mysqli_stmt_close($registrationStmt);
    }
}

$statusClass = strtolower(
    str_replace(
        ' ',
        '-',
        $event['status']
    )
);

$posterPath = '';

if (!empty($event['poster'])) {
    $posterPath =
        'assets/posters/' .
        rawurlencode($event['poster']);
}

$today = date('Y-m-d');

$isExpired =
    !empty($event['event_date']) &&
    $event['event_date'] < $today;

$canRegister =
    $role === 'Student' &&
    $event['status'] === 'Upcoming' &&
    !$isExpired &&
    !$isRegistered;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php
        echo htmlspecialchars(
            $event['event_name']
        );
        ?>
        | CampusEvent
    </title>

    <link rel="stylesheet" href="assets/css/style.css?v=85000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main event-detail-page">

        <div class="detail-hero">

            <div>

                <a href="events.php" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Events
                </a>

                <h1>
                    <?php
                    echo htmlspecialchars(
                        $event['event_name']
                    );
                    ?>
                </h1>

                <p>
                    View complete information for this campus event.
                </p>

            </div>

            <span class="badge badge-<?php
            echo htmlspecialchars($statusClass);
            ?>">
                <?php
                echo htmlspecialchars(
                    $event['status']
                );
                ?>
            </span>

        </div>

        <div class="detail-layout">

            <div class="detail-poster-card">

                <?php if ($posterPath !== '') { ?>

                    <img src="<?php echo $posterPath; ?>" class="detail-poster" alt="<?php
                       echo htmlspecialchars(
                           $event['event_name']
                       );
                       ?>">

                <?php } else { ?>

                    <div class="no-poster">

                        <i class="fa-regular fa-image"></i>

                        <span>No poster uploaded</span>

                    </div>

                <?php } ?>

            </div>

            <div class="detail-info-card">

                <div class="detail-info-header">

                    <div>
                        <h2>Event Information</h2>

                        <p>
                            Important details about this event.
                        </p>
                    </div>

                </div>

                <div class="detail-info-grid">

                    <div class="detail-info-item">

                        <div class="detail-info-icon">
                            <i class="fa-regular fa-calendar"></i>
                        </div>

                        <div>
                            <span>Date</span>

                            <strong>
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime(
                                        $event['event_date']
                                    )
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                    <div class="detail-info-item">

                        <div class="detail-info-icon">
                            <i class="fa-regular fa-clock"></i>
                        </div>

                        <div>
                            <span>Time</span>

                            <strong>
                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime(
                                        $event['event_time']
                                    )
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                    <div class="detail-info-item">

                        <div class="detail-info-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>

                        <div>
                            <span>Venue</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $event['location']
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                    <?php if (!empty($event['club_name'])) { ?>

                        <div class="detail-info-item">

                            <div class="detail-info-icon">
                                <i class="fa-solid fa-users"></i>
                            </div>

                            <div>
                                <span>Organized By</span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $event['club_name']
                                    );
                                    ?>
                                </strong>
                            </div>

                        </div>

                    <?php } ?>

                    <?php if ($role === 'Admin') { ?>

                        <div class="detail-info-item">

                            <div class="detail-info-icon">
                                <i class="fa-solid fa-wallet"></i>
                            </div>

                            <div>
                                <span>Budget</span>

                                <strong>
                                    RM <?php
                                    echo number_format(
                                        (float) $event['budget'],
                                        2
                                    );
                                    ?>
                                </strong>
                            </div>

                        </div>

                    <?php } ?>

                    <?php if ($role === 'Student') { ?>

                        <div class="detail-info-item">

                            <div class="detail-info-icon">
                                <i class="fa-solid fa-ticket"></i>
                            </div>

                            <div>
                                <span>Registration Fee</span>

                                <strong>
                                    <?php
                                    echo isset($event['event_fee']) &&
                                        (float) $event['event_fee'] > 0
                                        ? 'RM ' . number_format(
                                            (float) $event['event_fee'],
                                            2
                                        )
                                        : 'Free';
                                    ?>
                                </strong>
                            </div>

                        </div>

                    <?php } ?>

                </div>

                <div class="detail-section">

                    <h3>Description</h3>

                    <p>
                        <?php
                        echo !empty($event['description'])
                            ? nl2br(
                                htmlspecialchars(
                                    $event['description']
                                )
                            )
                            : 'No description provided for this event.';
                        ?>
                    </p>

                </div>

                <?php if ($role === 'Student') { ?>

                    <div class="student-registration-box">

                        <h3>
                            <i class="fa-solid fa-clipboard-check"></i>
                            Registration Status
                        </h3>

                        <?php if ($isRegistered) { ?>

                            <div class="student-registration-status registered">

                                <i class="fa-solid fa-circle-check"></i>

                                <div>
                                    <strong>Already Registered</strong>

                                    <p>
                                        You have already registered for this event.
                                    </p>
                                </div>

                            </div>

                        <?php } elseif ($isExpired) { ?>

                            <div class="student-registration-status expired">

                                <i class="fa-solid fa-clock-rotate-left"></i>

                                <div>
                                    <strong>Registration Expired</strong>

                                    <p>
                                        The event date has already passed.
                                    </p>
                                </div>

                            </div>

                        <?php } elseif (
                            $event['status'] !== 'Upcoming'
                        ) { ?>

                            <div class="student-registration-status closed">

                                <i class="fa-solid fa-lock"></i>

                                <div>
                                    <strong>Registration Closed</strong>

                                    <p>
                                        Registration is not available for this event.
                                    </p>
                                </div>

                            </div>

                        <?php } else { ?>

                            <div class="student-registration-status available">

                                <i class="fa-solid fa-circle-info"></i>

                                <div>
                                    <strong>Registration Available</strong>

                                    <p>
                                        Complete the registration form to join this event.
                                    </p>
                                </div>

                            </div>

                        <?php } ?>

                    </div>

                <?php } ?>

                <div class="detail-actions">

                    <?php if ($canRegister) { ?>

                        <a href="register_event.php?id=<?php
                        echo $eventId;
                        ?>" class="btn-register-detail">
                            <i class="fa-solid fa-user-plus"></i>
                            Register Now
                        </a>

                    <?php } ?>

                    <?php if (
                        $role === 'Student' &&
                        $isRegistered
                    ) { ?>

                        <a href="my_registrations.php" class="btn-secondary">
                            <i class="fa-solid fa-clipboard-list"></i>
                            View My Registration
                        </a>

                    <?php } ?>

                    <?php if ($role === 'Admin') { ?>

                        <a href="edit_event.php?id=<?php
                        echo $eventId;
                        ?>" class="btn-edit-detail">
                            <i class="fa-solid fa-pen-to-square"></i>
                            Edit Event
                        </a>

                    <?php } ?>

                    <a href="events.php" class="btn-back-detail">
                        <i class="fa-solid fa-arrow-left"></i>
                        Back
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