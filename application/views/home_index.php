<html>
<head>
	<?=$headers?>
</head>

<body>
	<form method="post" action="/login">
		<input type="text" name="username" /> <br/>
		<input type="password" name="password" /> <br/>
		<input type="submit" />
	</form>
	<button><a href="/logout">wyloguj</a></button><br/>
	<button><a href="/test">testowo</a></button><br/>
	<?=\system\core\Session::debug() ?>
	<?=debug($_COOKIE) ?>
	<?=$timer?>
</body>
</html>