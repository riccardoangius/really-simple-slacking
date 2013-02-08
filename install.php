<?php
/*
 * Nel caso in cui il database MYSQL_DB impostato in config.php non esista, 
 * si viene redirezionati in questa pagina dove si tenta l'installazione automatica.
 */


require "config.php";
require "mysql_functions.php";
mysql_start(true);

if (mysql_select_db(MYSQL_DB))
	header('Location: '.URL);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns='http://www.w3.org/1999/xhtml'>

<head>
<title>Really Simple Slacking - Install page</title>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<link rel='stylesheet' type='text/css' href='css/reset.css' />
<link rel='stylesheet' type='text/css' href='css/style.css' />
</head>

<body class="install">

<?php

// Parametro per corretta lettura di PHP_EOL nel file
ini_set("auto_detect_line_endings", true);

// Tenta la creazione di un handle per file
// Se non esiste, viene ignorato il warning e l'interruzione dello script interrotta con un messaggio ad hoc.
$file = @fopen(MYSQL_INIT_FILE, "r");

// Controlla se l'handle è stato creato
if (!$file) 
	die("Couldn't open MySQL initialization file.");

echo "<p>Please wait while we install your awesome RSS reader... don't leave this page!</p><p style='word-wrap: break-word'>"; flush();


while (($query = fgets($file)) !== false) {

	// Ignora righe vuote e commenti
	if ($query == PHP_EOL || substr($query, 0, 2) == '--')
		continue;
		
	// Esegue l'istruzione contenuta nella riga corrente o termina lo script in caso di errore
	mysql_query($query) or die("<p>Something went wrong with the installation, please check your database configuration.</p><p>Here's a hint: <strong>".mysql_error()."</strong></p>");
	
	// Stampa immediatamente un punto per ogni istruzione eseguita
	echo "."; flush();
}

echo "</p>";

fclose($file);

echo "<p>All set! <a href=''>Show me the awesome!</a></p>";

?>
</body></html>