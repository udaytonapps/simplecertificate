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
        function alertWin()
        {
            alert("List cleared successfully.");
        }
    </script>
<?php
$OUTPUT->bodyStart();

if($USER->instructor) {
    if (!$userList) {
        ?>
        <h1>No students have received a certificate</h1>
        <a href="index.php" class="btn btn-primary pull-left"><span aria-hidden="true"></span> Back</a>
        <?php
    } else {
        ?>
        <a href="index.php" class="btn btn-primary pull-left"><span class="fa fa-reply" aria-hidden="true"></span> Back</a>
        <a onclick="alertWin()" href="clearList.php" class="btn btn-success pull-left"><span class="fa fa-trash" aria-hidden="true"></span> Clear Results</a>
        <div class="container">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>x
                <?php
                foreach ($userList as $student) {
                    $userID = $student['user_id'];
                    $certDate = $student['date_awarded'];
                    $certDate = date('F j, Y H:i a', strtotime($certDate));
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