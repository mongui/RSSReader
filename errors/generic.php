<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>Error <?= $errnum ?></title>
	<style type="text/css">
	* { font-family: Helvetica; }
	#box {
		margin: 10%;
		border: 1px solid #6A6A6A;
		border-radius: 8px;
		font-size: 22px;
		box-shadow: 0 0 15px #8B8888;
	}
	.errheader {
		color: #878787;
		font-size: 100px;
		font-weight: bold;
		text-transform: uppercase;
	}
	#oops {
		display: inline-block;
		margin-top: 0.2em;
	}

	#errnum {
		text-align: right;
		margin-right: 0.2em;
	}
	img {
		float: left;
		margin: 1em 3em;
	}
	</style>
</head>
<body>
	<div id="box">
		<img src="data:image/png;base64,
		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
		" alt="Mrs. Bitters" />

		<div id="oops" class="errheader">Oops!</div>
		<p><?= $text ?></p>
		
		<div id="errnum" class="errheader"><?= $errnum ?></div>
	</div>
</body>
</html>