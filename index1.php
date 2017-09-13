<?php
//类继承过程中的函数重写
class father {
	const B = 'father';
	private function printStr(){
		echo 'Hello father';
	}
}
class son extends father{
	const B = 'son';
	public function printStr(){
		echo "Hello son";
	}
	public function pp(){
		parent::printStr();
	}
}

$obj = new father();
$objs = new son();
echo son::B;
//$obj -> printStr();
//$objs -> printStr();
//$objs -> pp();

//类继承过程中的元素重写以及静态
class people {
	public $stature = '175';//身高
	static public $weight = '60';//体重

	public function getStature(){
		echo $this->stature;
	}

	public function getWeight(){
		echo self::$weight;
	}
}

class student extends people{
	public $stature = '170';
	static public $weight = '50';//体s重
	public function getStature(){
		echo $this->stature;
	}

	public function getWeight(){
		self::get();
	}

	static public function get(){
		echo parent::$weight;
	}
}

$stu = new student();
//$stu->getStature();
//$stu::get();

//抽象类
abstract class abstractClass{
	abstract static protected function getValue();
	public function getOut(){
		echo $this->getValue();
	}
}

class classObj1 extends abstractClass {
	static public function getValue(){
		return 'This is value';
	}
}
$obj1 = new classObj1();
//$obj1->getOut();

//接口
interface interfaceClass{
	const b = 'Interface constant';
	public function get($name);
	public function set($name,$value=1);
}

class classObj2 implements interfaceClass{
	public function get($name){
		echo $this->$name;
	}
	public function set($name,$value='asd'){
		$this->$name = $value;
	}
}





