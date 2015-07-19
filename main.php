<?php
require_once('DatabaseRecord.php');

class User extends DatabaseRecord {
    protected $columns  = ["name", "email"];
    protected $parent = "";
}

class Comment extends DatabaseRecord {
    protected $columns  = ["text", "time", "user_id"];
    protected $parent = "user";
}

try {
	DatabaseRecord::setDatabase(new PDO("mysql:dbname=book;host=127.0.0.1", "book", "1111"));
} catch (PDOException $e) {
	echo "PDO error: " . $e->getMessage() . "\n";
}

$comment = new Comment(2);

echo "[" . $comment->user->name . "] ";
echo $comment->text . "\n";

// foreach ( Comment::all() as $comment ) {
// 	echo "User: $comment->user_id [$comment->time] $comment->text\n";
// }
