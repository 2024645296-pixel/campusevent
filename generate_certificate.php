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
    !isset($_GET['registration_id']) ||
    !is_numeric($_GET['registration_id'])
) {
    header("Location: registrations.php?error=invalid");
    exit();
}

$registrationId = (int) $_GET['registration_id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        r.registration_id,
        r.attendance_status,
        r.certificate_status,
        e.event_id,
        e.event_date
     FROM registrations r
     INNER JOIN events e
        ON r.event_id = e.event_id
     WHERE r.registration_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $registrationId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$registration = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$registration) {
    header("Location: registrations.php?error=notfound");
    exit();
}

if ($registration['attendance_status'] !== 'Attended') {
    header("Location: registrations.php?error=notattended");
    exit();
}

if ($registration['certificate_status'] === 'Generated') {
    header(
        "Location: certificate.php?registration_id=" .
        $registrationId
    );
    exit();
}

$certificateNumber = sprintf(
    "CE-%s-E%04d-R%05d",
    date(
        "Y",
        strtotime($registration['event_date'])
    ),
    (int) $registration['event_id'],
    $registrationId
);

$updateStmt = mysqli_prepare(
    $conn,
    "UPDATE registrations
     SET
        certificate_status = 'Generated',
        certificate_number = ?,
        certificate_generated_at = NOW()
     WHERE registration_id = ?"
);

mysqli_stmt_bind_param(
    $updateStmt,
    "si",
    $certificateNumber,
    $registrationId
);

if (mysqli_stmt_execute($updateStmt)) {
    mysqli_stmt_close($updateStmt);

    header(
        "Location: certificate.php?registration_id=" .
        $registrationId
    );
    exit();
}

mysqli_stmt_close($updateStmt);

header("Location: registrations.php?error=generatefailed");
exit();