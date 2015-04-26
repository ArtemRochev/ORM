<?php
require_once("entity.php");

class Section extends Entity {
    protected $columns  = ["title"];
    protected $parents  = [];
    protected $children = ["categories" => "Category"];
    protected $siblings = [];
}

class Category extends Entity {
    protected $columns  = ["title"];
    protected $parents  = ["section"];
    protected $children = ["posts" => "Post"];
    protected $siblings = [];
}

class Post extends Entity {
    protected $columns  = ["content", "title"];
    protected $parents  = ["category"];
    protected $children = ["comments" => "Comment"];
    protected $siblings = ["tags" => "Tag"];
}

class Comment extends Entity {
    protected $columns  = ["text"];
    protected $parents  = ["post", "user"];
    protected $children = [];
    protected $siblings = [];
}

class Tag extends Entity {
    protected $columns  = ["name"];
    protected $parents  = [];
    protected $children = [];
    protected $siblings = ["posts" => "Post"];
}

class User extends Entity {
    protected $columns  = ["name", "email", "age"];
    protected $parents  = [];
    protected $children = ["comments" => "Comment"];
    protected $siblings = [];
}


// insert

// Entity::setDatabase(new PDO("sqlite:test.db"));
Entity::setDatabase(new PDO("pgsql:dbname=test", "xtreem", "dt3ftbfh4m"));

// $sections = [
//     ["title" => "Shit"],
//     ["title" => "Books"],
//     ["title" => "News"],
//     ["title" => "Movies"],
//     ["title" => "Audio"],
//     ["title" => "Music"]
// ];

// $categories = [
//     ["title" => "Science", "section" => 1],
//     ["title" => "Tales", "section" => 1],
//     ["title" => "Detective", "section" => 3],
//     ["title" => "Thriller", "section" => 3],
//     ["title" => "XXX", "section" => 3],
//     ["title" => "Fantasy", "section" => 3],
//     ["title" => "Rap", "section" => 4],
//     ["title" => "Grind Core", "section" => 4],
//     ["title" => "Black Metal", "section" => 4],
//     ["title" => "Heavy Metal", "section" => 4],
//     ["title" => "Pop", "section" => 4],
//     ["title" => "Grunge", "section" => 4],
//     ["title" => "Country", "section" => 4],
//     ["title" => "Rock", "section" => 4],
//     ["title" => "Hard Rock", "section" => 4]
// ];

// $catSections = [];

// foreach ( $sections as $data ) {
//     $section = new Section();
//     $section->title = $data["title"];
//     $section->save();
//     $catSections[] = $section;
// }

// foreach ( $categories as $data ) {
//     $cat = new Category();
//     $cat->title = $data["title"];
//     $cat->section = $catSections[$data["section"]];
//     $cat->save();
// }


// $cat = new Category(35);
// $cat->delete();

foreach ( User::all() as $user ) {
    echo "{$user->id}: {$user->name}\n";
}


