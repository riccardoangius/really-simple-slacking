<?php
// Crea i tag per riempire la nuvola condivisa da tutti gli utenti

require_once "rss_functions.php";

// Controllo su cookie non contraffatto
if (user_logged_in()) {

	$page = @is_numeric($_GET['page']) ? $_GET['page'] : 0;
	$offset = $page * TAGS_PER_CLOUD;

	// Query SQL
	$tags_q = "SELECT * FROM ".TAGS_TABLE." WHERE count > ".MIN_TAG_COUNT." AND stopword = FALSE ORDER BY count DESC LIMIT {$offset},".TAGS_PER_CLOUD;

	$tags_r = mysql_query($tags_q) or die (mysql_error());

	if (mysql_num_rows($tags_r) > 0) {

		// Denominatore del rapporto tra le occorrenze del tag e 
		$denominator = 0;
		
		// Calcolo del denomiatore tramite iterazione, che si ritiene più performante 
		// di una ulteriore chiamata MySQL per il solo conteggio della somma
		while ($tag = mysql_fetch_assoc($tags_r))
			$denominator = $denominator + $tag['count'];

		mysql_data_seek($tags_r, 0);

		while ($tag = mysql_fetch_assoc($tags_r)) {

			// Calcolo del peso relativo del tag
			$weight = $tag['count']/$denominator;

			// Calcolo dell'altezza in EMs
			$size = TAG_HEIGHT_MULTIPLIER * $weight;
		
			// Normalizzazione
			if ($size > MAX_TAG_HEIGHT) $size = MAX_TAG_HEIGHT;
		
			echo 	"<div class='tag'>", 
						"<a href='?tag={$tag['name']}' style='font-size: {$size}em'>",
							"{$tag['name']}",
						"</a>",
						"<span class='delete' title='Delete tag \"{$tag['name']}\"'>".TAG_DELETE_CHAR."</span>",
					"</div>";
	
		}
	}
}

?>