<?php
// Tenta il login con i dati passati con metodo POST
// Se il login fallisce, tenta la creazione di un nuovo utente e
// in caso di esito positivo esegue il login di questi.
// Altrimenti rinvia alla pagina di login segnalando l'errore.
require_once  "rss_functions.php";

$name = @$_POST['name'];
$password = @$_POST['password'];

$location = URL."index.php";

$logged_in = rss_login($name, $password);

if (!$logged_in) {
	if (rss_signup($name, $password))
		rss_login($name, $password);
	else {
		$location .= "?bad_login=".(string)!$logged_in;
		if (!empty($name))
			$location .= "&username=".$name;
	}
}
	
header("Location: ".$location);

?>