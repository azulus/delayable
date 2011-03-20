<?php

namespace delayable;

class DelayedMethodLoader implements \Iterator,\ArrayAccess,\Countable,\RecursiveIterator{
	public $_loaded = false;
	public $obj;
	public $method;
	public $args;
	private $value;
	public static $values = Array();
	public static $otherTags = Array();
	public static $pending = Array();
	public static $delayed = Array();
	public static $toParse = Array();
	private $exception = null;
	public static $depth = 0;
	
	public function __construct(&$obj, $method, $args = Array()) {
		$this->obj = $obj instanceof \RecursiveIteratorIterator ? $obj->getInnerIterator() : $obj;
		$this->method = $method;
		$this->args = $args;
		array_push(\delayable\DelayedMethodLoader::$pending, $this);
	}
	
	public function _inner() {
		return $this->obj;
	}
	
	public function __toString() {
		try{
			return (string)($this->getValue());
		}catch(\Exception $e) {
			return "";
		}	
	}
	
	public function __call($method, $params) {
		$value = $this->getValue();
		return call_user_func_array(Array($value,$method), $params);
	}
	
	public function __get($var) {
		if($var === "_loaded")
			return $this->_loaded;
			
		$wrapped = $this->getValue();
		return $wrapped->$var;
	}
	
	public function __set($var, $value) {
		if($var === "_loaded") 
			return ($this->_loaded = $value);
			
		$wrapped = $this->getValue();
		$wrapped->$var = $value;
	}
	
	public function getValue() {
		if(!$this->_loaded) {				
			$obj = $this;
			self::loadPending(null, $this);
		}
		return $this->value;
	}
	
	private function setValue($value) {
		$this->value = $value;
		$this->_loaded = true;
	}
	
	public static function hasPending() {
		return count(self::$values) > 0 ||
			count(self::$pending) > 0 ||
			count(self::$delayed) > 0 ||
			count(self::$toParse) > 0 ||
			count(self::$otherTags) > 0; 
	}
	
	public static function loadPending($pending = null, &$requestObj = null) {					
		while($obj = array_shift(self::$pending)) {
			if($obj->obj instanceof \templates\TemplateParser && $obj->method == "runTag" && $obj->args[0] == "PARSE") {
				self::$toParse[] = $obj;
				
			}elseif($obj->obj instanceof \templates\TemplateParser && $obj->method == "runTag") {
				self::$otherTags[] = $obj;
				
			}elseif($obj->obj instanceof \delayable\Delayable) {
				$clazz = get_class($obj->obj);
				if(!isset(self::$delayed[$clazz])) self::$delayed[$clazz] = Array();
				self::$delayed[$clazz][] = $obj;
				
			}else{					
				self::$values[] = $obj;				
			}
		}
		$delayedCount = 0;
		foreach(self::$delayed as $obj) $delayedCount += count($obj);
		
		$tags = self::$otherTags;
		usort($tags, "\\delayable\\DelayedMethodLoader::tagCompare");
		self::$otherTags = Array();
		while($obj = array_shift($tags)) {
			$obj->setValue(call_user_func_array(Array(&$obj->obj, $obj->method), &$obj->args));

			if(count(self::$pending) > 0) {
				self::$otherTags = array_merge($tags, self::$otherTags);
				$tags = Array();
				return self::loadPending();
			}

		}
		
		$found = count(self::$toParse) > 0;
		while($obj = array_shift(self::$toParse)) {
			$obj->setValue(call_user_func_array(Array(&$obj->obj, $obj->method), &$obj->args));
			if(count(self::$pending) > 0) return self::loadPending();
		}
		if($found) return self::loadPending();
		
		$found = count(self::$values) > 0;
		while($obj = array_shift(self::$values)) {
			$obj->setValue(call_user_func_array(Array(&$obj->obj, $obj->method), &$obj->args));
			if(count(self::$pending) > 0) return self::loadPending();
		}
		if($found) return self::loadPending();
		
		while(count(self::$delayed) > 0) {
			$methods = array_shift(self::$delayed);
			
			$toDelay = Array();
			foreach($methods as $obj) {
				$toDelay[] = Array(&$obj->obj, $obj->method, &$obj->args);
			}
			$methods[0]->obj->loadDelayed($toDelay);
			foreach($methods as $obj) {
				$obj->setValue(call_user_func_array(Array(&$obj->obj, $obj->method), &$obj->args));
			}
			
			if(count(self::$pending) > 0) return self::loadPending();
		}
	}
	
	public static function tagCompare($a, $b) {
		if($a->args[1] == $b->args[1]) return 0;
		return (substr_count($a->args[1], '{/') > substr_count($b->args[1], '{/')) ? -1 : 1;		
	}
	
	public function rewind(){$this->getValue();reset($this->value);}
	public function current(){$this->getValue();return current($this->value);}
	public function key(){$this->getValue();return key($this->value);}
	public function next(){$this->getValue();return next($this->value);}
	public function valid(){$this->getValue();return $this->current() !== false;}
	
	public function count(){$this->getValue();return count($this->value);}
	
	public function offsetExists($offset){$this->getValue();return count($this->value) > $offset;}
	public function offsetGet($offset){$this->getValue();return $this->value[$offset];}
	public function offsetSet($offset,$value){$this->getValue();$this->value[$offset] = $value;}
	public function offsetUnset($offset){$this->getValue();unset($this->value[$offset]);}
	
	public function hasChildren(){return $this->value instanceof RecursiveIterator;}
	public function getChildren(){return $this->value;}
}
