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
    header("Location: events.php");
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

mysqli_stmt_bind_param($stmt, "i", $eventId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$event = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$event) {
    header("Location: events.php?error=notfound");
    exit();
}

$error = "";

/* Update event */
if (isset($_POST['update'])) {
    $eventName = trim($_POST['event_name'] ?? '');
    $clubName = trim($_POST['club_name'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $budget = (float) ($_POST['budget'] ?? 0);
    $eventFee = (float) ($_POST['event_fee'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    $allowedStatuses = [
        'Upcoming',
        'Ongoing',
        'Completed',
        'Cancelled'
    ];

    if (
        $eventName === '' ||
        $clubName === '' ||
        $eventDate === '' ||
        $eventTime === '' ||
        $location === '' ||
        $description === ''
    ) {
        $error = "Please complete all required fields.";
    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid event status selected.";
    } elseif ($budget < 0 || $eventFee < 0) {
        $error = "Budget and event fee cannot be negative.";
    }

    $poster = $event['poster'];

    if (
        $error === '' &&
        isset($_FILES['poster']) &&
        $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
            $error = "Poster upload failed.";
        } else {
            $allowedMimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $maxFileSize = 5 * 1024 * 1024;

            if ($_FILES['poster']['size'] > $maxFileSize) {
                $error = "Poster size must not exceed 5 MB.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file(
                    $finfo,
                    $_FILES['poster']['tmp_name']
                );
                finfo_close($finfo);

                if (!isset($allowedMimeTypes[$mimeType])) {
                    $error = "Only JPG, PNG and WEBP files are allowed.";
                } else {
                    $uploadDirectory = __DIR__ . '/assets/posters/';

                    if (!is_dir($uploadDirectory)) {
                        mkdir($uploadDirectory, 0775, true);
                    }

                    $extension = $allowedMimeTypes[$mimeType];
                    $newPosterName = 'event_' .
                        $eventId . '_' .
                        time() . '.' .
                        $extension;

                    $destination = $uploadDirectory . $newPosterName;

                    if (
                        move_uploaded_file(
                            $_FILES['poster']['tmp_name'],
                            $destination
                        )
                    ) {
                        if (
                            !empty($poster) &&
                            file_exists($uploadDirectory . $poster)
                        ) {
                            unlink($uploadDirectory . $poster);
                        }

                        $poster = $newPosterName;
                    } else {
                        $error = "Unable to save the uploaded poster.";
                    }
                }
            }
        }
    }

    if ($error === '') {
        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE events
             SET event_name = ?,
                 club_name = ?,
                 event_date = ?,
                 event_time = ?,
                 location = ?,
                 description = ?,
                 budget = ?,
                 event_fee = ?,
                 status = ?,
                 poster = ?
             WHERE event_id = ?"
        );

        mysqli_stmt_bind_param(
            $updateStmt,
            "ssssssddssi",
            $eventName,
            $clubName,
            $eventDate,
            $eventTime,
            $location,
            $description,
            $budget,
            $eventFee,
            $status,
            $poster,
            $eventId
        );

        if (mysqli_stmt_execute($updateStmt)) {
            mysqli_stmt_close($updateStmt);

            header("Location: events.php?success=updated");
            exit();
        }

        $error = "Failed to update event: " .
            mysqli_stmt_error($updateStmt);

        mysqli_stmt_close($updateStmt);
    }

    /* Keep entered values if validation fails */
    $event['event_name'] = $eventName;
    $event['club_name'] = $clubName;
    $event['event_date'] = $eventDate;
    $event['event_time'] = $eventTime;
    $event['location'] = $location;
    $event['description'] = $description;
    $event['budget'] = $budget;
    $event['event_fee'] = $eventFee;
    $event['status'] = $status;
    $event['poster'] = $poster;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Event | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=500">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main edit-event-page">

        <div class="page-header">

            <div>
                <a href="events.php" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Events
                </a>

                <h1>Edit Event</h1>

                <p>
                    Update programme information and manage the event status.
                </p>
            </div>

            <div class="page-summary">
                <span>
                    <i class="fa-solid fa-calendar-check"></i>
                </span>
                <small>Event ID #<?php echo $eventId; ?></small>
            </div>

        </div>

        <?php if ($error !== '') { ?>

            <div class="error edit-event-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>

        <div class="edit-event-steps">

            <div class="edit-step active">
                <span>1</span>
                <p>Event Information</p>
            </div>

            <div class="edit-step">
                <span>2</span>
                <p>Poster & Finance</p>
            </div>

            <div class="edit-step">
                <span>3</span>
                <p>Status Review</p>
            </div>

            <div class="edit-step">
                <span>4</span>
                <p>Save Changes</p>
            </div>

        </div>

        <div class="card edit-event-card">

            <div class="section-header">

                <div>
                    <h2>Event Information</h2>

                    <p>
                        Review and update the details before saving.
                    </p>
                </div>

            </div>

            <form method="POST" enctype="multipart/form-data" class="edit-event-form">

                <div class="form-grid">

                    <div class="form-group form-full">
                        <label for="event_name">Event Name</label>

                        <input type="text" id="event_name" name="event_name" value="<?php
                        echo htmlspecialchars($event['event_name']);
                        ?>" required>
                    </div>

                    <div class="form-group form-full">
                        <label for="club_name">Club Name</label>

                        <input type="text" id="club_name" name="club_name" value="<?php
                        echo htmlspecialchars($event['club_name']);
                        ?>" placeholder="Example: IT Club" required>
                    </div>

                    <div class="form-group">
                        <label for="event_date">Event Date</label>

                        <input type="date" id="event_date" name="event_date" value="<?php
                        echo htmlspecialchars($event['event_date']);
                        ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="event_time">Event Time</label>

                        <input type="time" id="event_time" name="event_time" value="<?php
                        echo htmlspecialchars($event['event_time']);
                        ?>" required>
                    </div>

                    <div class="form-group form-full">
                        <label for="location">Event Location</label>

                        <input type="text" id="location" name="location" value="<?php
                        echo htmlspecialchars($event['location']);
                        ?>" required>
                    </div>

                    <div class="form-group form-full">
                        <label for="description">Event Description</label>

                        <textarea id="description" name="description" required><?php
                        echo htmlspecialchars($event['description']);
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="budget">Estimated Budget (RM)</label>

                        <input type="number" id="budget" name="budget" min="0" step="0.01" value="<?php
                        echo htmlspecialchars($event['budget']);
                        ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="event_fee">Student Event Fee (RM)</label>

                        <input type="number" id="event_fee" name="event_fee" min="0" step="0.01" value="<?php
                        echo htmlspecialchars($event['event_fee']);
                        ?>" required>
                    </div>

                    <div class="form-group form-full">
                        <label for="status">Event Status</label>

                        <select id="status" name="status" required>
                            <?php
                            $statuses = [
                                'Upcoming',
                                'Ongoing',
                                'Completed',
                                'Cancelled'
                            ];

                            foreach ($statuses as $statusOption) {
                                $selected = $event['status'] === $statusOption
                                    ? 'selected'
                                    : '';
                                ?>

                                <option value="<?php echo $statusOption; ?>" <?php echo $selected; ?>>
                                    <?php echo $statusOption; ?>
                                </option>

                            <?php } ?>
                        </select>
                    </div>

                </div>

                <div class="poster-upload-section">

                    <div class="poster-upload-field">

                        <label for="poster">
                            Event Poster
                        </label>

                        <input type="file" id="poster" name="poster"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                        <small>
                            JPG, PNG or WEBP only. Maximum size 5 MB.
                        </small>

                    </div>

                    <div class="poster-preview">

                        <p>Current Poster</p>

                        <?php if (!empty($event['poster'])) { ?>

                            <img src="assets/posters/<?php
                            echo rawurlencode($event['poster']);
                            ?>" alt="Event Poster">

                        <?php } else { ?>

                            <div class="poster-preview-empty">
                                <i class="fa-regular fa-image"></i>
                                <span>No poster uploaded</span>
                            </div>

                        <?php } ?>

                    </div>

                </div>

                <div class="document-box">

                    <div class="document-header">

                        <div>
                            <h3>Supporting Documents</h3>

                            <p>
                                Review the templates and supporting forms if required.
                            </p>
                        </div>

                    </div>

                    <div class="doc-grid">

                        <a href="assets/documents/official-letter-template.docx" class="doc-btn" download>
                            <i class="fa-regular fa-file-word"></i>
                            Official Letter Template
                        </a>

                        <a href="assets/documents/proposal-paper-template.docx" class="doc-btn" download>
                            <i class="fa-regular fa-file-word"></i>
                            Proposal Paper Template
                        </a>

                        <a href="assets/documents/student-activity-application-form.pdf" class="doc-btn" target="_blank"
                            rel="noopener">
                            <i class="fa-regular fa-file-pdf"></i>
                            Activity Application Form
                        </a>

                        <a href="assets/documents/speaker-profile-form.pdf" class="doc-btn" target="_blank"
                            rel="noopener">
                            <i class="fa-regular fa-file-pdf"></i>
                            Speaker Profile Form
                        </a>

                    </div>

                </div>

                <div class="edit-event-actions">

                    <button type="submit" name="update" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Update Event
                    </button>

                    <a href="events.php" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i>
                        Cancel
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