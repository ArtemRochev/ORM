```
all();
allWhere();
findById();
findOne();
save();
delete();
checkExists();
```


**Usage:**

```php
class Post extends DatabaseRecord {
    protected $parents = ["author"];
}
```

**Create new post:**
```php
$post = new Post;

$post->title = 'hello world';
$post->save();
```

**Get post name by id:**
```php
$post = Post::findOne(3);

$post->title;
$post->author->name;
```

**List of posts:**
```php
Post::all();

$user = User::findOne([
    'email' => 'jack@gmail.com'
]);
Post::allWhere([
    'author_id' => $user->id,
    'isPublished' => true
]);
```
