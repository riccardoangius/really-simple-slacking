<?php
// Recepisce da una chiamata AJAX i parametri 
// per la modifica delle fonti dell'utente loggato
// e stampa l'ID della fonte oggetto di modifica

require_once "rss_functions.php";

$action = $_POST['action'];
$target = $_POST['target'];

if ($user = user_logged_in(true))
	echo edit_sources($user, $action, $target);

?>