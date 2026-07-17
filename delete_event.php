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

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php?error=invalid");
    exit();
}

$eventId = (int) $_GET['id'];

/* Get event and poster */
$eventStmt = mysqli_prepare(
    $conn,
    "SELECT event_id, event_name, poster
     FROM events
     WHERE event_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $eventStmt,
    "i",
    $eventId
);

mysqli_stmt_execute($eventStmt);

$eventResult = mysqli_stmt_get_result($eventStmt);
$event = mysqli_fetch_assoc($eventResult);

mysqli_stmt_close($eventStmt);

if (!$event) {
    header("Location: events.php?error=notfound");
    exit();
}

mysqli_begin_transaction($conn);

try {
    /*
       Delete registrations first because they are
       connected to the selected event.
    */
    $registrationStmt = mysqli_prepare(
        $conn,
        "DELETE FROM registrations
         WHERE event_id = ?"
    );

    mysqli_stmt_bind_param(
        $registrationStmt,
        "i",
        $eventId
    );

    if (!mysqli_stmt_execute($registrationStmt)) {
        throw new Exception(
            mysqli_stmt_error($registrationStmt)
        );
    }

    mysqli_stmt_close($registrationStmt);

    /* Delete event */
    $deleteStmt = mysqli_prepare(
        $conn,
        "DELETE FROM events
         WHERE event_id = ?"
    );

    mysqli_stmt_bind_param(
        $deleteStmt,
        "i",
        $eventId
    );

    if (!mysqli_stmt_execute($deleteStmt)) {
        throw new Exception(
            mysqli_stmt_error($deleteStmt)
        );
    }

    mysqli_stmt_close($deleteStmt);

    mysqli_commit($conn);

    /* Delete poster file after database deletion */
    if (!empty($event['poster'])) {
        $posterPath =
            __DIR__ . '/assets/posters/' . $event['poster'];

        if (is_file($posterPath)) {
            unlink($posterPath);
        }
    }

    header("Location: events.php?success=deleted");
    exit();

} catch (Throwable $exception) {
    mysqli_rollback($conn);

    header("Location: events.php?error=delete");
    exit();
}
?>