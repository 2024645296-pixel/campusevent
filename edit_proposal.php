<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'Club Leader') {
    header("Location: dashboard.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$error = "";

/* =========================================
   VALIDATE PROPOSAL ID
========================================= */

if (
    !isset($_GET['id']) ||
    !is_numeric($_GET['id']) ||
    (int) $_GET['id'] < 1
) {
    header("Location: my_proposals.php?error=invalid");
    exit();
}

$proposalId = (int) $_GET['id'];

/* =========================================
   HELPER FUNCTIONS
========================================= */

function deleteFileIfExists(string $path): void
{
    if ($path !== '' && is_file($path)) {
        unlink($path);
    }
}

function uploadDocument(
    string $fieldName,
    string $label,
    int $userId,
    string $directory,
    array &$newUploadedPaths,
    string &$error
): string {
    if (
        !isset($_FILES[$fieldName]) ||
        $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return "";
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        $error = "Unable to upload the {$label}.";
        return "";
    }

    if ($_FILES[$fieldName]['size'] > 5 * 1024 * 1024) {
        $error = "{$label} must not exceed 5 MB.";
        return "";
    }

    $allowedMimeTypes = [
        'application/pdf' =>
            'pdf',
        'application/msword' =>
            'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' =>
            'docx',
        'image/jpeg' =>
            'jpg',
        'image/png' =>
            'png'
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    if (!$finfo) {
        $error = "Unable to validate the {$label}.";
        return "";
    }

    $mimeType = finfo_file(
        $finfo,
        $_FILES[$fieldName]['tmp_name']
    );

    finfo_close($finfo);

    if (!isset($allowedMimeTypes[$mimeType])) {
        $error = "{$label} must be PDF, DOC, DOCX, JPG or PNG.";
        return "";
    }

    if (
        !is_dir($directory) &&
        !mkdir($directory, 0775, true)
    ) {
        $error = "Unable to create the supporting document folder.";
        return "";
    }

    $storedName =
        $fieldName . '_' .
        $userId . '_' .
        time() . '_' .
        bin2hex(random_bytes(4)) . '.' .
        $allowedMimeTypes[$mimeType];

    $destination = $directory . $storedName;

    if (
        !move_uploaded_file(
            $_FILES[$fieldName]['tmp_name'],
            $destination
        )
    ) {
        $error = "Unable to save the {$label}.";
        return "";
    }

    $newUploadedPaths[] = $destination;

    return $storedName;
}

/* =========================================
   GET CURRENT PROPOSAL
========================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM program_proposals
     WHERE proposal_id = ?
       AND user_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $proposalId,
    $userId
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$proposal = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$proposal) {
    header("Location: my_proposals.php?error=notfound");
    exit();
}

/* Only pending proposals can be edited */
if (($proposal['status'] ?? '') !== 'Pending') {
    header("Location: my_proposals.php?error=locked");
    exit();
}

/* Current form values */
$programName = $proposal['program_name'] ?? '';
$clubName = $proposal['club_name'] ?? '';
$personInCharge = $proposal['person_in_charge'] ?? '';
$objective = $proposal['objective'] ?? '';
$description = $proposal['description'] ?? '';
$proposalDate = $proposal['proposal_date'] ?? '';
$proposalTime = $proposal['proposal_time'] ?? '';
$location = $proposal['location'] ?? '';
$expectedParticipants = (int) ($proposal['expected_participants'] ?? 0);
$budget = (float) ($proposal['budget'] ?? 0);
$eventFee = (float) ($proposal['event_fee'] ?? 0);

$currentPoster = $proposal['poster'] ?? '';
$currentOfficialLetter = $proposal['official_letter'] ?? '';
$currentProposalPaper = $proposal['proposal_paper'] ?? '';
$currentActivityForm = $proposal['activity_form'] ?? '';
$currentSpeakerProfile = $proposal['speaker_profile'] ?? '';

/* =========================================
   UPDATE PROPOSAL
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName =
        trim($_POST['program_name'] ?? '');

    $clubName =
        trim($_POST['club_name'] ?? '');

    $personInCharge =
        trim($_POST['person_in_charge'] ?? '');

    $objective =
        trim($_POST['objective'] ?? '');

    $description =
        trim($_POST['description'] ?? '');

    $proposalDate =
        trim($_POST['proposal_date'] ?? '');

    $proposalTime =
        trim($_POST['proposal_time'] ?? '');

    $location =
        trim($_POST['location'] ?? '');

    $expectedParticipants =
        (int) ($_POST['expected_participants'] ?? 0);

    $budget =
        (float) ($_POST['budget'] ?? 0);

    $eventFee =
        (float) ($_POST['event_fee'] ?? 0);

    $today = date('Y-m-d');

    if ($programName === '') {
        $error = "Please enter the programme name.";
    } elseif ($clubName === '') {
        $error = "Please enter the club name.";
    } elseif ($personInCharge === '') {
        $error = "Please enter the person in charge.";
    } elseif ($objective === '') {
        $error = "Please enter the programme objective.";
    } elseif ($proposalDate === '') {
        $error = "Please select the programme date.";
    } elseif ($proposalDate < $today) {
        $error = "Programme date cannot be earlier than today.";
    } elseif ($proposalTime === '') {
        $error = "Please select the programme time.";
    } elseif ($location === '') {
        $error = "Please enter the programme location.";
    } elseif ($expectedParticipants < 1) {
        $error = "Expected participants must be at least 1.";
    } elseif ($budget < 0) {
        $error = "Budget cannot be negative.";
    } elseif ($eventFee < 0) {
        $error = "Event fee cannot be negative.";
    }

    $newUploadedPaths = [];
    $filesToDeleteAfterSuccess = [];

    $poster = $currentPoster;
    $officialLetter = $currentOfficialLetter;
    $proposalPaper = $currentProposalPaper;
    $activityForm = $currentActivityForm;
    $speakerProfile = $currentSpeakerProfile;

    /* =========================================
       OPTIONAL NEW POSTER
    ========================================= */

    if (
        $error === '' &&
        isset($_FILES['poster']) &&
        $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
            $error = "Unable to upload the programme poster.";
        } elseif ($_FILES['poster']['size'] > 5 * 1024 * 1024) {
            $error = "Poster size must not exceed 5 MB.";
        } else {
            $allowedPosterTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);

            if (!$finfo) {
                $error = "Unable to validate the poster.";
            } else {
                $posterMime = finfo_file(
                    $finfo,
                    $_FILES['poster']['tmp_name']
                );

                finfo_close($finfo);

                if (!isset($allowedPosterTypes[$posterMime])) {
                    $error =
                        "Only JPG, PNG and WEBP poster files are allowed.";
                } else {
                    $posterDirectory =
                        __DIR__ . '/assets/posters/';

                    if (
                        !is_dir($posterDirectory) &&
                        !mkdir($posterDirectory, 0775, true)
                    ) {
                        $error =
                            "Unable to create the poster upload folder.";
                    } else {
                        $newPoster =
                            'proposal_' .
                            $userId . '_' .
                            time() . '_' .
                            bin2hex(random_bytes(4)) . '.' .
                            $allowedPosterTypes[$posterMime];

                        $newPosterPath =
                            $posterDirectory . $newPoster;

                        if (
                            !move_uploaded_file(
                                $_FILES['poster']['tmp_name'],
                                $newPosterPath
                            )
                        ) {
                            $error =
                                "Unable to save the uploaded poster.";
                        } else {
                            $newUploadedPaths[] =
                                $newPosterPath;

                            if ($currentPoster !== '') {
                                $filesToDeleteAfterSuccess[] =
                                    $posterDirectory .
                                    $currentPoster;
                            }

                            $poster = $newPoster;
                        }
                    }
                }
            }
        }
    }

    /* =========================================
       OPTIONAL NEW DOCUMENTS
    ========================================= */

    $documentDirectory =
        __DIR__ . '/assets/proposal_documents/';

    if ($error === '') {
        $newOfficialLetter = uploadDocument(
            'official_letter',
            'Official Letter',
            $userId,
            $documentDirectory,
            $newUploadedPaths,
            $error
        );

        if ($newOfficialLetter !== '') {
            if ($currentOfficialLetter !== '') {
                $filesToDeleteAfterSuccess[] =
                    $documentDirectory .
                    $currentOfficialLetter;
            }

            $officialLetter =
                $newOfficialLetter;
        }
    }

    if ($error === '') {
        $newProposalPaper = uploadDocument(
            'proposal_paper',
            'Proposal Paper',
            $userId,
            $documentDirectory,
            $newUploadedPaths,
            $error
        );

        if ($newProposalPaper !== '') {
            if ($currentProposalPaper !== '') {
                $filesToDeleteAfterSuccess[] =
                    $documentDirectory .
                    $currentProposalPaper;
            }

            $proposalPaper =
                $newProposalPaper;
        }
    }

    if ($error === '') {
        $newActivityForm = uploadDocument(
            'activity_form',
            'Activity Application Form',
            $userId,
            $documentDirectory,
            $newUploadedPaths,
            $error
        );

        if ($newActivityForm !== '') {
            if ($currentActivityForm !== '') {
                $filesToDeleteAfterSuccess[] =
                    $documentDirectory .
                    $currentActivityForm;
            }

            $activityForm =
                $newActivityForm;
        }
    }

    if ($error === '') {
        $newSpeakerProfile = uploadDocument(
            'speaker_profile',
            'Speaker Profile Form',
            $userId,
            $documentDirectory,
            $newUploadedPaths,
            $error
        );

        if ($newSpeakerProfile !== '') {
            if ($currentSpeakerProfile !== '') {
                $filesToDeleteAfterSuccess[] =
                    $documentDirectory .
                    $currentSpeakerProfile;
            }

            $speakerProfile =
                $newSpeakerProfile;
        }
    }

    /* =========================================
       DATABASE UPDATE
    ========================================= */

    if ($error === '') {
        $updateStmt = mysqli_prepare(
            $conn,
            "UPDATE program_proposals
             SET
                program_name = ?,
                club_name = ?,
                person_in_charge = ?,
                objective = ?,
                description = ?,
                proposal_date = ?,
                proposal_time = ?,
                location = ?,
                expected_participants = ?,
                budget = ?,
                event_fee = ?,
                poster = ?,
                official_letter = ?,
                proposal_paper = ?,
                activity_form = ?,
                speaker_profile = ?
             WHERE proposal_id = ?
               AND user_id = ?
               AND status = 'Pending'"
        );

        if (!$updateStmt) {
            $error =
                "Database preparation failed: " .
                mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $updateStmt,
                "ssssssssiddsssssii",
                $programName,
                $clubName,
                $personInCharge,
                $objective,
                $description,
                $proposalDate,
                $proposalTime,
                $location,
                $expectedParticipants,
                $budget,
                $eventFee,
                $poster,
                $officialLetter,
                $proposalPaper,
                $activityForm,
                $speakerProfile,
                $proposalId,
                $userId
            );

            if (mysqli_stmt_execute($updateStmt)) {
                mysqli_stmt_close($updateStmt);

                foreach (
                    $filesToDeleteAfterSuccess
                    as $oldFile
                ) {
                    deleteFileIfExists($oldFile);
                }

                header(
                    "Location: my_proposals.php?success=updated"
                );
                exit();
            }

            $error =
                "Failed to update proposal: " .
                mysqli_stmt_error($updateStmt);

            mysqli_stmt_close($updateStmt);
        }
    }

    /* Remove only newly uploaded files when update fails */
    if ($error !== '') {
        foreach ($newUploadedPaths as $newFile) {
            deleteFileIfExists($newFile);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Proposal | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=edit-proposal-1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main submit-proposal-page">

        <div class="page-header">

            <div>
                <a href="my_proposals.php" class="detail-back-link">
                    <i class="fa-solid fa-arrow-left"></i>
                    Back to My Proposals
                </a>

                <h1>Edit Programme Proposal</h1>

                <p>
                    Update the proposal information while
                    it is still pending administrator review.
                </p>
            </div>

            <div class="page-summary">

                <span>
                    <i class="fa-solid fa-pen-to-square"></i>
                </span>

                <small>Pending Proposal</small>

            </div>

        </div>

        <?php if ($error !== '') { ?>

            <div class="error submit-proposal-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>

        <form method="POST" enctype="multipart/form-data" class="submit-proposal-form" id="editProposalForm">

            <!-- PROGRAMME INFORMATION -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Programme Information</h2>

                        <p>
                            Update the main information about
                            the proposed programme.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-solid fa-file-lines"></i>
                    </span>

                </div>

                <div class="form-grid">

                    <div class="form-group form-full">

                        <label for="program_name">
                            Programme Name
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="program_name" name="program_name" value="<?php
                        echo htmlspecialchars(
                            $programName
                        );
                        ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="club_name">
                            Club Name
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="club_name" name="club_name" value="<?php
                        echo htmlspecialchars(
                            $clubName
                        );
                        ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="person_in_charge">
                            Person In Charge
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="person_in_charge" name="person_in_charge" value="<?php
                        echo htmlspecialchars(
                            $personInCharge
                        );
                        ?>" required>

                    </div>

                    <div class="form-group form-full">

                        <label for="objective">
                            Programme Objective
                            <span class="required-mark">*</span>
                        </label>

                        <textarea id="objective" name="objective" required><?php
                        echo htmlspecialchars($objective);
                        ?></textarea>

                    </div>

                    <div class="form-group form-full">

                        <label for="description">
                            Programme Description
                        </label>

                        <textarea id="description" name="description"><?php
                        echo htmlspecialchars($description);
                        ?></textarea>

                    </div>

                </div>

            </div>

            <!-- POSTER -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Programme Poster</h2>

                        <p>
                            Keep the current poster or upload
                            a replacement.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-regular fa-image"></i>
                    </span>

                </div>

                <?php if ($currentPoster !== '') { ?>

                    <div class="edit-current-poster">

                        <div class="edit-current-poster-preview">

                            <img src="assets/posters/<?php
                            echo rawurlencode($currentPoster);
                            ?>" alt="Current Programme Poster">

                        </div>

                        <div class="edit-current-poster-info">

                            <span class="poster-current-badge">
                                <i class="fa-solid fa-circle-check"></i>
                                Current Poster
                            </span>

                            <strong>
                                <?php echo htmlspecialchars($currentPoster); ?>
                            </strong>

                            <p>
                                Upload a new poster below only if you want
                                to replace the current poster.
                            </p>

                            <a href="assets/posters/<?php
                            echo rawurlencode($currentPoster);
                            ?>" target="_blank" rel="noopener noreferrer" class="btn-view-current-poster">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                Open Full Poster
                            </a>

                        </div>

                    </div>

                <?php } else { ?>

                    <div class="proposal-poster-placeholder edit-no-poster">
                        <i class="fa-regular fa-image"></i>
                        <span>No current poster</span>
                    </div>

                <?php } ?>

                <div class="form-group form-full">

                    <label for="poster">
                        Upload New Poster
                    </label>

                    <input type="file" id="poster" name="poster"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <small class="form-help-text">
                        Leave this empty to keep the current poster.
                        JPG, PNG or WEBP only, maximum 5 MB.
                    </small>

                </div>

            </div>

            <!-- SUPPORTING DOCUMENTS -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Supporting Documents</h2>

                        <p>
                            Upload a new file only when you want
                            to replace the existing document.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-solid fa-paperclip"></i>
                    </span>

                </div>

                <div class="fixed-document-grid">

                    <div class="edit-document-item">

                        <label for="official_letter">
                            Official Letter
                        </label>

                        <?php if (
                            $currentOfficialLetter !== ''
                        ) { ?>

                            <a href="assets/proposal_documents/<?php
                            echo rawurlencode(
                                $currentOfficialLetter
                            );
                            ?>" target="_blank">
                                View current file
                            </a>

                        <?php } else { ?>

                            <small>No current file</small>

                        <?php } ?>

                        <input type="file" id="official_letter" name="official_letter"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

                    </div>

                    <div class="edit-document-item">

                        <label for="proposal_paper">
                            Proposal Paper
                        </label>

                        <?php if (
                            $currentProposalPaper !== ''
                        ) { ?>

                            <a href="assets/proposal_documents/<?php
                            echo rawurlencode(
                                $currentProposalPaper
                            );
                            ?>" target="_blank">
                                View current file
                            </a>

                        <?php } else { ?>

                            <small>No current file</small>

                        <?php } ?>

                        <input type="file" id="proposal_paper" name="proposal_paper"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

                    </div>

                    <div class="edit-document-item">

                        <label for="activity_form">
                            Activity Application Form
                        </label>

                        <?php if (
                            $currentActivityForm !== ''
                        ) { ?>

                            <a href="assets/proposal_documents/<?php
                            echo rawurlencode(
                                $currentActivityForm
                            );
                            ?>" target="_blank">
                                View current file
                            </a>

                        <?php } else { ?>

                            <small>No current file</small>

                        <?php } ?>

                        <input type="file" id="activity_form" name="activity_form"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

                    </div>

                    <div class="edit-document-item">

                        <label for="speaker_profile">
                            Speaker Profile Form
                        </label>

                        <?php if (
                            $currentSpeakerProfile !== ''
                        ) { ?>

                            <a href="assets/proposal_documents/<?php
                            echo rawurlencode(
                                $currentSpeakerProfile
                            );
                            ?>" target="_blank">
                                View current file
                            </a>

                        <?php } else { ?>

                            <small>No current file</small>

                        <?php } ?>

                        <input type="file" id="speaker_profile" name="speaker_profile"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

                    </div>

                </div>

            </div>

            <!-- SCHEDULE -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Schedule, Venue & Budget</h2>

                        <p>
                            Update the schedule, participation
                            and financial information.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </span>

                </div>

                <div class="form-grid">

                    <div class="form-group">

                        <label for="proposal_date">
                            Programme Date
                            <span class="required-mark">*</span>
                        </label>

                        <input type="date" id="proposal_date" name="proposal_date" min="<?php
                        echo date('Y-m-d');
                        ?>" value="<?php
                        echo htmlspecialchars(
                            $proposalDate
                        );
                        ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="proposal_time">
                            Programme Time
                            <span class="required-mark">*</span>
                        </label>

                        <input type="time" id="proposal_time" name="proposal_time" value="<?php
                        echo htmlspecialchars(
                            $proposalTime
                        );
                        ?>" required>

                    </div>

                    <div class="form-group form-full">

                        <label for="location">
                            Programme Location
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="location" name="location" value="<?php
                        echo htmlspecialchars(
                            $location
                        );
                        ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="expected_participants">
                            Expected Participants
                            <span class="required-mark">*</span>
                        </label>

                        <input type="number" id="expected_participants" name="expected_participants" min="1" value="<?php
                        echo $expectedParticipants;
                        ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="budget">
                            Estimated Budget (RM)
                            <span class="required-mark">*</span>
                        </label>

                        <input type="number" id="budget" name="budget" min="0" step="0.01" value="<?php
                        echo htmlspecialchars(
                            number_format(
                                $budget,
                                2,
                                '.',
                                ''
                            )
                        );
                        ?>" required>

                    </div>

                    <div class="form-group form-full">

                        <label for="event_fee">
                            Student Event Fee (RM)
                            <span class="required-mark">*</span>
                        </label>

                        <input type="number" id="event_fee" name="event_fee" min="0" step="0.01" value="<?php
                        echo htmlspecialchars(
                            number_format(
                                $eventFee,
                                2,
                                '.',
                                ''
                            )
                        );
                        ?>" required>

                        <small class="form-help-text">
                            Enter 0.00 for a free event.
                        </small>

                    </div>

                </div>

                <div class="proposal-status-note">

                    <i class="fa-solid fa-circle-info"></i>

                    <div>
                        <strong>Pending Proposal</strong>

                        <p>
                            Changes can only be made before
                            the administrator approves or rejects
                            this proposal.
                        </p>
                    </div>

                </div>

                <div class="submit-proposal-actions">

                    <button type="submit" class="btn-primary" id="updateProposalButton">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Update Proposal
                    </button>

                    <a href="my_proposals.php" class="btn-secondary">
                        <i class="fa-solid fa-xmark"></i>
                        Cancel
                    </a>

                </div>

            </div>

        </form>

        <div class="footer-small">
            <?php include 'includes/footer.php'; ?>
        </div>

    </div>

    <script>
        const editProposalForm =
            document.getElementById(
                "editProposalForm"
            );

        if (editProposalForm) {
            editProposalForm.addEventListener(
                "submit",
                function () {
                    const updateButton =
                        document.getElementById(
                            "updateProposalButton"
                        );

                    updateButton.disabled = true;

                    updateButton.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
                }
            );
        }
    </script>

</body>

</html>