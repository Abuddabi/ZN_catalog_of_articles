<?php
// $start = microtime(true);
header('Content-type: text/html; charset=UTF-8');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Moscow');

define('ROOT_DIR', dirname(__FILE__));
$path_to_articles = ROOT_DIR . '/articles';
$title = 'Каталог статей';

function get_articles_list($path_to_articles, $page, $per_page = 10)
{	
	//ПАГИНАЦИЯ
	$per_page; //число статей на страницу
	$start = ($page-1)*$per_page;
	// $finish = $start+$per_page-1;

	$articles = [];
	if ($handle = opendir($path_to_articles)) {
	    while (false !== ($entry = readdir($handle))) {
	        if ($entry != '.' && $entry != '..') {
	        	$fopen = fopen($path_to_articles.'/'.$entry, 'r');
	        	if ($fopen) {
	        		$modification_date = filemtime($path_to_articles.'/'.$entry); //Дата изменения файла
	        		$articles[$modification_date]['f_name'] = $entry; //название файла
	        		$articles[$modification_date]['article_name'] = fgets($fopen); //Название статьи - первая строка файла 
	        		fclose($fopen);	        		
	        	} else { /* ошибка чтения файла */ }
	        } else { /* ничего не делать */ }
	    }
	    closedir($handle);
	} else { /* ошибка открытия папки */ }
	$articles_count = count($articles); // Всего статей
	krsort($articles); //массив статей, отсортированный по дате изменения в порядке убывания (недавно измененные - первые)
	$articles = array_slice($articles, $start, $per_page); //оcтавляем только 10 записей

	return ['articles'=>$articles,'articles_count'=>$articles_count,];
}

function get_one_article($path_to_file)
{
	$article = [];
	$fopen = fopen($path_to_file, 'r');
	if ($fopen) {
		$article['creation_date'] = date('Y-m-d', basename($path_to_file, '.txt')); //Дата создания файла
		$article['modification_date'] = date('Y-m-d', filemtime($path_to_file)); //Дата изменения файла
		$article['article_name'] = fgets($fopen); //Название статьи - первая строка файла
		$tmp_text = file_get_contents($path_to_file);
		$article['article_text'] = mb_substr($tmp_text, mb_strlen($article['article_name'])); //Вырезаем 1ю строку
		fclose($fopen);	        		
	} else { /* ошибка чтения файла */ }

	return $article;
}

if (!empty($_GET['show'])) { //вывод 1 статьи
	$f_name = $_GET['show'];
	$article = get_one_article($path_to_articles.'/'.$f_name);
	$title .= ' | '.$article['article_name'];
	$h1 = $article['article_name']; //уникальные H1 у каждой статьи
} elseif (!empty($_GET['add_article'])) { //Добавление новой статьи
	$title .= ' | Добавление новой статьи';
	$h1 = 'Добавление статьи';
} elseif (!empty($_POST['add_article'])) { //Обработка формы (добавление статьи)
	$new_f_name = time().'.txt';
	$article_name =  $_POST['article_name'];
	// Объединяем Название статьи и текст
	$article = $article_name ."\n". file_get_contents($_FILES['file']['tmp_name']);
	$result = file_put_contents($path_to_articles.'/'.$new_f_name, $article);
	if ($result) $msg = 'Статья успешно добавлена'; // РЕАЛИЗОВАТЬ КУКИ ИЛИ СЕССИЮ
	else $msg = 'Ошибка добавления статьи';
	// echo '<pre>'; var_dump($_POST); die();
	header('Location: index.php'); //передресация на Главную, иначе работает некорректно 
} else { //Главная страница
	$h1 = 'Статьи';
	$page = $_GET['page'] ?? 1; // $page = $_GET['page'] если он установлен, иначе = 1
	$per_page = 10; //число статей на страницу
	$articles_arr = get_articles_list($path_to_articles, $page, $per_page);
	$numb_of_pages = ceil($articles_arr['articles_count']/$per_page); //количество страниц
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
			if (!empty($msg)) { ?> 
				<p><?=$msg;?></p> <?php
			}
			if (!empty($_GET['show'])) { // вывод контента статьи
			?>
			<!-- Хлебные крошки -->
			<a href="index.php">Главная</a>
			<span> > </span>
			<a href="#"><?=$article['article_name'];?></a>

			<!-- Текст статьи -->
			<p><?=$article['article_text']?></p>
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
				foreach ($articles_arr['articles'] as $article) { ?>
					<a href="index.php?show=<?=$article['f_name'];?>"><?=$article['article_name'];?></a><br>
				<?php } //ПАГИНАЦИЯ ?>
				<p>Страницы: 
				<?php 
				for ($i=1; $i <= $numb_of_pages; $i++) { ?>
					<a href="index.php?page=<?=$i;?>"><?=$i;?></a>
				<?php } ?>
				</p>
				<div>Всего статей: <?=$articles_arr['articles_count'];?></div>
			<?php } ?>
		</div>
		<div class="right">
			<?php
			if (!empty($_GET['show'])) { ?>
				<div><b>Дата изменения статьи: <?=$article['modification_date'];?></b></div>
				<div><b>Дата создания статьи: <?=$article['creation_date'];?></b></div>
				<div><a href="index.php">Вернуться на Главную</a></div>
			<?php 
			} elseif (!empty($_GET['add_article'])) { ?>
				<div><a href="index.php">Вернуться на Главную</a></div>
			<?php
			} else { ?> 
				<a class="add_article" href="index.php?add_article=1">Добавить статью</a>
			<?php } ?>
		</div>
	</div>
	<?php //echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.'; ?>
</body>
</html>


