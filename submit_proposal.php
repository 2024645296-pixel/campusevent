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
$error = "";

$programName = "";
$clubName = "";
$personInCharge = "";
$objective = "";
$description = "";
$proposalDate = "";
$proposalTime = "";
$location = "";
$expectedParticipants = "";
$budget = "";
$eventFee = "";



function removeUploadedFiles(array $files): void
{
    foreach ($files as $file) {
        if ($file !== '' && is_file($file)) {
            unlink($file);
        }
    }
}

/* =========================================
   HELPER: UPLOAD ONE SUPPORTING DOCUMENT
========================================= */

function uploadProposalDocument(
    string $fieldName,
    string $label,
    int $userId,
    string $uploadDirectory,
    array &$uploadedFilePaths,
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

    $maxFileSize = 5 * 1024 * 1024;

    if ($_FILES[$fieldName]['size'] > $maxFileSize) {
        $error = "{$label} must not exceed 5 MB.";
        return "";
    }

    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        => 'docx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
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

    if (!is_dir($uploadDirectory)) {
        if (!mkdir($uploadDirectory, 0775, true)) {
            $error = "Unable to create the supporting document folder.";
            return "";
        }
    }

    $extension = $allowedMimeTypes[$mimeType];

    $storedName =
        $fieldName . '_' .
        $userId . '_' .
        time() . '_' .
        bin2hex(random_bytes(4)) . '.' .
        $extension;

    $destination = $uploadDirectory . $storedName;

    if (
        !move_uploaded_file(
            $_FILES[$fieldName]['tmp_name'],
            $destination
        )
    ) {
        $error = "Unable to save the {$label}.";
        return "";
    }

    $uploadedFilePaths[] = $destination;

    return $storedName;
}

/* =========================================
   SUBMIT PROPOSAL
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName = trim($_POST['program_name'] ?? '');
    $clubName = trim($_POST['club_name'] ?? '');
    $personInCharge = trim($_POST['person_in_charge'] ?? '');
    $objective = trim($_POST['objective'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $proposalDate = trim($_POST['proposal_date'] ?? '');
    $proposalTime = trim($_POST['proposal_time'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $expectedParticipants =
        (int) ($_POST['expected_participants'] ?? 0);
    $budget = (float) ($_POST['budget'] ?? 0);
    $eventFee = (float) ($_POST['event_fee'] ?? 0);

    $status = 'Pending';
    $poster = "";
    $officialLetter = "";
    $proposalPaper = "";
    $activityForm = "";
    $speakerProfile = "";

    $today = date("Y-m-d");
    $uploadedFilePaths = [];

    /* FORM VALIDATION */

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

    /* POSTER UPLOAD */

    if (
        $error === '' &&
        isset($_FILES['poster']) &&
        $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE
    ) {
        if ($_FILES['poster']['error'] !== UPLOAD_ERR_OK) {
            $error = "Unable to upload the programme poster.";
        } else {
            $allowedPosterTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp'
            ];

            $maxPosterSize = 5 * 1024 * 1024;

            if ($_FILES['poster']['size'] > $maxPosterSize) {
                $error = "Poster size must not exceed 5 MB.";
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);

                if (!$finfo) {
                    $error = "Unable to validate the poster file.";
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
                            $posterExtension =
                                $allowedPosterTypes[$posterMime];

                            $poster =
                                'proposal_' .
                                $userId . '_' .
                                time() . '_' .
                                bin2hex(random_bytes(4)) .
                                '.' .
                                $posterExtension;

                            $posterDestination =
                                $posterDirectory . $poster;

                            if (
                                !move_uploaded_file(
                                    $_FILES['poster']['tmp_name'],
                                    $posterDestination
                                )
                            ) {
                                $error =
                                    "Unable to save the uploaded poster.";
                            } else {
                                $uploadedFilePaths[] =
                                    $posterDestination;
                            }
                        }
                    }
                }
            }
        }
    }

    /* FIXED SUPPORTING DOCUMENTS */

    if ($error === '') {
        $documentDirectory =
            __DIR__ . '/assets/proposal_documents/';

        $officialLetter = uploadProposalDocument(
            'official_letter',
            'Official Letter',
            $userId,
            $documentDirectory,
            $uploadedFilePaths,
            $error
        );
    }

    if ($error === '') {
        $proposalPaper = uploadProposalDocument(
            'proposal_paper',
            'Proposal Paper',
            $userId,
            __DIR__ . '/assets/proposal_documents/',
            $uploadedFilePaths,
            $error
        );
    }

    if ($error === '') {
        $activityForm = uploadProposalDocument(
            'activity_form',
            'Activity Application Form',
            $userId,
            __DIR__ . '/assets/proposal_documents/',
            $uploadedFilePaths,
            $error
        );
    }

    if ($error === '') {
        $speakerProfile = uploadProposalDocument(
            'speaker_profile',
            'Speaker Profile Form',
            $userId,
            __DIR__ . '/assets/proposal_documents/',
            $uploadedFilePaths,
            $error
        );
    }

    /* INSERT PROPOSAL */

    if ($error === '') {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO program_proposals
            (
                user_id,
                program_name,
                club_name,
                person_in_charge,
                objective,
                description,
                proposal_date,
                proposal_time,
                location,
                expected_participants,
                budget,
                event_fee,
                poster,
                official_letter,
                proposal_paper,
                activity_form,
                speaker_profile,
                status
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            removeUploadedFiles($uploadedFilePaths);

            $error =
                "Database preparation failed: " .
                mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param(
                $stmt,
                "issssssssiddssssss",
                $userId,
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
                $status
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                header(
                    "Location: my_proposals.php?success=submitted"
                );
                exit();
            }

            $error =
                "Failed to submit proposal: " .
                mysqli_stmt_error($stmt);

            mysqli_stmt_close($stmt);
            removeUploadedFiles($uploadedFilePaths);
        }
    } else {
        removeUploadedFiles($uploadedFilePaths);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Submit Proposal | CampusEvent</title>

    <link rel="stylesheet" href="assets/css/style.css?v=43000">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main submit-proposal-page">

        <div class="page-header">

            <div>
                <h1>Submit Programme Proposal</h1>

                <p>
                    Complete the programme information and submit it for administrator approval.
                </p>
            </div>

            <div class="page-summary">

                <span>
                    <i class="fa-solid fa-file-circle-plus"></i>
                </span>

                <small>New Proposal</small>

            </div>

        </div>

        <?php if ($error !== '') { ?>

            <div class="error submit-proposal-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php } ?>

        <div class="proposal-progress">

            <div class="proposal-progress-item active">
                <span>1</span>

                <div>
                    <strong>Programme Details</strong>
                    <small>Basic programme information</small>
                </div>
            </div>

            <div class="proposal-progress-item">
                <span>2</span>

                <div>
                    <strong>Poster</strong>
                    <small>Optional programme poster</small>
                </div>
            </div>

            <div class="proposal-progress-item">
                <span>3</span>

                <div>
                    <strong>Documents</strong>
                    <small>Supporting documents</small>
                </div>
            </div>

            <div class="proposal-progress-item">
                <span>4</span>

                <div>
                    <strong>Schedule</strong>
                    <small>Date, venue and budget</small>
                </div>
            </div>

            <div class="proposal-progress-item">
                <span>5</span>

                <div>
                    <strong>Submit</strong>
                    <small>Send for approval</small>
                </div>
            </div>

        </div>

        <form method="POST" enctype="multipart/form-data" class="submit-proposal-form" id="proposalForm">

            <!-- PROGRAMME INFORMATION -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Programme Information</h2>

                        <p>
                            Enter the main information about the proposed programme.
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

                        <input type="text" id="program_name" name="program_name"
                            placeholder="Example: Leadership Camp 2026"
                            value="<?php echo htmlspecialchars($programName); ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="club_name">
                            Club Name
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="club_name" name="club_name"
                            placeholder="Example: Information Systems Club"
                            value="<?php echo htmlspecialchars($clubName); ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="person_in_charge">
                            Person In Charge
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="person_in_charge" name="person_in_charge"
                            placeholder="Example: Nurkhairunnisa" value="<?php
                            echo htmlspecialchars($personInCharge);
                            ?>" required>

                    </div>

                    <div class="form-group form-full">

                        <label for="objective">
                            Programme Objective
                            <span class="required-mark">*</span>
                        </label>

                        <textarea id="objective" name="objective"
                            placeholder="Explain the main objectives of the programme."
                            required><?php echo htmlspecialchars($objective); ?></textarea>

                    </div>

                    <div class="form-group form-full">

                        <label for="description">
                            Programme Description
                        </label>

                        <textarea id="description" name="description"
                            placeholder="Provide additional information about the programme."><?php echo htmlspecialchars($description); ?></textarea>

                    </div>

                </div>

            </div>

            <!-- POSTER -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Programme Poster</h2>

                        <p>
                            Upload an optional portrait poster for the programme.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-regular fa-image"></i>
                    </span>

                </div>

                <div class="proposal-poster-layout">

                    <div class="proposal-upload-box">

                        <label for="poster" class="proposal-upload-label">

                            <span class="proposal-upload-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </span>

                            <strong>Upload Event Poster</strong>

                            <small>
                                JPG, PNG or WEBP • Maximum 5 MB
                            </small>

                            <span class="proposal-upload-button">
                                Choose Poster
                            </span>

                        </label>

                        <input type="file" id="poster" name="poster"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    </div>

                    <div class="proposal-poster-preview">

                        <p>Poster Preview</p>

                        <div class="proposal-poster-placeholder" id="posterPlaceholder">
                            <i class="fa-regular fa-image"></i>
                            <span>No poster selected</span>
                        </div>

                        <img src="" id="posterPreview" alt="Poster Preview">

                    </div>

                </div>

            </div>

            <!-- DOCUMENT TEMPLATE CENTRE -->

            <div class="card submit-proposal-card document-template-card">

                <div class="section-header">

                    <div>
                        <h2>Supporting Document Templates</h2>

                        <p>
                            Download the required templates, complete them, then upload the finished documents below.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-solid fa-file-arrow-down"></i>
                    </span>

                </div>

                <div class="template-steps">

                    <div class="template-step active">
                        <span>1</span>

                        <div>
                            <strong>Download Template</strong>
                            <small>Choose the required document</small>
                        </div>
                    </div>

                    <div class="template-step">
                        <span>2</span>

                        <div>
                            <strong>Complete Document</strong>
                            <small>Fill in the required information</small>
                        </div>
                    </div>

                    <div class="template-step">
                        <span>3</span>

                        <div>
                            <strong>Upload Document</strong>
                            <small>Attach the completed file below</small>
                        </div>
                    </div>

                    <div class="template-step">
                        <span>4</span>

                        <div>
                            <strong>Submit Proposal</strong>
                            <small>Send everything for approval</small>
                        </div>
                    </div>

                </div>

                <div class="template-information-box">

                    <i class="fa-solid fa-circle-info"></i>

                    <div>
                        <strong>Before uploading your documents</strong>

                        <p>
                            Download the official template, fill in all required information,
                            save the completed document, and upload it in the matching section below.
                        </p>
                    </div>

                </div>

                <div class="document-template-grid">

                    <a href="assets/documents/official-letter-template.docx" download class="document-template-item">
                        <span class="document-template-icon word">
                            <i class="fa-solid fa-file-word"></i>
                        </span>

                        <span class="document-template-content">
                            <strong>Official Letter Template</strong>
                            <small>Microsoft Word document</small>
                        </span>

                        <span class="document-template-action">
                            <i class="fa-solid fa-download"></i>
                            Download
                        </span>
                    </a>

                    <a href="assets/documents/proposal-paper-template.docx" download class="document-template-item">
                        <span class="document-template-icon word">
                            <i class="fa-solid fa-file-word"></i>
                        </span>

                        <span class="document-template-content">
                            <strong>Proposal Paper Template</strong>
                            <small>Microsoft Word document</small>
                        </span>

                        <span class="document-template-action">
                            <i class="fa-solid fa-download"></i>
                            Download
                        </span>
                    </a>

                    <a href="assets/documents/student-activity-application-form.pdf" target="_blank"
                        rel="noopener noreferrer" class="document-template-item">
                        <span class="document-template-icon pdf">
                            <i class="fa-solid fa-file-pdf"></i>
                        </span>

                        <span class="document-template-content">
                            <strong>Student Activity Application Form</strong>
                            <small>PDF document</small>
                        </span>

                        <span class="document-template-action">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            View PDF
                        </span>
                    </a>

                    <a href="assets/documents/speaker-profile-form.pdf" target="_blank" rel="noopener noreferrer"
                        class="document-template-item">
                        <span class="document-template-icon pdf">
                            <i class="fa-solid fa-file-pdf"></i>
                        </span>

                        <span class="document-template-content">
                            <strong>Speaker Profile Form</strong>
                            <small>PDF document</small>
                        </span>

                        <span class="document-template-action">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            View PDF
                        </span>
                    </a>

                </div>

            </div>

            <!-- FIXED SUPPORTING DOCUMENTS -->

            <div class="card submit-proposal-card fixed-documents-card">

                <div class="section-header">

                    <div>
                        <h2>Supporting Documents</h2>

                        <p>
                            Upload the relevant official documents for administrator review.
                        </p>
                    </div>

                    <span class="proposal-section-icon">
                        <i class="fa-solid fa-paperclip"></i>
                    </span>

                </div>

                <div class="fixed-document-note">

                    <i class="fa-solid fa-circle-info"></i>

                    <p>
                        PDF, DOC, DOCX, JPG and PNG only. Maximum file size is 5 MB for each document.
                        Supporting documents are optional unless required by the administrator.
                    </p>

                </div>

                <div class="fixed-document-grid">

                    <label for="official_letter" class="fixed-document-item">
                        <input type="file" id="official_letter" name="official_letter"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="fixed-document-input" hidden>

                        <span class="fixed-document-icon word">
                            <i class="fa-solid fa-file-word"></i>
                        </span>

                        <span class="fixed-document-content">

                            <strong>Official Letter</strong>

                            <small class="fixed-document-filename" data-default="Click to choose a file">
                                Click to choose a file
                            </small>

                        </span>

                        <span class="fixed-document-action">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </span>

                    </label>

                    <label for="proposal_paper" class="fixed-document-item">
                        <input type="file" id="proposal_paper" name="proposal_paper"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="fixed-document-input" hidden>

                        <span class="fixed-document-icon word">
                            <i class="fa-solid fa-file-word"></i>
                        </span>

                        <span class="fixed-document-content">

                            <strong>Proposal Paper</strong>

                            <small class="fixed-document-filename" data-default="Click to choose a file">
                                Click to choose a file
                            </small>

                        </span>

                        <span class="fixed-document-action">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </span>

                    </label>

                    <label for="activity_form" class="fixed-document-item">
                        <input type="file" id="activity_form" name="activity_form"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="fixed-document-input" hidden>

                        <span class="fixed-document-icon pdf">
                            <i class="fa-solid fa-file-pdf"></i>
                        </span>

                        <span class="fixed-document-content">

                            <strong>Activity Application Form</strong>

                            <small class="fixed-document-filename" data-default="Click to choose a file">
                                Click to choose a file
                            </small>

                        </span>

                        <span class="fixed-document-action">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </span>

                    </label>

                    <label for="speaker_profile" class="fixed-document-item">
                        <input type="file" id="speaker_profile" name="speaker_profile"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="fixed-document-input" hidden>

                        <span class="fixed-document-icon pdf">
                            <i class="fa-solid fa-file-pdf"></i>
                        </span>

                        <span class="fixed-document-content">

                            <strong>Speaker Profile Form</strong>

                            <small class="fixed-document-filename" data-default="Click to choose a file">
                                Click to choose a file
                            </small>

                        </span>

                        <span class="fixed-document-action">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </span>

                    </label>

                </div>

            </div>

            <!-- SCHEDULE -->

            <div class="card submit-proposal-card">

                <div class="section-header">

                    <div>
                        <h2>Schedule, Venue & Budget</h2>

                        <p>
                            Enter the proposed schedule, venue and financial information.
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

                        <input type="date" id="proposal_date" name="proposal_date" min="<?php echo date('Y-m-d'); ?>"
                            value="<?php
                            echo htmlspecialchars($proposalDate);
                            ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="proposal_time">
                            Programme Time
                            <span class="required-mark">*</span>
                        </label>

                        <input type="time" id="proposal_time" name="proposal_time" value="<?php
                        echo htmlspecialchars($proposalTime);
                        ?>" required>

                    </div>

                    <div class="form-group form-full">

                        <label for="location">
                            Programme Location
                            <span class="required-mark">*</span>
                        </label>

                        <input type="text" id="location" name="location" placeholder="Example: Dewan Kuliah Pusat"
                            value="<?php echo htmlspecialchars($location); ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="expected_participants">
                            Expected Participants
                            <span class="required-mark">*</span>
                        </label>

                        <input type="number" id="expected_participants" name="expected_participants" min="1"
                            placeholder="Example: 150" value="<?php
                            echo $expectedParticipants > 0
                                ? htmlspecialchars(
                                    (string) $expectedParticipants
                                )
                                : '';
                            ?>" required>

                    </div>

                    <div class="form-group">

                        <label for="budget">
                            Estimated Budget (RM)
                            <span class="required-mark">*</span>
                        </label>

                        <input type="number" id="budget" name="budget" min="0" step="0.01" placeholder="Example: 500.00"
                            value="<?php
                            echo $budget !== ''
                                ? htmlspecialchars((string) $budget)
                                : '';
                            ?>" required>

                    </div>

                    <div class="form-group form-full">

                        <label for="event_fee">
                            Student Event Fee (RM)
                            <span class="required-mark">*</span>
                        </label>

                        <input type="number" id="event_fee" name="event_fee" min="0" step="0.01"
                            placeholder="Enter 0.00 for a free event or example: 10.00" value="<?php
                            echo $eventFee !== ''
                                ? htmlspecialchars((string) $eventFee)
                                : '';
                            ?>" required>

                        <small class="form-help-text">
                            Enter 0.00 if students can join for free. For a paid event, enter the registration fee
                            charged to each student.
                        </small>

                    </div>

                </div>

                <div class="proposal-status-note">

                    <i class="fa-solid fa-circle-info"></i>

                    <div>
                        <strong>Proposal Approval Process</strong>

                        <p>
                            After submission, the proposal status will be set to
                            <b>Pending</b>. The administrator will review the programme
                            information and supporting documents before making a decision.
                        </p>
                    </div>

                </div>

                <div class="submit-proposal-actions">

                    <button type="submit" name="submit" class="btn-primary" id="submitProposalButton">
                        <i class="fa-solid fa-paper-plane"></i>
                        Submit Proposal
                    </button>

                    <a href="dashboard.php" class="btn-secondary">
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
        /* POSTER PREVIEW */

        const posterInput =
            document.getElementById("poster");

        const posterPreview =
            document.getElementById("posterPreview");

        const posterPlaceholder =
            document.getElementById("posterPlaceholder");

        if (posterInput) {
            posterInput.addEventListener(
                "change",
                function () {
                    const selectedFile = this.files[0];

                    if (!selectedFile) {
                        posterPreview.style.display = "none";
                        posterPreview.src = "";
                        posterPlaceholder.style.display = "flex";
                        return;
                    }

                    const allowedTypes = [
                        "image/jpeg",
                        "image/png",
                        "image/webp"
                    ];

                    if (!allowedTypes.includes(selectedFile.type)) {
                        alert(
                            "Only JPG, PNG and WEBP poster files are allowed."
                        );

                        this.value = "";
                        posterPreview.style.display = "none";
                        posterPlaceholder.style.display = "flex";
                        return;
                    }

                    if (selectedFile.size > 5 * 1024 * 1024) {
                        alert(
                            "Poster size must not exceed 5 MB."
                        );

                        this.value = "";
                        posterPreview.style.display = "none";
                        posterPlaceholder.style.display = "flex";
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function (event) {
                        posterPreview.src = event.target.result;
                        posterPreview.style.display = "block";
                        posterPlaceholder.style.display = "none";
                    };

                    reader.readAsDataURL(selectedFile);
                }
            );
        }

        /* FIXED SUPPORTING DOCUMENTS */

        document
            .querySelectorAll(".fixed-document-input")
            .forEach(function (input) {
                input.addEventListener(
                    "change",
                    function () {
                        const item =
                            this.closest(".fixed-document-item");

                        const fileName =
                            item.querySelector(
                                ".fixed-document-filename"
                            );

                        const selectedFile =
                            this.files[0];

                        if (!selectedFile) {
                            fileName.textContent =
                                fileName.dataset.default;

                            item.classList.remove(
                                "file-selected"
                            );

                            return;
                        }

                        const allowedExtensions = [
                            "pdf",
                            "doc",
                            "docx",
                            "jpg",
                            "jpeg",
                            "png"
                        ];

                        const extension =
                            selectedFile.name
                                .split(".")
                                .pop()
                                .toLowerCase();

                        if (
                            !allowedExtensions.includes(
                                extension
                            )
                        ) {
                            alert(
                                "Documents must be PDF, DOC, DOCX, JPG or PNG."
                            );

                            this.value = "";

                            fileName.textContent =
                                fileName.dataset.default;

                            item.classList.remove(
                                "file-selected"
                            );

                            return;
                        }

                        if (
                            selectedFile.size >
                            5 * 1024 * 1024
                        ) {
                            alert(
                                "Each document must not exceed 5 MB."
                            );

                            this.value = "";

                            fileName.textContent =
                                fileName.dataset.default;

                            item.classList.remove(
                                "file-selected"
                            );

                            return;
                        }

                        fileName.textContent =
                            selectedFile.name;

                        item.classList.add(
                            "file-selected"
                        );
                    }
                );
            });

        /* PREVENT DOUBLE SUBMISSION */

        const proposalForm =
            document.getElementById("proposalForm");

        if (proposalForm) {
            proposalForm.addEventListener(
                "submit",
                function () {
                    const submitButton =
                        document.getElementById(
                            "submitProposalButton"
                        );

                    submitButton.disabled = true;

                    submitButton.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Submitting...';
                }
            );
        }
    </script>

</body>

</html>