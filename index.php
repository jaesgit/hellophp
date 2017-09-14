<?php
#sublime git test
class base{
	public function sayHello(){
		echo "Hello";
	}
} 
trait sayPhp{
	public function sayHello(){
		parent::sayHello();
		echo ' php';
	}
}
trait hello{
	public function hello(){
		echo 'Hello';
	}
}
trait world{
	public function world(){
		echo ' world!';
	}
}
class myHelloPhp extends base{
	use sayPhp,hello,world;
	public function sayHello(){
		parent::sayHello();
		echo ' world';
	}
	public function world(){
		echo ' php!';
	}
}
$o = new myHelloPhp();
//$o->sayHello();
$o->hello();
$o->world();