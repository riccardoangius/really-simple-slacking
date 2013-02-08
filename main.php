<!-- Corpo principale della pagina contenente news feed, nuvola e form per la modifica delle fonti -->
<div id='hey'>Hey <?php echo $user['name']; ?></div>
<div id='user_menu'><br/><br/>
<a href='logout.php' id='logout'>Log out</a>
</div>

<div id='edit_sources'>
	<? include "edit_sources_form.php"; ?>
</div><!-- #edit_sources --> 		

<div id='page_title'>
<?php
if (IS_TAG_PAGE) {
	echo 	"#",
			"<span id='tag_page_title'>",
				$_GET['tag'],
			"</span>";
}
else 
	echo "Slack away!";
?>
</div><!-- #page_title -->

<div id='reader'>
	<?php include "reader.php"; ?>
</div><!-- #reader -->
