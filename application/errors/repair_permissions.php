<!doctype html>
<html>
<head>
	<title>Incorrect Permissions</title>
</head>

<style type="text/css">
::selection{ background-color: #E13300; color: white; }
::moz-selection{ background-color: #E13300; color: white; }
::webkit-selection{ background-color: #E13300; color: white; }
* { text-align:center; }
div#container {
	margin:60px auto;
}
section { 
	display:inline-block;
    width:33%;
	margin:0 auto 40px; 
	padding:30px 100px;
	background:#ededed; 
	border:1px solid #ccc; 
	border-radius:7px; 
	box-shadow:inset 0 1px 0 white,inset 0 -1px 0 white,inset 1px 0 0 white,inset -1px 0 0 white, 0 0 10px rgba(0,0,0,0.2); 
}
h1 { font-size:28px; }
h2 { font-size:20px; margin-bottom:35px !important; }
p, h1, h2 { margin:25px 0; }
p.path { color:#E13300; font-size:16px; font-weight:bold; font-style:italic; }
</style>

<body>
	<div id="container">
		<section>
            <h1>You need to fix permissions!</h1>
			<h2>These paths should be "777" chmod permission.</h2>
			<?php foreach ($paths as $key => $path): ?>
                <p class="path"><?=$path?></p>
            <?php endforeach; ?>
		</section>
	</div>
</body>
</html>