<?php
function __autoload($class) {
	if(file_exists($file = "{$class}.php")) {
		include $file;
	}
}

abstract class PSO {
	protected static $next_poll = 0;
	protected static $poll_interval = 1;
	
	public static function drain() {
		$pools = func_get_args();
		
		while(true) {
			$read = $write = $except = array();
			
			foreach($pools as $pool) {
				list($poolRead, $poolWrite) = $pool->getStreams();
				$read = array_merge($read, $poolRead);
				$write = array_merge($write, $poolWrite);
			}

			if(!$read) return;
			
			$wait = self::$next_poll - microtime(true);
			if($wait < 0) $wait = 0;
			$wait_s = floor($wait);
			$wait_us = floor(($wait - $wait_s) * 1000000);
			
			if(stream_select($read, $write, $except, $wait_s, $wait_us)) {
				foreach($read as $fp) {
					list($pool, $conn) = self::find_connection($fp, $pools);
					
					$pool->readData($conn);
				}
				
				foreach($write as $fp) {
					list($pool, $conn) = self::find_connection($fp, $pools);
					
					$pool->sendBuffer($conn);
				}
			}
			
			
			if(self::$next_poll < microtime(true)) {
				foreach($pools as $pool) {
					$pool->raiseEvent('Tick');
				}
				
				self::$next_poll = microtime(true) + self::$poll_interval;
			}
		}
	}
	
	protected static function find_connection($fp, $pools) {
		foreach($pools as $pool) {
			if($conn = $pool->findConnection($fp)) {
				return array($pool, $conn);
			}
		}
	}
}