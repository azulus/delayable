<?php

namespace delayable;

class DelayedObject implements \Iterator,\ArrayAccess,\Countable,\RecursiveIterator{
	private $obj;
	
	public function __construct($obj) {
		$this->obj = $obj instanceof \delayable\DelayedIteratorIterator ? $obj->getInnerIterator() : $obj;
	}
	
	public function _inner() {
		return $this->obj;
	}
	
	public function __toString() {
		try{
			return (string)($this->obj);
		}catch(Exception $e) {
			return "";
		}	
	}
	
	public function __call($method, $params) {
		if($method == "fetch_array") {
			return call_user_func_array(Array($this->obj, $method), $params);
		}else{
			return delay(new \delayable\DelayedMethodLoader($this->obj, $method, $params));
		}
	}
	
	public function __get($var) {
		return delay(new \delayable\DelayedMethodLoader($this, "_getValue", Array($var)));
	}
	
	public function _getValue($var) {
		return $this->obj->$var;
	}
	
	public function __set($var, $value) {
		$this->obj->$var = $value;
	}
	
	
	public function rewind(){
		if($this->obj instanceof \Iterator) {
			return $this->obj->rewind();
		}
		reset($this->obj);
	}
	public function current(){
		current($this->obj);
	}
	public function key(){
		return key($this->obj);
	}
	public function next(){
		return next($this->obj);
	}
	public function valid(){
		return $this->current() !== false;
	}
	public function count(){
		return count($this->obj);
	}
	public function offsetExists($offset){return count($this->obj) > $offset;}
	public function _offsetGet($offset){return $this->obj[$offset];}
	public function offsetSet($offset,$value){$this->obj[$offset] = $value;}
	public function offsetUnset($offset){unset($this->obj[$offset]);}
	public function hasChildren(){return $this->obj instanceof RecursiveIterator;}
	public function getChildren(){return $this->obj;}
	
	public function offsetGet($offset){
		return delay(new \delayable\DelayedMethodLoader($this, "_offsetGet", Array($offset)));
	}
}
