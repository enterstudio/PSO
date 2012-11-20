<?php
class PSO_Connection {
	use EventProvider;

	public static $chunk_size = 4096;
	
	public $pool;
	public $stream;
	
	protected $outputBuffer = '';
	
	public function readData() {
		$data = fread($this->stream, static::$chunk_size);
		return $data;
	}
	
	public function sendBuffer() {
		if($this->outputBuffer === '')
			return;
		
		if(strlen($this->outputBuffer) > static::$chunk_size) {
			$chunk = substr($this->outputBuffer, 0, static::$chunk_size);
			$this->outputBuffer = substr($this->outputBuffer, static::$chunk_size);
		} else {
			$chunk = $this->outputBuffer;
			$this->outputBuffer = '';
		}
		
		$written = @fwrite($this->stream, $chunk);
		if(!$written) {
			$this->disconnect();
		}
	}

	public function send($data) {
		$this->outputBuffer .= $data;
	}
	
	public function hasOutput() {
		return $this->outputBuffer != '';
	}
	
	public function disconnect() {
		// If the connection is still active, drain the buffer before disconnecting
		if(is_resource($this->stream) && $this->outputBuffer) {
			$client = $this;
			return $this->pool->onTick(function() use ($client) {
				$client->disconnect();
				return 'unregister';
			});
		}
		
		$this->raiseEvent('Disconnect');
		$this->pool->disconnect($this);
		@fclose($this->stream);
	}
}