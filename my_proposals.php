<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'Club Leader') {
    header("Location: dashboard.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';

$safeSearch = mysqli_real_escape_string(
    $conn,
    $search
);

$where = "WHERE user_id = $userId";

if ($search !== '') {
    $where .= " AND (
        program_name LIKE '%$safeSearch%'
        OR club_name LIKE '%$safeSearch%'
        OR status LIKE '%$safeSearch%'
        OR location LIKE '%$safeSearch%'
    )";
}

$proposals = mysqli_query(
    $conn,
    "SELECT *
     FROM program_proposals
     $where
     ORDER BY proposal_id DESC"
);

if (!$proposals) {
    die(
        "Database query failed: " .
        mysqli_error($conn)
    );
}

$totalProposals = mysqli_num_rows($proposals);

function getProposalCount($conn, $userId, $status = null)
{
    if ($status === null) {
        $sql = "
            SELECT COUNT(*) AS total
            FROM program_proposals
            WHERE user_id = $userId
        ";
    } else {
        $safeStatus = mysqli_real_escape_string(
            $conn,
            $status
        );

        $sql = "
            SELECT COUNT(*) AS total
            FROM program_proposals
            WHERE user_id = $userId
            AND status = '$safeStatus'
        ";
    }

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int) ($row['total'] ?? 0);
}

$allCount = getProposalCount(
    $conn,
    $userId
);

$pendingCount = getProposalCount(
    $conn,
    $userId,
    'Pending'
);

$approvedCount = getProposalCount(
    $conn,
    $userId,
    'Approved'
);

$rejectedCount = getProposalCount(
    $conn,
    $userId,
    'Rejected'
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>My Proposals | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=42000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main my-proposals-page">

        <div class="page-header">

            <div>
                <h1>My Proposals</h1>

                <p>
                    View and monitor the status of programme proposals submitted by your club.
                </p>
            </div>

            <a href="submit_proposal.php" class="btn-primary">
                <i class="fa-solid fa-file-circle-plus"></i>
                Submit New Proposal
            </a>

        </div>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'submitted'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Proposal submitted successfully and is waiting for administrator approval.
            </div>

        <?php } ?>

        <?php if (
            isset($_GET['success']) &&
            $_GET['success'] === 'updated'
        ) { ?>

            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Proposal updated successfully.
            </div>

        <?php } ?>

        <div class="my-proposal-summary">

            <div class="my-proposal-summary-card">

                <div class="my-proposal-summary-icon blue">
                    <i class="fa-solid fa-file-lines"></i>
                </div>

                <div>
                    <h2><?php echo $allCount; ?></h2>
                    <p>Total Proposals</p>
                </div>

            </div>

            <div class="my-proposal-summary-card">

                <div class="my-proposal-summary-icon orange">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>

                <div>
                    <h2><?php echo $pendingCount; ?></h2>
                    <p>Pending Review</p>
                </div>

            </div>

            <div class="my-proposal-summary-card">

                <div class="my-proposal-summary-icon green">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>
                    <h2><?php echo $approvedCount; ?></h2>
                    <p>Approved</p>
                </div>

            </div>

            <div class="my-proposal-summary-card">

                <div class="my-proposal-summary-icon red">
                    <i class="fa-solid fa-circle-xmark"></i>
                </div>

                <div>
                    <h2><?php echo $rejectedCount; ?></h2>
                    <p>Rejected</p>
                </div>

            </div>

        </div>

        <div class="card my-proposals-card">

            <div class="section-header">

                <div>
                    <h2>Proposal List</h2>

                    <p>
                        Search your proposals by programme, club, location or status.
                    </p>
                </div>

            </div>

            <form method="GET" class="my-proposals-search-form">

                <div class="search-input-wrap">

                    <i class="fa-solid fa-magnifying-glass search-icon"></i>

                    <input type="text" name="search" placeholder="Search programme, club, location or status..."
                        value="<?php echo htmlspecialchars($search); ?>">

                </div>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search
                </button>

                <a href="my_proposals.php" class="btn-secondary">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset
                </a>

            </form>

            <div class="table-responsive">

                <table class="my-proposals-table">

                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Programme Name</th>
                            <th>Club</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalProposals > 0) { ?>

                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($proposals)) {
                                $statusClass = strtolower(
                                    str_replace(
                                        ' ',
                                        '-',
                                        $row['status']
                                    )
                                );
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>

                                        <div class="my-proposal-name">
                                            <?php
                                            echo htmlspecialchars(
                                                $row['program_name']
                                            );
                                            ?>
                                        </div>

                                        <?php if (
                                            !empty($row['person_in_charge'])
                                        ) { ?>

                                            <small class="my-proposal-pic">
                                                PIC:
                                                <?php
                                                echo htmlspecialchars(
                                                    $row['person_in_charge']
                                                );
                                                ?>
                                            </small>

                                        <?php } ?>

                                    </td>

                                    <td>
                                        <?php
                                        echo htmlspecialchars(
                                            $row['club_name']
                                        );
                                        ?>
                                    </td>

                                    <td class="my-proposal-date">
                                        <?php
                                        echo date(
                                            "d M Y",
                                            strtotime($row['proposal_date'])
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

                                    <td class="my-proposal-budget">
                                        RM <?php
                                        echo number_format(
                                            (float) $row['budget'],
                                            2
                                        );
                                        ?>
                                    </td>

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

                                    <td>
                                        <div class="proposal-action-group">

                                            <a href="proposal_detail.php?id=<?php
                                            echo (int) $row['proposal_id'];
                                            ?>" class="btn-action btn-view">
                                                <i class="fa-regular fa-eye"></i>
                                                View
                                            </a>

                                            <?php if ($row['status'] === 'Pending') { ?>

                                                <a href="edit_proposal.php?id=<?php
                                                echo (int) $row['proposal_id'];
                                                ?>" class="btn-action btn-edit-proposal">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    Edit
                                                </a>

                                            <?php } else { ?>

                                                <span class="proposal-locked-label">
                                                    <i class="fa-solid fa-lock"></i>
                                                    Locked
                                                </span>

                                            <?php } ?>

                                        </div>
                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="8">

                                    <div class="empty-state">

                                        <div class="empty-state-icon">
                                            <i class="fa-regular fa-file-lines"></i>
                                        </div>

                                        <h3>No proposals found</h3>

                                        <p>
                                            Submit your first programme proposal or reset the search.
                                        </p>

                                        <a href="submit_proposal.php" class="btn-primary">
                                            Submit Proposal
                                        </a>

                                    </div>

                                </td>
                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalProposals > 0) { ?>

                <div class="table-footer">

                    Showing <?php echo $totalProposals; ?>
                    proposal<?php
                    echo $totalProposals !== 1
                        ? 's'
                        : '';
                    ?>

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
        }, 3500);
    </script>

</body>

</html>