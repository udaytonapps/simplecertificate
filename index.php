<?php
require_once "../config.php";

use Tsugi\Core\LTIX;

$p = $CFG->dbprefix;

$LAUNCH = LTIX::requireData();

$currentTime = new DateTime('now', new DateTimeZone($CFG->timezone));
$currentTime = $currentTime->format("Y-m-d H:i:s");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $USER->instructor) {
    
}

$OUTPUT->header();

$OUTPUT->bodyStart();

$OUTPUT->flashMessages();

echo "<h1>Welcome</h1>";

$OUTPUT->footerStart();

$OUTPUT->footerEnd();