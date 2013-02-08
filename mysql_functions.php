<?php
// Stabilisce una connessione con il server MySQL
// Non viene utilizzato mysql_close() poichè secondo le specifiche il link
// viene comunque chiuso al termine dell'esecuzione dello script
function mysql_start($skip_db_selection = false) {
	$link = mysql_connect(MYSQL_HST, MYSQL_USR, MYSQL_PWD);
	
	if (!$link)
		die("Could not establish a database connection.");
	
	mysql_set_charset('utf8'); 
	
	if ($skip_db_selection)
	 	return;
	
	$db_selected = mysql_select_db(MYSQL_DB);
	
	// Rinvia all'auto-installazione in caso di database non trovato
	if (!$db_selected) {
		header('Location: '.URL.INSTALL_FILE);
		die();
	}
	
}

// Tratta una stringa affinchè sia convertita in UTF-8 e non ci sia
// possibilità di SQL injection
function mysql_ready_string($string, $original_encoding = "UTF-8") {
	$string = mb_convert_encoding($string, "UTF-8", $original_encoding);
	$string = strip_tags($string, "<a><img><p>");
	$string = mysql_real_escape_string($string);
	return $string;
}

?>