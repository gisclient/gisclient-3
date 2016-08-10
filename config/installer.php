<?php

// TODO: in newer installation guidelines, tmp and var files are moved.
// Adapt script to new situation
// TODO: config file should be readable only by the web server!!
// require_once __DIR__ . '/config.db.php';

$httpdWritableDirs = array(
    '/import/',
	'/files/',
	'/symbols/usersymbols/',
	'/symbols/usersymbols/pixmaps/',
	'/map/',
	'/public/services/tmp/',
	'/public/admin/export/',
	'/config/debug/',
);

class GcInstaller {

	private $options;

	public function __construct(array $options) {
		$this->options = $options;
	}

	public function createMissingDir($path) {
		$path_elements = explode(DIRECTORY_SEPARATOR, $path);
		$test_path = '';

		for ($i = 0; $i < count($path_elements); $i++) {
			if ($i == 0 && $path_elements[0] == '') {
				$test_path = DIRECTORY_SEPARATOR;
				continue;
			}

			$realParent = realpath($test_path);
			$test_path .= $path_elements[$i] . DIRECTORY_SEPARATOR;

			if (file_exists($test_path)) {
				if (!is_dir($test_path)) {
					write_log("$test_path is not a directory");
					return R3_ERROR;
				}
			} else {
				// in case of a directory referenced by a symbolic link, realpath 
				// gives the accurate parent
				$realTestPath = $realParent . DIRECTORY_SEPARATOR . $path_elements[$i];
				if (($rv = mkdir($realTestPath)) === false) {
					throw new Exception("Could not mkdir($realTestPath) ");
				}
				echo "mkdir($realTestPath) ok\n";
			}
		}
		if (!is_writable($test_path)) {
			write_log("$test_path is not a writeable directory");
			return R3_ERROR;
		}
		return R3_OK;
	}

	/**
	 * Create directories where the webserver can write to
	 * 
	 * @param array $dirs
	 * @param string $base
	 * @throws Exception
	 */
	function checkOutputDirs(array $dirs, $base = '') {

		foreach ($dirs as $dir) {
			$currentDir = $base . $dir;
			echo $currentDir . "\n";
			if (!file_exists($currentDir)) {
				if ($this->options['simulate']) {
					echo "create directory {$currentDir}\n";
					continue;
				} else if ($this->options['create_dir']) {
					echo "create $currentDir\n";
					$this->createMissingDir($currentDir);
				}
			}
			if (!$this->options['writable_for_webserver']){
				// we do not need to make this writable for the web service
				// this could be the case where php is run as a web server
				continue;
			}
			
			if (is_link($currentDir)) {
				// if it is a link, then verify target 
				if (($realDir = readlink($currentDir)) === false) {
					throw new Exception("Could not readlink($currentDir)");
				}
			} else {
				$realDir = realpath($currentDir);
			}

			if (($filegroup = filegroup($realDir)) === false) {
				throw new Exception("Could not get filegroup of $currentDir");
			}
			echo "gid: $filegroup\n";
			
			$httpdGid = $this->getGroupGid($this->options['webserver_group']);
			if ($filegroup != $httpdGid) {
				if ($this->options['simulate']) {
					echo "set group of {$realDir} to $httpdGid\n";
				} else {
					if (!($rv = chgrp($realDir, $httpdGid))) {
						echo "WARNING: could not change group of {$currentDir} to " .
						$this->options['webserver_group'] . "\n";
					}
				}
			}

			$perms = fileperms($realDir);
			$groupReadable = $perms & 0x0010;
			if (!$groupReadable) {
				$newPerms = $perms | 0x0010;
				if ($this->options['simulate']) {
					echo "set {$realDir} to group writable: " . decoct($newPerms) . "\n";
				} else {
					if (!($rv = chmod("{$realDir}", $newPerms))) {
						echo "WARNING: could not change permission of {$base}{$dir} to 0755\n";
					}
				}
			}
		}
	}

	function getGroupGid($group) {
		if (false === ($groupInfo = posix_getgrnam($group))) {
			throw new Exception("Can not get group id of $group");
		}
		return $groupInfo['gid'];
	}

	function exec($cmd, &$output, &$retval) {
		if ($this->options['simulate']) {
			echo $cmd . "\n";
		} else {
			exec($cmd, $output, $retval);
			if ($retval !== 0) {
				throw new Exception("failed to execute $cmd");
			}
		}
	}

	function setBasePermissions($base) {
		if ($this->options['root_as_default']) {
			$cmd = "chown -R root.root $base";
			$this->exec($cmd, $output, $retval);
		}

		$cmd = "find $base -type d -exec chmod 755 {} \;";
		$this->exec($cmd, $output, $retval);

		$cmd = "find $base -type f -exec chmod 644 {} \;";
		$this->exec($cmd, $output, $retval);
	}

}

if (count(debug_backtrace()) === 0 &&
		basename($argv[0]) == basename(__FILE__)) {

		$options = array(
			'simulate' => false,
			'create_dir' => false,
			'root_as_default' => false,
			'writable_for_webserver' => true,
			'webserver_group' => 'apache',
		);

	$cli_options = getopt('Wrscg:');
	if (array_key_exists('s', $cli_options)) {
		$options['simulate'] = true;
	}
	if (array_key_exists('c', $cli_options)) {
		$options['create_dir'] = true;
	}
	if (array_key_exists('r', $cli_options)) {
		$options['root_as_default'] = true;
	}
	if (array_key_exists('g', $cli_options)) {
		$options['webserver_group'] = $cli_options['g'];
	}
	if (array_key_exists('W', $cli_options)) {
		$options['writable_for_webserver'] = false;
	}

	$installer = new GcInstaller($options);
	$installDir = realpath(__DIR__ . '/..') . '/';
	echo "setting base permissions\n";
	$installer->setBasePermissions($installDir);

	echo "make output dirs writable for the web server";
	$installer->checkOutputDirs($httpdWritableDirs, $installDir);
}
