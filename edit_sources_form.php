<?php 

// Form per la modifica delle fonti dell'utente loggato
require_once "rss_functions.php";

if (@$user || $user = user_logged_in(true)) {

	// Sprona a inserire una nuova fonte
	echo "<span id='type_something'>Try your favourite news website's address!</span>";

	// Per nuova fonte inserita manualmente
	echo 	"<input type='text' id='new_source_url' />",
			"<input type='submit' value='Add source' id='add_source' />";

	// Crea il menu a tendina se l'utente ha delle fonti
	if (!empty($user['sources'])) {
	
			echo "<select id='victim_source_id'>";
			
			// Prende le informazioni sulle fonti dell'utente
			$sources = get_sources_data($user['sources']);
	
			foreach ($sources as $source) {
		
				// Troncatura del nome per il menu a tendina
				// Il nome completo viene preservato e reso visibile dall'attributo title
				$source['shortname'] = substr($source['source_name'], 0, 15);
		
				echo "<option value='{$source['id']}' title='{$source['source_name']}'> {$source['shortname']}";
		
				if (strcmp($source['shortname'], $source['source_name']) < 0)
					echo "&hellip;";
			
				echo "</option>";
	
			}

			echo 	"</select>",
					"<input type='submit' value='Remove source' id='remove_source' />";

	}	
}
?>