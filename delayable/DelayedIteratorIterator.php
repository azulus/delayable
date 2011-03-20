<?php

namespace delayable;

class DelayedIteratorIterator extends \RecursiveIteratorIterator implements \ArrayAccess,\Countable{	
	public function __construct($obj){
		parent::__construct(
				$obj instanceof \RecursiveIteratorIterator ? 
					$obj->getInnerIterator() : 
					$obj instanceof \Iterator ?
						$obj :
						new \delayable\DelayedObject($obj)
		);
	}
	
	public function offsetExists($offset){$obj = &$this->getInnerIterator();return isset($obj[$offset]);}
	public function _offsetGet($offset){$obj = &$this->getInnerIterator();return $obj[$offset];}
	public function offsetSet($offset,$value){$obj = &$this->getInnerIterator();$obj[$offset] = $value;}
	public function offsetUnset($offset){$obj = &$this->getInnerIterator();unset($obj[$offset]);}
	
	public function offsetGet($offset){
		if($this->getInnerIterator() instanceof \ArrayAccess) {
			return delay(new delayable\DelayedMethodLoader($this->getInnerIterator(), "offsetGet", Array($offset)));	
		}else{
			$obj = &$this->getInnerIterator();
			return $obj[$offset];
		}
	}
	
	public function _inner() {
		return $this->getInnerIterator();
	}
	
	public function __toString() {
		return (string)$this->getInnerIterator();
	}
	
	public function __call($method, $parameters) {
		$obj = $this->getInnerIterator() instanceof \delayable\DelayedObject ? 
			$this->getInnerIterator() : 
			new \delayable\DelayedObject($this->getInnerIterator());
		return call_user_func_array(Array($obj, $method), $parameters);
	}
	
	public function __get($var) {
		return $this->getInnerIterator()->$var;
	}
	
	public function __set($var, $value) {
		$this->getInnerIterator()->$var = $value;
	}
	
	public function count() {
		return count($this->getInnerIterator());
	}
}
