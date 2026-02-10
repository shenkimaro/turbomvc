<?php

/**
 *
 * @author ibanez
 */
class Thread {

	private $commands = [];

	public function addCommands($command) {
		$this->commands[] = $command;
	}

	public function run() {
		$count = count($this->commands);
		for ($j = 0; $j < $count; $j++) {
			$commmand = $this->commands[$j];
			$pipe[$j] = popen(" $commmand ", 'w');
		}

		// wait for them to finish
		for ($j = 0; $j < $count; ++$j) {
			pclose($pipe[$j]);
		}
	}

}
