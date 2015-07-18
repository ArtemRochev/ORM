**Create new post in DB:
```php
require_once('DatabaseRecord.php');

class Post extends DatabaseRecord {
  protected $columns = ['title', 'content'];
}

$post = new Post;

$post->title = 'Title of new post';
$post->content = 'Content of new post';
$post->save();
```
