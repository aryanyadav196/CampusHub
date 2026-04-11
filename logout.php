<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";

unset($_SESSION["user"]);
set_flash("success", "You have been logged out.");
redirect_to("login.php");
