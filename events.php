<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$userId = (int) ($_SESSION['user_id'] ?? 0);

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$date = isset($_GET['date'])
    ? trim($_GET['date'])
    : '';

$status = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

$safeSearch = mysqli_real_escape_string(
    $conn,
    $search
);

$safeDate = mysqli_real_escape_string(
    $conn,
    $date
);

$safeStatus = mysqli_real_escape_string(
    $conn,
    $status
);

$today = date('Y-m-d');

/* =========================================
   BUILD FILTER
========================================= */

if ($role === 'Student') {
    /*
       Students do not need to see cancelled events.
    */
    $where = "WHERE events.status != 'Cancelled'";
} else {
    $where = "WHERE 1";
}

/* Search filter */
if ($search !== '') {
    $where .= " AND (
        events.event_name LIKE '%$safeSearch%'
        OR events.club_name LIKE '%$safeSearch%'
        OR events.location LIKE '%$safeSearch%'
    )";
}

/* Date filter */
if ($date !== '') {
    $where .= " AND events.event_date = '$safeDate'";
}

/* Status filter */
if ($status !== '') {
    $where .= " AND events.status = '$safeStatus'";
}

/* =========================================
   GET EVENTS
========================================= */

if ($role === 'Student') {
    /*
       LEFT JOIN checks whether the current student
       has already registered for each event.
    */
    $sql = "
        SELECT
            events.*,
            registrations.registration_id AS my_registration_id
        FROM events
        LEFT JOIN registrations
            ON events.event_id = registrations.event_id
            AND registrations.user_id = $userId
        $where
        ORDER BY events.event_date DESC, events.event_id DESC
    ";
} else {
    $sql = "
        SELECT
            events.*,
            NULL AS my_registration_id
        FROM events
        $where
        ORDER BY events.event_date DESC, events.event_id DESC
    ";
}

$events = mysqli_query(
    $conn,
    $sql
);

if (!$events) {
    die(
        "Database query failed: " .
        mysqli_error($conn)
    );
}

$totalEvents = mysqli_num_rows($events);

/* =========================================
   TABLE COLUMN COUNT
========================================= */

/*
Base columns:
1. No.
2. Event Name
3. Date
4. Time
5. Location
6. Status
7. View
*/
$columnCount = 7;

/* Admin only: Budget */
if ($role === 'Admin') {
    $columnCount++;
}

/* Student only: Fee */
if ($role === 'Student') {
    $columnCount++;
}

/* Student only: Register */
if ($role === 'Student') {
    $columnCount++;
}

/* Admin only: Edit + Delete */
if ($role === 'Admin') {
    $columnCount += 2;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Club Events | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=80000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main">

        <div class="page-header">

            <div>
                <h1>Club Events</h1>

                <p>
                    <?php if ($role === 'Student') { ?>

                        Browse available campus events and register for upcoming activities.

                    <?php } elseif ($role === 'Club Leader') { ?>

                        View approved club events and programme information.

                    <?php } else { ?>

                        View approved club events and manage event information.

                    <?php } ?>
                </p>
            </div>

            <div class="page-summary">

                <span>
                    <?php echo $totalEvents; ?>
                </span>

                <small>
                    Event<?php echo $totalEvents !== 1 ? 's' : ''; ?>
                </small>

            </div>

        </div>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'updated'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Event updated successfully.
            </div>

        <?php } ?>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'deleted'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Event deleted successfully.
            </div>

        <?php } ?>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'registered'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Event registration completed successfully.
            </div>

        <?php } ?>

        <?php if (isset($_GET['error'])) { ?>

            <div class="error events-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php if ($_GET['error'] === 'invalid') { ?>

                    Invalid event selected.

                <?php } elseif ($_GET['error'] === 'notfound') { ?>

                    Event not found.

                <?php } elseif ($_GET['error'] === 'delete') { ?>

                    Unable to delete the selected event.

                <?php } elseif ($_GET['error'] === 'duplicate') { ?>

                    You have already registered for this event.

                <?php } elseif ($_GET['error'] === 'closed') { ?>

                    Registration for this event is no longer available.

                <?php } else { ?>

                    Unable to complete the requested action.

                <?php } ?>

            </div>

        <?php } ?>

        <div class="card events-card">

            <div class="section-header">

                <div>
                    <h2>Event List</h2>

                    <p>
                        Search and filter events by name, club, location, date or status.
                    </p>
                </div>

            </div>

            <form method="GET" class="event-filter-form">

                <div class="search-input-wrap">

                    <i class="fa-solid fa-magnifying-glass search-icon"></i>

                    <input type="text" name="search" placeholder="Search event, club or location..."
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>

                <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>">

                <select name="status">

                    <option value="">
                        All Status
                    </option>

                    <option value="Upcoming" <?php
                    echo $status === 'Upcoming'
                        ? 'selected'
                        : '';
                    ?>>
                        Upcoming
                    </option>

                    <option value="Ongoing" <?php
                    echo $status === 'Ongoing'
                        ? 'selected'
                        : '';
                    ?>>
                        Ongoing
                    </option>

                    <option value="Completed" <?php
                    echo $status === 'Completed'
                        ? 'selected'
                        : '';
                    ?>>
                        Completed
                    </option>

                    <?php if ($role !== 'Student') { ?>

                        <option value="Cancelled" <?php
                        echo $status === 'Cancelled'
                            ? 'selected'
                            : '';
                        ?>>
                            Cancelled
                        </option>

                    <?php } ?>

                </select>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search
                </button>

                <a href="events.php" class="btn-secondary">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>

            </form>

            <div class="table-responsive">

                <table class="events-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>

                            <?php if ($role === 'Admin') { ?>
                                <th>Budget</th>
                            <?php } ?>

                            <?php if ($role === 'Student') { ?>
                                <th>Fee</th>
                            <?php } ?>

                            <th>Status</th>

                            <?php if ($role === 'Student') { ?>
                                <th>Registration</th>
                            <?php } ?>

                            <th>View</th>

                            <?php if ($role === 'Admin') { ?>
                                <th>Edit</th>
                                <th>Delete</th>
                            <?php } ?>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalEvents > 0) { ?>

                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($events)) {
                                $statusClass = strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $row['status']
                                    )
                                );

                                $isRegistered =
                                    !empty($row['my_registration_id']);

                                $isExpired =
                                    !empty($row['event_date']) &&
                                    $row['event_date'] < $today;

                                $canRegister =
                                    $role === 'Student' &&
                                    $row['status'] === 'Upcoming' &&
                                    !$isExpired &&
                                    !$isRegistered;
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>

                                        <div class="event-name-cell">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['event_name']
                                            );
                                            ?>
                                        </div>

                                        <?php if (!empty($row['club_name'])) { ?>

                                            <small class="event-club-name">
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['club_name']
                                                );
                                                ?>
                                            </small>

                                        <?php } ?>

                                    </td>

                                    <td class="date-cell">
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($row['event_date'])
                                        );
                                        ?>
                                    </td>

                                    <td class="time-cell">
                                        <?php
                                        echo date(
                                            "h:i A",
                                            strtotime($row['event_time'])
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

                                    <?php if ($role === 'Admin') { ?>

                                        <td class="budget-cell">
                                            RM <?php
                                            echo number_format(
                                                (float) $row['budget'],
                                                2
                                            );
                                            ?>
                                        </td>

                                    <?php } ?>

                                    <?php if ($role === 'Student') { ?>

                                        <td class="event-fee-cell">

                                            <?php if (
                                                isset($row['event_fee']) &&
                                                (float) $row['event_fee'] > 0
                                            ) { ?>

                                                RM <?php
                                                echo number_format(
                                                    (float) $row['event_fee'],
                                                    2
                                                );
                                                ?>

                                            <?php } else { ?>

                                                <span class="free-event-label">
                                                    Free
                                                </span>

                                            <?php } ?>

                                        </td>

                                    <?php } ?>

                                    <td>

                                        <span class="badge badge-<?php
                                        echo htmlspecialchars($statusClass);
                                        ?>">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['status']
                                            );
                                            ?>
                                        </span>

                                    </td>

                                    <?php if ($role === 'Student') { ?>

                                        <td>

                                            <?php if ($isRegistered) { ?>

                                                <span class="registration-state registered">

                                                    <i class="fa-solid fa-circle-check"></i>

                                                    Registered

                                                </span>

                                            <?php } elseif ($isExpired) { ?>

                                                <span class="registration-state expired">

                                                    <i class="fa-solid fa-clock-rotate-left"></i>

                                                    Expired

                                                </span>

                                            <?php } elseif (
                                                $row['status'] !== 'Upcoming'
                                            ) { ?>

                                                <span class="registration-state closed">

                                                    <i class="fa-solid fa-lock"></i>

                                                    Closed

                                                </span>

                                            <?php } elseif ($canRegister) { ?>

                                                <a class="btn-action btn-register" href="register_event.php?id=<?php
                                                echo (int) $row['event_id'];
                                                ?>">
                                                    <i class="fa-solid fa-user-plus"></i>
                                                    Register
                                                </a>

                                            <?php } ?>

                                        </td>

                                    <?php } ?>

                                    <td>

                                        <a class="btn-action btn-view" href="event_detail.php?id=<?php
                                        echo (int) $row['event_id'];
                                        ?>">
                                            <i class="fa-regular fa-eye"></i>
                                            View
                                        </a>

                                    </td>

                                    <?php if ($role === 'Admin') { ?>

                                        <td>
                                            <a class="btn-action btn-edit" href="edit_event.php?id=<?php
                                            echo (int) $row['event_id'];
                                            ?>">
                                                <i class="fa-solid fa-pen"></i>
                                                Edit
                                            </a>
                                        </td>

                                        <td>
                                            <a class="btn-action btn-delete-event" href="delete_event.php?id=<?php
                                            echo (int) $row['event_id'];
                                            ?>"
                                                onclick="return confirm('Are you sure you want to delete this event? All registrations related to this event will also be deleted.');">
                                                <i class="fa-solid fa-trash"></i>
                                                Delete
                                            </a>
                                        </td>

                                    <?php } ?>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>

                                <td colspan="<?php echo $columnCount; ?>">

                                    <div class="empty-state">

                                        <div class="empty-state-icon">
                                            <i class="fa-regular fa-calendar-xmark"></i>
                                        </div>

                                        <h3>No events found</h3>

                                        <p>
                                            Try another keyword or reset the filters.
                                        </p>

                                        <a href="events.php" class="btn-secondary">
                                            Reset Filters
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalEvents > 0) { ?>

                <div class="table-footer">
                    Showing <?php echo $totalEvents; ?>
                    event<?php echo $totalEvents !== 1 ? 's' : ''; ?>
                </div>

            <?php } ?>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

    <script>
        window.setTimeout(function () {
            const toast =
                document.getElementById("successMsg");

            if (!toast) {
                return;
            }

            toast.classList.add("toast-hide");

            window.setTimeout(function () {
                toast.remove();
            }, 400);
        }, 3200);
    </script>

</body>

</html>