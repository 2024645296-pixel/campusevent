<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];
$userName = $_SESSION['name'];

function getCount($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int) ($row['total'] ?? 0);
}

$totalProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM program_proposals"
);

$pendingProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM program_proposals
     WHERE status = 'Pending'"
);

$approvedProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM program_proposals
     WHERE status = 'Approved'"
);

$rejectedProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM program_proposals
     WHERE status = 'Rejected'"
);

$totalEvents = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM events"
);

$totalRegistrations = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM registrations"
);

$totalUsers = getCount(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);


$myProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM program_proposals
     WHERE user_id = $userId"
);

$myPendingProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM program_proposals
     WHERE user_id = $userId
     AND status = 'Pending'"
);

$myApprovedProposal = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM program_proposals
     WHERE user_id = $userId
     AND status = 'Approved'"
);


$myRegistration = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM registrations
     WHERE user_id = $userId"
);

$myAttended = getCount(
    $conn,
    "SELECT COUNT(*) AS total
     FROM registrations
     WHERE user_id = $userId
     AND attendance_status = 'Attended'"
);

$myMerit = $myAttended * 10;


$recentEvents = mysqli_query($conn, "
    SELECT *
    FROM events
    WHERE status = 'Upcoming'
    AND event_date >= CURDATE()
    ORDER BY event_id DESC
    LIMIT 3
");

if (!$recentEvents) {
    die("Event query failed: " . mysqli_error($conn));
}

$totalRecentEvents = mysqli_num_rows($recentEvents);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=400">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main dashboard-page">

        <div class="topbar">

            <div></div>

            <div class="profile-box">

                <span class="notification">
                    <i class="fa-solid fa-bell"></i>
                </span>

                <span class="avatar">
                    <?php
                    echo strtoupper(
                        substr($userName, 0, 2)
                    );
                    ?>
                </span>

                <div class="profile-text">
                    <strong>
                        <?php echo htmlspecialchars($userName); ?>
                    </strong>

                    <small>
                        <?php echo htmlspecialchars($role); ?>
                    </small>
                </div>

            </div>

        </div>

        <div class="welcome-section">

            <div>
                <h1>
                    Welcome back,
                    <?php echo htmlspecialchars($userName); ?>
                </h1>

                <p>
                    Here's what's happening with your campus events today.
                </p>
            </div>

        </div>

        <div class="dashboard-grid">

            <?php if ($role === 'Admin') { ?>

                <div class="glass-card">
                    <div class="circle blue">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>

                    <div>
                        <h2><?php echo $totalProposal; ?></h2>
                        <p>Total Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle orange">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>

                    <div>
                        <h2><?php echo $pendingProposal; ?></h2>
                        <p>Pending Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <h2><?php echo $approvedProposal; ?></h2>
                        <p>Approved Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle red">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>

                    <div>
                        <h2><?php echo $rejectedProposal; ?></h2>
                        <p>Rejected Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle purple">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <div>
                        <h2><?php echo $totalEvents; ?></h2>
                        <p>Total Events</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle sky">
                        <i class="fa-solid fa-user-pen"></i>
                    </div>

                    <div>
                        <h2><?php echo $totalRegistrations; ?></h2>
                        <p>Total Registrations</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle teal">
                        <i class="fa-solid fa-users"></i>
                    </div>

                    <div>
                        <h2><?php echo $totalUsers; ?></h2>
                        <p>Total Users</p>
                    </div>
                </div>

            <?php } ?>

            <?php if ($role === 'Club Leader') { ?>

                <div class="glass-card">
                    <div class="circle blue">
                        <i class="fa-solid fa-file-lines"></i>
                    </div>

                    <div>
                        <h2><?php echo $myProposal; ?></h2>
                        <p>My Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle orange">
                        <i class="fa-solid fa-hourglass-half"></i>
                    </div>

                    <div>
                        <h2><?php echo $myPendingProposal; ?></h2>
                        <p>My Pending Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle green">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>

                    <div>
                        <h2><?php echo $myApprovedProposal; ?></h2>
                        <p>My Approved Proposals</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle purple">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <div>
                        <h2><?php echo $totalEvents; ?></h2>
                        <p>Available Events</p>
                    </div>
                </div>

            <?php } ?>

            <?php if ($role === 'Student') { ?>

                <div class="glass-card">
                    <div class="circle purple">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>

                    <div>
                        <h2><?php echo $totalEvents; ?></h2>
                        <p>Available Events</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle sky">
                        <i class="fa-solid fa-clipboard-list"></i>
                    </div>

                    <div>
                        <h2><?php echo $myRegistration; ?></h2>
                        <p>My Registrations</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle green">
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <div>
                        <h2><?php echo $myMerit; ?></h2>
                        <p>Merit Points</p>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="circle orange">
                        <i class="fa-solid fa-user-check"></i>
                    </div>

                    <div>
                        <h2><?php echo $myAttended; ?></h2>
                        <p>Events Attended</p>
                    </div>
                </div>

            <?php } ?>

        </div>

        <section class="dashboard-event-section">

            <div class="section-header dashboard-event-header">

                <div>
                    <h2>
                        <?php
                        if ($role === 'Admin') {
                            echo "Recent Events Available";
                        } elseif ($role === 'Club Leader') {
                            echo "Latest Club Events";
                        } else {
                            echo "Upcoming Events For You";
                        }
                        ?>
                    </h2>

                    <p>
                        View the latest upcoming events in the system.
                    </p>
                </div>

                <a href="events.php" class="btn-secondary">
                    View All Events
                </a>

            </div>

            <?php if ($totalRecentEvents > 0) { ?>

                <div class="event-card-grid">

                    <?php while ($event = mysqli_fetch_assoc($recentEvents)) { ?>

                        <div class="event-preview-card">

                            <?php if (!empty($event['poster'])) { ?>

                                <img src="assets/posters/<?php
                                echo rawurlencode($event['poster']);
                                ?>" class="event-preview-img" alt="<?php
                                echo htmlspecialchars(
                                    $event['event_name']
                                );
                                ?>">

                            <?php } else { ?>

                                <div class="event-preview-placeholder">
                                    <i class="fa-regular fa-image"></i>
                                    <span>No Poster</span>
                                </div>

                            <?php } ?>

                            <div class="event-preview-body">

                                <span class="event-preview-status">
                                    Upcoming
                                </span>

                                <h3>
                                    <?php
                                    echo htmlspecialchars(
                                        $event['event_name']
                                    );
                                    ?>
                                </h3>

                                <div class="event-preview-meta">

                                    <p>
                                        <i class="fa-regular fa-calendar"></i>

                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($event['event_date'])
                                        );
                                        ?>
                                    </p>

                                    <p>
                                        <i class="fa-regular fa-clock"></i>

                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($event['event_time'])
                                        );
                                        ?>
                                    </p>

                                    <p>
                                        <i class="fa-solid fa-location-dot"></i>

                                        <?php
                                        echo htmlspecialchars(
                                            $event['location']
                                        );
                                        ?>
                                    </p>

                                    <?php if ($role === 'Student') { ?>

                                        <p>
                                            <i class="fa-solid fa-money-bill"></i>

                                            <?php
                                            echo (float) $event['event_fee'] > 0
                                                ? 'RM ' . number_format(
                                                    (float) $event['event_fee'],
                                                    2
                                                )
                                                : 'Free';
                                            ?>
                                        </p>

                                    <?php } ?>

                                </div>

                                <a href="event_detail.php?id=<?php
                                echo (int) $event['event_id'];
                                ?>" class="event-view-btn">
                                    View Event
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>

                            </div>

                        </div>

                    <?php } ?>

                </div>

            <?php } else { ?>

                <div class="dashboard-empty-state">

                    <i class="fa-regular fa-calendar-xmark"></i>

                    <h3>No upcoming events</h3>

                    <p>
                        New upcoming events will appear here.
                    </p>

                </div>

            <?php } ?>

        </section>

        <?php include 'includes/footer.php'; ?>

    </div>

</body>

</html>