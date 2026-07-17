<?php
include 'includes/db.php';

$id = $_POST['proposal_id'];
$reason = $_POST['reject_reason'];

mysqli_query($conn, "
UPDATE program_proposals
SET status='Rejected',
reject_reason='$reason'
WHERE proposal_id='$id'
");

header("Location: manage_proposals.php");
?>