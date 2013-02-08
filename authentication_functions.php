<?php
// Funzioni di autenticazione

// Tenta il login utente e in caso positivo imposta il cookie identificativo
function rss_login($username, $password) {
	
	if (empty($username) || empty($password)) 
		return false;
	
	$username = substr($username, 0, MAX_USERNAME_LENGTH);
	$username = mysql_ready_string($username);
	$password = md5($password);	

	$login_q = "SELECT * FROM ".USERS_TABLE." WHERE name = '{$username}' AND password = '{$password}'";

	$login_r = mysql_query($login_q) or die(mysql_error());

	$login_a = mysql_fetch_assoc($login_r);

	// Creazione del cookie in caso di esito positivo
	if (mysql_num_rows($login_r) > 0) {

		$cookie[0] = $login_a['name'];
		$cookie[1] = $login_a['id'];
		
		// Creazione di un campo hash per verificare che il cookie non sia contraffatto
		$cookie[2] = md5($login_a['name']." ".$login_a['id']." ".SALT);

		$cookie = implode(",", $cookie);

		setcookie(COOKIE_NAME, $cookie, time() + COOKIE_LIFETIME);
	
		return true;
	}
	
	return false;
}

// Esegue il logout eliminando il cookie
function rss_logout() {

	setcookie(COOKIE_NAME, "", 0);
	
}

// Controlla se l'utente è loggato
// Nel caso in cui $get_all è vero viene eseguita una connessione al database 
// per ottenere tutte le informazioni sull'utente
// Altrimenti viene eseguito solo un semplice controllo sulla correttezza dei campi del cookie per evitare contraffazioni
// e restituito un array con configurazione {name, id}
function user_logged_in($get_all = false) {

	if (isset($_COOKIE[COOKIE_NAME])) {

		$cookie = explode(",", $_COOKIE[COOKIE_NAME]);
	
		if (md5($cookie[0]." ".$cookie[1]." ".SALT) == $cookie[2]) {
			
			if ($get_all) {
			
				$user_q = "SELECT * FROM ".USERS_TABLE." WHERE id = '{$cookie[1]}'";
				$user_r = mysql_query($user_q) or die(mysql_error());
				$user = mysql_fetch_assoc($user_r);
				if (mysql_num_rows($user_r) == 1)
					$user['sources_a'] = (!empty($user['sources'])) ? explode(DELIMITER, $user['sources']) : Array();
				else
					$user = false;
			}
			else {
				$user['name'] = $cookie[0];
				$user['id'] = $cookie[1];
			}
			return $user;
		}
			
	}
	return false;
	
}

// Tenta la registrazione di un nuovo utente
function rss_signup($username, $password) {
	
	if (empty($username) || empty($password)) 
		return false;
			
	$username = mysql_ready_string($username);
	$password = md5($password);	
	
	// Tenta di inserire una nuova riga per il nuovo utente, e se il nome utente è già preso in carico, non esegue alcuna modifica
	// Si usa la clausola "ON DUPLICATE KEY UPDATE name = name" poichè l'alternativa "INSERT IGNORE" ignora anche altri errori che potrebbero avvenire
	$signup_q = "INSERT INTO ".USERS_TABLE." (name, password) VALUES('{$username}', '{$password}') ON DUPLICATE KEY UPDATE name = name";

	$signup_r = mysql_query($signup_q) or die(mysql_error());

	$signup_success = (mysql_affected_rows() == 1);
	
	return $signup_success;
}
?>