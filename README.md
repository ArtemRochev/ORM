**Include and extend:**
```php
require_once('DatabaseRecord.php');

DatabaseRecord::setDatabase(new PDO("mysql:dbname=blog;host=127.0.0.1", "blog", "1111"));

class User extends DatabaseRecord {
    protected $columns  = ["name", "email"];
    protected $parent = "";
}

class Post extends DatabaseRecord {
    protected $columns  = ["text", "time", "user_id"];
    protected $parent = "user";
}
```

**Create new post in DB:**
```php
$post = new Post;

$post->title = 'Title of new post';
$post->content = 'Content of new post';
$post->save();
```

**Get post from DB by id:**
```php
$post = new Post(3);

$post->title;
$post->content;
$post->user->name;
```
**Get list of posts from DB:**
```php
foreach ( Post::all() as $post ) {
  $post->title;
  $post->content;
}
```
