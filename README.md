**Usage:**

```php
class User extends DatabaseRecord {
    protected $childrens = ["post"];
}

class Post extends DatabaseRecord {
    protected $parent = "author";
}
```

**Create new post:**
```php
$post = new Post;

$post->title = 'Title';
$post->content = 'Content';
$post->author_id = 1; 
$post->save();
```

**Get post and user name by id:**
```php
$post = new Post(3);

$post->title;
$post->content;
$post->author->name;
```

**Get list of posts:**
```php
foreach ( Post::all() as $post ) {
  $post->title;
  $post->content;
}
```

**Get posts some author:**
```php
Post::all([
  'author_id' => 2
]);
```
