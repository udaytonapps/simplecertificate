<?php
require_once "../config.php";

use Tsugi\Core\LTIX;

$p = $CFG->dbprefix;

$LAUNCH = LTIX::requireData();

$OUTPUT->bodyStart();

$certificateST  = $PDOX->prepare("SELECT * FROM {$p}certificate WHERE link_id = :linkId");
$certificateST->execute(array(":linkId" => $LINK->id));
$certificate = $certificateST->fetch(PDO::FETCH_ASSOC);

$userSt = $PDOX->prepare("SELECT * FROM {$p}cert_award WHERE cert_id = :certId");
$userSt->execute(array(":certId" => $certificate["cert_id"]));
$userList = $userSt->fetchAll(PDO::FETCH_ASSOC);

$delUsers = $PDOX->prepare("DELETE FROM {$p}cert_award WHERE cert_id = :certId");
$delUsers->execute(array(":certId" => $certificate["cert_id"]));

header('Location: ' . addSession('usage.php'));
return;