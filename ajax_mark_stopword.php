<?php
// Recepisce da una chiamata AJAX i parametri 
// per la segnalazione di un tag come stopword
// Funziona solo con utenti loggati

require_once  "rss_functions.php";

$tag = $_POST['tag'];

if (user_logged_in())
	mark_stopword($tag);

?>