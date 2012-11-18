<?php
class PSO_TCPClientPool extends PSO_ClientPool {
	public static $connection_class = 'PSO_TCPClientConnection';

	public function addTarget($host, $port, $bindip='0.0.0.0') {
		$class = static::$connection_class;
		$options['socket']['bindto'] = $bindip;
		$context = stream_context_create($options);
		$stream = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, ini_get("default_socket_timeout"), STREAM_CLIENT_CONNECT, $context);
		$conn = new $class($stream);
		$this->addConnection($conn);
		return $conn;
	}

}