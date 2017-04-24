<?php

namespace donatj\MockWebServer;

class MockWebServer {

	const VND = 'VND.DonatStudios.MockWebServer';

	protected $pid = null;

	/**
	 * @var string
	 */
	private $host;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * Indicates whether or not the server was successfully started
	 *
	 * @var bool
	 */
	private $started = false;

	/**
	 * TestWebServer constructor.
	 *
	 * @param int    $port Network port to run on
	 * @param string $host Listening hostname
	 */
	public function __construct( $port = 8123, $host = "127.0.0.1" ) {
		$this->port = $port;
		$this->host = $host;
	}

	/**
	 * Start the Web Server on the selected port and host
	 */
	public function start() {
		if( $this->isRunning() ) {
			return;
		}
		$stdout = tempnam(sys_get_temp_dir(), 'mockserv-stdout-');
		$cmd    = "php -S {$this->host}:{$this->port} " . __DIR__ . '/server.php';

		$fullCmd = sprintf("%s > %s 2>&1 & echo $!",
			escapeshellcmd($cmd),
			escapeshellarg($stdout)
		);

		$this->pid = exec(
			$fullCmd,
			$o,
			$ret
		);

		if( !ctype_digit($this->pid) ) {
			throw new \RuntimeException("Error starting server, received '{$this->pid}', expected int PID");
		}

		sleep(1); // just to make sure it's fully started up, maybe not necessary

		if( !$this->isRunning() ) {
			throw new \RuntimeException("Failed to start server. Is something already running on port {$this->port}?");
		}

		$this->started = true;

		register_shutdown_function(function () {
			if( $this->isRunning() ) {
				$this->shutdown();
			}
		});
	}

	/**
	 * Is the Web Server currently running?
	 *
	 * @return bool
	 */
	public function isRunning() {
		if( !$this->pid ) {
			return false;
		}

		$result = shell_exec(sprintf("ps %d",
			$this->pid));
		if( count(preg_split("/\n/", $result)) > 2 ) {
			return true;
		}

		return false;
	}

	public function shutdown() {
		exec(sprintf('kill %d',
			$this->pid));

		$this->started = false;
	}

	/**
	 * Get the HTTP root of the webserver
	 *  e.g.: http://127.0.0.1:8123
	 *
	 * @return string
	 */
	public function getServerRoot() {
		return "http://{$this->host}:{$this->port}";
	}

	/**
	 * @return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return int
	 */
	public function getPort() {
		return $this->port;
	}
}