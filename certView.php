<?php
require_once "../config.php";

use Tsugi\Core\LTIX;

$p = $CFG->dbprefix;

$LAUNCH = LTIX::requireData();

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$currentTime = $currentTime->format("m-d-Y H:i:s");

$certificateST  = $PDOX->prepare("SELECT * FROM {$p}certificate WHERE link_id = :linkId");
$certificateST->execute(array(":linkId" => $LINK->id));
$certificate = $certificateST->fetch(PDO::FETCH_ASSOC);

$issueName = !$certificate || $certificate["issued_by"] == null ? "name" : $certificate["issued_by"];
$titleDis = !$certificate || $certificate["title"] == null ? "title of award" : $certificate["title"];
$headerDis = !$certificate || $certificate["header"] == null ? "Certificate of Completion" : $certificate["header"];
$deptDis = !$certificate || $certificate["department"] == null ? "University of Dayton" : $certificate["department"];

if($certificate) {
    $awardSt = $PDOX->prepare("SELECT * FROM {$p}cert_award WHERE cert_id = :certId AND user_id = :userId");
    $awardSt->execute(array(":certId" => $_SESSION["cert_id"], ":userId" => $USER->id));
    $award = $awardSt->fetch(PDO::FETCH_ASSOC);

    $nameST = $PDOX->prepare("SELECT displayname FROM {$p}lti_user WHERE user_id = :user_id");
    $nameST->execute(array(":user_id" => $award["user_id"]));
    $name = $nameST->fetch(PDO::FETCH_ASSOC);
}

$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="main.css">
<?php
$OUTPUT->bodyStart();

if(!$USER->instructor) {
    if (!$certificate) {
        ?>
        <h1>No certificate exists.</h1>
        <?php
    } else {
        if($certificate["DETAILS"]=="                    " || $certificate["DETAILS"]==null) {
            ?>
            <input type="button" value="Print" class="button" onclick="printCert()"/>
            <div id="printArea">
                <div class="certBack">
                    <img src="images/certBack2.png" class="certBack2">
                    <br><br>
                    <br><br>
                    <div class="title1edit"><?= $headerDis ?></div>
                    <br><br>
                    <p class="line2">This is to certify that</p>
                    <br><br>
                    <p class="title3"><u><?= $name["displayname"] ?></u></p>
                    <p class="line3">has completed</p>
                    <p class="title2edit"><?= $titleDis ?></p>
                    <p class="line4">on</p>
                    <p class="title32"><?= $award["date_awarded"] ?></p>
                    <p class="line5">Issued by</p>
                    <p class="title4edit"><?= $issueName ?></p>
                    <p class="line6">________________________________________</p>
                    <p class="deptEdit"><?= $deptDis ?></p>
                </div>
            </div>
            <?php
        }
        else {
            ?>
            <div class="printButton">
                <input type="button" value="Print" class="btn btn-success" onclick="printCert()"/>
            </div>
            <div class="certBack" id="printArea">
                <div>
                    <img src="images/certBack2.png" class="certBack2">
                    <br><br>
                    <br><br>
                    <div class="title1edit"><?= $headerDis ?></div>
                    <br><br>
                    <p class="line2">This is to certify that</p>
                    <br><br>
                    <p class="title3"><u><?= $name["displayname"] ?></u></p>
                    <p class="line3">has completed</p>
                    <p class="title2edit"><?= $titleDis ?></p>
                    <p class="line4">on</p>
                    <p class="title32"><?= $award["date_awarded"] ?></p>
                    <p class="detailsHead">Certificate Requirements:</p>
                    <p class="detailsEdit"><?= $certificate['DETAILS'] ?></p>
                    <p class="line5">Issued by</p>
                    <p class="title4edit"><?= $issueName ?></p>
                    <p class="line6">________________________________________</p>
                    <p class="deptEdit"><?= $deptDis ?></p>
                </div>
            </div>
            <?php
        }

    }
}

$OUTPUT->footerStart();

?>
    <script type="text/javascript">

        function printCert() {
            var printPage = document.getElementById('printArea');
            var printView = window.open('', '', 'width=1100, height=850');
            printView.document.open();
            printView.document.write(printPage.innerHTML);
            printView.document.write('<html><link rel="stylesheet" href="printStyle.css" /></head><body onload="window.print()"></html>');
            printView.document.close();
        }
    </script>
<?php