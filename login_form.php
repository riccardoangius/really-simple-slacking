<!-- Form per il login via AJAX -->
<div id="newbie_tooltip">New around here? <br/>Just pick a username and a password, fill in the fields, and get slacking!</div>

<div id="login">

	<form name="login" action="login.php" method="post">
<div>Username<br/><input type="text" name="name" /></div>
<div>Password<br/><input type="password" name="password" /></div>
<div><input type="submit" value="Log in\Sign up" id="login_submit" /></div>
</form>
</div>

<?php	
// Se si sono verificati degli errori in una chiamata precedente a login.php
// visualizza i messaggi corrispondenti

if (isset($_GET['bad_login']))
		echo "<div class='error' id='bad_login'><span class='dismiss'>dismiss</span><h1>OOOPS!</h1><p>Please check your username and password :)</p>";

if (isset($_GET['username']))
	echo "<p>Were you trying to signup? In that case, the name <b>{$_GET['username']}</b> has already been taken!</p>
	 </div>"; 
		
?>