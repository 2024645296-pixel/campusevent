<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id']) ||
    (int) $_GET['id'] < 1
) {
    header("Location: manage_proposals.php?error=invalid");
    exit();
}

$proposalId = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM program_proposals
     WHERE proposal_id = ?
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
    $proposalId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$proposal = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$proposal) {
    header("Location: manage_proposals.php?error=notfound");
    exit();
}


if ($proposal['status'] === 'Approved') {
    header(
        "Location: manage_proposals.php?error=alreadyapproved"
    );
    exit();
}


$eventName =
    $proposal['program_name'];

$eventDate =
    $proposal['proposal_date'];

$eventTime =
    $proposal['proposal_time'];

$location =
    $proposal['location'];

$description =
    $proposal['description'] !== ''
    ? $proposal['description']
    : $proposal['objective'];

$poster =
    $proposal['poster'] ?? '';

$eventFee =
    (float) ($proposal['event_fee'] ?? 0);

$eventStatus = 'Upcoming';


mysqli_begin_transaction($conn);

try {

    /* CHECK WHETHER EVENT ALREADY EXISTS */

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT event_id
         FROM events
         WHERE event_name = ?
           AND event_date = ?
         LIMIT 1"
    );

    if (!$checkStmt) {
        throw new Exception(
            "Unable to check the existing event."
        );
    }

    mysqli_stmt_bind_param(
        $checkStmt,
        "ss",
        $eventName,
        $eventDate
    );

    mysqli_stmt_execute($checkStmt);

    $checkResult =
        mysqli_stmt_get_result($checkStmt);

    $existingEvent =
        mysqli_fetch_assoc($checkResult);

    mysqli_stmt_close($checkStmt);

    /* CREATE EVENT IF IT DOES NOT EXIST */

    if (!$existingEvent) {

        $eventStmt = mysqli_prepare(
            $conn,
            "INSERT INTO events
            (
                event_name,
                event_date,
                event_time,
                location,
                description,
                poster,
                event_fee,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$eventStmt) {
            throw new Exception(
                "Event preparation failed: " .
                mysqli_error($conn)
            );
        }

        mysqli_stmt_bind_param(
            $eventStmt,
            "ssssssds",
            $eventName,
            $eventDate,
            $eventTime,
            $location,
            $description,
            $poster,
            $eventFee,
            $eventStatus
        );

        if (!mysqli_stmt_execute($eventStmt)) {
            throw new Exception(
                "Unable to create the event: " .
                mysqli_stmt_error($eventStmt)
            );
        }

        mysqli_stmt_close($eventStmt);
    }

    /* UPDATE PROPOSAL STATUS */

    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE program_proposals
         SET
            status = 'Approved',
            reject_reason = NULL,
            admin_remark = NULL
         WHERE proposal_id = ?"
    );

    if (!$updateStmt) {
        throw new Exception(
            "Proposal update preparation failed."
        );
    }

    mysqli_stmt_bind_param(
        $updateStmt,
        "i",
        $proposalId
    );

    if (!mysqli_stmt_execute($updateStmt)) {
        throw new Exception(
            "Unable to approve the proposal: " .
            mysqli_stmt_error($updateStmt)
        );
    }

    mysqli_stmt_close($updateStmt);

    mysqli_commit($conn);

    header(
        "Location: manage_proposals.php?success=approved"
    );
    exit();

} catch (Throwable $error) {

    mysqli_rollback($conn);

    die(
        "Approval failed: " .
        htmlspecialchars($error->getMessage())
    );
}
?>