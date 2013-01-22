<?php error_reporting(E_ALL & ~E_NOTICE); header('Content-Type: text/html; charset=utf-8'); ?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Bing Cache Dumper</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<style type="text/css">
	body {
		background-color: #DDDDDD;
		font-family: "Trebuchet MS", Arial, sans-serif;
	}
	h1, h2 {
		font-family: serif;
		text-align: center;
	}
	#page {
		background-color: #FFFFFF;
		border: 2px solid #AAAAAA;
		margin: 0 auto;
		padding: 20px;
		width: 80%;
	}
	span.help {
		cursor: help;
	}
	div.error {
		border: 1px solid #AA0000;
		background-color: #FFAAAA;
		padding: 5px;
	}
	.google {
		font-size: 1.2em;
	}
	.copy {
		text-align: center;
		color: #525252;
	}
	.copy a {
		color: #52728C;
	}
	</style>
</head>
<body>
<div id="page">
<h1>Bing Cache Dumper</h1>
<form action="<?=basename(__FILE__)?>">
<table align="center" cellpadding="4">
	<tr>
		<td>
			<span class="help" title="Сайт, который будем скачивать">Домен</span>
		</td>
		<td>
			<input type="text" name="domain" value="<?=$_GET['domain']?>">
		</td>
	</tr>
		<td>
			<span class="help" title="Количество страниц выдачи, которые будут распарсены. 0 - парсить все.">Количество страниц</span>
		</td>
		<td>
			<input type="text" name="p" value="<?=isset($_GET['p'])?$_GET['p']:2?>">
		</td>
	</tr>
	<tr>
		<td>
			<span class="help" title="Задержка между запросами, секунды">Задержка</span>
		</td>
		<td>
			<input type="text" name="sleep" value="<?=isset($_GET['sleep'])?$_GET['sleep']:3?>"> сек.
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2">
<?php
if(is_dir('./out/') && is_writeable('./out/')) {
?>
			<input type="submit" name="submit" value="Начать">
<?php
} else {
unset($_GET['submit']);
?>
			<div class="error">Папка <?=dirname(__FILE__)?>/out/ должна существовать и быть доступной для записи!</div>
<?php
}
?>
		</td>
	</tr>
</table>
</form>
<?php
set_time_limit(0);
function dbg($var)
{
	echo '<pre>';
	ob_start();
	echo var_dump($var);
	$c = ob_get_contents();
	ob_end_clean();
	echo htmlspecialchars($c);
	echo '</pre>';
}

function say($str)
{
	static $n = 0;
	echo "Msg #$n: $str<a name=\"prgrs-$n\"></a><script>document.location.hash='prgrs-$n';</script><br>";
	flush();
	$n++;
}

if(empty($_GET['domain']))
	unset($_GET['submit']);

if(isset($_GET['submit']))
{
	$domain = $_GET['domain'];
	$sleep = $_GET['sleep'];
	$p = $_GET['p'];
	
	// Start parsing
	$pages = array();
	$parse = true;
	
	$url = 'http://www.bing.com/search?q=site%3A'.$domain.'&go=&form=QBRE&filt=all';
	for($i = 0; $url && ($i < $p || $p == 0); $i++)
	{
		say($url);
		$page = file_get_contents($url);
		
		preg_match_all('#<a href="(http://cc.bingj.com/cache.aspx.*)" onmousedown="return si_T\(\'.*\'\)">#misU', $page, $matches);
		preg_match_all('#<cite>(.*)</cite>#misU', $page, $matches2);
		foreach($matches[1] as $id => $match)
		{
			$pages[] = array(
				'cache'	=> str_replace('&amp;', '&', $match),
				'url'	=> str_replace('&amp;', '&', strip_tags($matches2[1][$id])),
			);
		}
		say(sizeof($matches[1]).' pages found.');
		
		preg_match('#<a\s+class="sb_pagN" href="(/search?.*)" onmousedown="return si_T\(.*\)">#misU', $page, $matches);
		$url = empty($matches[1])?false:"http://www.bing.com".str_replace('&amp;', '&', $matches[1]);
		sleep($sleep);
	}
	
	$dir = 'out/'.$domain;
	@mkdir($dir, 0777);
	chmod($dir, 0777);
	
	say('<b>Starting downloading cache...</b>');
	say(sizeof($pages).' total pages found in cache.');
	$i = 0;
	foreach($pages as $page)
	{
		$part = parse_url($page['url']);
		$container = $dir.'/'.$part['host'].dirname($part['path']);
		$file = $container.'/'.((dirname($part['path']) == $part['path'])?'index':basename($part['path'])).((empty($part['query']))?'':'_'.urlencode($part['query'])).'.html';
		
		is_dir($container) or mkdir($container, 0777, true);
		chmod($container, 0777);
		
		$cache = file_get_contents($page['cache']);
		
		$cache = preg_replace(array('#^<base href=".*<div style="position:relative">#misU', '#</div>$#misU'), '', $cache);
		
		file_put_contents($file, $cache);
		chmod($file, 0777);
		
		say("Page $page[url] saved to $file");
		
		flush();
		sleep($sleep);
		$i++;
	}
	say('<b>Parsing finished!</b>');
}
?>
</div>
<div class="copy">&copy; Alek$, <a href="http://nevkontakte.org.ru">http://nevkontakte.org.ru</a></div>
</body>
</html>
