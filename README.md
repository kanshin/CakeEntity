What is CakeEntity?
======================================

- find() now returns array of objects instead of array of arrays.
- Plugin for CakePHP
- 100% compatible with the standard Model.
- Open source. Available in GitHub. MIT Lisense.
- CakePHP 1.3, PHP 5.2 > 

https://github.com/kanshin/CakeEntity

Installation
------------

Clone in plugin directory with name `entity`:

    git clone git://github.com/kanshin/CakeEntity.git plugins/entity

Or download an archive and extract in `plugins/entity`.

How to use
----------

CakeEntity don't change anything with just the installation. You have to
enables the functionality by indicating to use it. This is for compati-
bility reason.

Use `EntityModel` as the super class of models where you want to activate.

    App::import('Model', 'Entity.EntityModel');
    
    class Post extends EntityModel {
        ...

Then in the options of the `find`, specify `entity` => true:

    $entity = $this->Post->find('all', array(
        'conditions' => ...
        'order' => ...
        'entity' => true, 
    ));

Now the `$result` includes the array of objects (entities).

Entity class
-------------

`Entity` class is the default class used as the result of objects.
If there is a class with the model's name + 'Entity', that class is
uses instead. (i.e. For model "Post", the class "PostEntity" is used)

Array access for Entity object
------------------------------

Entity's property can be accessed using array syntax.

    echo $post['title']; // == $post->title

Array access is vest suited with using with Smarty.

    Hello, my name is {$post.author.name|h}.

Also array access introduces two important feature:

- access controll for security.
- cache for performance.

For more information
---------------------

[Introducing CakeEntity (PHP study in Tokyo 10/1/2011)](http://www.slideshare.net/basuke/introducing-cakeentity-9496875)


