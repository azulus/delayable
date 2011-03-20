<?php

namespace example;

class UrlLoader implements \delayable\Delayable{
	private static $urls = Array();
	private $content = Array();
		
	public function loadDelayed($methods) {
		$node_count = count($methods);
		
		$curl_arr = array();
		$master = curl_multi_init();
		
		for($i = 0; $i < $node_count; $i++) {
			$url = $this->makeUrl($methods[$i][2][0], @$methods[$i][2][1]);
			$curl_arr[$i] = curl_init($url);
			curl_setopt($curl_arr[$i], CURLOPT_RETURNTRANSFER, true);
			curl_multi_add_handle($master, $curl_arr[$i]);
		}
		
		$running = 0;
		do {
		    curl_multi_exec($master,$running);
		} while($running > 0);
		
		for($i = 0; $i < $node_count; $i++) {
			$methods[$i][0]->content[$this->makeUrl($methods[$i][2][0], @$methods[$i][2][1])] = curl_multi_getcontent($curl_arr[$i]);
		}
	}
	
	private function makeUrl($url, $parameters) {
		if(count($parameters) > 0) {
			$queryString = "";
			foreach($parameters as $key => $value) {
				$queryString .= ($queryString == "" ? "?" : "&") . urlencode($key)."=".urlencode($value);
			}	
			$url .= $queryString;
		}
		return $url;
	}
	
	private function loadSingle($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$this->content[$url] = curl_exec($ch);
		curl_close($ch);
	}
	
	public function get($url, $parameters = Array()) {
		$url = $this->makeUrl($url, $parameters);
		if($this->content[$url] === null)
			$this->loadSingle($url, $parameters);

		return $this->content[$url];
	}
}
