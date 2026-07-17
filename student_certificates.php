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

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        registrations.registration_id,
        registrations.event_id,
        registrations.attendance_status,
        registrations.payment_status,
        events.event_name,
        events.club_name,
        events.event_date,
        events.event_time,
        events.location,
        events.poster,
        events.status AS event_status
     FROM registrations
     INNER JOIN events
        ON registrations.event_id = events.event_id
     WHERE registrations.user_id = ?
     ORDER BY events.event_date DESC,
              registrations.registration_id DESC"
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

$certificates = [];

while ($row = mysqli_fetch_assoc($result)) {
    $certificates[] = $row;
}

mysqli_stmt_close($stmt);

$totalRegistrations = count($certificates);
$availableCertificates = 0;
$waitingCertificates = 0;
$unavailableCertificates = 0;

foreach ($certificates as $certificate) {
    if ($certificate['attendance_status'] === 'Attended') {
        $availableCertificates++;
    } elseif ($certificate['attendance_status'] === 'Absent') {
        $unavailableCertificates++;
    } else {
        $waitingCertificates++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Certificates | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=96000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main student-certificates-page">

        <div class="page-header">

            <div>
                <h1>Student Certificates</h1>

                <p>
                    View and generate certificates for events you have attended.
                </p>
            </div>

            <div class="page-summary">

                <span>
                    <?php echo $availableCertificates; ?>
                </span>

                <small>
                    Certificate
                    <?php
                    echo $availableCertificates !== 1
                        ? 's'
                        : '';
                    ?>
                </small>

            </div>

        </div>

        <?php if (
            isset($_GET['error']) &&
            $_GET['error'] === 'unavailable'
        ) { ?>

            <div class="error certificate-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                Certificate is only available after your attendance has been confirmed.

            </div>

        <?php } ?>

        <div class="certificate-summary-grid">

            <div class="certificate-summary-card">

                <div class="certificate-summary-icon blue">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>

                <div>
                    <h2>
                        <?php echo $totalRegistrations; ?>
                    </h2>
                    <p>Total Registered Events</p>
                </div>

            </div>

            <div class="certificate-summary-card">

                <div class="certificate-summary-icon green">
                    <i class="fa-solid fa-award"></i>
                </div>

                <div>
                    <h2>
                        <?php echo $availableCertificates; ?>
                    </h2>
                    <p>Available Certificates</p>
                </div>

            </div>

            <div class="certificate-summary-card">

                <div class="certificate-summary-icon orange">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>

                <div>
                    <h2>
                        <?php echo $waitingCertificates; ?>
                    </h2>
                    <p>Waiting Attendance</p>
                </div>

            </div>

            <div class="certificate-summary-card">

                <div class="certificate-summary-icon red">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <div>
                    <h2>
                        <?php echo $unavailableCertificates; ?>
                    </h2>
                    <p>Unavailable</p>
                </div>

            </div>

        </div>

        <div class="card certificates-card">

            <div class="section-header">

                <div>
                    <h2>Certificate List</h2>

                    <p>
                        Certificates become available after attendance is marked as Attended.
                    </p>
                </div>

            </div>

            <?php if ($totalRegistrations > 0) { ?>

                <div class="certificate-list">

                    <?php foreach ($certificates as $row) { ?>

                        <?php
                        $isAvailable =
                            $row['attendance_status'] === 'Attended';

                        $isAbsent =
                            $row['attendance_status'] === 'Absent';

                        if ($isAvailable) {
                            $certificateState = 'available';
                            $certificateLabel = 'Available';
                            $certificateIcon = 'fa-circle-check';
                        } elseif ($isAbsent) {
                            $certificateState = 'unavailable';
                            $certificateLabel = 'Unavailable';
                            $certificateIcon = 'fa-circle-xmark';
                        } else {
                            $certificateState = 'waiting';
                            $certificateLabel = 'Waiting Attendance';
                            $certificateIcon = 'fa-hourglass-half';
                        }
                        ?>

                        <div class="certificate-item">

                            <div class="certificate-item-poster">

                                <?php if (!empty($row['poster'])) { ?>

                                    <img src="assets/posters/<?php
                                    echo rawurlencode($row['poster']);
                                    ?>" alt="<?php
                                    echo htmlspecialchars(
                                        $row['event_name']
                                    );
                                    ?>">

                                <?php } else { ?>

                                    <div class="certificate-poster-empty">

                                        <i class="fa-regular fa-image"></i>

                                    </div>

                                <?php } ?>

                            </div>

                            <div class="certificate-item-content">

                                <div class="certificate-item-title">

                                    <div>

                                        <span class="certificate-event-label">
                                            Event Certificate
                                        </span>

                                        <h3>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['event_name']
                                            );
                                            ?>
                                        </h3>

                                    </div>

                                    <span class="certificate-state certificate-<?php
                                    echo $certificateState;
                                    ?>">
                                        <i class="fa-solid <?php
                                        echo $certificateIcon;
                                        ?>"></i>

                                        <?php echo $certificateLabel; ?>
                                    </span>

                                </div>

                                <div class="certificate-event-details">

                                    <span>
                                        <i class="fa-solid fa-users"></i>

                                        <?php
                                        echo !empty($row['club_name'])
                                            ? htmlspecialchars(
                                                $row['club_name']
                                            )
                                            : 'CampusEvent';
                                        ?>
                                    </span>

                                    <span>
                                        <i class="fa-regular fa-calendar"></i>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime(
                                                $row['event_date']
                                            )
                                        );
                                        ?>
                                    </span>

                                    <span>
                                        <i class="fa-solid fa-location-dot"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $row['location']
                                        );
                                        ?>
                                    </span>

                                    <span>
                                        <i class="fa-solid fa-star"></i>
                                        10 Merit Points
                                    </span>

                                </div>

                                <div class="certificate-item-actions">

                                    <?php if ($isAvailable) { ?>

                                        <a href="certificate.php?id=<?php
                                        echo (int) $row['registration_id'];
                                        ?>" target="_blank" class="btn-primary">
                                            <i class="fa-solid fa-award"></i>
                                            View Certificate
                                        </a>

                                    <?php } elseif ($isAbsent) { ?>

                                        <span class="certificate-message unavailable">

                                            <i class="fa-solid fa-circle-xmark"></i>

                                            Certificate is unavailable because attendance was marked Absent.

                                        </span>

                                    <?php } else { ?>

                                        <span class="certificate-message waiting">

                                            <i class="fa-solid fa-clock"></i>

                                            Waiting for the administrator to confirm your attendance.

                                        </span>

                                    <?php } ?>

                                    <a href="registration_detail.php?id=<?php
                                    echo (int) $row['registration_id'];
                                    ?>" class="btn-secondary">
                                        <i class="fa-regular fa-eye"></i>
                                        Registration Details
                                    </a>

                                </div>

                            </div>

                        </div>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <div class="empty-state">

                    <div class="empty-state-icon">
                        <i class="fa-solid fa-award"></i>
                    </div>

                    <h3>No certificate records yet</h3>

                    <p>
                        Register and attend an event to receive a participation certificate.
                    </p>

                    <a href="events.php" class="btn-primary">
                        Browse Events
                    </a>

                </div>

            <?php } ?>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

</body>

</html>