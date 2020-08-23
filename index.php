<?php
// $start = microtime(true);
session_start();
// echo '<pre>'; var_dump($_POST); var_dump($_FILES); die();
header('Content-type: text/html; charset=UTF-8');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Moscow');

define('ROOT_DIR', dirname(__FILE__));
$path_to_articles = ROOT_DIR . '/texts';
$title = 'Каталог статей';

function out($arg)
{
	echo '<pre>'; print_r($arg); die();

}

function get_articles_list($path_to_articles, $page, $per_page = 10) //выдает список статей на Главную
{	
	//ПАГИНАЦИЯ
	$per_page; //число статей на страницу
	$start = ($page-1)*$per_page;
	// $finish = $start+$per_page-1;
	$articles = [];
	if ($dir = opendir($path_to_articles)) {
	    while (false !== ($entry = readdir($dir))) {
	        if ($entry != '.' && $entry != '..') {
	        	$fopen = fopen($path_to_articles.'/'.$entry, 'r');
	        	if ($fopen) {
	        		//ключ элемента - название файла
	        		$articles[$entry]['modification_date'] = filemtime($path_to_articles.'/'.$entry); //Дата изменения файла
	        		$articles[$entry]['article_name'] = fgets($fopen); //Название статьи - первая строка файла 
	        		$articles[$entry]['creation_date'] = fgets($fopen); //Дата создания файла
	        		fclose($fopen);
	        	} else { /* ошибка чтения файла */ }
	        } else { /* ничего не делать */ }
	    }
	    closedir($dir);
	} else { /* ошибка открытия папки */ }
	array_multisort(array_column($articles, 'modification_date'), SORT_DESC, $articles); //массив статей, отсортированный по дате изменения в порядке убывания (недавно измененные - первые)
	$articles = array_slice($articles, $start, $per_page); //оcтавляем только 10 записей

	return $articles;
}

function get_files_count($path_to_dir) //считает количество файлов в папке
{
	$dir = opendir($path_to_dir);
	$count = 0;
	while (false !== ($file = readdir($dir))){
	    if($file == '.' || $file == '..' || is_dir($path_to_dir.'/'. $file)){
	        continue;
	    } else $count++;
	}
	closedir($dir);

	return $count;
}

function get_one_article($path_to_file) //выдает 1 статью
{
	$article = [];
	$fopen = fopen($path_to_file, 'r');
	if ($fopen) {
		$article['article_name'] = fgets($fopen); //Название статьи
		$article['creation_date'] = fgets($fopen); //Дата создания файла
		$article['article_text'] = fgets($fopen); //Текст статьи
		// $tmp_text = file_get_contents($path_to_file);
		// $article['article_text'] = mb_substr($tmp_text, mb_strlen($article['article_name'])); //Вырезаем 1ю строку
		fclose($fopen);	        		
	} else { /* ошибка чтения файла */ }
	$article['modification_date'] = date('Y-m-d', filemtime($path_to_file)); //Дата изменения файла

	return $article;
}

function save_article($path_to_articles) //сохраняет статью
{
	if (empty($_POST['article_name'])) {
	 	$_SESSION['msg'] = ['error'=>1,'txt'=>'Заполните Название статьи'];
		header('Location: index.php?add_article=1');
	} elseif (empty($_FILES['file']['name']) && empty($_POST['article_text'])) {
		$_SESSION['msg'] = ['error'=>1,'txt'=>'Загрузите файл ИЛИ заполните текст статьи'];
		header('Location: index.php?add_article=1');
	} elseif (!empty($_FILES['file']['name']) && !empty($_POST['article_text'])) { // Если загрузили И файл И текст
		$_SESSION['msg'] = ['error'=>1,'txt'=>'Загрузите файл ИЛИ заполните текст статьи.'];
		header('Location: index.php?add_article=1');
	} elseif (empty($_POST['article_text']) && $_FILES['file']['type'] != 'text/plain') {
		$_SESSION['msg'] = ['error'=>1,'txt'=>'Неверный формат файла. Загрузите txt файл'];
		header('Location: index.php?add_article=1');
	} else {
		if (!empty($_FILES['file']['name']) && empty($_POST['article_text'])) { //загрузили файл
			$file = $_FILES['file']['tmp_name'];
			$text = str_replace("\n",'<br>',file_get_contents($file)); //Заменяем переводы строк на <br>
		} elseif (empty($_FILES['file']['name']) && !empty($_POST['article_text'])) { //Заполнили текст
			$text = str_replace("\n",'<br>',$_POST['article_text']); //Заменяем переводы строк на <br>
		} else { /* Еще варианты? */ }
		$article_name =  $_POST['article_name']; // Название статьи
		$new_f_name = (get_files_count($path_to_articles)+1).'.txt'; //задает новое имя
		$creation_date = date('Y-m-d', time()); //Дата создания файла
		// Объединяем Название статьи, дату создания файла  и текст
		$article = $article_name."\n".$creation_date."\n".$text;
		$result = file_put_contents($path_to_articles.'/'.$new_f_name, $article);
		if ($result) $_SESSION['msg'] = ['success'=>1, 'txt'=>'Статья успешно добавлена'];
		else $_SESSION['msg'] = ['error'=>1, 'txt'=>'Ошибка добавления статьи'];
		header('Location: index.php');
	}
}

function delete_article($path_to_articles) //удаляет статью
{
	$f_name = $_GET['delete']; //Имя файла
	$result = unlink($path_to_articles.'/'.$f_name);
	
	return $result;
}

function if_msg()
{
	if (!empty($_SESSION['msg'])) { 
		if (!empty($_SESSION['msg']['success'])) $class=' class="success" ';
		elseif (!empty($_SESSION['msg']['error'])) $class=' class="error" ';
		else $class=''; ?> 
		<p <?=$class;?> ><?=$_SESSION['msg']['txt'];?></p> <?php
		unset($_SESSION['msg']);
	}
}

//РОУТИНГ
$show = $add = $add_form = $delete = $main = false;
if (!empty($_GET['show'])) $show = true;
elseif (!empty($_GET['add_article'])) $add = true;
elseif (!empty($_POST['add_article'])) $add_form = true;
elseif (!empty($_GET['delete'])) $delete = true;
else $main = true;

if ($show) { //вывод 1 статьи
	$f_name = $_GET['show'];
	$article = get_one_article($path_to_articles.'/'.$f_name);
	$title .= ' | '.$article['article_name'];
	$h1 = $article['article_name']; //уникальные H1 у каждой статьи
} elseif ($add) { //Добавление новой статьи
	$title .= ' | Добавление новой статьи';
	$h1 = 'Добавление статьи';
} elseif ($add_form) { //Обработка формы (добавление статьи)
	save_article($path_to_articles);
} elseif ($delete) {
	$result = delete_article($path_to_articles);
	if ($result) $_SESSION['msg'] = ['success'=>1, 'txt'=>'Статья успешно удалена'];
	else $_SESSION['msg'] = ['error'=>1, 'txt'=>'Ошибка удаления статьи'];
	header('Location: index.php');
} elseif ($main) { //Главная страница
	$h1 = 'Статьи';
	$page = isset($_GET['page']) ? $_GET['page'] : 1;
	//$page = $_GET['page'] ?? 1; // $page = $_GET['page'] если он установлен, иначе = 1
	$per_page = 5; //число статей на страницу
	$articles = get_articles_list($path_to_articles, $page, $per_page);
	$articles_count = get_files_count($path_to_articles); //Всего статей
	$numb_of_pages = ceil($articles_count/$per_page); //количество страниц
} else {}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title><?=$title?></title>
  <!-- <link rel="stylesheet" href="style.css"> -->
	<style>
		html{min-height: 100%;}
		html, body{
		display: flex; 
		flex-direction: column; 
		padding:0; 
		margin:0;
		}
		body{flex: auto;font-family: 'Open Sans', sans-serif;}
		#header, #main{
		width: 100%;
		max-width: 1520px;
		min-width: 430px;
		margin:auto;
		box-sizing: border-box;
		}
		#header a{text-decoration:none;}
		#header a:hover>h1{color:blue;}
		#main {flex-grow: 1;display: flex;}
		#main .middle{flex-grow:1;max-width:80%;padding:25px;}
		#main .right{
		flex-grow: 1;
		min-width: 200px;
		max-width:20%;
		padding: 25px 0;
		}
		h1{padding:0 25px;margin-bottom:0;color:black;}
		.pag-active{background: yellow;}
		.delete{margin-left: 28px;}
		.creation_date{margin-left: 8px;}
		.success{
		border:3px solid green;
		box-sizing:border-box;
		background:#46ec46;
		margin:0 0 10px;
		padding:5px;
		}
		.error{
		border:3px solid red;
		box-sizing:border-box;
		background:#e95a5a;
		margin:0 0 10px;
		padding:5px;
		}
		.arrow{width: 15px;margin-right: 8px;}
	</style>
</head>
<body>
	<div id="header">
		<a href="index.php"><h1><?=$h1?></h1></a>
	</div>
	<div id="main">
		<div class="middle">
			<?php
			if ($show) { // вывод контента статьи
			?>
			<!-- Хлебные крошки -->
			<a href="index.php">Главная</a>
			<span> > </span>
			<a href="#"><?=$article['article_name'];?></a>

			<!-- Текст статьи -->
			<p><?=$article['article_text']?></p>
			<?php
			} elseif ($add) { // Добавление новой статьи
				if_msg(); // Сообщения об успехе/ошибке
			?>
				<form enctype="multipart/form-data" method="POST" action="index.php">
					<label>
						Название статьи:<br>
						<input name="article_name" type="text">
					</label><br>
					<label>
						Текст статьи<br>
						<textarea name="article_text" cols="100" rows="30"></textarea>
					</label><br>
					<label>
						Файл<br>
						<input name="file" type="file" accept="text/plain"> <!--принимает только txt файлы-->
					</label><br>
					<button name="add_article" type="submit" value="true">Добавить</button>
				</form>
			<?php
			} elseif ($main) { // вывод Главной страницы
				if_msg(); // Сообщения об успехе/ошибке
				foreach ($articles as $f_name => $article) { ?>
					<img class="arrow" src="https://cdn0.iconfinder.com/data/icons/feather/96/591276-arrow-right-64.png" alt="стрелка">
					<a href="index.php?show=<?=$f_name;?>"><?=$article['article_name'];;?></a>
					<span class="creation_date" ><?=$article['creation_date'];?></span>
					<a class="delete" href="index.php?delete=<?=$f_name;?>">Удалить</a><br>
				<?php } //ПАГИНАЦИЯ ?>
				<p>Страницы: 
				<?php 
				for ($i=1; $i <= $numb_of_pages; $i++) { 
					if($page == $i) $class = ' class="pag-active" ';
					else $class ='';?>
					<a <?=$class;?> href="index.php?page=<?=$i;?>"><?=$i;?></a>
				<?php } ?>
				</p>
				<div>Всего статей: <?=$articles_count;?></div>
			<?php } else {} ?>
		</div>
		<div class="right">
			<?php
			if ($show) { ?>
				<div><b>Дата изменения статьи: <?=$article['modification_date'];?></b></div>
				<div><b>Дата создания статьи: <?=$article['creation_date'];?></b></div>
				<div><a href="index.php">Вернуться на Главную</a></div>
			<?php 
			} elseif ($add) { ?>
				<div><a href="index.php">Вернуться на Главную</a></div>
			<?php
			} elseif ($main)  { ?> 
				<a class="add_article" href="index.php?add_article=1">Добавить статью</a>
			<?php } else {} ?>
		</div>
	</div>
	<?php //echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.'; ?>
</body>
</html>


