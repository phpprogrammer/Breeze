<!doctype html>
<html>
<head>
	<title>403 Access Denied</title>
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
h2 { font-size:24px; }
p { font-size:20px; }
p, h1, h2 { margin:25px 0; }
p#url { color:#E13300; font-size:20px; font-weight:bold; font-style:italic; }
</style>

<body>
	<div id="container">
		<section>
            <h1>HTTP 403 Error</h1>
			<h2>Access Denied</h2>
			<p id="url"><?='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?></p>
			<p>We're sorry, but you aren't allowed to view this page!</p>
		</section>
	</div>
</body>
</html>