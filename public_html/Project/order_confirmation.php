<?php
    require(__DIR__ . "/../../partials/nav.php");

    if (!is_logged_in()) {
        flash("You are not logged in!", "warning");
        die(header("Location: $BASE_PATH" . "login.php"));
    }
    

    require_once(__DIR__ . "/../../partials/footer.php");
?>