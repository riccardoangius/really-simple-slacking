<?php
require_once  "rss_functions.php";

rss_logout();
	
header("Location: ".URL."?logout=true")

?>