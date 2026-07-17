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

/* Validate event ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: reports.php");
    exit();
}

$eventId = (int) $_GET['id'];
$error = "";

/* Get event report */
function getEventReport($conn, $eventId)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            events.*,
            COUNT(registrations.registration_id) AS total_participants,
            SUM(
                CASE
                    WHEN registrations.attendance_status = 'Attended'
                    THEN 1
                    ELSE 0
                END
            ) AS total_attended
         FROM events
         LEFT JOIN registrations
            ON events.event_id = registrations.event_id
         WHERE events.event_id = ?
         GROUP BY events.event_id
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, "i", $eventId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return $event;
}

$event = getEventReport($conn, $eventId);

if (!$event) {
    header("Location: reports.php?error=notfound");
    exit();
}

/* Update report */
if (isset($_POST['update_report'])) {
    $reportSummary = trim($_POST['report_summary'] ?? '');
    $reportStatus = trim($_POST['report_status'] ?? '');
    $reportRemark = trim($_POST['report_remark'] ?? '');

    $allowedStatuses = [
        'Pending',
        'Approved',
        'Rejected'
    ];

    if ($reportSummary === '') {
        $error = "Please enter the report summary.";
    } elseif (!in_array($reportStatus, $allowedStatuses, true)) {
        $error = "Invalid report status selected.";
    } elseif (
        $reportStatus === 'Rejected' &&
        $reportRemark === ''
    ) {
        $error = "Please provide a remark when rejecting the report.";
    }

    if ($error === '') {
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE events
             SET report_summary = ?,
                 report_status = ?,
                 report_remark = ?
             WHERE event_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "sssi",
            $reportSummary,
            $reportStatus,
            $reportRemark,
            $eventId
        );

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);

            header("Location: reports.php?success=updated");
            exit();
        }

        $error = "Unable to update report: " .
            mysqli_stmt_error($stmt);

        mysqli_stmt_close($stmt);
    }

    /* Keep submitted data if validation fails */
    $event['report_summary'] = $reportSummary;
    $event['report_status'] = $reportStatus;
    $event['report_remark'] = $reportRemark;
}

$reportStatus = !empty($event['report_status'])
    ? $event['report_status']
    : 'Pending';

$statusClass = strtolower(
    str_replace(' ', '-', $reportStatus)
);

$totalParticipants = (int) (
    $event['total_participants'] ?? 0
);

$totalAttended = (int) (
    $event['total_attended'] ?? 0
);

$attendanceRate = $totalParticipants > 0
    ? round(($totalAttended / $totalParticipants) * 100)
    : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Event Report Details | CampusEvent</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css?v=600"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >
</head>

<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main report-detail-page">

    <div class="detail-hero">

        <div>
            <a href="reports.php" class="detail-back-link">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Reports
            </a>

            <h1>Event Report Details</h1>

            <p>
                Review event performance, participant statistics and report status.
            </p>
        </div>

        <span class="badge badge-<?php echo $statusClass; ?>">
            <?php echo htmlspecialchars($reportStatus); ?>
        </span>

    </div>

    <?php if ($error !== '') { ?>

        <div class="error report-detail-error">
            <i class="fa-solid fa-circle-exclamation"></i>

            <?php echo htmlspecialchars($error); ?>
        </div>

    <?php } ?>

    <div class="report-detail-layout">

        <div class="detail-poster-card">

            <?php if (!empty($event['poster'])) { ?>

                <img
                    src="assets/posters/<?php
                    echo rawurlencode($event['poster']);
                    ?>"
                    class="detail-poster"
                    alt="<?php
                    echo htmlspecialchars($event['event_name']);
                    ?>"
                >

            <?php } else { ?>

                <div class="no-poster">
                    <i class="fa-regular fa-image"></i>
                    <span>No poster uploaded</span>
                </div>

            <?php } ?>

        </div>

        <div class="detail-info-card">

            <div class="report-detail-title">

                <div>
                    <span class="proposal-label">
                        Event Performance Report
                    </span>

                    <h2>
                        <?php
                        echo htmlspecialchars($event['event_name']);
                        ?>
                    </h2>
                </div>

                <span class="badge badge-<?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($reportStatus); ?>
                </span>

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
                                strtotime($event['event_date'])
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
                                strtotime($event['event_time'])
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
                            echo htmlspecialchars($event['location']);
                            ?>
                        </strong>
                    </div>

                </div>

                <div class="detail-info-item">

                    <div class="detail-info-icon">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div>
                        <span>Registrations</span>

                        <strong>
                            <?php echo $totalParticipants; ?>
                            participant<?php
                            echo $totalParticipants !== 1 ? 's' : '';
                            ?>
                        </strong>
                    </div>

                </div>

                <div class="detail-info-item">

                    <div class="detail-info-icon">
                        <i class="fa-solid fa-user-check"></i>
                    </div>

                    <div>
                        <span>Attended</span>

                        <strong>
                            <?php echo $totalAttended; ?>
                            participant<?php
                            echo $totalAttended !== 1 ? 's' : '';
                            ?>
                        </strong>
                    </div>

                </div>

                <div class="detail-info-item">

                    <div class="detail-info-icon">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>

                    <div>
                        <span>Attendance Rate</span>

                        <strong>
                            <?php echo $attendanceRate; ?>%
                        </strong>
                    </div>

                </div>

            </div>

            <div class="detail-section">

                <h3>Event Description</h3>

                <p>
                    <?php
                    echo !empty($event['description'])
                        ? nl2br(
                            htmlspecialchars($event['description'])
                        )
                        : 'No event description provided.';
                    ?>
                </p>

            </div>

            <div class="detail-section">

                <h3>Current Report Summary</h3>

                <p>
                    <?php
                    echo !empty($event['report_summary'])
                        ? nl2br(
                            htmlspecialchars($event['report_summary'])
                        )
                        : 'No report summary has been added yet.';
                    ?>
                </p>

            </div>

            <?php if (!empty($event['report_remark'])) { ?>

                <div class="detail-section report-remark-section">

                    <h3>
                        <i class="fa-regular fa-comment-dots"></i>
                        Admin Remark
                    </h3>

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars($event['report_remark'])
                        );
                        ?>
                    </p>

                </div>

            <?php } ?>

        </div>

    </div>

    <div class="card action-card report-approval-card">

        <div class="section-header">

            <div>
                <h2>Report Approval</h2>

                <p>
                    Edit the report summary and update its approval status.
                </p>
            </div>

            <a
                href="event_report.php?id=<?php echo $eventId; ?>"
                target="_blank"
                class="btn-secondary print-btn"
            >
                <i class="fa-solid fa-file-pdf"></i>
                Generate Event Report
            </a>

        </div>

        <form
            method="POST"
            class="report-approval-form"
        >

            <div class="form-grid">

                <div class="form-group form-full">

                    <label for="report_summary">
                        Report Summary
                    </label>

                    <textarea
                        id="report_summary"
                        name="report_summary"
                        placeholder="Describe the event outcome, achievements and participant response."
                        required
                    ><?php
                    echo htmlspecialchars(
                        $event['report_summary'] ?? ''
                    );
                    ?></textarea>

                </div>

                <div class="form-group">

                    <label for="report_status">
                        Report Status
                    </label>

                    <select
                        id="report_status"
                        name="report_status"
                        required
                    >
                        <option
                            value="Pending"
                            <?php
                            echo $reportStatus === 'Pending'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Pending
                        </option>

                        <option
                            value="Approved"
                            <?php
                            echo $reportStatus === 'Approved'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Approved
                        </option>

                        <option
                            value="Rejected"
                            <?php
                            echo $reportStatus === 'Rejected'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Rejected
                        </option>
                    </select>

                </div>

                <div class="form-group form-full">

                    <label for="report_remark">
                        Admin Remark
                    </label>

                    <textarea
                        id="report_remark"
                        name="report_remark"
                        placeholder="Example: Report approved. Photos and attendance records are complete."
                    ><?php
                    echo htmlspecialchars(
                        $event['report_remark'] ?? ''
                    );
                    ?></textarea>

                </div>

            </div>

            <div class="detail-actions">

                <button
                    type="submit"
                    name="update_report"
                    class="btn-primary"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    Update Report
                </button>

                <a
                    href="reports.php"
                    class="btn-secondary"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                    Back
                </a>

            </div>

        </form>

    </div>

    <div class="footer-small">
        <?php include 'includes/footer.php'; ?>
    </div>

</div>

</body>
</html>