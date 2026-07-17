<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid proposal ID.");
}

$proposalId = (int) $_GET['id'];

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        program_proposals.*,
        events.poster AS event_poster
     FROM program_proposals
     LEFT JOIN events
        ON program_proposals.proposal_id = events.proposal_id
     WHERE program_proposals.proposal_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $proposalId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$proposal = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$proposal) {
    die("Proposal not found.");
}

$generatedBy = $_SESSION['name'] ?? 'CampusEvent Administrator';
$generatedDate = date("d F Y, h:i A");

$proposalNumber = "PROP-" . str_pad(
    $proposalId,
    4,
    "0",
    STR_PAD_LEFT
);

$statusClass = strtolower($proposal['status']);

$posterPath = "";

if (!empty($proposal['event_poster'])) {
    $posterPath = "assets/posters/" . rawurlencode(
        $proposal['event_poster']
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo htmlspecialchars($proposalNumber); ?>
        | Proposal Report
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #eef2f7;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            padding: 30px;
        }

        .report-toolbar {
            width: 210mm;
            max-width: 100%;
            margin: 0 auto 18px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .report-toolbar button,
        .report-toolbar a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 42px;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .print-button {
            color: white;
            background: #1d4ed8;
            border: 1px solid #1d4ed8;
        }

        .back-button {
            color: #334155;
            background: white;
            border: 1px solid #cbd5e1;
        }

        .report-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 18mm 17mm 16mm;
            background: white;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.15);
        }

        .report-header {
            display: grid;
            grid-template-columns: 90px 1fr 90px;
            align-items: center;
            gap: 18px;
            padding-bottom: 16px;
            border-bottom: 3px solid #1e3a8a;
        }

        .report-logo {
            width: 85px;
            height: auto;
            display: block;
        }

        .report-header-text {
            text-align: center;
        }

        .report-header-text h1 {
            margin: 0 0 5px;
            color: #111827;
            font-size: 20px;
            text-transform: uppercase;
        }

        .report-header-text h2 {
            margin: 0 0 4px;
            color: #1e3a8a;
            font-size: 16px;
            text-transform: uppercase;
        }

        .report-header-text p {
            margin: 0;
            color: #475569;
            font-size: 11px;
        }

        .report-number {
            text-align: right;
            color: #475569;
            font-size: 10px;
            line-height: 1.5;
        }

        .report-title {
            margin: 24px 0 20px;
            text-align: center;
        }

        .report-title h2 {
            margin: 0 0 7px;
            color: #111827;
            font-size: 23px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .report-title p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        .status-line {
            margin-bottom: 18px;
            text-align: center;
        }

        .status-badge {
            display: inline-block;
            min-width: 105px;
            padding: 7px 15px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-approved {
            color: #166534;
            background: #dcfce7;
        }

        .status-pending {
            color: #92400e;
            background: #fef3c7;
        }

        .status-rejected {
            color: #991b1b;
            background: #fee2e2;
        }

        .section-title {
            margin: 22px 0 10px;
            padding: 8px 11px;
            color: white;
            background: #1e3a8a;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table th,
        .info-table td {
            padding: 9px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
            vertical-align: top;
            font-size: 11px;
            line-height: 1.5;
        }

        .info-table th {
            width: 31%;
            color: #334155;
            background: #f8fafc;
        }

        .text-section {
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            font-size: 11px;
            line-height: 1.7;
            white-space: normal;
        }

        .rejection-box {
            color: #991b1b;
            background: #fff1f2;
            border-color: #fecdd3;
        }

        .poster-section {
            text-align: center;
            page-break-inside: avoid;
        }

        .report-poster {
            display: block;
            max-width: 100%;
            max-height: 150mm;
            margin: 12px auto 0;
            object-fit: contain;
            border: 1px solid #cbd5e1;
        }

        .poster-empty {
            margin-top: 12px;
            padding: 40px;
            color: #94a3b8;
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            font-size: 12px;
        }

        .approval-table {
            width: 100%;
            margin-top: 24px;
            border-collapse: collapse;
        }

        .approval-table td {
            width: 50%;
            height: 80px;
            padding: 10px;
            border: 1px solid #cbd5e1;
            vertical-align: bottom;
            font-size: 11px;
        }

        .signature-line {
            display: block;
            width: 80%;
            margin: 0 auto 8px;
            border-top: 1px solid #111827;
        }

        .signature-text {
            text-align: center;
        }

        .report-footer {
            margin-top: 25px;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-top: 1px solid #cbd5e1;
            color: #64748b;
            font-size: 9px;
        }

        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {

            html,
            body {
                margin: 0;
                padding: 0;
                background: white;
            }

            body {
                padding: 0;
            }

            .report-toolbar {
                display: none !important;
            }

            .report-page {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }

            .section-title,
            .report-header,
            .report-title,
            .info-table,
            .text-section,
            .poster-section,
            .approval-table {
                break-inside: avoid;
            }

            .report-header {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .section-title,
            .status-badge {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>

    <div class="report-toolbar">

        <a href="proposal_detail.php?id=<?php echo $proposalId; ?>" class="back-button">
            Back
        </a>

        <button type="button" class="print-button" onclick="window.print()">
            Print / Save PDF
        </button>

    </div>

    <main class="report-page">

        <header class="report-header">

            <div>
                <img src="assets/images/logo uitm.png" class="report-logo" alt="UiTM Logo">
            </div>

            <div class="report-header-text">
                <h1>Universiti Teknologi MARA</h1>
                <h2>CampusEvent Management System</h2>
                <p>Student Club Event & Proposal Management System</p>
            </div>

            <div class="report-number">
                <strong>Report No.</strong><br>
                <?php echo htmlspecialchars($proposalNumber); ?>
            </div>

        </header>

        <section class="report-title">
            <h2>Programme Proposal Report</h2>
            <p>Official proposal information generated by CampusEvent</p>
        </section>

        <div class="status-line">
            <span class="status-badge status-<?php
            echo htmlspecialchars($statusClass);
            ?>">
                <?php echo htmlspecialchars($proposal['status']); ?>
            </span>
        </div>

        <h3 class="section-title">1. Proposal Information</h3>

        <table class="info-table">
            <tr>
                <th>Proposal Number</th>
                <td><?php echo htmlspecialchars($proposalNumber); ?></td>
            </tr>

            <tr>
                <th>Programme Name</th>
                <td>
                    <?php echo htmlspecialchars($proposal['program_name']); ?>
                </td>
            </tr>

            <tr>
                <th>Club Name</th>
                <td>
                    <?php echo htmlspecialchars($proposal['club_name']); ?>
                </td>
            </tr>

            <tr>
                <th>Programme Date</th>
                <td>
                    <?php
                    echo date(
                        "d F Y",
                        strtotime($proposal['proposal_date'])
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Programme Time</th>
                <td>
                    <?php
                    echo date(
                        "h:i A",
                        strtotime($proposal['proposal_time'])
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Location</th>
                <td>
                    <?php echo htmlspecialchars($proposal['location']); ?>
                </td>
            </tr>

            <tr>
                <th>Estimated Budget</th>
                <td>
                    RM <?php
                    echo number_format(
                        (float) $proposal['budget'],
                        2
                    );
                    ?>
                </td>
            </tr>

            <tr>
                <th>Approval Status</th>
                <td>
                    <?php echo htmlspecialchars($proposal['status']); ?>
                </td>
            </tr>
        </table>

        <h3 class="section-title">2. Programme Objective</h3>

        <div class="text-section">
            <?php
            echo !empty($proposal['objective'])
                ? nl2br(htmlspecialchars($proposal['objective']))
                : 'No objective was provided.';
            ?>
        </div>

        <h3 class="section-title">3. Programme Description</h3>

        <div class="text-section">
            <?php
            echo !empty($proposal['description'])
                ? nl2br(htmlspecialchars($proposal['description']))
                : 'No description was provided.';
            ?>
        </div>

        <?php if (
            $proposal['status'] === 'Rejected' &&
            !empty($proposal['reject_reason'])
        ) { ?>

            <h3 class="section-title">4. Reason for Rejection</h3>

            <div class="text-section rejection-box">
                <?php
                echo nl2br(
                    htmlspecialchars($proposal['reject_reason'])
                );
                ?>
            </div>

        <?php } ?>

        <section class="poster-section">

            <h3 class="section-title">
                <?php
                echo $proposal['status'] === 'Rejected'
                    ? '5. Event Poster'
                    : '4. Event Poster';
                ?>
            </h3>

            <?php if ($posterPath !== "") { ?>

                <img src="<?php echo $posterPath; ?>" class="report-poster" alt="Event Poster">

            <?php } else { ?>

                <div class="poster-empty">
                    No event poster has been uploaded.
                </div>

            <?php } ?>

        </section>

        <table class="approval-table">
            <tr>
                <td>
                    <span class="signature-line"></span>

                    <div class="signature-text">
                        Prepared / Submitted By<br>
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $proposal['club_name']
                            );
                            ?>
                        </strong>
                    </div>
                </td>

                <td>
                    <span class="signature-line"></span>

                    <div class="signature-text">
                        Reviewed / Approved By<br>
                        <strong>CampusEvent Administrator</strong>
                    </div>
                </td>
            </tr>
        </table>

        <footer class="report-footer">

            <div>
                Generated by:
                <?php echo htmlspecialchars($generatedBy); ?>
            </div>

            <div>
                Generated on:
                <?php echo htmlspecialchars($generatedDate); ?>
            </div>

        </footer>

    </main>

</body>

</html>