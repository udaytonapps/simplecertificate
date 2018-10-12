<?php
require_once "../config.php";

use Tsugi\Core\LTIX;

$p = $CFG->dbprefix;

$LAUNCH = LTIX::requireData();

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$currentTime = $currentTime->format("Y-m-d H:i:s");

$certificateST  = $PDOX->prepare("SELECT * FROM {$p}certificate WHERE link_id = :linkId");
$certificateST->execute(array(":linkId" => $LINK->id));
$certificate = $certificateST->fetch(PDO::FETCH_ASSOC);

if($certificate) {
    $issueName = $certificate["issued_by"];
    $details = $certificate["DETAILS"];
    $TITLE = $certificate["title"];
} else {
    $issueName = "name";
    $details = "description of award";
    $TITLE = "title of award";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $USER->instructor) {
    $title = isset($_POST["title"]) ? $_POST["title"] : " ";
    $issuedBy = isset($_POST["issued_by"]) ? $_POST["issued_by"] : " ";
    $DETAILS = isset($_POST["DETAILS"]) ? $_POST["DETAILS"] : " ";

    if($certificate) {
        $updateStmt = $PDOX->prepare("UPDATE {$p}certificate SET title=:title, issued_by=:issued_by, DETAILS=:DETAILS, modified=:modified WHERE cert_id = :certId");
        $updateStmt->execute(array(
            ":title" => $title,
            ":issued_by" => $issuedBy,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime,
            ":certId" => $certificate["cert_id"]
        ));
        $_SESSION['success'] = "Information saved successfully";
        header('Location: ' . addSession('index.php'));
        return;
    } else {
        $createCert = $PDOX->prepare("INSERT INTO {$p}certificate (context_id, link_id, user_id, title, issued_by, DETAILS, modified)
                                VALUES (:contextId, :linkId, :userId, :title, :issued_by, :DETAILS, :modified)");
        $createCert->execute(array(
            ":contextId" => $CONTEXT->id,
            ":linkId" => $LINK->id,
            ":userId" => $USER->id,
            ":title" => $title,
            ":issued_by" => $issuedBy,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime
        ));
        $_SESSION['success'] = "Information saved successfully";
        header('Location: ' . addSession('index.php'));
        return;
    }
} else if ($_SERVER['REQUEST_METHOD'] == 'GET' && !$USER->instructor) {
    if($certificate) {
        $_SESSION["cert_id"] = $certificate["cert_id"];

        $awardSt = $PDOX->prepare("SELECT * FROM {$p}cert_award WHERE cert_id = :certId AND user_id = :userId");
        $awardSt->execute(array(":certId" => $_SESSION["cert_id"], ":userId" => $USER->id));
        $awardId = $awardSt->fetch(PDO::FETCH_ASSOC);
        if(!$awardId) {
            $createAward = $PDOX->prepare("INSERT INTO {$p}cert_award (cert_id, user_id, date_awarded)
                                VALUES (:certId, :userId, :dateAwarded)");
            $createAward->execute(array(
                ":certId" => $certificate["cert_id"],
                ":userId" => $USER->id,
                ":dateAwarded" => $currentTime
            ));
        }
    } else {
        $_SESSION['error'] = 'No certificate exists';
    }
}

$OUTPUT->header();

$OUTPUT->bodyStart();

if($USER->instructor) {
    if(!$certificate) {
        ?>
        <h1>Welcome</h1>
        <form method='post'>
            <div class='form-group'>
                <label for='issued_by'>Issued by:</label>
                <input type='text' name='issued_by' class='form-control' id='issued_by' value="<?= $issueName ?>">
            </div>
            <div class='form-group'>
                <label for='title'>Accomplishment or Name:</label>
                <input type='text' name='title' class='form-control' id='title' value="<?= $TITLE ?>">
            </div>
            <div class='form=group'>
                <label for='DETAILS'>Details:</label>
                <input type='text' name='DETAILS' class='form-control' id='DETAILS' value="<?= $details ?>">
            </div>
            <button type='submit' class='btn btn-default'>Submit</button>
        </form>
        <?php
    } else {
        ?>
        <h1>Welcome</h1>
        <form method='post'>
            <div class='form-group'>
                <label for='issued_by'>Issued by:</label>
                <input type='text' name='issued_by' class='form-control' id='issued_by' value="<?= $issueName ?>">
            </div>
            <div class='form-group'>
                <label for='title'>Accomplishment or Name:</label>
                <input type='text' name='title' class='form-control' id='title' value="<?= $TITLE ?>">
            </div>
            <div class='form=group'>
                <label for='DETAILS'>Details:</label>
                <input type='text' name='DETAILS' class='form-control' id='DETAILS' value="<?= $details ?>">
            </div>
            <button type='submit' class='btn btn-default'>Submit</button>
        </form>
        <?php
        if($certificate) {
            ?>
            <br><br>
            <div style="width:1000px; height:800px; padding:20px; text-align:center; border:10px solid #357ebd">
                <div style="width:950px; height:750px; padding:20px; text-align:center; border:5px solid #dc3545">
                    <span style="font-size:50px; font-weight:bold">Certificate of Completion</span>
                    <br><br>
                    <span style="font-size:30px"><u><?= $certificate["title"] ?></u></span>
                    <br><br>
                    <span style="font-size:25px">Issued to <u>Recipient Name</u></span>

                    <span style="font-size:25px">on <u>Date Awarded</u></span>
                    <br><br>
                    <div style="width:950px; height:200px; padding:10px">
                        <span style="font-size:20px; text-align:left"><?= $certificate["DETAILS"] ?></span>
                        <br><br>
                        <span style="font-size:25px; text-align:right">Issued by <u><?= $certificate["issued_by"] ?></u></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
} else if($_SERVER['REQUEST_METHOD'] == 'GET' && !$USER->instructor && $certificate) {
    $nameST = $PDOX->prepare("SELECT displayname FROM {$p}lti_user WHERE user_id = :user_id");
    $nameST->execute(array(":user_id" => $awardId["user_id"]));
    $name = $nameST->fetch(PDO::FETCH_ASSOC);
    ?>
    <div style="width:1000px; height:800px; padding:20px; text-align:center; border:10px solid #357ebd">
        <div style="width:950px; height:750px; padding:20px; text-align:center; border:5px solid #dc3545">
            <span style="font-size:50px; font-weight:bold">Certificate of Completion</span>
            <br><br>
            <span style="font-size:30px"><u><?= $certificate["title"] ?></u></span>
            <br><br>
            <span style="font-size:25px">Issued to <u><?= $name["displayname"] ?></u></span>

            <span style="font-size:25px">on <?= $awardId["date_awarded"] ?></span>
            <br><br>
            <div style="width:950px; height:200px; padding:10px">
                <span style="font-size:20px; text-align:left"><?= $certificate["DETAILS"] ?></span>
                <br><br>
                <span style="font-size:25px; text-align:right">Issued by <u><?= $certificate["issued_by"] ?></u></span>
            </div>
        </div>
    </div>
    <?php
}

$OUTPUT->flashMessages();

$OUTPUT->footerStart();

$OUTPUT->footerEnd();