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
$error = "";

/* Validate event ID */
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php?error=invalid");
    exit();
}

$eventId = (int) $_GET['id'];

/* Get event */
$eventStmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM events
     WHERE event_id = ?
     LIMIT 1"
);

if (!$eventStmt) {
    die("Database preparation failed: " . mysqli_error($conn));
}

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

/* Prevent registration for unavailable events */
$today = date('Y-m-d');

$isExpired =
    !empty($event['event_date']) &&
    $event['event_date'] < $today;

if (
    $event['status'] !== 'Upcoming' ||
    $isExpired
) {
    header("Location: events.php?error=closed");
    exit();
}

/* Get student information */
$userStmt = mysqli_prepare(
    $conn,
    "SELECT
        name,
        matric_no,
        email,
        phone
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $userStmt,
    "i",
    $userId
);

mysqli_stmt_execute($userStmt);

$userResult = mysqli_stmt_get_result($userStmt);
$user = mysqli_fetch_assoc($userResult);

mysqli_stmt_close($userStmt);

if (!$user) {
    header("Location: logout.php");
    exit();
}

/* Check duplicate registration */
$checkStmt = mysqli_prepare(
    $conn,
    "SELECT registration_id
     FROM registrations
     WHERE event_id = ?
     AND user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkStmt,
    "ii",
    $eventId,
    $userId
);

mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);
$existingRegistration = mysqli_fetch_assoc($checkResult);

mysqli_stmt_close($checkStmt);

$alreadyRegistered = !empty($existingRegistration);

/* Default form values */
$fullName = $user['name'] ?? '';
$matricNo = $user['matric_no'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$paymentMethod = '';

/* Submit registration */
if (
    isset($_POST['register']) &&
    !$alreadyRegistered
) {
    $fullName = trim($_POST['full_name'] ?? '');
    $matricNo = trim($_POST['matric_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $eventFee = (float) ($event['event_fee'] ?? 0);
    $receipt = '';
    $attendanceStatus = 'Registered';

    if ($fullName === '') {
        $error = "Please enter your full name.";
    } elseif ($matricNo === '') {
        $error = "Please enter your matric number.";
    } elseif ($email === '') {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($phone === '') {
        $error = "Please enter your phone number.";
    }

    if ($eventFee > 0) {
        $paymentMethod = trim(
            $_POST['payment_method'] ?? ''
        );

        $paymentStatus = 'Pending Verification';

        if ($error === '' && $paymentMethod === '') {
            $error = "Please select a payment method.";
        }

        if (
            $error === '' &&
            (
                !isset($_FILES['receipt']) ||
                $_FILES['receipt']['error'] === UPLOAD_ERR_NO_FILE
            )
        ) {
            $error = "Please upload your payment receipt.";
        }

        if (
            $error === '' &&
            isset($_FILES['receipt']) &&
            $_FILES['receipt']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            if ($_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
                $error = "Unable to upload the payment receipt.";
            } else {
                $allowedMimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'application/pdf' => 'pdf'
                ];

                $maxFileSize = 5 * 1024 * 1024;

                if ($_FILES['receipt']['size'] > $maxFileSize) {
                    $error = "Receipt size must not exceed 5 MB.";
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);

                    $mimeType = finfo_file(
                        $finfo,
                        $_FILES['receipt']['tmp_name']
                    );

                    finfo_close($finfo);

                    if (!isset($allowedMimeTypes[$mimeType])) {
                        $error = "Only JPG, PNG, WEBP and PDF receipts are allowed.";
                    } else {
                        $uploadDirectory =
                            __DIR__ . '/assets/receipts/';

                        if (!is_dir($uploadDirectory)) {
                            mkdir(
                                $uploadDirectory,
                                0775,
                                true
                            );
                        }

                        $extension =
                            $allowedMimeTypes[$mimeType];

                        $receipt =
                            'receipt_' .
                            $userId . '_' .
                            $eventId . '_' .
                            time() . '.' .
                            $extension;

                        $destination =
                            $uploadDirectory . $receipt;

                        if (
                            !move_uploaded_file(
                                $_FILES['receipt']['tmp_name'],
                                $destination
                            )
                        ) {
                            $error = "Unable to save the uploaded receipt.";
                        }
                    }
                }
            }
        }
    } else {
        $paymentMethod = 'Free Event';
        $paymentStatus = 'Paid';
    }

    if ($error === '') {
        $insertStmt = mysqli_prepare(
            $conn,
            "INSERT INTO registrations
            (
                event_id,
                user_id,
                full_name,
                matric_no,
                email,
                phone,
                payment_status,
                payment_method,
                receipt,
                attendance_status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$insertStmt) {
            $error =
                "Database preparation failed: " .
                mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $insertStmt,
                "iissssssss",
                $eventId,
                $userId,
                $fullName,
                $matricNo,
                $email,
                $phone,
                $paymentStatus,
                $paymentMethod,
                $receipt,
                $attendanceStatus
            );

            if (mysqli_stmt_execute($insertStmt)) {
                mysqli_stmt_close($insertStmt);

                header(
                    "Location: events.php?success=registered"
                );
                exit();
            }

            $error =
                "Registration failed: " .
                mysqli_stmt_error($insertStmt);

            mysqli_stmt_close($insertStmt);

            if ($receipt !== '') {
                $receiptPath =
                    __DIR__ .
                    '/assets/receipts/' .
                    $receipt;

                if (is_file($receiptPath)) {
                    unlink($receiptPath);
                }
            }
        }
    }
}

$eventFee = (float) ($event['event_fee'] ?? 0);

$statusClass = strtolower(
    str_replace(' ', '-', $event['status'])
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register Event | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=90000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main register-event-page">

        <div class="page-header">

            <div>

                <a href="event_detail.php?id=<?php echo $eventId; ?>" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to Event
                </a>

                <h1>Event Registration</h1>

                <p>
                    Complete your details and payment information to join this event.
                </p>

            </div>

            <div class="page-summary">

                <span>
                    <i class="fa-solid fa-user-plus"></i>
                </span>

                <small>Registration</small>

            </div>

        </div>

        <?php if ($error !== '') { ?>

            <div class="error register-event-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>

        <div class="register-event-layout">

            <aside class="card register-event-summary">

                <?php if (!empty($event['poster'])) { ?>

                    <img src="assets/posters/<?php
                    echo rawurlencode($event['poster']);
                    ?>" class="register-event-poster" alt="<?php
                    echo htmlspecialchars(
                        $event['event_name']
                    );
                    ?>">

                <?php } else { ?>

                    <div class="register-event-poster-empty">

                        <i class="fa-regular fa-image"></i>

                        <span>No poster uploaded</span>

                    </div>

                <?php } ?>

                <div class="register-event-summary-body">

                    <span class="badge badge-<?php
                    echo htmlspecialchars($statusClass);
                    ?>">
                        <?php
                        echo htmlspecialchars(
                            $event['status']
                        );
                        ?>
                    </span>

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $event['event_name']
                        );
                        ?>
                    </h2>

                    <?php if (!empty($event['club_name'])) { ?>

                        <p class="register-event-organizer">

                            <i class="fa-solid fa-users"></i>

                            <?php
                            echo htmlspecialchars(
                                $event['club_name']
                            );
                            ?>

                        </p>

                    <?php } ?>

                    <div class="register-event-meta">

                        <div>
                            <i class="fa-regular fa-calendar"></i>

                            <span>
                                <?php
                                echo date(
                                    "d M Y",
                                    strtotime($event['event_date'])
                                );
                                ?>
                            </span>
                        </div>

                        <div>
                            <i class="fa-regular fa-clock"></i>

                            <span>
                                <?php
                                echo date(
                                    "h:i A",
                                    strtotime($event['event_time'])
                                );
                                ?>
                            </span>
                        </div>

                        <div>
                            <i class="fa-solid fa-location-dot"></i>

                            <span>
                                <?php
                                echo htmlspecialchars(
                                    $event['location']
                                );
                                ?>
                            </span>
                        </div>

                        <div>
                            <i class="fa-solid fa-ticket"></i>

                            <span>
                                <?php
                                echo $eventFee > 0
                                    ? 'RM ' .
                                    number_format(
                                        $eventFee,
                                        2
                                    )
                                    : 'Free Event';
                                ?>
                            </span>
                        </div>

                    </div>

                </div>

            </aside>

            <div class="card register-event-card">

                <?php if ($alreadyRegistered) { ?>

                    <div class="registration-complete-state">

                        <div class="registration-complete-icon">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>

                        <h2>Already Registered</h2>

                        <p>
                            You have already registered for this event.
                            View your registration to check payment and attendance status.
                        </p>

                        <div class="register-event-actions">

                            <a href="my_registrations.php" class="btn-primary">
                                <i class="fa-solid fa-clipboard-list"></i>
                                View My Registrations
                            </a>

                            <a href="events.php" class="btn-secondary">
                                Back to Events
                            </a>

                        </div>

                    </div>

                <?php } else { ?>

                    <div class="section-header">

                        <div>
                            <h2>Student Information</h2>

                            <p>
                                Review your details before submitting the registration.
                            </p>
                        </div>

                        <span class="register-section-icon">
                            <i class="fa-regular fa-id-card"></i>
                        </span>

                    </div>

                    <form method="POST" enctype="multipart/form-data" class="register-event-form">

                        <div class="form-grid">

                            <div class="form-group form-full">

                                <label for="full_name">
                                    Full Name
                                    <span class="required-mark">*</span>
                                </label>

                                <input type="text" id="full_name" name="full_name" value="<?php
                                echo htmlspecialchars($fullName);
                                ?>" required>

                            </div>

                            <div class="form-group">

                                <label for="matric_no">
                                    Matric Number
                                    <span class="required-mark">*</span>
                                </label>

                                <input type="text" id="matric_no" name="matric_no" value="<?php
                                echo htmlspecialchars($matricNo);
                                ?>" required>

                            </div>

                            <div class="form-group">

                                <label for="phone">
                                    Phone Number
                                    <span class="required-mark">*</span>
                                </label>

                                <input type="text" id="phone" name="phone" value="<?php
                                echo htmlspecialchars($phone);
                                ?>" placeholder="Example: 0123456789" required>

                            </div>

                            <div class="form-group form-full">

                                <label for="email">
                                    Email Address
                                    <span class="required-mark">*</span>
                                </label>

                                <input type="email" id="email" name="email" value="<?php
                                echo htmlspecialchars($email);
                                ?>" required>

                            </div>

                        </div>

                        <?php if ($eventFee > 0) { ?>

                            <div class="register-payment-section">

                                <div class="section-header">

                                    <div>
                                        <h2>Payment Information</h2>

                                        <p>
                                            Complete payment and upload your receipt for verification.
                                        </p>
                                    </div>

                                    <span class="register-section-icon">
                                        <i class="fa-solid fa-credit-card"></i>
                                    </span>

                                </div>

                                <div class="payment-amount-box">

                                    <span>Amount to Pay</span>

                                    <strong>
                                        RM <?php
                                        echo number_format(
                                            $eventFee,
                                            2
                                        );
                                        ?>
                                    </strong>

                                    <small>
                                        Payment status will be Pending Verification after submission.
                                    </small>

                                </div>

                                <div class="form-grid">

                                    <div class="form-group form-full">

                                        <label for="payment_method">
                                            Payment Method
                                            <span class="required-mark">*</span>
                                        </label>

                                        <select id="payment_method" name="payment_method" required>
                                            <option value="">
                                                Select Payment Method
                                            </option>

                                            <option value="Online Banking" <?php
                                            echo $paymentMethod ===
                                                'Online Banking'
                                                ? 'selected'
                                                : '';
                                            ?>>
                                                Online Banking
                                            </option>

                                            <option value="QR Payment" <?php
                                            echo $paymentMethod ===
                                                'QR Payment'
                                                ? 'selected'
                                                : '';
                                            ?>>
                                                QR Payment
                                            </option>

                                            <option value="Cash" <?php
                                            echo $paymentMethod ===
                                                'Cash'
                                                ? 'selected'
                                                : '';
                                            ?>>
                                                Cash
                                            </option>

                                        </select>

                                    </div>

                                    <div class="form-group form-full">

                                        <label for="receipt">
                                            Payment Receipt
                                            <span class="required-mark">*</span>
                                        </label>

                                        <input type="file" id="receipt" name="receipt"
                                            accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf"
                                            required>

                                        <small class="register-file-note">
                                            Accepted files: JPG, PNG, WEBP or PDF. Maximum 5 MB.
                                        </small>

                                    </div>

                                </div>

                            </div>

                        <?php } else { ?>

                            <div class="free-registration-box">

                                <i class="fa-solid fa-circle-check"></i>

                                <div>
                                    <strong>Free Event</strong>

                                    <p>
                                        No payment is required. Payment status will be marked as Paid automatically.
                                    </p>
                                </div>

                            </div>

                        <?php } ?>

                        <div class="registration-confirmation-note">

                            <i class="fa-solid fa-circle-info"></i>

                            <p>
                                By submitting this form, you confirm that all information provided is correct.
                            </p>

                        </div>

                        <div class="register-event-actions">

                            <button type="submit" name="register" class="btn-primary">
                                <i class="fa-solid fa-paper-plane"></i>
                                Submit Registration
                            </button>

                            <a href="event_detail.php?id=<?php
                            echo $eventId;
                            ?>" class="btn-secondary">
                                Cancel
                            </a>

                        </div>

                    </form>

                <?php } ?>

            </div>

        </div>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

</body>

</html>