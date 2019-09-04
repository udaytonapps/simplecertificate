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

$issueName = !$certificate || $certificate["issued_by"] == null ? "Robert Hooke" : $certificate["issued_by"];
$titleDis = !$certificate || $certificate["title"] == null ? "Module on Hooke's Law" : $certificate["title"];
$headerDis = !$certificate || $certificate["header"] == null ? "Certificate of Completion" : $certificate["header"];
$deptDis = !$certificate || $certificate["department"] == null ? "" : $certificate["department"];
$selected = !$certificate || $certificate["background"] == null ? "1" : $certificate["background"];

if ($_SERVER['REQUEST_METHOD']== 'POST' && $USER->instructor) {
    $background = isset($_POST["background"]) ? $_POST["background"] : " ";
    $title = isset($_POST["title"]) ? $_POST["title"] : " ";
    $header = isset($_POST["header"]) ? $_POST["header"] : " ";
    $issuedBy = isset($_POST["issued_by"]) ? $_POST["issued_by"] : " ";
    $department = isset($_POST["department"]) ? $_POST["department"] : " ";
    $DETAILS = isset($_POST["DETAILS"]) ? $_POST["DETAILS"] : " ";

    if($certificate) {
        $updateStmt = $PDOX->prepare("UPDATE {$p}certificate SET background=:background, title=:title, header=:header, issued_by=:issued_by, department=:department, DETAILS=:DETAILS, modified=:modified WHERE cert_id = :certId");
        $updateStmt->execute(array(
            ":background" => $background,
            ":title" => $title,
            ":header" => $header,
            ":issued_by" => $issuedBy,
            ":department" => $department,
            ":DETAILS" => $DETAILS,
            ":modified" => $currentTime,
            ":certId" => $certificate["cert_id"]
        ));
    } else {
        $createCert = $PDOX->prepare("INSERT INTO {$p}certificate (context_id, link_id, user_id, background, title, header, issued_by, department, DETAILS, modified)
                                VALUES (:contextId, :linkId, :userId, :background, :title, :header, :issued_by, :department, :DETAILS, :modified)");
        $createCert->execute(array(
            ":contextId" => $CONTEXT->id,
            ":linkId" => $LINK->id,
            ":userId" => $USER->id,
            ":background" => $background,
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
<link rel="stylesheet" type="text/css" href="main.css" xmlns="http://www.w3.org/1999/html">
    <script>
        $('#background').on('change', function() {
            // Save value in localstorage
            localStorage.setItem("background", $(this).val());
        });

        $(document).ready(function() {
            if ($('#background').length) {
                $('#background').val(localStorage.getItem("background"));
            }
        });

        var x, i, j, selElmnt, a, b, c;
        x = document.getElementsByClassName("background");
        for (i = 0; i < x.length; i++) {
            selElmnt = x[i].getElementsByTagName("select")[0];
            /*for each element, create a new DIV that will act as the selected item:*/
            a = document.createElement("DIV");
            a.setAttribute("class", "select-selected");
            a.innerHTML = selElmnt.options[selElmnt.selectedIndex].innerHTML;
            x[i].appendChild(a);
            /*for each element, create a new DIV that will contain the option list:*/
            b = document.createElement("DIV");
            b.setAttribute("class", "select-items select-hide");
            for (j = 0; j < selElmnt.length; j++) {
                /*for each option in the original select element,
                create a new DIV that will act as an option item:*/
                c = document.createElement("DIV");
                c.innerHTML = selElmnt.options[j].innerHTML;
                c.addEventListener("click", function(e) {
                    /*when an item is clicked, update the original select box,
                    and the selected item:*/
                    var y, i, k, s, h;
                    s = this.parentNode.parentNode.getElementsByTagName("select")[0];
                    h = this.parentNode.previousSibling;
                    for (i = 0; i < s.length; i++) {
                        if (s.options[i].innerHTML == this.innerHTML) {
                            s.selectedIndex = i;
                            h.innerHTML = this.innerHTML;
                            y = this.parentNode.getElementsByClassName("same-as-selected");
                            for (k = 0; k < y.length; k++) {
                                y[k].removeAttribute("class");
                            }
                            this.setAttribute("class", "same-as-selected");
                            break;
                        }
                    }
                    h.click();
                });
                b.appendChild(c);
            }
            x[i].appendChild(b);
            a.addEventListener("click", function(e) {
                /*when the select box is clicked, close any other select boxes,
                and open/close the current select box:*/
                e.stopPropagation();
                closeAllSelect(this);
                this.nextSibling.classList.toggle("select-hide");
                this.classList.toggle("select-arrow-active");
            });
        }
        function closeAllSelect(elmnt) {
            /*a function that will close all select boxes in the document,
            except the current select box:*/
            var x, y, i, arrNo = [];
            x = document.getElementsByClassName("select-items");
            y = document.getElementsByClassName("select-selected");
            for (i = 0; i < y.length; i++) {
                if (elmnt == y[i]) {
                    arrNo.push(i)
                } else {
                    y[i].classList.remove("select-arrow-active");
                }
            }
            for (i = 0; i < x.length; i++) {
                if (arrNo.indexOf(i)) {
                    x[i].classList.add("select-hide");
                }
            }
        }
        /*if the user clicks anywhere outside the select box,
        then close all select boxes:*/
        document.addEventListener("click", closeAllSelect);
    </script>
<?php

$OUTPUT->bodyStart();
$OUTPUT->flashMessages();

if($USER->instructor) {
    ?>
    <div class="box">
        <div>
            <a href="usage.php" class="btn btn-primary pull-right"><span class="fa fa-eye"
                                                                         aria-hidden="true"></span> Certificates Earned</a>
            <h1 class="header">Simple Certificate<a href="https://ewiki.udayton.edu/isidore/Simple_Certificate" target="_blank" class="link1"><span class="fa fa-share-square-o" aria-hidden="true"></span><u>How do students earn certificates?</u></a></h1>

            <p class="instructions">Fill out the fields below to create your certificate. You'll be able to see a preview of how the certificate will
            look at the bottom of the page. You can also track those that have earned the certificate under the 'Certificates Earned' button.</p>
        </div>
    <br>
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
                <label class="inputs" for="background">Certificate Background:</label>
            </div>
            <div class="col-sm-9">
                <select class="dropdown" id="background" name="background" >
                    <option value="" selected disabled hidden>Choose Background</option>
                    <option value="1">Blue Ribbon</option>
                    <option value="2">Gold Medal</option>
                    <option value="3">Dark</option>
                </select>
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="header">Title of Certificate:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="27" class="form-control" id="header" name="header" placeholder="<?= $headerDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="title">Title of Achievement:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="36" class="form-control" id="title" name="title" placeholder="<?= $titleDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issued_by">Awards Issued By:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="24" class="form-control" id="issued_by" name="issued_by" placeholder="<?= $issueName ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issueDep">Issuing Department/Unit:</label>
            </div>
            <div class="col-sm-8">
                <input maxlength="80" class="form-control" id="department" name="department" placeholder="Department of Mechanical Engineering">
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
                <label class="inputs" for="background">Certificate Background:</label>
            </div>
            <div class="col-sm-9">
                <select class="dropdown" id="background" name="background">
                    <?php
                    if($selected == 1) {
                        ?>
                        <option value="1" selected>Blue Ribbon</option>
                        <option value="2">Gold Medal</option>
                        <option value="3">Dark</option>
                        <?php
                    } else if($selected == 2) {
                        ?>
                        <option value="1">Blue Ribbon</option>
                        <option value="2" selected>Gold Medal</option>
                        <option value="3">Dark</option>
                        <?php
                    } else {
                        ?>
                        <option value="1">Blue Ribbon</option>
                        <option value="2">Gold Medal</option>
                        <option value="3" selected>Dark</option>
                        <?php
                    }
                    ?>

                </select>
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="header">Title of Certificate:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="27" class="form-control" id="header" name="header" value="<?= $headerDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="title">Title of Achievement:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="36" class="form-control" id="title" name="title" value="<?= $titleDis ?>">
            </div>
        </div>
        <div class="container">
            <div class="col-sm-3">
                <label class="inputs" for="issued_by">Awards Issued By:</label>
            </div>
            <div class="col-sm-9">
                <input maxlength="24" class="form-control" id="issued_by" name="issued_by" value="<?= $issueName ?>">
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
        <?php
        if($certificate) {
                ?>
                <hr class="hr">
                <div class="container">
                    <div class="col-sm-6">
                    </div>
                    <div class="col-sm">
                        <p class="preview">Preview</p>
                    </div>
                    <div class="col-sm-6">
                    </div>
                </div>

                <?php
                if($certificate['background'] == 1) {
                    ?>
                        <div class="certBack1">
                    <?php
                } else if($certificate['background'] == 2) {
                    ?>
                        <div class="certBack2">
                    <?php
                } else if($certificate['background'] == 3) {
                    ?>
                        <div class="certBack3">
                    <?php
                }
                ?>
                    <br><br>
                    <br><br>
                    <div class="title1edit"><?= $headerDis ?></div>
                    <br><br>
                    <p class="line2">This is to certify that</p>

                    <br><br>
                    <p class="title3edit"><u>Recipient Name</u></p>
                    <p class="line3">has completed</p>
                    <p class="title2edit"><?= $titleDis ?></p>
                    <p class="line4">on</p>
                    <p class="title3edit2">Date Awarded</p>
                    <?php
                    if(!$certificate["DETAILS"]=="") {
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

}  else if($_SERVER['REQUEST_METHOD'] == 'GET' && !$USER->instructor) {
        header('Location:'.addSession('certView.php'));
}

$OUTPUT->footerStart();

$OUTPUT->footerEnd();