<!doctype html>
<html>
<head>
	<title>400 Bad Request</title>
    <script type="text/javascript">
        window.onload = function() {
            var span = document.getElementById('timer'),
            oldValue = span.innerHTML,
            time = 0,
            interval = setInterval(function() {
                time = parseInt(span.innerHTML) - 1;
                if (time < 0) {
                    time = 0;
                }
                span.innerHTML = time;
                if (span.innerHTML <= 0) {
                    clearInterval(interval);
                    document.getElementsByTagName('p')[1].innerHTML = 'Time is out! You may have to reload the page.';
                }
            }, 1000);
        }
    </script>
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
			<h1>HTTP 400 Error</h1>
			<h2>Bad or illegal request</h2>
			<p id="url"><?='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?></p>
			<p>Please wait <span id="timer"><?=$time?></span> seconds and repeat request.</p>
		</section>
	</div>
</body>
</html>