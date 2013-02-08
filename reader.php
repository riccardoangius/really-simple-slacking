<?php
// Crea il newsfeed per l'utente loggato in base ai parametri di pagina e (opzionalmente) tag
require_once "rss_functions.php";

$page = @is_numeric($_GET['page']) ? $_GET['page'] : 0;
$offset = $page * POSTS_PER_PAGE;

// Controllo utente loggato
if (@$user || $user = user_logged_in(true)) {

	// Nel caso in cui l'utente non abbia ancora aggiunto fonti
	// e si tratti del newsfeed principale, suggerisce di aggiungere
	// le fonti con il maggior numero di sottoscrittori
	if (empty($user['sources']) && !IS_TAG_PAGE) {
		
		echo 'Add a source to get started!';	
	
		$popular_sources_q = "SELECT * FROM ".SOURCES_TABLE." ORDER by subscribers DESC LIMIT ".MAX_POPULAR_SOURCES;
		$popular_sources_r = mysql_query($popular_sources_q) or die(mysql_error());
	
		if (mysql_num_rows($popular_sources_r) > 0) {
	
			echo 	"<div id='popular_sources'>", 
					"Here's what others are reading:"; 
		
			while ($source = mysql_fetch_assoc($popular_sources_r))
				echo "<p><a href='{$source['url']}' class='subscribe'>{$source['source_name']}</a></p>";

			echo 	"</div><!-- #popular_sources -->";
	
		}
	}
	else {
		// L'utente è iscritto a una più fonti delle fonti
	
		// Prima parte della query MySQL la cui clausola WHERE viene completata in base al tipo di newsfeed richiesto
		$articles_q = "SELECT * FROM ".CACHE_TABLE." INNER JOIN ".SOURCES_TABLE." ON ".CACHE_TABLE.".source = ".SOURCES_TABLE.".id WHERE ";

		if (IS_TAG_PAGE) {
			$tag = mysql_ready_string($_GET['tag']);
			$articles_q .= "MATCH (title, summary) AGAINST ('{$tag}') ";
		}
		else {
			// Nel caso di prima pagina (alla prima apertura o all'aggiunta di  unanuova fonte, che 
			// risulta in un refresh via AJAX del newsfeed) esegue il caching delle fonti a cui l'utente è iscritto
			if ($page == 0)
				cache_sources($user['sources']);
			
			// Trasforma "a, b, …, z" in "a', 'b', …, 'z'" per la clausola IN
			$sources = str_replace(DELIMITER, "', '", $user['sources']);
			
			$articles_q .= "source IN ('{$sources}') ";
		}
		
		$articles_q .= "ORDER by date_timestamp DESC LIMIT {$offset},".POSTS_PER_PAGE;

		$articles_r = mysql_query($articles_q) or die(mysql_error());

		// Flag per il segnalatore .different_source che indica visivamente durante lo scrolling
		// il passaggio ad una fonte diversa
		$last_source = 0;

		if (mysql_num_rows($articles_r) > 0) {

			while ($article = mysql_fetch_assoc($articles_r)) {
			
				echo "<div class='entry'>";
								
				echo 	"<div class='entry_source'>",
						"<a href='{$article['url']}' title='{$article['source_name']}'>{$article['source_name']}</a>",
						"</div>";

				// Mostra un segnalatore di differente fonte, utile per lo scrolling veloce
				if ($last_source != $article['source']) {
					echo "<div class='different_source'></div>";
					$last_source = $article['source'];
				}

				// Se si tratta di pagina di tag, mostra un piccolo testo che permette
				// la sottoscrizione alla fonte dell'articolo se l'utente non è già iscritto
				if (IS_TAG_PAGE) {
					
					$tooltip_classes = "subscribe add_source_{$article['source']}";

					if (in_array($article['source'], $user['sources_a'])) 
						$tooltip_classes .= " hidden";
							
					echo 	"<a href='{$article['url']}' class='{$tooltip_classes}'>",
					 		"subscribe",
							"</a>";
				}
				

				echo 	"<div class='entry_title'><a href='{$article['link']}' target='_blank'>{$article['title']}</a></div>",
						"<div class='entry_summary'>{$article['summary']}",
						"<a class='read_more' target='_blank' title='Original article' href='{$article['link']}'>&hellip;</a>",
						"</div>";
					
				echo "</div><!-- .entry -->";
	
			}
	
		}
		else if ($page == 0) {

			/* È la prima pagina e non ci sono articoli da mostrare: viene mostrato un errore.
			 * I casi possibili sono i seguenti:
			 *
			 * A. I feed aggiunti, anche se di formato regolare, non contengono ancora articoli.
			 *
			 * B. È la pagina di un tag che MySQL rileva come stopword perchè troppo frequente (falso positivo che si 
			 * risolve quando vengono aggiunte ulteriori fonti, è ad esempio il caso di #Facebook quando viene aggiunta 
			 * solo il feed RSS di Mashable)
			 *
			 * C. Si tratta effettivamente di una stopword che non è stata inclusa nel database. 
			 *
			 * Si è cercato di inserirne il maggior numero possibile per evitare questi casi, ma si 
			 * ritiene che un uso concreto della piattaforma  crowd-sourcing per segnalare questi casi 
			 * (tramite il tooltip .tag. delete) permetta in pochi giorni di eliminare il problema.
			 *
			 */
			
			echo "<div class='error'>";

			if (IS_TAG_PAGE)
				echo 	"No posts with this tag!<br/>",
					 	"(Or, maybe, this is just a very common word: ", 
						"please help us and delete it, it won't show up again!)";
			else
				echo 	"No posts from your feeds yet!";

			echo "</div><!-- .error -->";
		}
	}
}
?>