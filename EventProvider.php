<?php
trait EventProvider {
	protected $events = array();
	
	public function raiseEvent($event, $args=array(), $target=NULL, $context=NULL) {
		$event = 'on' . $event;
		
		if(!$context)
			$context = $this;
		
		if(!is_array($args))
			$args = array($args);
		
		if(isset($this->events[$event])) {
			foreach($this->events[$event] as $filter => $callback) {
				if(!$target && !is_int($filter)) {
					if($filter != $target)
						continue;
				}

				call_user_func_array($callback->bindTo($context, $context), $args);
			}
		}
	}
	
	public function __call($name, $args) {
		if(substr($name,0,2) == 'on') {
			switch($n = count($args)) {
				case 2:
					$this->events[$name][$args[0]] = $args[1];
					break;
				case 1:
					$this->events[$name][] = $args[0];
					break;
				default:
					trigger_error("on{$name} expects 1 or 2 arguments, {$n} given", E_USER_ERROR);
					break;
			}
			
			return true;
		}
	}
}
