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

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$safeSearch = mysqli_real_escape_string($conn, $search);

if ($search !== "") {
    $sql = "
        SELECT *
        FROM program_proposals
        WHERE program_name LIKE '%$safeSearch%'
        OR club_name LIKE '%$safeSearch%'
        OR status LIKE '%$safeSearch%'
        OR proposal_date LIKE '%$safeSearch%'
        ORDER BY proposal_id DESC
    ";
} else {
    $sql = "
        SELECT *
        FROM program_proposals
        ORDER BY proposal_id DESC
    ";
}

$proposals = mysqli_query($conn, $sql);

if (!$proposals) {
    die("Database query failed: " . mysqli_error($conn));
}

$totalProposals = mysqli_num_rows($proposals);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Proposals | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=50001">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main">

        <div class="page-header">

            <div>
                <h1>Manage Proposals</h1>

                <p>
                    Review submitted proposals before making approval decisions.
                </p>
            </div>

            <div class="page-summary">
                <span><?php echo $totalProposals; ?></span>
                <small>Proposal<?php echo $totalProposals !== 1 ? 's' : ''; ?></small>
            </div>

        </div>

        <?php if (isset($_GET['success'])) { ?>
            <div class="toast-success" id="successMsg">
                <span>✓</span>
                Proposal status updated successfully.
            </div>
        <?php } ?>

        <div class="card proposals-card">

            <div class="section-header">

                <div>
                    <h2>All Program Proposals</h2>

                    <p>
                        Search and review proposals submitted by club leaders.
                    </p>
                </div>

            </div>

            <form method="GET" class="proposal-search-form">

                <div class="search-input-wrap">
                    <span class="search-icon">⌕</span>

                    <input type="text" name="search" placeholder="Search proposal, club, status or date..."
                        value="<?php echo htmlspecialchars($search); ?>">
                </div>

                <button type="submit" class="btn-primary">
                    Search
                </button>

                <a href="manage_proposals.php" class="btn-secondary">
                    Reset
                </a>

            </form>

            <div class="table-responsive">

                <table class="proposal-table">

                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Program Name</th>
                            <th>Club</th>
                            <th>Date</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th>Review</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if ($totalProposals > 0) { ?>

                            <?php
                            $no = 1;

                            while ($row = mysqli_fetch_assoc($proposals)) {
                                $statusClass = strtolower($row['status']);
                                ?>

                                <tr>

                                    <td>
                                        <?php echo $no++; ?>
                                    </td>

                                    <td>
                                        <div class="proposal-name">
                                            <?php echo htmlspecialchars($row['program_name']); ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?php echo htmlspecialchars($row['club_name']); ?>
                                    </td>

                                    <td>
                                        <?php echo date("d M Y", strtotime($row['proposal_date'])); ?>
                                    </td>

                                    <td>
                                        RM <?php echo number_format((float) $row['budget'], 2); ?>
                                    </td>

                                    <td>
                                        <span class="badge badge-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <a class="btn-view" href="proposal_detail.php?id=<?php echo $row['proposal_id']; ?>">
                                            View Details
                                            <span>›</span>
                                        </a>
                                    </td>

                                </tr>

                            <?php } ?>

                        <?php } else { ?>

                            <tr>
                                <td colspan="7">

                                    <div class="empty-state">
                                        <div class="empty-state-icon">📄</div>

                                        <h3>No proposals found</h3>

                                        <p>
                                            Try another search keyword or reset the search.
                                        </p>

                                        <a href="manage_proposals.php" class="btn-secondary">
                                            Reset Search
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
                    proposal<?php echo $totalProposals !== 1 ? 's' : ''; ?>
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