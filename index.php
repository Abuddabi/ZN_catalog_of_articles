<?php
header('Content-type: text/html; charset=UTF-8');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Moscow');

define('ROOT_DIR', dirname(__FILE__));
$path_to_articles = ROOT_DIR . '/articles';
$title = 'Каталог статей';

function get_articles_list($path_to_articles)
{
	$articles = [];
	if ($handle = opendir($path_to_articles)) {
	    while (false !== ($entry = readdir($handle))) {
	        if ($entry != '.' && $entry != '..') {
	        	$fopen = fopen($path_to_articles.'/'.$entry, 'r');
	        	if ($fopen) {
	        		//создаем массив, где ключ название файла, а значение его 1я строка
	        		$articles[$entry] = fgets($fopen);
	        		fclose($fopen);	        		
	        	} else { /* ошибка чтения файла */ }
	        } else { /* ничего не делать */ }
	    }
	    closedir($handle);
	} else { /* ошибка открытия папки */ }

	return $articles;
}

if (!empty($_GET['show'])) { //вывод 1 статьи
	$f_name = $_GET['show'];
	$article_name = get_articles_list($path_to_articles)[$f_name];
	$title .= ' | '.$article_name;
	$h1 = $article_name; //уникальные H1 у каждой статьи
	$article_text = file_get_contents($path_to_articles.'/'.$f_name);
	$first_line_pos = mb_strlen($article_name);
	$article_text = mb_substr($article_text, $first_line_pos); //вырезаем первую строку
} elseif (!empty($_GET['add_article'])) { //Добавление новой статьи
	$title .= ' | Добавление новой статьи';
	$h1 = 'Добавление статьи';
} elseif (!empty($_POST['add_article'])) { //Обработка формы (добавление статьи)
	move_uploaded_file($_FILES['file']['tmp_name'], $path_to_articles.'/'.(count(get_articles_list($path_to_articles))+1).'.txt');
	echo '<pre>';
	var_dump($_POST['article_name']); 
	var_dump($_FILES['file']['tmp_name']);
	die();
} else { //
	$h1 = 'Статьи';
	$articles = get_articles_list($path_to_articles);
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?=$title?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
	<div id="header">
		<h1><?=$h1?></h1>
	</div>
	<div id="main">
		<div class="middle">
			<?php 
			if (!empty($_GET['show'])) { // вывод контента статьи
			?>
			<!-- Хлебные крошки -->
			<a href="index.php">Главная</a>
			<span> > </span>
			<a href="#"><?=$article_name?></a>

			<!-- Текст статьи -->
			<p><?=$article_text?></p>
			<?php
			} elseif (!empty($_GET['add_article'])) { // Добавление новой статьи
			?>
				<form enctype="multipart/form-data" method="POST" action="index.php">
					<label>
						Название статьи:<br>
						<input name="article_name" type="text">
					</label><br>
					<label>
						Файл<br>
						<input name="file" type="file" accept="text/plain"> <!--принимает только txt файлы-->
					</label><br>
					<button name="add_article" type="submit" value="true">Добавить</button>
				</form>
			<?php
			} else { // вывод Главной страницы
				foreach ($articles as $f_name => $title) { ?>
					<a href="index.php?show=<?=$f_name?>"><?=$title?></a><br>
				<?php } ?>
				<div>Всего статей: <?=count($articles);?></div>
			<?php } ?>
		</div>
		<div class="right">
			<?php
			if (!empty($_GET['show'])) { ?>
				<div>Дата изменения статьи: </div>
				<div>Дата создания статьи: </div>
				<div><a href="index.php">Вернуться на Главную</a></div>
			<?php 
			} else { ?> 
				<a class="add_article" href="index.php?add_article=1">Добавить статью</a>
			<?php } ?>
		</div>
	</div>
</body>
</html>


