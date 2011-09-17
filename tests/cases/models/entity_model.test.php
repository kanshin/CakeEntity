<?php

App::import('Model', 'Entity.EntityModel');

/**
 *	Models
 *  
 *  Author --many--> Post --one---> PostImage
 *                        --many--> PostComment
 *                        --many--> PostStar (not EntityModel)
 *  
 */


class TestEntityModel extends EntityModel {
	public $useTable = false;
}

class Author extends TestEntityModel {
	public $name = 'Author';
	
	public $hasMany = array(
		'Post', 
	);
}

class Post extends TestEntityModel {
	public $name = 'Post';
	
	public $belongsTo = array(
		'Author', 
	);
	
	public $hasOne = array(
		'Image' => array(
			'className' => 'PostImage',
		), 
	);
	
	public $hasMany = array(
		'Comment' => array(
			'className' => 'PostComment',
		), 
		'Star' => array(
			'className' => 'PostStar',
		), 
	);
	
	/**
	 *	dummy implementation of find(). 
	 */
	public function find($type, $query = array()) {
		$result = null;
		
		$this->beforeFind($query);
		
		switch ($type) {
			case 'all':
				$result = SampleData::$arrayOfData;
				break;
				
			case 'first':
				if (!empty($query['contain'])) {
					$result = SampleData::$associatedData;
				} else {
					$result = SampleData::$simpleData;
				}
				break;
				
			case 'count':
				$result = 3;
				break;
		}
		
		$result = $this->afterFind($result, true);
		
		return $result;
	}
}

class PostImage extends TestEntityModel {
	public $name = 'PostImage';
	
	public $belongsTo = array(
		'Post', 
	);
}

class PostComment extends TestEntityModel {
	public $name = 'PostComment';
	
	public $belongsTo = array(
		'Post', 
	);
}

class PostStar extends AppModel {
	public $name = 'PostStar';
	public $useTable = false;
	
	public $belongsTo = array(
		'Post', 
	);
}

/**
 *	Entities
 */

class AuthorEntity extends Entity {
}

class PostEntity extends Entity {
}

class PostCommentEntity extends Entity {
}

/**
 *	Data
 */

class SampleData {
	public static $simpleData = array(
		'Post' => array(
			'id' => 123, 
			'title' => 'Hello', 
		), 
	);
	
	public static $associatedData = array(
		'Post' => array(
			'id' => 123, 
			'title' => 'Hello', 
			'author_id' => 345, 
		), 
		'Author' => array(
			'id' => 345, 
			'name' => 'Bob', 
		),
		'Image' => array(
			'id' => 234, 
			'post_id' => 123, 
			'path' => '/path/to/image.jpg', 
		),
		'Comment' => array(
			array(
				'id' => 101, 
				'post_id' => 123, 
				'comment' => 'hello', 
			),
			array(
				'id' => 102, 
				'post_id' => 123, 
				'comment' => 'world', 
			),
			array(
				'id' => 103, 
				'post_id' => 123, 
				'comment' => 'again', 
			),
		),
		'Star' => array(
			array(
				'id' => 201, 
				'post_id' => 123, 
				'point' => 1, 
			),
			array(
				'id' => 202, 
				'post_id' => 123, 
				'point' => 3, 
			),
		),
	);
	
	public static $arrayOfData = array(
		array(
			'Post' => array(
				'id' => 123, 
				'title' => 'Hello', 
			), 
		),
		array(
			'Post' => array(
				'id' => 124, 
				'title' => 'world', 
			), 
		),
		array(
			'Post' => array(
				'id' => 125, 
				'title' => 'again', 
			), 
		),
	);
}

/**
 *	Testcases
 */

class EntityModelTestCase extends CakeTestCase {
	public function startTest() {
		$this->Post = ClassRegistry::init('Post');
	}
	
	public function endTest() {
		unset($this->Post);
		ClassRegistry::flush();
	}
	
	public function testEntityCreation() {
		
		// 1. create entity. It must be instance of PostEntity.
		$s1 = $this->Post->entity();
		$this->assertTrue(is_a($s1, 'PostEntity'));
		
		// 2. create entity with data. Attributes can be accessed.
		$s2 = $this->Post->entity(SampleData::$simpleData);
		$this->assertTrue(is_a($s2, 'PostEntity'));
		$this->assertEqual($s2->id, 123);
		$this->assertEqual($s2->title, 'Hello');
		
		// 3. create entity with complex data.
		$s3 = $this->Post->entity(SampleData::$associatedData);
		
		// 3a. ensure object is PostEntity.
		$this->assertTrue(is_a($s3, 'PostEntity'));
		$this->assertEqual($s3->id, 123);
		$this->assertEqual($s3->title, 'Hello');
		
		// 3b. belongsTo association.
		$this->assertTrue(is_a($s3->author, 'AuthorEntity'));
		$this->assertEqual($s3->author->id, 345);
		$this->assertEqual($s3->author->name, 'Bob');
		
		// 3c. hasOne association. Entity has no specific class.
		$this->assertTrue(is_a($s3->image, 'AppEntity'));
		$this->assertEqual($s3->image->id, 234);
		
		// 3d. hasMany association.
		$this->assertEqual(count($s3->comments), 3);
		$this->assertTrue(is_a($s3->comments[0], 'PostCommentEntity'));
		$this->assertEqual($s3->comments[0]->comment, 'hello');
		
		// 3e. hasMany association (not EntityModel).
		$this->assertEqual(count($s3->star), 2);
		$this->assertTrue(is_array($s3->star[0]));
		$this->assertEqual($s3->star[0]['point'], 1);
	}
	
	public function testFind() {
		
		// 1. Test emulated find()
		$result = $this->Post->find('first');
		$this->assertTrue(is_array($result));
		$this->assertEqual($result['Post']['title'], 'Hello');
		
		// 2. OK, let's rock.
		$s1 = $this->Post->find('first', array('entity' => true));
		$this->assertTrue(is_a($s1, 'PostEntity'));
		
	}
	
	public function testEntityGetModel() {
		
		// Entity may return its Model object.
		$s1 = $this->Post->entity();
		$Model = $s1->getModel();
		$this->assertTrue(is_a($Model, 'Post'));
	}
}

