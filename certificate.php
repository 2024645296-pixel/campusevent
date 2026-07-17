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

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id'])
) {
    header(
        "Location: student_certificates.php?error=unavailable"
    );
    exit();
}

$registrationId = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        registrations.registration_id,
        registrations.full_name,
        registrations.matric_no,
        registrations.attendance_status,
        events.event_name,
        events.club_name,
        events.event_date,
        events.location
     FROM registrations
     INNER JOIN events
        ON registrations.event_id = events.event_id
     WHERE registrations.registration_id = ?
     AND registrations.user_id = ?
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
    "ii",
    $registrationId,
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$certificate = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (
    !$certificate ||
    $certificate['attendance_status'] !== 'Attended'
) {
    header(
        "Location: student_certificates.php?error=unavailable"
    );
    exit();
}

$certificateNumber =
    'CE-' .
    date(
        'Y',
        strtotime($certificate['event_date'])
    ) .
    '-' .
    str_pad(
        (string) $registrationId,
        5,
        '0',
        STR_PAD_LEFT
    );

$issueDate = date('d M Y');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        Certificate -
        <?php
        echo htmlspecialchars($certificate['event_name']);
        ?>
    </title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 28px;
            color: #172033;
            background: #eef2f7;
            font-family: Georgia, "Times New Roman", serif;
        }

        .certificate-toolbar {
            width: min(1120px, 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin: 0 auto 18px;
            font-family: Arial, sans-serif;
        }

        .certificate-toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .toolbar-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 16px;
            color: #ffffff;
            background: #2563eb;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .toolbar-btn.secondary {
            color: #334155;
            background: #ffffff;
            border: 1px solid #cbd5e1;
        }

        .certificate-sheet {
            width: min(1120px, 100%);
            min-height: 760px;
            position: relative;
            margin: 0 auto;
            padding: 58px 70px;
            overflow: hidden;
            background: #ffffff;
            border: 16px solid #172554;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
        }

        .certificate-sheet::before {
            content: "";
            position: absolute;
            inset: 12px;
            pointer-events: none;
            border: 3px solid #c59b3c;
        }

        .certificate-sheet::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            right: -180px;
            bottom: -180px;
            border: 40px solid rgba(197, 155, 60, 0.12);
            border-radius: 50%;
        }

        .certificate-corner {
            position: absolute;
            width: 150px;
            height: 150px;
            border-color: #c59b3c;
            border-style: solid;
        }

        .certificate-corner.top-left {
            top: 29px;
            left: 29px;
            border-width: 4px 0 0 4px;
        }

        .certificate-corner.top-right {
            top: 29px;
            right: 29px;
            border-width: 4px 4px 0 0;
        }

        .certificate-corner.bottom-left {
            bottom: 29px;
            left: 29px;
            border-width: 0 0 4px 4px;
        }

        .certificate-corner.bottom-right {
            right: 29px;
            bottom: 29px;
            border-width: 0 4px 4px 0;
        }

        .certificate-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .certificate-brand {
            margin-bottom: 34px;
        }

        .certificate-brand-mark {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 66px;
            height: 66px;
            margin-bottom: 12px;
            color: #c59b3c;
            border: 3px solid #c59b3c;
            border-radius: 50%;
            font-size: 28px;
        }

        .certificate-brand h3 {
            margin: 0;
            color: #172554;
            font-family: Arial, sans-serif;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .certificate-brand h3 span {
            color: #2563eb;
        }

        .certificate-brand p {
            margin: 5px 0 0;
            color: #64748b;
            font-family: Arial, sans-serif;
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .certificate-title-small {
            margin-bottom: 5px;
            color: #c59b3c;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 6px;
            text-transform: uppercase;
        }

        .certificate-title {
            margin: 0;
            color: #172554;
            font-size: 52px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .certificate-subtitle {
            margin: 5px 0 30px;
            color: #475569;
            font-family: Arial, sans-serif;
            font-size: 17px;
            letter-spacing: 4px;
            text-transform: uppercase;
        }

        .certificate-presented {
            margin: 0 0 13px;
            color: #64748b;
            font-size: 17px;
            font-style: italic;
        }

        .certificate-name {
            display: inline-block;
            min-width: 70%;
            margin: 0 auto 22px;
            padding: 0 25px 10px;
            color: #172554;
            border-bottom: 2px solid #c59b3c;
            font-size: 37px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .certificate-text {
            max-width: 780px;
            margin: 0 auto;
            color: #475569;
            font-size: 17px;
            line-height: 1.8;
        }

        .certificate-event-name {
            display: block;
            margin: 10px 0;
            color: #172554;
            font-size: 25px;
            font-weight: 700;
        }

        .certificate-meta {
            display: flex;
            justify-content: center;
            gap: 35px;
            flex-wrap: wrap;
            margin-top: 25px;
            color: #475569;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .certificate-meta div {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .certificate-meta i {
            color: #c59b3c;
        }

        .certificate-signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 90px;
            max-width: 650px;
            margin: 65px auto 0;
        }

        .certificate-signature {
            text-align: center;
        }

        .certificate-signature-line {
            margin-bottom: 8px;
            border-top: 1px solid #334155;
        }

        .certificate-signature strong {
            display: block;
            color: #172554;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .certificate-signature span {
            color: #64748b;
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        .certificate-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-top: 35px;
            color: #64748b;
            font-family: Arial, sans-serif;
            font-size: 9px;
        }

        .certificate-seal {
            display: grid;
            place-items: center;
            width: 76px;
            height: 76px;
            color: #c59b3c;
            border: 3px double #c59b3c;
            border-radius: 50%;
            font-size: 9px;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            transform: rotate(-8deg);
        }

        @media (max-width: 800px) {
            body {
                padding: 12px;
            }

            .certificate-sheet {
                min-height: auto;
                padding: 45px 30px;
            }

            .certificate-title {
                font-size: 34px;
            }

            .certificate-name {
                min-width: 90%;
                font-size: 27px;
            }

            .certificate-signatures {
                gap: 35px;
            }
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }

            body {
                padding: 0;
                background: #ffffff;
            }

            .certificate-toolbar {
                display: none !important;
            }

            .certificate-sheet {
                width: 297mm;
                height: 210mm;
                min-height: 210mm;
                margin: 0;
                padding: 18mm 22mm;
                border-width: 10mm;
                box-shadow: none;
                page-break-after: avoid;
            }
        }
    </style>
</head>

<body>

    <div class="certificate-toolbar">

        <div class="certificate-toolbar-left">

            <a href="student_certificates.php" class="toolbar-btn secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>

        </div>

        <button type="button" class="toolbar-btn" onclick="window.print()">
            <i class="fa-solid fa-file-pdf"></i>
            Print / Save as PDF
        </button>

    </div>

    <div class="certificate-sheet">

        <div class="certificate-corner top-left"></div>
        <div class="certificate-corner top-right"></div>
        <div class="certificate-corner bottom-left"></div>
        <div class="certificate-corner bottom-right"></div>

        <div class="certificate-content">

            <div class="certificate-brand">

                <div class="certificate-brand-mark">
                    <i class="fa-solid fa-award"></i>
                </div>

                <h3>
                    Campus<span>Event</span>
                </h3>

                <p>
                    Student Club Event Management System
                </p>

            </div>

            <div class="certificate-title-small">
                Certificate
            </div>

            <h1 class="certificate-title">
                Certificate
            </h1>

            <p class="certificate-subtitle">
                of Participation
            </p>

            <p class="certificate-presented">
                This certificate is proudly presented to
            </p>

            <div class="certificate-name">
                <?php
                echo htmlspecialchars(
                    $certificate['full_name']
                );
                ?>
            </div>

            <p class="certificate-text">

                for successfully participating in

                <span class="certificate-event-name">
                    <?php
                    echo htmlspecialchars(
                        $certificate['event_name']
                    );
                    ?>
                </span>

                held on
                <?php
                echo date(
                    "d F Y",
                    strtotime($certificate['event_date'])
                );
                ?>

                at

                <?php
                echo htmlspecialchars(
                    $certificate['location']
                );
                ?>.

                This certificate recognises the participant's
                involvement and contribution to the programme.

            </p>

            <div class="certificate-meta">

                <div>
                    <i class="fa-solid fa-users"></i>

                    Organized by

                    <?php
                    echo !empty($certificate['club_name'])
                        ? htmlspecialchars(
                            $certificate['club_name']
                        )
                        : 'CampusEvent';
                    ?>
                </div>

                <div>
                    <i class="fa-solid fa-star"></i>
                    10 Merit Points
                </div>

                <div>
                    <i class="fa-solid fa-id-card"></i>

                    <?php
                    echo htmlspecialchars(
                        $certificate['matric_no']
                    );
                    ?>
                </div>

            </div>

            <div class="certificate-signatures">

                <div class="certificate-signature">

                    <div class="certificate-signature-line"></div>

                    <strong>
                        Club Advisor
                    </strong>

                    <span>
                        Signature
                    </span>

                </div>

                <div class="certificate-signature">

                    <div class="certificate-signature-line"></div>

                    <strong>
                        Student Affairs Representative
                    </strong>

                    <span>
                        Signature and Official Stamp
                    </span>

                </div>

            </div>

            <div class="certificate-footer">

                <div>
                    <strong>Certificate No:</strong>
                    <?php echo $certificateNumber; ?>

                    <br>

                    <strong>Issued:</strong>
                    <?php echo $issueDate; ?>
                </div>

                <div class="certificate-seal">
                    Verified<br>
                    CampusEvent
                </div>

                <div>
                    This certificate was generated electronically
                    through CampusEvent.
                </div>

            </div>

        </div>

    </div>

</body>

</html>