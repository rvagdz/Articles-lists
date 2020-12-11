<?php
date_default_timezone_set('PRC');
ini_set('memory_limit', '-1');
header('Content-type: text/html; charset=utf-8');
$_SERVER['params'] = explode('/', basename($_SERVER['SCRIPT_FILENAME']) . (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : NULL));
$_SERVER['self'] = isset($_SERVER['params'][0]) ? $_SERVER['params'][0] : basename($_SERVER['SCRIPT_FILENAME']);
$_SERVER['mod'] = isset($_SERVER['params'][1]) ? strtolower($_SERVER['params'][1]) : NULL;
$_SERVER['home'] = '//'. $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
$_SERVER['canonical'] = $_SERVER['home'] . $_SERVER['self'] . (empty($_SERVER['mod']) ? NULL : "/{$_SERVER['mod']}/");
$_SERVER['search'] = isset($_GET['s']) ? strip_tags($_GET['s']) : NULL;
$_SERVER['db'] = new PDO('sqlite:'. sha1('1cc19b8ad37da28e78a62e8e4bc7ccb8@​‍﻿﻿​‍﻿﻿‌‍‌﻿‌‍‍‌‌﻿‌​​‍﻿‍‌‍‍‌‌‍﻿﻿​‍﻿﻿​‍‌‍​‍‌‍'. $_SERVER['HTTP_HOST']) .'.db');
$_SERVER['db']->exec("CREATE TABLE IF NOT EXISTS articles(id INTEGER NOT NULL PRIMARY KEY, hash TEXT NOT NULL, date TEXT NOT NULL, auther TEXT NOT NULL, title TEXT NOT NULL, contents TEXT NOT NULL);");

$_SERVER['html'] = '';
$_SERVER['title'] = 'Articles lists - Focus on your creation!';
switch($_SERVER['mod']){
	case 'sitemap':
		header('Content-type:text/xml');
		$_SERVER['cache'] = $_SERVER['db']->query("SELECT * FROM articles ORDER BY id DESC LIMIT 0,99999;")->fetchAll(PDO::FETCH_ASSOC);
		foreach($_SERVER['cache'] as $_SERVER['v'])	$_SERVER['html'] .= "<url><loc>http:{$_SERVER['home']}{$_SERVER['self']}/article/{$_SERVER['v']['id']}/</loc><lastmod>". date('Y-m-d\TH:i:s+00:00', $_SERVER['v']['date']) ."</lastmod></url>\r\n";
		echo "<?xml version='1.0' encoding='UTF-8'?>\r\n<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'\r\nxmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\r\nxsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/0.9
 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'>\r\n<url><loc>http:{$_SERVER['home']}{$_SERVER['self']}</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\r\n<url><loc>http:{$_SERVER['home']}{$_SERVER['self']}/sitemap/</loc><changefreq>daily</changefreq><priority>1.0</priority></url>\r\n{$_SERVER['html']}</urlset>";
		exit;
	case 'compose':
		$_SERVER['title'] = 'Compose article';
		header('Expires:-1');
		header('Cache-Control:no_cache');
		header('Pragma:no-cache');
		if($_POST){
			$_POST['auther'] = empty($_POST['auther']) ? 'Guest' : $_POST['auther'];
			$_POST['title'] = empty($_POST['title']) ? NULL : htmlspecialchars($_POST['title']);
			$_POST['contents'] = empty($_POST['contents']) ? NULL : str_replace(PHP_EOL, '<br />', strip_tags($_POST['contents'], '<a><img>'));
			$_SERVER['hash'] = sha1('1cc19b8ad37da28e78a62e8e4bc7ccb8@'. $_POST['title'] . $_POST['contents']);
			if(empty($_POST['title']))$_SERVER['html'] = "<p class='err'>Missing title!</p>";
			if(empty($_POST['contents']))$_SERVER['html'] .= "<p class='err'>Missing content!</p>";
			if(!empty($_POST['title']) && !empty($_POST['contents'])){
				$_SERVER['cache'] = $_SERVER['db']->query("SELECT * FROM articles WHERE hash='{$_SERVER['hash']}';")->fetch(PDO::FETCH_ASSOC);
				if(!$_SERVER['cache']){
					$_SERVER['db']->exec("INSERT INTO articles(hash, date, auther, title, contents) VALUES ('{$_SERVER['hash']}', '". time() ."', '{$_POST['auther']}', '{$_POST['title']}', '{$_POST['contents']}​‍﻿﻿​‍﻿﻿‌‍‌﻿‌‍‍‌‌﻿‌​​‍﻿‍‌‍‍‌‌‍﻿﻿​‍﻿﻿​‍‌‍​‍‌‍');");
					header("location:{$_SERVER['home']}{$_SERVER['self']}/compose/") && exit;
				}else{
					$_SERVER['html'] .= "<p class='msg'>Repeated writing is invalid, Click <a target='_blank' href='{$_SERVER['home']}{$_SERVER['self']}/article/{$_SERVER['cache']['id']}/'>here</a> to view.</p>";
					$_POST['contents'] = '';
					$_POST['title'] = '';
				}
			}
		}
		$_POST['auther'] = empty($_POST['auther']) ? NULL : $_POST['auther'];
		$_POST['title'] = empty($_POST['title']) ? NULL : $_POST['title'];
		$_POST['contents'] = empty($_POST['contents']) ? NULL : $_POST['contents'];
		$_SERVER['html'] .= "<form action='' method='post' target='_self' class='compose'><input type='text' name='title' class='w100' autocomplete='off' x-webkit-speech='' spellcheck='false' onMouseOver='this.focus();' placeholder='Article title' value='{$_POST['title']}' /><textarea name='contents' autocomplete='off' x-webkit-speech='' spellcheck='false' onMouseOver='this.focus();' placeholder='Article content' >{$_POST['contents']}</textarea><p class='tip'>The allowed HTML tags are &lt;a href=\"URL\"&gt;TEXT&lt;/a&gt; and &lt;img src=\"URL\" /&gt;</p><input type='text' name='auther' autocomplete='off' x-webkit-speech='' spellcheck='false' onMouseOver='this.focus();' placeholder='Author of the article' value='{$_POST['auther']}' />&nbsp;<input type='submit' /></form>";
		break;
	case 'article':
		if(empty($_SERVER['params'][2])){
			header("location:{$_SERVER['home']}{$_SERVER['self']}") && exit;
		}elseif(!preg_match('/^\d+$/i', $_SERVER['params'][2])){
			$_SERVER['params'][2] = preg_replace('/[^\d]+/i','', $_SERVER['params'][2]);
			header("location:{$_SERVER['home']}{$_SERVER['self']}/{$_SERVER['mod']}/{$_SERVER['params'][2]}/") && exit;
		}else{
			$_SERVER['cache'] = $_SERVER['db']->query("SELECT * FROM articles WHERE id='{$_SERVER['params'][2]}';")->fetch(PDO::FETCH_ASSOC);
			$_SERVER['prev'] = $_SERVER['db']->query("SELECT * FROM articles WHERE id = (SELECT max(id) FROM articles WHERE id < {$_SERVER['params'][2]});")->fetch(PDO::FETCH_ASSOC);
			$_SERVER['next'] = $_SERVER['db']->query("SELECT * FROM articles WHERE id = (SELECT min(id) FROM articles WHERE id > ". ($_SERVER['params'][2] == 0 ? 1 : $_SERVER['params'][2]) .")")->fetch(PDO::FETCH_ASSOC);
			if($_SERVER['cache']){
				$_SERVER['title'] = $_SERVER['cache']['title'];
				$_SERVER['canonical'] .= "{$_SERVER['params'][2]}/";
				$_SERVER['html'] = "<div class='auther'>By {$_SERVER['cache']['auther']}</div>{$_SERVER['cache']['contents']}<p class='posted'>Posted [at] ". date('Y-m-d H:i:s[A] l', $_SERVER['cache']['date']) ."</p><ul class='nav'><li class='l3'>". ($_SERVER['prev'] ? "&laquo;&nbsp;<a href='{$_SERVER['home']}{$_SERVER['self']}/{$_SERVER['mod']}/{$_SERVER['prev']['id']}/'>{$_SERVER['prev']['title']}</a>" : '&nbsp;') ."</li><li class='r3'>". ($_SERVER['next'] ? "<a href='{$_SERVER['home']}{$_SERVER['self']}/{$_SERVER['mod']}/{$_SERVER['next']['id']}/'>{$_SERVER['next']['title']}</a>&nbsp;&raquo;" : '&nbsp;') ."</li><div class='clear'></div></ul>";
			}else{
				header('HTTP/1.1 404 Not Found') & header('Status: 404 Not Found');
				$_SERVER['html'] = "<div class='err'>[404] The article does not exist or has been deleted!</div>";
			}
		}
		break;
	case 'application.appcache':
		header('content-type:text/cache-manifest;charset=utf8');
		echo"CACHE MANIFEST\r\n";
		exit;
	default:
		$_SERVER['sugguest'] = isset($_GET['sugguest']) ? strip_tags($_GET['sugguest']) : NULL;
		if($_SERVER['sugguest']){
			$_SERVER['HTTP_REFERER'] = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) : NULL;
			$_GET['cb'] = isset($_GET['cb']) ? $_GET['cb'] : NULL;
			header('Access-Control-Allow-Origin: *');
			header('Access-Control-Allow-Methods: GET');
			header('Access-Control-Allow-Headers:x-requested-with,content-type');
			header('content-type:text/javascript;charset=utf8');
			$_SERVER['sug'] = '"<div style=\'text-align:center;color:#f00\'>Illegal request.</div>"';
			//if(parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST) ==  $_SERVER['HTTP_REFERER']){
				$_SERVER['cache'] = $_SERVER['db']->query("SELECT * FROM articles WHERE title LIKE '%". $_SERVER['sugguest'] ."%' or contents LIKE '%". $_SERVER['sugguest'] ."%' ORDER BY id DESC LIMIT 0,10;")->fetchAll(PDO::FETCH_ASSOC);
				$_SERVER['sug'] = '';
				foreach($_SERVER['cache'] as $_SERVER['v']) $_SERVER['sug'] .= '"'. $_SERVER['v']['title'] .'",';
			//}
			echo preg_match('/^BaiduSuggestion\.res\.__\d+$/i', $_GET['cb']) ? $_GET['cb'] .'({s:['. rtrim($_SERVER['sug'], ',') .']});' : '';
			exit;
		}
		$_SERVER['params'][2] = isset($_SERVER['params'][2]) ? $_SERVER['params'][2] : '';
		if($_SERVER['params'][2] == '1' || (string)$_SERVER['params'][2] == '0') header("location:{$_SERVER['home']}{$_SERVER['self']}") && exit;
		if(!empty($_SERVER['params'][2]) && !preg_match('/^\d+$/i', $_SERVER['params'][2])){
			$_SERVER['params'][2] = preg_replace('/[^\d]+/i', '', $_SERVER['params'][2]);
			header("location:{$_SERVER['home']}{$_SERVER['self']}/{$_SERVER['mod']}/{$_SERVER['params'][2]}/") && exit;
		}
		$_SERVER['params'][2] = (int)$_SERVER['params'][2];
		$_SERVER['cache'] = $_SERVER['search'] ? $_SERVER['db']->query("SELECT * FROM articles WHERE title LIKE '%". $_SERVER['search'] ."%' or contents LIKE '%". $_SERVER['search'] ."%' ORDER BY id DESC LIMIT ". ($_SERVER['params'][2] > 0 ? ($_SERVER['params'][2] - 1) * 20 : '0') .",20;")->fetchAll(PDO::FETCH_ASSOC) : $_SERVER['db']->query("SELECT * FROM articles ORDER BY id DESC LIMIT ". ($_SERVER['params'][2] > 0 ? ($_SERVER['params'][2] - 1) * 20 : '0') .",20;")->fetchAll(PDO::FETCH_ASSOC);
		$_SERVER['total'] = $_SERVER['search'] ? $_SERVER['db']->query("SELECT COUNT(*) AS total FROM articles WHERE title like '%". $_SERVER['search'] ."%' or contents like '%". $_SERVER['search'] ."%'")->fetchAll(PDO::FETCH_ASSOC) : $_SERVER['db']->query("SELECT COUNT(*) AS total FROM articles;")->fetchAll(PDO::FETCH_ASSOC);
		$_SERVER['title'] = $_SERVER['search'] ? $_SERVER['search'] : $_SERVER['title'];
		$_SERVER['canonical'] .= $_SERVER['search'] ? '?s='.urlencode($_SERVER['search']) : '';
		$_SERVER['total'] = $_SERVER['total'][0]['total'];
		if($_SERVER['search'] && $_SERVER['cache'] && count($_SERVER['cache']) == 1) header("location:{$_SERVER['home']}{$_SERVER['self']}/article/{$_SERVER['cache'][0]['id']}/") && exit;
		if(count($_SERVER['cache']) > 0){
			foreach($_SERVER['cache'] as $_SERVER['v']) $_SERVER['html'] .= "<p class='item' title='{$_SERVER['v']['auther']} - {$_SERVER['v']['title']}'>". date('d/m', $_SERVER['v']['date']) ." <a href='{$_SERVER['home']}{$_SERVER['self']}/article/{$_SERVER['v']['id']}/'>{$_SERVER['v']['title']}</a></p>";
			if(($_SERVER['total'] = (int)$_SERVER['total']) > 20){
				$_SERVER['prev'] = $_SERVER['params'][2] - ($_SERVER['params'][2] == 0 ? 2 : 1);
				$_SERVER['next'] = $_SERVER['params'][2] + ($_SERVER['params'][2] == 0 ? 2 : 1);
				$_SERVER['canonical'] .= $_SERVER['params'][2] > 0  ? "{$_SERVER['params'][2]}/" : '';
				$_SERVER['html'] .= "<p class='pagebar'>". ( $_SERVER['params'][2] > 1 ? ($_SERVER['params'][2] == 2 ? "<a href='{$_SERVER['home']}{$_SERVER['self']}'>Previous</a> " : "<a href='{$_SERVER['home']}{$_SERVER['self']}/page/{$_SERVER['prev']}/'>Previous</a> ") : 'Previous ' ) ."[ ". ($_SERVER['params'][2] < 2 ? 1 : $_SERVER['params'][2]) ." / ". ceil($_SERVER['total'] / 20) ." ]". (ceil($_SERVER['total'] / 20) > $_SERVER['params'][2] ? " <a href='{$_SERVER['home']}{$_SERVER['self']}/page/{$_SERVER['next']}/'>Next</a>": ' Next') ."</p>";
			}
		}else{
			$_SERVER['html'] = "<div class='err'>[404] There are no more articles!</div>";
		}
}?><!DOCTYPE html>
<html dir="ltr" lang="en" manifest="<?php echo $_SERVER['home'] . $_SERVER['self'];?>/application.appcache">
	<head>
		<title><?php echo $_SERVER['title'];?></title>
		<meta name="theme-color" content="#fff" />
		<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="generator" content="Powered by Articles lists - Focus on your creation!">
		<meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1" />
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
		<link rel="preload" as="script" href="//static.mipcdn.com/c/s/a221588ebe27afc9.pp.ua/jquery.1.7.1.js" />
		<link rel="preload" as="script" href="//static.mipcdn.com/c/s/opensug.github.io/js/opensug.js" />
		<link rel="canonical" href="<?php echo $_SERVER['canonical'];?>" />
		<meta name="google-site-verification" content="pUfJ0ETzNF_-HHPQARajlbubz7dtRnLVwHFwMy4n9MA" />
		<!--A simple and lightweight article writing program based on PHP+SQLite3, you only need to focus on your creation.(https://github.com/rvagdz/Articles-lists)-->
		<style type="text/css">body{margin:0;padding:0}a{text-decoration:none;color:#444}a:hover{text-decoration:underline;color:#000}:focus,a:focus{outline:none}h2{margin-bottom:5px;padding-bottom:5px;font-weight:400;text-align:center;border-bottom:1px dashed #eee}form.compose{margin:1em 0}input{padding:5px;vertical-align:middle;border:1px solid #d8d8d8}input,textarea{outline:none;box-sizing:border-box;-webkit-box-sizing:border-box;-moz-box-sizing:border-box}textarea{margin:.5em 0;resize:none;width:100%;height:280px}input.btn{margin-left:-1px;background-color:#f8f8f8;color:#555;*height:28px;cursor:pointer}ul{margin:0 0 5px;padding-top:5px;padding-left:0}ul.nav{margin-top:5px;border-top:1px dashed #eee}li{list-style-type:none;float:left;white-space:nowrap;overflow:hidden}li.l1{width:20%;text-align:center;line-height:25px;border-radius:2px;border:1px solid #bbb;cursor:pointer}li.r1{float:right;width:77%;text-align:right}li.l3{text-align:left}li.l3,li.r3{text-overflow:ellipsis;width:50%}li.r3{text-align:right}p{margin:0;padding:0}p.item{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#444}p.msg{padding:5px;color:#555;background-color:#c6ecf9}p.tip{padding-bottom:5px;color:#555}p.err{padding:5px;color:#555;background-color:#f9d7d4}p.posted{margin-top:5px;text-align:right;color:#aaa}p.pagebar{margin-top:.2em;padding-bottom:.5em;border-bottom:1px dashed #eee;color:#444}div.wrapper{margin-right:auto;margin-left:auto;width:100%;max-width:470px;*width:470px}div.wrapper img{display:block;margin:0 auto;border:none;*width:454px;width:auto;height:auto;max-width:100%;max-height:100%}div.inner{margin:8px;font-size:13px;overflow:hidden}div.err{text-align:center;color:red}div.auther{margin-bottom:5px;text-align:center;color:#aaa}input.w100{width:100%}div.clear{clear:both}div.foot{margin-top:8px;text-align:center}</style>
	</head>

	<body>
		<div class="wrapper">
			<div class="inner">
				<form method="get" action="<?php echo $_SERVER['home'] . $_SERVER['self'];?>" target="_self">
				<ul>
					<a href="<?php echo $_SERVER['home'] . $_SERVER['self'];?>"><li class="l1">HOME</li></a><li class="r1"><input type='text' name='s' id='s' autocomplete='off' x-webkit-speech='' spellcheck='false' onMouseOver='this.focus();' placeholder='keyword' value='<?php echo $_SERVER['search'];?>' /><input type='submit' value='Search' class='btn' /></li><div class='clear'></div>
				</ul>
				</form>
				<h2><?php echo $_SERVER['title'];?></h2>
				<?php echo $_SERVER['html'];?>
				<div class="foot"><a href="<?php echo $_SERVER['home'] . $_SERVER['self'];?>/compose/">compose</a>, <a href="<?php echo $_SERVER['home'] . $_SERVER['self'];?>/sitemap/">sitemap</a>, <a target="_blank" href="//nic.ua/en/signup/ntniixhv">pp.ua</a></div>
			</div>
			<div class='clear'></div>
		</div>
		<script type="text/javascript" referrerpolicy="no-referrer" src="//static.mipcdn.com/c/s/a221588ebe27afc9.pp.ua/jquery.1.7.1.js"></script>
		<script type="text/javascript" referrerpolicy="no-referrer" src="//static.mipcdn.com/c/s/opensug.github.io/js/opensug.js"></script>
		<script async src="//www.googletagmanager.com/gtag/js?id=G-QSQ1G04JZN"></script>
		<script type="text/javascript">(function(){typeof(arguments[0].bind)=='function'&&arguments[0].bind('s',{'XOffset':'-1','fontColor':'#444','fontColorHI':'#666','fontSize':'12px','borderColor':'#d8d8d8','bgcolorHI':'#f8f8f8','sugSubmit':true,'source':'<?php echo $_SERVER['home'] . $_SERVER['self'];?>?sugguest='});}(this.google||{}));window.dataLayer=window.dataLayer||[]; function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-QSQ1G04JZN');</script>
	</body>
</html>