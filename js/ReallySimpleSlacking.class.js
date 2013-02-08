/**
 *	ReallySimpleSlacking Javascript Tools
 *
 *	@author Riccardo Angius
 */

// Costanti
const ENTER_KEY = 13;

const READER_URL = "reader.php";
const EDIT_SOURCES_FORM = "edit_sources_form.php";
const MARK_STOPWORD_WORKER = "ajax_mark_stopword.php";
const EDIT_SOURCES_WORKER = "ajax_edit_sources.php";
const TAGS_URL = "tags.php";

// Valori CSS (ForeGround e HIDden) per la nuvola dei tag e per il menu utente
const FG_TAG_CLOUD_LEFT = '50%';
const FG_TAG_CLOUD_TOP = '10%';
const HID_TAG_CLOUD_OPACITY = '0.5';

const FG_USER_MENU_TOP = '-9em';

// Classe Really Simple Slacking
function ReallySimpleSlacking() {

	// Oggetti jQuery degli elementi DOM più usati per il binding
	var loadingOverlay = $('#loading_overlay');
	var editSourcesForm = $('#edit_sources');	
	var tagCloud = $('#tag_cloud');
	var reader = $('#reader');
	var userMenu = $('#user_menu');

	// Selettori CSS degli elementi DOM più usati 
	// e ricaricati con chiamate AJAX (per uso con jQuery)
	
	var newSourceInputS = '#new_source_url';
	var victimSourceInputS = '#victim_source_id';
	var typeSomethingS = '#type_something';	
	
	// Prefisso per la classe dei tooltip appartenenti alla stessa fonte
	var subscribeTooltipClassPrefix = 'add_source_';	
	
	var isLoginPage, isTagPage;
	
	var tagsPage = 0, readerPage = 0, scrolledDown = false, lastScrollY = 0, loadingNewPage = false;
	
	var scrollCheckHandle = null;
		
	tagCloud.foregroundLeft = FG_TAG_CLOUD_LEFT;
	tagCloud.foregroundTop = FG_TAG_CLOUD_TOP;
	tagCloud.hiddenOpacity = HID_TAG_CLOUD_OPACITY;
	
	// La nuvola è nascosta di default, e per il suo toggle vengono salvati questi attributi CSS
	tagCloud.hiddenTop = tagCloud.css('top');
	tagCloud.hiddenLeft = tagCloud.css('left');
	tagCloud.hiddenOpacity = tagCloud.css('opacity');
	
	userMenu.foregroundTop = FG_USER_MENU_TOP;
	
	// Il menu utente è nascosto di default, e per il suo toggle viene salvato quest'attributo CSS
	userMenu.hiddenTop = userMenu.css('top');
	
	//	Carica una nuova pagina ed eventualmente la sostituisce
	function loadNewPage() {

		loadingNewPage = true;

		// Preparazione chiamata AJAX
		var requestData = {};

		if (readerPage > 0)
			requestData.page = readerPage;
		else
			reader.toggleClass('half_opacity');

		if (isTagPage)
			requestData.tag = $("#tag_page_title").text();

		// Chiamata AJAX
		$.ajax({
			url: READER_URL,
			type: "GET",
			data: requestData,
			success: function(new_content) {
				
				// In caso di pagina precedente il contenuto viene inserito alla fine, altrimenti viene sostituito.
				if (readerPage > 0) 
					reader.append(new_content);
				else {	
					reader.html(new_content);			
					if ($('#popular_sources').length)
						$('body').addClass('no_sources');
					else {
						loadTags();
					}
				}

				// Disabilita l'endless scrolling se non vi è ulteriore contenuto
				if (!new_content)
					if (readerPage > 0)
						scrollCheckOff(); 
				
				loadingNewPage = false;
			},
			complete: function() {
				if (readerPage == 0) 
					$('#reader').toggleClass('half_opacity');	
			}
		});

	}
	
	// Riempe la nuvola dei tag e la rende visibile se è stata effettivamente riempita
	// Nel caso in cui l'intersezione delle fonti aggiunte da ogni utente sia costituita solo da
	// feed di webcomics (contenenti un bassissimo numero di parole) è molto probabile che non venga visualizzata
	function loadTags() {
		
		$('#tag_container').addClass('loading');
		
		$.ajax({
			url: TAGS_URL,
			type: 'GET',
			data: { page: tagsPage },
			success: function(new_tags) {
						if (new_tags) {
							$('#tag_container').html(new_tags);

							if (tagsPage == 0) {
								hookTagActions();
								tagCloud.removeClass('hidden');
							}
						}
						else if (tagsPage > 0) {
								// Se non vi sono tag e si è raggiunta la fine, rinizia dalla prima pagina
								tagsPage = 0;
								loadTags();
						}

			},
			complete: function() { $('#tag_container').removeClass('loading'); }
		});

	}
	
	// Carica il form per la modifica delle fonti ed eventualmente aggiorna il news feed
	function loadEditSourcesForm() {

		// Chiamata AJAX
		$.ajax({
			url: EDIT_SOURCES_FORM,
			type: "GET",
			success: function(updatedForm) {
						editSourcesForm.html(updatedForm);
						editSourcesForm.removeClass('loading');

						// Aggiorna il news feed se opportuno
						if (!isTagPage) {
							readerPage = 0;
							loadNewPage();
						}
						
					 }
		});


	}
	
	// Catalizza i click sui tooltip di iscrizione alle fonti
	function hookSubscribeTooltips() {

		reader.on('click', '.subscribe',
			function(event) { 
				
				// Previene l'apertura della pagina del link
				event.preventDefault();			
				
				// Nasconde i tooltip della stessa fonte
				var siblingTooltips = $('.' + $(this).attr('class').replace(/(\s)/g, '.'));
				siblingTooltips.addClass('hidden');

				// Aggiunge la fonte
				editSources($(this).attr('href'), 'add'); 

			}
		);

	}
	
	// Mostra o nasconde la nuvola dei tag a seconda del contenuto di event.data.show
	function toggleTagCloud(event) {

		if (tagCloud.hasClass('hidden'))
			return;

		var show = event.data.show;

		tagCloud.animate({ 
							'opacity': show ? 1 : tagCloud.hiddenOpacity, 
							'left': show ? tagCloud.foregroundLeft : tagCloud.hiddenLeft,  
							'top': show ? tagCloud.foregroundTop : tagCloud.hiddenTop
						});

	}
	
	// Catalizza le azioni sugli elementi dei tag
	function hookTagActions() {

		// Al passaggio del mouse mostra un tooltip per segnalare un tag come stopword
		tagCloud.on('hover', '.tag',
			function(event) {

				$(this).children('.delete').stop(true).animate({opacity: (event.type == 'mouseenter') ? 1 : 0 });			

			}
		);

		// Segnala un tag come stopword
		tagCloud.on('click', '.tag .delete',
			function() {
				var tooltipText = $(this).text();
				var tagElement = $(this).parent('.tag');

				$(this).text('');

				$.ajax({
					url: MARK_STOPWORD_WORKER,
					type: "POST",
					data: { tag: tagElement.text() },
					success: function() {	
						// Nasconde il tag, al termine elimina l'elemento DOM e ricarica la pagina corrente dei tag
						tagElement.animate({opacity: 0, width: 0}, 500, function() { tagElement.remove();  loadTags(); });
					},
					error: function() { 
						// Ripristina il tooltip in caso di errore
						$(this).text(tooltipText); 
					}
				});
	 		}
		);

	}
	
	// Modifica con chiamata AJAX le fonti dell'utente corrente
	function editSources(targetSource, actionToPerform) {

		// Controllo validità argomenti
		if (typeof(targetSource) == 'string' || parseInt(targetSource) == NaN) {

			// Disabilita temporaneamente il form
			editSourcesForm.addClass('loading');

				$.ajax({
					url: EDIT_SOURCES_WORKER,
					type: "POST",
					data: { target: targetSource, action: actionToPerform },
					success: function(sourceId) {

								// La risposta di EDIT_SOURCES_WORKER contiene solo l'id della fonte che si è aggiunta o rimossa
								if (!sourceId) {
									alert("This is not a valid RSS or Atom feed!");
									// Ripristina il form
									editSourcesForm.removeClass('loading');
									return;
								}

								switch (actionToPerform) {
									case 'remove':
										if (isTagPage) {
											// Mostra i tooltip per l'iscrizione alla fonte rimossa
											var subscribeTooltips = $('.' + subscribeTooltipClassPrefix + sourceId);
											subscribeTooltips.removeClass('hidden');
										}

									break;
									case 'add':
										$('body').removeClass('no_sources');
									break;
								}

								// Ricarica il form con le fonti aggiornate
								loadEditSourcesForm();

							}	
				});

		}

	}
	
	// Catalizza gli eventi relativi alla modifica delle fonti
	function hookEditSourcesActions() {

		// Alla pressione di un tasto nell'input di nuova fonte rimuove il suggerimento
		// e nel caso del tasto Invio esegue la chiamata AJAX
		editSourcesForm.on('keydown', newSourceInputS,
			function(event) { 
				$(typeSomethingS).animate({ opacity: 0 }, 500, $(this).hide);
				if (event.which == ENTER_KEY) 
					editSources($(newSourceInputS).val(), 'add'); 
			}
		);	

		// Catalizza i click sui bottoni di aggiunta e rimozione fonti
		editSourcesForm.on('click', '#add_source', function(event) { console.log(event); console.log('f'); editSources($(newSourceInputS).val(), 'add'); });

		editSourcesForm.on('click', '#remove_source', function(event) { console.log(event); console.log('f'); editSources($(victimSourceInputS).val(), 'remove'); });

		$("#main").on('click', typeSomethingS, function() { $(newSourceInputS).focus(); });

		hookSubscribeTooltips();

	}
	
	// Catalizza i click su bottoni e affini che permettono di nascondere gli errori
	function hookErrorDismissers() {

		$("body").on('click', '.error .dismiss',
			function() {		
				parent = $(this).parent(".error");	
				parent.animate({opacity: 0}, 500, function() { parent.remove(); });
			}
		);

	}
	
	// 
	function toggleLoadingOverlay(event) {

		var show = event.data.show;

		if (show)
			loadingOverlay.removeClass('hidden');
		else
			loadingOverlay.addClass('hidden');
	}
	
	// Catalizza lo scrolling e segnala se è avvenuto scrolling verso il basso
	// Viene settato un flag invece di eseguire direttamente scrollCheck() per 
	// evitare un inutile sovraccarico ad ogni movimento della scrollbar
	function hookScrollMarker() {

		$(window).scroll(function (event) { 

			if (!scrolledDown) {
				var y = $(window).scrollTop();

				if (y - lastScrollY > 0) scrolledDown = true;

				lastScrollY = y;
			}
		});		
		
	}
	
	// Carica la pagina successiva se si è raggiunta la fine di una con del contenuto
	function scrollCheck() {

		var contentPresent = ($(".entry").length);

		if (scrolledDown && reachedEndOfPage() && contentPresent && !loadingNewPage) { 

			scrolledDown = false; 
			readerPage++;
			loadNewPage();
		}

	}

	// Controlla periodicamente se è avvenuto scrolling verso il basso
	function scrollCheckOn() {
		if (!scrollCheckHandle)
			scrollCheckHandle = setInterval(scrollCheck, 250 );
	}

	// Disabilita il controllo di cui sopra
	function scrollCheckOff() {
		if (scrollCheckHandle)
			clearInterval(scrollCheckHandle);
		scrollCheckHandle = null;
	}
	
	// Ritorna vero se la y della scrollbar è tra gli ultimi 100 pixel di document
	function reachedEndOfPage() {

		return ($(window).scrollTop() >= $(document).height() - $(window).height() - 100);

	}
	
	// Catalizza gli eventi sui menu
	function hookMenusHover() {

		// All'entrata del mouse mostra la nuvola
		tagCloud.on('mouseenter', { show: true }, toggleTagCloud);

		// Nasconde la nuvola al click sul tooltip relativo
		tagCloud.on('click', '.dismiss', { show: false }, toggleTagCloud);

		// Carica pagina successiva dei tag
		tagCloud.on('click', '#more_tags', function() { tagsPage++; loadTags(); });	


		// Mostra o nasconde il menu utente al passaggio del mouse
		$("#user_menu, #hey").on('hover', 
			function(event) {
				$("#user_menu").stop(true)
							   .animate({ top: (event.type == 'mouseenter') ? userMenu.foregroundTop : userMenu.hiddenTop }, 200);
			} 
		);

	}
	
	// Inizializza le variabili d'ambiente e catalizza le azioni nella pagina
	this.main = function() {
		
		isLoginPage = $('body').hasClass('is_login_page');

		hookErrorDismissers();
		
		if (isLoginPage) {
			$('#login_submit').on('click', { show: true}, toggleLoadingOverlay);	
			$(window).on('pageshow', { show: false }, toggleLoadingOverlay);
		}
		else {
			isTagPage = $('body').hasClass('is_tag_page');
			loadTags();
	 		hookMenusHover();

			hookEditSourcesActions();

			hookScrollMarker();

			scrollCheckOn();

		}
			
	}
}

// Al caricamento completo del DOM esegue il programma/classes
$(document).ready(function() {
	
	reallySimpleSlacking = new ReallySimpleSlacking();
	
	reallySimpleSlacking.main();
	
});