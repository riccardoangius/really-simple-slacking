<?php
require_once "rss_functions.php";

// Controlla se l'utente è loggato ed eventualmente riempe $user con i dati di questi
$user = user_logged_in(true);

// Stringa per le classi usate da Javascript e dal foglio di stile
$body_classes = '';

if (empty($user['sources']))
	$body_classes .= 'no_sources ';
	
if (!$user)
	$body_classes .= 'is_login_page ';
else if (IS_TAG_PAGE) 
	$body_classes .= 'is_tag_page ';
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns='http://www.w3.org/1999/xhtml'>

<head>
<title>Really Simple Slacking</title>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<meta property="og:title" content="Really Simple Slacking, your personal newsfeed" /> 
<meta property="og:description" content="Sign up and start slacking!" />
<meta property="og:image" content="<?php echo URL; ?>images/mini-logo.png" />
<base href='<?php echo URL; ?>'>
<link rel='stylesheet' type='text/css' href='css/reset.css' />
<link rel='stylesheet' type='text/css' href='css/style.css' />
<script type='text/javascript' src='js/jquery.min.js'></script>         
</head>

<body class="<?php echo $body_classes; ?>">
	<div id='header'>
		<a href=""></a>	
	</div>

<?php if ($user) include 'tag_cloud.html'; ?>

<div id='main'>
			
<?php
		if ($user) 
			include 'main.php';
		else
			include "login_form.php";
?>
</div><!-- #main -->

<div id='loading_overlay' class='hidden'></div>

<p id="w3c_validator">
	<a href="http://validator.w3.org/check?uri=referer"><img src="images/w3c.png" alt="Valid XHTML 1.0 Transitional" /></a>
</p>

<script type="text/javascript" src="js/ReallySimpleSlacking.class.js"></script>   
</body>
</html>