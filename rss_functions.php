<?php
require "config.php";
require "mysql_functions.php";
@require('classes/htmlfixer.class.php');
@require('classes/simplepie.inc');
require "authentication_functions.php";

mysql_start();


// Data una lista di identificativi numerici di fonti rss separate 
// da DELIMITER, restituisce un array contenente tutti i dati sulle suddette
function get_sources_data($sources) {

	$sources = str_replace(DELIMITER, "', '", $sources);

	$sources_q = "SELECT * FROM ".SOURCES_TABLE." WHERE id IN ('{$sources}')"; 
	$sources_r = mysql_query($sources_q) or die(mysql_error());
	
	$sources = Array();
	
	while ($source = mysql_fetch_assoc($sources_r))
		$sources[$source['id']] = $source;

	return $sources;
	
}


// Modifica le fonti di $user aggiungendo o rimuovendo 
// $target dalla sua lista personale
function edit_sources($user, $action, $target) {
	
	if (empty($target))
		return false;
	
	$actions = Array('add', 'remove');

	if (in_array($action, $actions)) {

		// Flag per verificare che le fonti siano state
		// effettivamente modificate da questa azione
		$update_user_sources = false;

		// Crea un nuovo array se non vi è ancora alcuna fonte
		$sources = (!empty($user['sources'])) ? explode(DELIMITER, $user['sources']) : Array();

		switch ($action) {

			case 'add': 

			// Aggiunge http(s) se non già presente nell'URL passato  
			if (!preg_match("#^http(s)?://#i", $target))
				$target = "http://".$target;
				
			// Controllo su URL ben formato
			if ($url = filter_var($target, FILTER_VALIDATE_URL)) {
				
				// Uso della libreria SimplePie per lettura dati feed
				$feed = new SimplePie();
				$feed->set_feed_url($url);
				$feed->enable_cache(false);
				$feed->init();
				$feed->handle_content_type();
				$feed_error = $feed->error();
				
				// Controllo su URL effettivamente corrispondente a un feed						
				if (empty($feed_error)) {
					
					$encoding = $feed->get_encoding();
					$source_name = mysql_ready_string($feed->get_title(), $encoding);

					// Controllo se il feed non sia stato ottenuto tramite link alternate/xml dell'URL passato
					$actual_url = $feed->get_link();

					if (!empty($actual_url)) $url = $actual_url;

					$url = mysql_ready_string($url, $encoding);
					$hash = md5($url);
					
					// Inserisce la fonte o se già esistente aggiorna il numero di sottoscrittori
					$insert_q = "INSERT INTO ".SOURCES_TABLE." (url, hash, source_name, subscribers) VALUES ('{$url}', '{$hash}', '{$source_name}', 1) ON DUPLICATE KEY UPDATE subscribers = subscribers + 1";

					mysql_query($insert_q) or die(mysql_error());

					$source_id = mysql_insert_id();

					// Se l'URL è già presente tra le fonti e tra le fonti dell'utente
					// non apporta alcuna modifica e corregge il numero di sottoscrittori
					// altrimenti la aggiunge
					// Viste le bassa probabilità che un utente aggiunga due volte
					// la stessa fonte e il fatto che fare un controllo prima di $insert_q
					// richiederebbe comunque una query aggiuntiva, si ritiene più performante questa soluzione
					if (!in_array($source_id, $sources)) {

						$sources[] = $source_id;

						$update_user_sources = true;

					}
					else {
						// Correzione di cui sopra
						$update_source_q = "UPDATE ".SOURCES_TABLE." SET subscribers = subscribers - 1 WHERE id = '{$source_id}'";
						mysql_query($update_source_q) or die(mysql_error());

					}
				}
			}

			break;

			case 'remove':

			// Controlla che $target sia effettivamente un intero 
			// (anche se contenuto nella stringa di input)
			if ($source_id = filter_var($target,FILTER_VALIDATE_INT)) {

				// Controlla che la fonte sia effettivamente presente
				if (in_array($source_id, $sources)) {

					// Aggiornamento sottoscrittori della fonte
					$update_source_q = "UPDATE ".SOURCES_TABLE." SET subscribers = subscribers - 1 WHERE id = '{$source_id}'";
					mysql_query($update_source_q) or die(mysql_error());

					// Rimozione della fonte
					$sources = array_diff($sources, array($source_id));
					$update_user_sources = true;

				}

			}

			break;
		}

		// Aggiorna le fonti utente
		if ($update_user_sources) {
			
			$sources = implode(",", $sources);

			$update_q = "UPDATE ".USERS_TABLE." SET sources = '{$sources}' WHERE id = '{$user['id']}'";

			mysql_query($update_q) or die(mysql_error().$update_q);

		}
		
		// Ritorna il numero della fonte su cui si è lavorato, se l'operazione è andata a buon fine
		if (isset($source_id))
			return $source_id;

	}
	return false;
}

// Esegue il caching delle fonti indicate numericamente
// e separate da DELIMITER nella stringa $sources
function cache_sources($sources) {

		$default_locale = setlocale(LC_ALL, 0);
		
		// Uso della libreria HtmlFixer per correggere HTML mal troncato
		// a causa del limite di byte della colonna MySQL summary
		$htmlfixer = new HtmlFixer();

		$current_time = time();

		$sources = str_replace(DELIMITER, "', '", $sources);

		$sources_q = "SELECT * FROM ".SOURCES_TABLE." WHERE id IN ('{$sources}') ORDER BY last_checked ASC";

		$sources_r = mysql_query($sources_q) or die(mysql_error());

		while ($source = mysql_fetch_assoc($sources_r)) {
		
			// Array per la selezione dei tag
			$all_words = Array();
			$select_words = Array();
			
			// Check per ignorare le fonti già in cache
			$last_entry_q = "SELECT * FROM ".CACHE_TABLE." WHERE source = {$source['id']} ORDER BY date_timestamp DESC LIMIT 1";
			$last_entry_r = mysql_query($last_entry_q) or die(mysql_error());
			
			$last_entry = mysql_fetch_assoc($last_entry_r);
			
			$source['last_cached'] = (mysql_num_rows($last_entry_r) > 0) ? $last_entry['date_timestamp'] : 0;			

			if (($current_time - $source['last_checked']) > CACHE_INTERVAL) {
				
				// Lettura del feed tramite libreria SimplePie
				$feed = new SimplePie();
				$feed->set_feed_url($source['url']);
				$feed->enable_cache(false);
				$feed->init();
				$feed->handle_content_type();
				$encoding = $feed->get_encoding();
			
				foreach ($feed->get_items() as $item) {
					
					$date_timestamp = $item->get_date("U"); 
						
					if ($date_timestamp > $source['last_cached']) {
									
						$title = mysql_ready_string($item->get_title(), $encoding);
						$link = mysql_ready_string($item->get_permalink(), $encoding);
						
						$summary = $item->get_content();
						$plain_summary = $summary;
						
						if (strlen($summary) > MAX_SUMMARY_LENGTH) {
							$summary = substr($summary, 0, MAX_SUMMARY_LENGTH);
						
							$summary = $htmlfixer->getFixedHtml($summary);
						
							$plain_summary = $summary;

						}						
						$summary = mysql_ready_string($summary, $encoding);

					$cache_q = "INSERT INTO ".CACHE_TABLE." (title, summary, link, date_timestamp, source) 
					VALUES('{$title}', '{$summary}', '{$link}', '{$date_timestamp}', '{$source['id']}')";

					$cache_r = mysql_query($cache_q) or die(mysql_error());
					
					setlocale(LC_ALL, $feed->get_language());
					
					// Pulizia del titolo + sommario per ottenere solo le stringhe alfabetiche		
					$text_for_tags = preg_replace("/[^a-zA-ZÀ-ÿ]/", " ", $item->get_title()." ".strip_tags($plain_summary));

					$all_words = array_merge($all_words, explode(" ", $text_for_tags));
					
					}
					else {
						break;
					}
				}

				$update_q = "UPDATE ".SOURCES_TABLE." SET last_checked = {$current_time} WHERE id='{$source['id']}'";
				$update_r = mysql_query($update_q) or die(mysql_error());
			
			}

			// Cernita delle parole (eliminazione numeri e parole troppo corte)
			foreach($all_words as $word) {
				$word_hash = md5($word);
				
				if ((strlen($word) > 3) && (!is_numeric($word))) {
					if (isset($select_words[$word_hash]))
						$select_words[$word_hash]['count']++;
					else {
						$select_words[$word_hash]['name'] = $word;
						$select_words[$word_hash]['count'] = 1;
					}
				}
			}


			$words_size = count($select_words);

			$i = 0;

			// Contenitore per eseguire un'unica query SQL 
			// per i tag contribuiti da questa fonte
			$values = "";
		
			foreach($select_words as $word) {
		
				$word['name'] = mysql_ready_string($word['name'], $encoding);
				$values .= "('{$word['name']}', {$word['count']})";
					
				// Non aggiungere la virgola all'ultima coppia <parola, conteggio>
				if (++$i < $words_size)
					$values .= ', ';

			}
			
 			if ($i > 0) {
				$tag_q = "INSERT INTO ".TAGS_TABLE." (name, count) VALUES {$values} ON DUPLICATE KEY UPDATE count = count + VALUES(".TAGS_TABLE.".count)";

				$tag_r = mysql_query($tag_q) or die(mysql_error()." ".$tag_q);
			}
			
			
			unset($all_words);
			unset($select_words);
			unset($values);
		}
		
		setlocale(LC_ALL, $default_locale);
		
}


// Segnala una parola come stopword nel database
function mark_stopword($tag) {
	
	if (empty($tag))
		return;

	$tag = mysql_ready_string($tag);

	$tag_q = "UPDATE ".TAGS_TABLE." SET stopword=TRUE WHERE name='{$tag}'";

	$tag_r = mysql_query($tag_q) or die(mysql_error());
	
}

?>