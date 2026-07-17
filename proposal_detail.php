<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

/* Only Admin and Club Leader may access this page */
if (!in_array($role, ['Admin', 'Club Leader'], true)) {
    header("Location: dashboard.php");
    exit();
}

/* Validate proposal ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $redirectPage = $role === 'Admin'
        ? 'manage_proposals.php'
        : 'my_proposals.php';

    header("Location: {$redirectPage}?error=invalid");
    exit();
}

$proposalId = (int) $_GET['id'];

/* Get proposal and related event */
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        program_proposals.*,
        events.event_id,
        events.poster AS event_poster
     FROM program_proposals
     LEFT JOIN events
        ON program_proposals.proposal_id = events.proposal_id
     WHERE program_proposals.proposal_id = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Database preparation failed: " . mysqli_error($conn));
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
    $redirectPage = $role === 'Admin'
        ? 'manage_proposals.php'
        : 'my_proposals.php';

    header("Location: {$redirectPage}?error=notfound");
    exit();
}

/* Club Leader can only view their own proposal */
if (
    $role === 'Club Leader' &&
    (int) $proposal['user_id'] !== $currentUserId
) {
    header("Location: my_proposals.php?error=unauthorized");
    exit();
}

$error = "";

/* =========================================
   ADMIN UPDATE PROPOSAL STATUS
========================================= */

if (
    isset($_POST['update_status']) &&
    $role === 'Admin'
) {
    $newStatus = trim($_POST['new_status'] ?? '');
    $rejectReason = trim($_POST['reject_reason'] ?? '');

    $allowedStatuses = [
        'Pending',
        'Approved',
        'Rejected'
    ];

    if (!in_array($newStatus, $allowedStatuses, true)) {
        $error = "Invalid proposal status selected.";
    } elseif (
        $newStatus === 'Rejected' &&
        $rejectReason === ''
    ) {
        $error = "Please provide a reason for rejection.";
    }

    if ($error === '') {
        mysqli_begin_transaction($conn);

        try {
            /* Update proposal status */
            if ($newStatus === 'Rejected') {
                $updateStmt = mysqli_prepare(
                    $conn,
                    "UPDATE program_proposals
                     SET status = ?,
                         reject_reason = ?
                     WHERE proposal_id = ?"
                );

                if (!$updateStmt) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param(
                    $updateStmt,
                    "ssi",
                    $newStatus,
                    $rejectReason,
                    $proposalId
                );
            } else {
                $updateStmt = mysqli_prepare(
                    $conn,
                    "UPDATE program_proposals
                     SET status = ?,
                         reject_reason = NULL
                     WHERE proposal_id = ?"
                );

                if (!$updateStmt) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param(
                    $updateStmt,
                    "si",
                    $newStatus,
                    $proposalId
                );
            }

            if (!mysqli_stmt_execute($updateStmt)) {
                throw new Exception(
                    mysqli_stmt_error($updateStmt)
                );
            }

            mysqli_stmt_close($updateStmt);

            /* Create event automatically after approval */
            if ($newStatus === 'Approved') {
                $checkStmt = mysqli_prepare(
                    $conn,
                    "SELECT event_id
                     FROM events
                     WHERE proposal_id = ?
                     LIMIT 1"
                );

                if (!$checkStmt) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param(
                    $checkStmt,
                    "i",
                    $proposalId
                );

                mysqli_stmt_execute($checkStmt);

                $checkResult =
                    mysqli_stmt_get_result($checkStmt);

                $existingEvent =
                    mysqli_fetch_assoc($checkResult);

                mysqli_stmt_close($checkStmt);

                if (!$existingEvent) {
                    $eventName =
                        $proposal['program_name'];

                    $clubName =
                        $proposal['club_name'];

                    $eventDate =
                        $proposal['proposal_date'];

                    $eventTime =
                        $proposal['proposal_time'];

                    $location =
                        $proposal['location'];

                    $description =
                        $proposal['description'];

                    $budget =
                        (float) $proposal['budget'];

                    $eventFee = 0.00;
                    $defaultStatus = 'Upcoming';

                    $poster =
                        $proposal['poster'] ?? '';

                    $insertStmt = mysqli_prepare(
                        $conn,
                        "INSERT INTO events
                        (
                            proposal_id,
                            event_name,
                            club_name,
                            event_date,
                            event_time,
                            location,
                            description,
                            budget,
                            event_fee,
                            status,
                            poster
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );

                    if (!$insertStmt) {
                        throw new Exception(
                            mysqli_error($conn)
                        );
                    }

                    mysqli_stmt_bind_param(
                        $insertStmt,
                        "issssssddss",
                        $proposalId,
                        $eventName,
                        $clubName,
                        $eventDate,
                        $eventTime,
                        $location,
                        $description,
                        $budget,
                        $eventFee,
                        $defaultStatus,
                        $poster
                    );

                    if (!mysqli_stmt_execute($insertStmt)) {
                        throw new Exception(
                            mysqli_stmt_error($insertStmt)
                        );
                    }

                    mysqli_stmt_close($insertStmt);
                }
            }

            mysqli_commit($conn);

            header(
                "Location: manage_proposals.php?success=updated"
            );
            exit();

        } catch (Throwable $exception) {
            mysqli_rollback($conn);

            $error = "Unable to update proposal: " .
                $exception->getMessage();
        }
    }

    /* Retain values if update fails */
    $proposal['status'] = $newStatus;

    $proposal['reject_reason'] =
        $newStatus === 'Rejected'
        ? $rejectReason
        : null;
}

/* =========================================
   DISPLAY INFORMATION
========================================= */

$proposalStatus = !empty($proposal['status'])
    ? $proposal['status']
    : 'Pending';

$statusClass = strtolower(
    str_replace(' ', '-', $proposalStatus)
);

/* Prefer event poster, then proposal poster */
$displayPoster = '';

if (!empty($proposal['event_poster'])) {
    $displayPoster = $proposal['event_poster'];
} elseif (!empty($proposal['poster'])) {
    $displayPoster = $proposal['poster'];
}

$backPage = $role === 'Admin'
    ? 'manage_proposals.php'
    : 'my_proposals.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Proposal Details | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=60000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main proposal-detail-page">

        <div class="detail-hero">

            <div>

                <a href="<?php echo $backPage; ?>" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Proposals
                </a>

                <h1>Proposal Details</h1>

                <p>
                    Review the submitted programme information and approval status.
                </p>

            </div>

            <span class="badge badge-<?php
            echo htmlspecialchars($statusClass);
            ?>">
                <?php echo htmlspecialchars($proposalStatus); ?>
            </span>

        </div>

        <?php if ($error !== '') { ?>

            <div class="error proposal-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>

        <div class="proposal-detail-layout">

            <div class="detail-poster-card">

                <?php if ($displayPoster !== '') { ?>

                    <img src="assets/posters/<?php
                    echo rawurlencode($displayPoster);
                    ?>" class="detail-poster" alt="<?php
                    echo htmlspecialchars(
                        $proposal['program_name']
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

                <div class="proposal-title-block">

                    <div>

                        <span class="proposal-label">
                            Programme Proposal
                        </span>

                        <h2>
                            <?php
                            echo htmlspecialchars(
                                $proposal['program_name']
                            );
                            ?>
                        </h2>

                    </div>

                    <span class="badge badge-<?php
                    echo htmlspecialchars($statusClass);
                    ?>">
                        <?php
                        echo htmlspecialchars($proposalStatus);
                        ?>
                    </span>

                </div>

                <div class="detail-info-grid">

                    <div class="detail-info-item">

                        <div class="detail-info-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>

                        <div>
                            <span>Club Name</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $proposal['club_name']
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                    <?php if (
                        !empty($proposal['person_in_charge'])
                    ) { ?>

                        <div class="detail-info-item">

                            <div class="detail-info-icon">
                                <i class="fa-solid fa-user-tie"></i>
                            </div>

                            <div>
                                <span>Person In Charge</span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $proposal['person_in_charge']
                                    );
                                    ?>
                                </strong>
                            </div>

                        </div>

                    <?php } ?>

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
                                        $proposal['proposal_date']
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
                                        $proposal['proposal_time']
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
                            <span>Location</span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $proposal['location']
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                    <?php if (
                        isset($proposal['expected_participants'])
                    ) { ?>

                        <div class="detail-info-item">

                            <div class="detail-info-icon">
                                <i class="fa-solid fa-user-group"></i>
                            </div>

                            <div>
                                <span>Expected Participants</span>

                                <strong>
                                    <?php
                                    echo number_format(
                                        (int) $proposal[
                                            'expected_participants'
                                        ]
                                    );
                                    ?>
                                </strong>
                            </div>

                        </div>

                    <?php } ?>

                    <div class="detail-info-item">

                        <div class="detail-info-icon">
                            <i class="fa-solid fa-wallet"></i>
                        </div>

                        <div>
                            <span>Estimated Budget</span>

                            <strong>
                                RM <?php
                                echo number_format(
                                    (float) $proposal['budget'],
                                    2
                                );
                                ?>
                            </strong>
                        </div>

                    </div>

                </div>

                <div class="detail-section">

                    <h3>Objective</h3>

                    <p>
                        <?php
                        echo !empty($proposal['objective'])
                            ? nl2br(
                                htmlspecialchars(
                                    $proposal['objective']
                                )
                            )
                            : 'No objective provided.';
                        ?>
                    </p>

                </div>

                <div class="detail-section">

                    <h3>Description</h3>

                    <p>
                        <?php
                        echo !empty($proposal['description'])
                            ? nl2br(
                                htmlspecialchars(
                                    $proposal['description']
                                )
                            )
                            : 'No description provided.';
                        ?>
                    </p>

                </div>

                <?php if (
                    $proposalStatus === 'Rejected' &&
                    !empty($proposal['reject_reason'])
                ) { ?>

                    <div class="detail-section rejection-section">

                        <h3>
                            <i class="fa-solid fa-circle-xmark"></i>
                            Reason for Rejection
                        </h3>

                        <p>
                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $proposal['reject_reason']
                                )
                            );
                            ?>
                        </p>

                    </div>

                <?php } ?>

            </div>

        </div>

        <?php if ($role === 'Admin') { ?>

            <div class="card proposal-action-card">

                <div class="section-header">

                    <div>

                        <h2>Admin Decision</h2>

                        <p>
                            Review the proposal before updating its approval status.
                        </p>

                    </div>

                    <a href="proposal_report.php?id=<?php
                    echo $proposalId;
                    ?>" target="_blank" class="btn-secondary print-btn">
                        <i class="fa-solid fa-file-pdf"></i>
                        Generate Proposal Report
                    </a>

                </div>

                <form method="POST" class="proposal-status-form">

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="new_status">
                                Proposal Status
                            </label>

                            <select id="new_status" name="new_status" required onchange="toggleRejectReason()">

                                <option value="Pending" <?php
                                echo $proposalStatus === 'Pending'
                                    ? 'selected'
                                    : '';
                                ?>>
                                    Pending
                                </option>

                                <option value="Approved" <?php
                                echo $proposalStatus === 'Approved'
                                    ? 'selected'
                                    : '';
                                ?>>
                                    Approved
                                </option>

                                <option value="Rejected" <?php
                                echo $proposalStatus === 'Rejected'
                                    ? 'selected'
                                    : '';
                                ?>>
                                    Rejected
                                </option>

                            </select>

                        </div>

                        <div class="form-group form-full" id="rejectReasonGroup">

                            <label for="reject_reason">
                                Reason for Rejection
                            </label>

                            <textarea id="reject_reason" name="reject_reason"
                                placeholder="Example: The budget is too high or the information is incomplete."><?php
                                echo htmlspecialchars(
                                    $proposal['reject_reason'] ?? ''
                                );
                                ?></textarea>

                        </div>

                    </div>

                    <div class="detail-actions">

                        <button type="submit" name="update_status" class="btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Update Status
                        </button>

                        <a href="manage_proposals.php" class="btn-secondary">
                            <i class="fa-solid fa-arrow-left"></i>
                            Back
                        </a>

                    </div>

                </form>

            </div>

        <?php } else { ?>

            <div class="proposal-back-section">

                <?php if ($proposalStatus === 'Approved') { ?>

                    <a href="proposal_report.php?id=<?php
                    echo $proposalId;
                    ?>" target="_blank" class="btn-primary">
                        <i class="fa-solid fa-file-pdf"></i>
                        Generate Official Proposal
                    </a>

                <?php } elseif ($proposalStatus === 'Pending') { ?>

                    <span class="proposal-print-notice pending">

                        <i class="fa-solid fa-hourglass-half"></i>

                        Official proposal will be available after approval.

                    </span>

                <?php } elseif ($proposalStatus === 'Rejected') { ?>

                    <span class="proposal-print-notice rejected">

                        <i class="fa-solid fa-circle-xmark"></i>

                        This proposal cannot be generated because it was rejected.

                    </span>

                <?php } ?>

                <a href="my_proposals.php" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to My Proposals
                </a>

            </div>

        <?php } ?>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

    <script>
        function toggleRejectReason() {
            const status =
                document.getElementById("new_status");

            const group =
                document.getElementById("rejectReasonGroup");

            const textarea =
                document.getElementById("reject_reason");

            if (!status || !group || !textarea) {
                return;
            }

            const isRejected =
                status.value === "Rejected";

            group.style.display =
                isRejected ? "block" : "none";

            textarea.required = isRejected;
        }

        toggleRejectReason();
    </script>

</body>

</html>