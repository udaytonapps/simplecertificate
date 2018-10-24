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

function findDisplayName($user_id, $PDOX, $p) {
    $nameST = $PDOX->prepare("SELECT displayname FROM {$p}lti_user WHERE user_id = :user_id");
    $nameST->execute(array(":user_id" => $user_id));
    $name = $nameST->fetch(PDO::FETCH_ASSOC);
    return $name["displayname"];
}

if($certificate) {
    $issueName = $certificate["issued_by"];
    $details = $certificate["DETAILS"];
    $TITLE = $certificate["title"];
    $HEADER = $certificate["header"];
} else {
    $issueName = "name";
    $details = "description of award";
    $TITLE = "title of award";
    $HEADER = "type of award (e.g. Certificate of Completion)";
}




if ($_SERVER['REQUEST_METHOD'] == 'POST' && $USER->instructor) {
    $title = isset($_POST["title"]) ? $_POST["title"] : " ";
    $header = isset($_POST["header"]) ? $_POST["header"] : " ";
    $issuedBy = isset($_POST["issued_by"]) ? $_POST["issued_by"] : " ";
    $DETAILS = isset($_POST["DETAILS"]) ? $_POST["DETAILS"] : " ";

    if($certificate) {

        $updateStmt = $PDOX->prepare("UPDATE {$p}certificate SET title=:title, header=:header, issued_by=:issued_by, DETAILS=:DETAILS, modified=:modified WHERE cert_id = :certId");
        $updateStmt->execute(array(
            ":title" => $title,
            ":header" => $header,
            ":issued_by" => $issuedBy,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime,
            ":certId" => $certificate["cert_id"]
        ));
        $_SESSION['success'] = "Information saved successfully";
        header('Location: ' . addSession('index.php'));
        return;
    } else if(!$certificate) {
        $createCert = $PDOX->prepare("INSERT INTO {$p}certificate (context_id, link_id, user_id, title, header, issued_by, DETAILS, modified)
                                VALUES (:contextId, :linkId, :userId, :title, :header, :issued_by, :DETAILS, :modified)");
        $createCert->execute(array(
            ":contextId" => $CONTEXT->id,
            ":linkId" => $LINK->id,
            ":userId" => $USER->id,
            ":title" => $title,
            ":header" => $header,
            ":issued_by" => $issuedBy,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime
        ));
        $_SESSION['success'] = "Information saved successfully";
        header('Location: ' . addSession('index.php'));
        return;
    } else {
        $_SESSION['error'] = 'Unable to save response please try again.';
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
} else {
    $userSt = $PDOX->prepare("SELECT * FROM {$p}cert_award WHERE cert_id = :certId");
    $userSt->execute(array(":certId" => $certificate["cert_id"]));
    $userList = $userSt->fetchAll(PDO::FETCH_ASSOC);
}

$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="main.css">
<?php
$OUTPUT->bodyStart();

if($USER->instructor) {
    if($USER->instructor && isset($_GET["mode"]) && $_GET["mode"] == "edit") {
    ?>
        <form method='post'>
            <div class="certBack">
                <br><br>
                <br><br>
                <input type='text' name='header' class='title1edit' id='header' placeholder="<?= $HEADER ?>">
                <br><br>
                <input type='text' name='title' class='title2edit' id='title' placeholder="<?= $TITLE ?>">
                <br><br>
                <br><br>
                <span class='title3edit'>Issued to: <u>Recipient Name</u></span>
                <br><br>
                <span class='title3edit2'>on <u>Date Awarded</u></span>
                <br><br>
                <textarea name='content' id='editor' class='ck-editor'>
                    <p>Enter details here.</p>
                </textarea>

                <div>
                    <input type='text' name='issued_by' class="title4edit" id='issued_by' placeholder="<?= $issueName ?>">
                </div>xx
            </div>
            <button type='submit' class='button'>Save</button>
        </form>
    <?php
    } else if($USER->instructor && isset($_GET["mode"]) && $_GET["mode"] == "list") {
        if (!$userList) {
            ?>
            <h1>No students have received a certificate</h1>
            <form method='get'>
                <button type='submit' class='button'>Exit</button>
            </form>
            <?php
        } else {
            ?>
            <form method='get'>
                <button type='submit' class='button'>Exit</button>
            </form>
            <div>
            <table class="table">
            <tr>
                <th>Student Name</th>
                <th>Date</th>
            </tr>
            <?php
            foreach ($userList as $student) {
                $userID = $student['user_id'];
                $certDate = $student['date_awarded'];
                $userName = findDisplayName($userID, $PDOX, $p);

                echo('<tr>
                               <td>
                                '.$userName.'
                               </td>
                               <td>
                                '.$certDate.'
                               </td>
                      </tr>');

                ?>
                </table>

                </div>
                <?php
            }

        }
    } else if($_SERVER['REQUEST_METHOD'] == 'GET') {
        ?>
        <div>
            <h1 style="font-weight: bold;">Welcome!</h1>
            <p style="font-size: 25px;">Click 'Edit' to set up your certificate</p>
        </div>
        <a href="index.php?mode=edit" class="btn btn-warning pull-left"><span class="fa fa-pencil"
                                                                                   aria-hidden="true"></span> Edit</a>
        <a href="index.php?mode=list" class="btn btn-warning pull-left"><span class="fa fa-eye"
                                                                               aria-hidden="true"></span> Usage</a>
        <br><br>
        <div class="certBack">
            <br><br>
            <br><br>
            <div class="title1">Certificate of Completion</div>
            <br><br>
            <p class="title2"><u><?= $certificate["title"] ?></u></p>
            <br><br>
            <p class="title3">Issued to: <u>Recipient Name</u></p>
            <br><br>
            <p class="title32">on Date Awarded</p>
            <div>
                <p class="details"><?= $certificate["DETAILS"] ?></p>

            </div>
            <div>
                <p class="title4">Issued by: <u><?= $certificate["issued_by"] ?></u></p>
            </div>
        </div>
        <?php
    }
}  else if($_SERVER['REQUEST_METHOD'] == 'GET' && !$USER->instructor && $certificate) {
    $nameST = $PDOX->prepare("SELECT displayname FROM {$p}lti_user WHERE user_id = :user_id");
    $nameST->execute(array(":user_id" => $awardId["user_id"]));
    $name = $nameST->fetch(PDO::FETCH_ASSOC);
    ?>

    <button class='button' onclick='printCert();'>
        <span class='icon'>Print</span>
    </button>
    <div class="certBack">
        <br><br>
        <br><br>
        <div class="title1">Certificate of Completion</div>
        <br><br>
        <p class="title2"><u><?= $certificate["title"] ?></u></p>
        <br><br>
        <span class="title3">Issued to <u><?= $name["displayname"] ?></u></span>
        <br><br>
        <span class="title32">on <u><?= $awardId["date_awarded"] ?></u></span>
        <div>
            <p class="details"><?= $certificate["DETAILS"] ?></p>

        </div>
        <div>
            <p class="title4">Issued by <u><?= $certificate["issued_by"] ?></u></p>
        </div>
    </div>
    <?php
}

$OUTPUT->flashMessages();

$OUTPUT->footerStart();

?>
    <script src="scripts/ckeditor5-build-classic/ckeditor.js" type="text/javascript"></script>
    <script type="text/javascript">

        $(document).ready(function() {
            ClassicEditor
                .create( document.querySelector('#editor'), {
                    toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote' ]
                })
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });
        });


        function printCert() {
            printView = window.open('','','width=1100, height=900');
            printView.document.write("<div class=\"certBack\">\n"+
"        <br><br>\n"+
"        <br><br>\n"+
"        <br><br>\n"+
"            <span class=\"title1\">Certificate of Completion</span>\n"+
"            <br><br>\n"+
"            <span class=\"title2\"><u><?= $certificate["title"] ?></u></span>\n"+
"            <br><br>\n"+
"            <span class=\"title3\">Issued to <u><?= $name["displayname"] ?></u></span>\n"+
"\n"+
"            <span class=\"title3\">on <?= $awardId["date_awarded"] ?></span>\n"+
"            <br><br>\n"+
"            <div>\n"+
"                <span class=\"title3\"><?= $certificate["DETAILS"] ?></span>\n"+
"                <br><br>\n"+
"                <span class=\"title3\"\">Issued by <u><?= $certificate["issued_by"] ?></u></span>\n"+
"            </div>\n"+
"    </div>");

            printView.document.close();
            printView.focus();
            printView.print();
            printView.close();
        }
    </script>
<?php

$OUTPUT->footerEnd();