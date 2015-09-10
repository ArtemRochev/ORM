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
$post = Post::findById(3);

$post->title;
$post->content;
$post->author->name;
```

**List of all posts:**
```php
Post::all();
```

**List of some posts:**
```php
Post::allWhere('author_id' => 2]);
```
