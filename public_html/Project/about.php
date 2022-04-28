<?php
//note we need to go up 1 more directory
require(__DIR__ . "/../../partials/nav.php");

if (!is_logged_in()) {
    flash("You are not logged in!", "warning");
    redirect("$BASE_PATH" . "login.php");
}

require_once(__DIR__ . "/../../partials/footer.php");
?>

<header>About Us</header>