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

$issueName = !$certificate || $certificate["issued_by"] == null ? "ex: 'Robert Hooke'" : $certificate["issued_by"];
$titleDis = !$certificate || $certificate["title"] == null ? "ex: 'Module on Hooke's Law'" : $certificate["title"];
$headerDis = !$certificate || $certificate["header"] == null ? "ex: 'Certificate of Completion'" : $certificate["header"];
$deptDis = !$certificate || $certificate["department"] == null ? "" : $certificate["department"];

if ($_SERVER['REQUEST_METHOD']== 'POST' && $USER->instructor) {
    $title = isset($_POST["title"]) ? $_POST["title"] : " ";
    $header = isset($_POST["header"]) ? $_POST["header"] : " ";
    $issuedBy = isset($_POST["issued_by"]) ? $_POST["issued_by"] : " ";
    $department = isset($_POST["department"]) ? $_POST["department"] : " ";
    $DETAILS = isset($_POST["DETAILS"]) ? $_POST["DETAILS"] : " ";

    if($certificate) {
        $updateStmt = $PDOX->prepare("UPDATE {$p}certificate SET title=:title, header=:header, issued_by=:issued_by, department=:department, DETAILS=:DETAILS, modified=:modified WHERE cert_id = :certId");
        $updateStmt->execute(array(
            ":title" => $title,
            ":header" => $header,
            ":issued_by" => $issuedBy,
            ":department" => $department,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime,
            ":certId" => $certificate["cert_id"]
        ));
    } else {
        $createCert = $PDOX->prepare("INSERT INTO {$p}certificate (context_id, link_id, user_id, title, header, issued_by, department, DETAILS, modified)
                                VALUES (:contextId, :linkId, :userId, :title, :header, :issued_by, :department, :DETAILS, :modified)");
        $createCert->execute(array(
            ":contextId" => $CONTEXT->id,
            ":linkId" => $LINK->id,
            ":userId" => $USER->id,
            ":title" => $title,
            ":header" => $header,
            ":issued_by" => $issuedBy,
            ":department" => $department,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime
        ));
    }
    $_SESSION['success'] = "Information saved successfully";
    header('Location: ' . addSession('index.php'));
    return;

} else if(!$USER->instructor) {
    if($certificate) {
        $_SESSION["cert_id"] = $certificate["cert_id"];

        $awardSt = $PDOX->prepare("SELECT * FROM {$p}cert_award WHERE cert_id = :certId AND user_id = :userId");
        $awardSt->execute(array(":certId" => $_SESSION["cert_id"], ":userId" => $USER->id));
        $award = $awardSt->fetch(PDO::FETCH_ASSOC);
        if(!$award) {
            $createAward = $PDOX->prepare("INSERT INTO {$p}cert_award (cert_id, user_id, date_awarded)
                                VALUES (:certId, :userId, :dateAwarded)");
            $createAward->execute(array(
                ":certId" => $certificate["cert_id"],
                ":userId" => $USER->id,
                ":dateAwarded" => $currentTime
            ));
        }
    }
}

$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="main.css">
<?php

$OUTPUT->bodyStart();

if($USER->instructor) {
    ?>
    <div class="box">
        <div>
            <a href="usage.php" class="btn btn-primary pull-right"><span class="fa fa-eye"
                                                                         aria-hidden="true"></span> Certificates Earned</a>
            <h1 class="header">Simple Certificate Tool</h1>
            <p class="instructions">Fill out fields below to create your certificate. You'll be able to see a
                preview of how the certificate will look when filled out at the bottom of the page. You can also track
                those that have earned the certificate under the 'Certificates Earned' button.</p>
        </div>
    <br>
    <?php
    $OUTPUT->flashMessages();
    ?>
        <div class="container">
            <form method="post" class="form-inline">
                <div class="container">
                    <div class="col-sm-3">
                        <p class="fields">Certificate Fields</p>
                    </div>
                    <div class="col-sm-9"></div>
                </div>
    <?php
    if(!$certificate) {
        ?>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="header">Title of Certificate:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="29" class="form-control" id="header" name="header" placeholder="<?= $headerDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="title">Title of Achievement:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="50" class="form-control" id="title" name="title" placeholder="<?= $titleDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issued_by">Awards Issued By:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="40" class="form-control" id="issued_by" name="issued_by" placeholder="<?= $issueName ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issueDep">Issuing Department/Unit:</label>
            </div>
            <div class="col-sm-8">
                <input maxlength="80" class="form-control" id="department" name="department" placeholder="ex: 'Department of Mechanical Engineering">
                <label class="inputs" for="department">(Optional)</label>
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="details">Certificate Requirements:</label>
            </div>
            <div class="col-sm-8">
                <textarea maxlength="240" class="details" id="DETAILS" name="DETAILS"><?= $certificate['DETAILS'] ?></textarea>
                <label class="inputs" for="DETAILS">(Optional)</label>
            </div>
        </div>
        <?php
        } else {
        ?>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="header">Title of Certificate:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="29" class="form-control" id="header" name="header" value="<?= $headerDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="title">Title of Achievement:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="50" class="form-control" id="title" name="title" value="<?= $titleDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issued_by">Awards Issued By:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="40" class="form-control" id="issued_by" name="issued_by" value="<?= $issueName ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issueDep">Issuing Department/Unit:</label>
            </div>
            <div class="col-sm-8">
                <input maxlength="80" class="form-control" id="department" name="department" value="<?= $certificate["department"] ?>">
                <label class="inputs" for="department">(Optional)</label>
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="details">Certificate Requirements:</label>
            </div>
            <div class="col-sm-8">
                <textarea maxlength="240" class="details" id="DETAILS" name="DETAILS"><?= $certificate['DETAILS'] ?></textarea>
                <label class="inputs" for="DETAILS">(Optional)</label>
            </div>
        </div>
        <?php
        }
        ?>
                <p class="breakText">* Date and Time of Completion will automatically be added to the certificate</p>
        <?php
        if(!$certificate) {
            ?>
                <button type='submit' class='btn btn-success'>Create Certificate</button>
            <?php
        } else {
            ?>
                <button type='submit' class='btn btn-success'>Update Certificate</button>
            <?php
        }
            ?>

            </form>
        </div>
    <hr class="hr">
    <p class="preview">Preview</p>
        <?php
        if($certificate) {
                ?>
                <div class="certBack">
                    <br><br>
                    <br><br>
                    <div id="resize" class="title1edit"><?= $headerDis ?></div>
                    <br><br>
                    <p class="line2">This is to certify that</p>

                    <br><br>
                    <p class="title3edit"><u>Recipient Name</u></p>
                    <p class="line3">has completed</p>
                    <p class="title2edit"><?= $titleDis ?></p>
                    <p class="line4">on</p>
                    <p class="title3edit2">Date Awarded</p>
                    <?php
                    if($certificate["DETAILS"]==" ") {
                        ?>
                        <p class="detailsHead">Certificate Requirements:</p>
                        <p class="detailsEdit"><?= $certificate['DETAILS'] ?></p>
                        <?php
                    }
                    ?>

                    <p class="line5">Issued by</p>
                    <p class="title4edit"><?= $issueName ?></p>
                    <p class="deptEdit"><?= $deptDis ?></p>
                </div>
                <?php
        }
        else {
            ?>
            <br><br>
            <div class="noCert">
                <div class="noPreview">
                    No Preview Available
                </div>
            </div>
            </div>
            <?php
        }

}  else if($_SERVER['REQUEST_METHOD'] == 'GET' && !$USER->instructor) {
        header('Location:'.addSession('certView.php'));
}

$OUTPUT->footerStart();

$OUTPUT->footerEnd();