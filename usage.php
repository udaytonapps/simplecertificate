<?php
require_once "../config.php";

use Tsugi\Core\LTIX;

$p = $CFG->dbprefix;

$LAUNCH = LTIX::requireData();

function findDisplayName($user_id, $PDOX, $p) {
    $nameST = $PDOX->prepare("SELECT displayname FROM {$p}lti_user WHERE user_id = :user_id");
    $nameST->execute(array(":user_id" => $user_id));
    $name = $nameST->fetch(PDO::FETCH_ASSOC);
    return $name["displayname"];
}

$certificateST  = $PDOX->prepare("SELECT * FROM {$p}certificate WHERE link_id = :linkId");
$certificateST->execute(array(":linkId" => $LINK->id));
$certificate = $certificateST->fetch(PDO::FETCH_ASSOC);

$userSt = $PDOX->prepare("SELECT * FROM {$p}cert_award WHERE cert_id = :certId");
$userSt->execute(array(":certId" => $certificate["cert_id"]));
$userList = $userSt->fetchAll(PDO::FETCH_ASSOC);


$OUTPUT->header();
?>
    <link rel="stylesheet" type="text/css" href="main.css">

    <script>
        function confirmResetTool() {
            return confirm("Are you sure that you want to clear all earned certificates? This cannot be undone.");
        }
    </script>
<?php
$OUTPUT->bodyStart();

if($USER->instructor) {
    ?>
    <ol class="breadcrumb">
        <li><a href="index.php">Certificate Admin</a></li>
        <li class="active">Certificates Earned</li>
    </ol>
    <?php
    if (!$userList) {
        ?>
        <div class="container">
            <div class="col-sm-1"></div>
            <div class="col-sm-4">
                <img class="noCertPic" src="images/undraw_community_8nwl.svg">
            </div>
            <div class="col-sm-7">
                <h1 class="text-muted">No certificates have been earned.</h1>
            </div>
            
        </div>
        <?php
    } else {
        ?>
        <div class="container">
            <a href="clearList.php" onclick="return confirmResetTool();" class="btn btn-success pull-right"><span class="fa fa-trash" aria-hidden="true"></span> Clear Results</a>
            <h1 class="compCerts">Completed Certificates</h1>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($userList as $student) {
                    $userID = $student['user_id'];
                    $certDate = $student['date_awarded'];
                    $certDate = date('F j, Y h:i a', strtotime($certDate));
                    $userName = findDisplayName($userID, $PDOX, $p);

                    echo('<tr>
                               <td>
                                '.$userName.'
                               </td>
                               <td>
                                '.$certDate.'
                               </td>
                      </tr>');
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}