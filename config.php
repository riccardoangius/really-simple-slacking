<?php

// Configurazione Server MySQL

define("MYSQL_USR","root");		// Utente
define("MYSQL_PWD","root");		// Password
define("MYSQL_HST","localhost");// Host
define("MYSQL_DB","rssDb");	// Database

// Fine Configurazione Server MYSQL

define("URL", "http://localhost/"); // URL della directory principale del sito compreso trailing slash

define("MIN_TAG_COUNT", 3);		// Numero occorrenze necessarie affinchè un tag sia visualizzato

define("POSTS_PER_PAGE", 20);		// Post per pagina
define("MAX_POPULAR_SOURCES", 300); // Massimo numero di suggerimenti di nuove fonti per volta

define("SALT", "1wW *}#`YaO]/}W->#WD0^Jye?=ky,\$h2-S{K[[~|nGYa#8SRS@B&:U^%pAQif/t"); // Salt anticontraffazione per i cookie

define("CACHE_INTERVAL", 60*60); 		// Intervallo minimo in secondi tra due letture consecutive dello stesso feed

define("COOKIE_NAME", "rss_cookie");	// Nome del cookie da impostare/leggere per l'autenticazione utente
define("COOKIE_LIFETIME", 60*60*24*2);	// Durata del cookie, in secondi

// Costanti da modificare constestualmente ad altri file

	define("INSTALL_FILE", "install.php");
	define("MYSQL_INIT_FILE", "sql/default_db.sql");

	// Costanti hardcoded nei file nella cartella sql/
	define("SOURCES_TABLE","`rssSources`"); 
	define("USERS_TABLE","`rssUsers`");
	define("CACHE_TABLE","`rssCache`");
	define("TAGS_TABLE","`rssTags`");

	// Rapportato alla lunghezza in byte della colonna CACHE_TABLE.summary
	define("MAX_SUMMARY_LENGTH", 4000);

	// Rapportato alla lunghezza in byte della colonna CACHE_TABLE.summary
	define("MAX_USERNAME_LENGTH", 8);

	// Rapportato alle misure di css/style.css
	define("TAGS_PER_CLOUD", 10);
	define("MAX_TAG_HEIGHT", 5);
	define("TAG_HEIGHT_MULTIPLIER", 45);

define("TAG_DELETE_CHAR", "x");	// Stringa del tooltip per segnalare un tag come stopword

define("DELIMITER", ",");	// Delimitatore usato negli array ridotti a stringa

define("IS_TAG_PAGE", !empty($_GET['tag']) && $_GET['tag'] != "null");

?>