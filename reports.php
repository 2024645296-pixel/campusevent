<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* REPORT DATA */
$reports = mysqli_query($conn, "
    SELECT
        events.event_id,
        events.event_name,
        events.event_date,
        events.location,
        events.report_status,
        COUNT(registrations.registration_id) AS total_participants
    FROM events
    LEFT JOIN registrations
        ON events.event_id = registrations.event_id
    GROUP BY
        events.event_id,
        events.event_name,
        events.event_date,
        events.location,
        events.report_status
    ORDER BY events.event_id DESC
");

if (!$reports) {
    die("Report query failed: " . mysqli_error($conn));
}

/* SUMMARY */
$totalEventsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM events"
);

$totalParticipantsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM registrations"
);

$approvedReportsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM events
     WHERE report_status = 'Approved'"
);

$pendingReportsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM events
     WHERE report_status = 'Pending'"
);

$totalEvents = mysqli_fetch_assoc($totalEventsQuery)['total'] ?? 0;
$totalParticipants = mysqli_fetch_assoc($totalParticipantsQuery)['total'] ?? 0;
$approvedReports = mysqli_fetch_assoc($approvedReportsQuery)['total'] ?? 0;
$pendingReports = mysqli_fetch_assoc($pendingReportsQuery)['total'] ?? 0;

/* CHART DATA */
$chart = mysqli_query($conn, "
    SELECT
        events.event_name,
        COUNT(registrations.registration_id) AS total_participants
    FROM events
    LEFT JOIN registrations
        ON events.event_id = registrations.event_id
    GROUP BY events.event_id, events.event_name
    ORDER BY total_participants DESC, events.event_id DESC
");

if (!$chart) {
    die("Chart query failed: " . mysqli_error($conn));
}

$chartData = [];
$highestParticipants = 0;

while ($chartRow = mysqli_fetch_assoc($chart)) {
    $participants = (int) $chartRow['total_participants'];

    if ($participants > $highestParticipants) {
        $highestParticipants = $participants;
    }

    $chartData[] = [
        'event_name' => $chartRow['event_name'],
        'total_participants' => $participants
    ];
}

$totalReportRows = mysqli_num_rows($reports);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=180">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main">

        <div class="page-header">

            <div>
                <h1>Reports</h1>

                <p>
                    Review event reports, participant statistics and approval status.
                </p>
            </div>

            <div class="page-summary">
                <span><?php echo $totalReportRows; ?></span>
                <small>Reports</small>
            </div>

        </div>

        <?php if (isset($_GET['success'])) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Report updated successfully.
            </div>

        <?php } ?>

        <div class="report-cards">

            <div class="report-card">

                <div class="report-card-icon report-icon-blue">
                    <i class="fa-regular fa-calendar"></i>
                </div>

                <div>
                    <h2><?php echo $totalEvents; ?></h2>
                    <p>Total Events</p>
                </div>

            </div>

            <div class="report-card">

                <div class="report-card-icon report-icon-purple">
                    <i class="fa-solid fa-users"></i>
                </div>

                <div>
                    <h2><?php echo $totalParticipants; ?></h2>
                    <p>Total Participants</p>
                </div>

            </div>

            <div class="report-card">

                <div class="report-card-icon report-icon-green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <h2><?php echo $approvedReports; ?></h2>
                    <p>Approved Reports</p>
                </div>

            </div>

            <div class="report-card">

                <div class="report-card-icon report-icon-orange">
                    <i class="fa-regular fa-clock"></i>
                </div>

                <div>
                    <h2><?php echo $pendingReports; ?></h2>
                    <p>Pending Reports</p>
                </div>

            </div>

        </div>

        <div class="card report-chart-card">

            <div class="section-header">

                <div>
                    <h2>Participant Overview</h2>

                    <p>
                        Compare the total registrations received for each event.
                    </p>
                </div>

            </div>

            <?php if (count($chartData) > 0) { ?>

                <div class="participants-chart">

                    <?php foreach ($chartData as $chartRow) { ?>

                        <?php
                        $participants = $chartRow['total_participants'];

                        if ($highestParticipants > 0) {
                            $percentage = ($participants / $highestParticipants) * 100;
                        } else {
                            $percentage = 0;
                        }
                        ?>

                        <div class="chart-row">

                            <div class="chart-label">
                                <?php
                                echo htmlspecialchars(
                                    $chartRow['event_name']
                                );
                                ?>
                            </div>

                            <div class="chart-bar-bg">

                                <div class="chart-bar" style="width: <?php echo $percentage; ?>%;"></div>

                            </div>

                            <strong>
                                <?php echo $participants; ?>
                            </strong>

                        </div>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <div class="empty-state">

                    <div class="empty-state-icon">
                        <i class="fa-solid fa-chart-column"></i>
                    </div>

                    <h3>No chart data available</h3>

                    <p>
                        Participant statistics will appear after students register.
                    </p>

                </div>

            <?php } ?>

        </div>

        <div class="card report-table-card">

            <div class="section-header">

                <div>
                    <h2>Event Report Summary</h2>

                    <p>
                        View participant totals and report approval status.
                    </p>
                </div>

            </div>

            <div class="table-responsive">

                <table class="reports-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Participants</th>
                            <th>Report Status</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalReportRows > 0) { ?>

                            <?php
                            $no = 1;
                            mysqli_data_seek($reports, 0);

                            while ($row = mysqli_fetch_assoc($reports)) {
                                $reportStatus = !empty($row['report_status'])
                                    ? $row['report_status']
                                    : 'Pending';

                                $statusClass = strtolower($reportStatus);
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>
                                        <div class="report-event-name">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['event_name']
                                            );
                                            ?>
                                        </div>
                                    </td>

                                    <td class="report-date-cell">
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($row['event_date'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['location']
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <span class="participant-count">
                                            <?php
                                            echo (int) $row['total_participants'];
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge badge-<?php
                                        echo htmlspecialchars($statusClass);
                                        ?>">
                                            <?php
                                            echo htmlspecialchars($reportStatus);
                                            ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a class="btn-view" href="report_detail.php?id=<?php
                                        echo (int) $row['event_id'];
                                        ?>">
                                            View Report
                                        </a>
                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="7">

                                    <div class="empty-state">

                                        <div class="empty-state-icon">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </div>

                                        <h3>No reports found</h3>

                                        <p>
                                            Event reports will appear in this table.
                                        </p>

                                    </div>

                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalReportRows > 0) { ?>

                <div class="table-footer">
                    Showing <?php echo $totalReportRows; ?>
                    report<?php echo $totalReportRows !== 1 ? 's' : ''; ?>
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