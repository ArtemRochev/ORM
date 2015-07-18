<?php
require_once('DatabaseRecord.php');

class Article extends DatabaseRecord {
    protected $columns  = ["title", "text"];
}

try {
	Article::setDatabase(new PDO("pgsql:dbname=postgres;host=127.0.0.1", "postgres", "1111"));
} catch (PDOException $e) {
	echo "PDO error: " . $e->getMessage() . "\n";
}

// $article = new Article();
// $article->title = 'TIT';
// $article->text = 'CON';
// $article->save();

// $article->title = 'TITLE';
// $article->save();

// echo $article->title . "\n";
// echo $article->text . "\n";

// for ( $i = 0; $i < 5; $i++ ) {
// 	$article = new Article();
// 	$article->title = "Title";
// 	$article->content = "Mega content";
// 	$article->save();
// }

foreach (Article::all() as $article) {
	echo "[" . $article->title . " - " . $article->text . "]\n";
}
