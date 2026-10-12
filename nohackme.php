<?php
if ( defined( 'ABSPATH' ) ) {

	/*
	if ( !defined('ABSPATH') ) {
		define('ABSPATH', dirname(dirname(dirname(__FILE__))) . '/');
	}
	*/

	$uploads_path = 'wp-content/uploads';
	if ( defined('UPLOADS') ) {
		$uploads_path = UPLOADS;
	}
	$settings_path = ABSPATH . $uploads_path . '/nohackme/';
	if ( !is_dir($settings_path) ) {
		mkdir($settings_path, 0777, true);
	}
	_nohackme_defender_hackme_generate_defaults($settings_path);

	$skip_check = _nohackme_defender_is_trusted_internal_request();
	if ( isset($_SERVER['REQUEST_URI']) and strlen($_SERVER['REQUEST_URI']) ) {
		$check_str = _nohackme_defender_esc_html(_nohackme_defender_sanitize_text_field($_SERVER['REQUEST_URI']));
		if ( strpos($check_str, '/wp-admin/') === false ) {} else {
			$skip_check = true;
		}
		if ( strpos($check_str, '/wp-admin/admin-ajax.php') === false ) {} else {
			$skip_check = true;
		}
	}
	if ( !$skip_check ) {

		$hackme_path = ABSPATH . $uploads_path . '/hackme_ban/';
		if ( !is_dir($hackme_path) ) {
			mkdir($hackme_path, 0777, true);
		}
	//	$anotice_path = ABSPATH . 'wp-content/uploads/anotices/';
	//	if ( !is_dir($anotice_path) ) {
	//		mkdir($anotice_path, 0777, true);
	//	}


		$hackme_string = '';
		$banned_string = 'Your request is temporarily blocked on this site. Try to come tomorrow.';

			$settings = _nohackme_defender_hackme_read_serialized_file($settings_path . 'settings', array());
			if (isset($settings) and is_array($settings) and count($settings)) { } else {
				$settings = array();
			}

		if ( isset($settings['cancel']) and is_numeric($settings['cancel']) and $settings['cancel'] == 1 ) {
	  // temporarily disabled
		} else {
			if ( isset($settings['block_msg']) and strlen($settings['block_msg']) ) {
				$banned_string = nl2br($settings['block_msg']);
			}

				$exclude_ips = array();
				if ( isset($settings['exclude_ip']) and strlen($settings['exclude_ip']) ) {
					$exclude_ips = str_replace("\r", "\n", $settings['exclude_ip']);
					$exclude_ips = str_replace("\n\n", "\n", $exclude_ips);
					$exclude_ips = explode("\n", $exclude_ips);
					if (isset($exclude_ips) and is_array($exclude_ips) and count($exclude_ips)) { foreach ($exclude_ips as $tmpid => $tmpval) {
						$exclude_ips[$tmpid] = _nohackme_defender_hackme_normalize_ip($tmpval);
						if ( !strlen($exclude_ips[$tmpid]) ) {
							unset($exclude_ips[$tmpid]);
						}
					} }
				}

			$skip_by_ua = false;
			$exclude_uas = array();
			if ( isset($settings['exclude_user_agent']) and strlen($settings['exclude_user_agent']) ) {
				$exclude_uas = str_replace("\r", "\n", $settings['exclude_user_agent']);
				$exclude_uas = str_replace("\n\n", "\n", $exclude_uas);
				$exclude_uas = explode("\n", $exclude_uas);
				if (isset($exclude_uas) and is_array($exclude_uas) and count($exclude_uas)) { foreach ($exclude_uas as $tmpid => $tmpval) {
					$exclude_uas [$tmpid]= str_replace('.', '', $tmpval);
				} }
			}
			if (count($exclude_uas)) {
				$current_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
				$current_user_agent = _nohackme_defender_esc_html(_nohackme_defender_sanitize_text_field($current_user_agent));
				foreach ($exclude_uas as $excluded_ua) {
					if (strpos($current_user_agent, $excluded_ua) !== false) {
						$skip_by_ua = true;
						break;
					}
				}
			}

				$client_ip = _nohackme_defender_hackme_get_ip();
				$client_ip_key = _nohackme_defender_hackme_get_ip_storage_key($client_ip);
				if ( !$skip_by_ua and isset($settings['exclude_google_ips']) and is_numeric($settings['exclude_google_ips']) and $settings['exclude_google_ips'] == 1 ) {
					if ( _nohackme_defender_hackme_isGoogleIp($client_ip) ) {
						$skip_by_ua = true;
				}
			}

			if ( !$skip_by_ua and isset($settings['exclude_yandex_ips']) and is_numeric($settings['exclude_yandex_ips']) and $settings['exclude_yandex_ips'] == 1 ) {
				if ( _nohackme_defender_hackme_isYandexIp($client_ip) ) {
					$skip_by_ua = true;
				}
			}

				if ( $skip_by_ua or in_array($client_ip, $exclude_ips, true)) {
				   // disabled for this IP
				} else {
					if ( strlen($client_ip_key) ) {
						if ( is_file($hackme_path . $client_ip_key) ) {
							if ( isset($settings['cancel_block']) and strlen($settings['cancel_block']) and isset($_GET[$settings['cancel_block']]) ) {
								@unlink($hackme_path . $client_ip_key);
							} else {
								echo strip_tags($banned_string, '<b><i><br><p><ul><li><ol><strong><em><div>');
								exit();
						}
					}

					$need_time_count = false;
					if ( isset($settings['block_if_min']) and is_numeric($settings['block_if_min']) and $settings['block_if_min'] > 20 ) {
						$need_time_count = true;
					} else if ( isset($settings['block_if_10min']) and is_numeric($settings['block_if_10min']) and $settings['block_if_10min'] > 50 ) {
						$need_time_count = true;
					} else if ( isset($settings['block_if_50min']) and is_numeric($settings['block_if_50min']) and $settings['block_if_50min'] > 100 ) {
						$need_time_count = true;
					}
						$isbanned = 0;
						if ( $need_time_count ) {
							$isbanned = _nohackme_defender_hackme_update_counters($settings_path, $client_ip, $client_ip_key, $settings);
						}
				if ( $isbanned ) {
					echo strip_tags($banned_string, '<b><i><br><p><ul><li><ol><strong><em><div>');
					exit();
				}

				if ( !isset($settings['disable_suspicious_paths']) || intval($settings['disable_suspicious_paths']) !== 1 ) {
					$suspicious_path_reason = _nohackme_defender_hackme_check_suspicious_path($settings_path, $client_ip, $client_ip_key);
					if ( strlen($suspicious_path_reason) ) {
						_nohackme_defender_hackme_banned_ip($client_ip, 5, $suspicious_path_reason);
						echo strip_tags($banned_string, '<b><i><br><p><ul><li><ol><strong><em><div>');
						exit();
					}
				}
			}
		}

			$skip_check_hack = false;
			if ( isset($_SERVER['REQUEST_URI']) and strlen($_SERVER['REQUEST_URI']) ) {
				$check_str = _nohackme_defender_esc_html(_nohackme_defender_sanitize_text_field($_SERVER['REQUEST_URI']));
				if ( strpos($check_str, '/wp-admin/') === false ) {} else {
					$skip_check_hack = true;
				}
			}


			$ahacks = array();
			$hacks_list_file = $settings_path . 'hacks_list';
			if (file_exists($hacks_list_file)) {
			  $ahacks = _nohackme_defender_hackme_read_active_patterns_file($hacks_list_file);
			}

			$found_hack = false;
			if ( isset($settings['disable_hack_payloads']) && intval($settings['disable_hack_payloads']) === 1 ) {
				$skip_check_hack = true;
			}

			if ( !$skip_check_hack and isset($ahacks) and is_array($ahacks) and count($ahacks) ) {
				$reqfound = _nohackme_defender_hackme_collect_request_text();
				if ( strlen($reqfound) ) {
					$reqfound = _nohackme_defender_esc_html(mb_strtolower($reqfound));
					$reqfound_for_save = _nohackme_defender_esc_html(_nohackme_defender_sanitize_text_field(mb_substr(mb_strtolower($reqfound), 0, 2000)));
					foreach ( $ahacks as $ahacks_item) {
						$ahacks_item = _nohackme_defender_esc_html(mb_strtolower($ahacks_item));
						if ( _nohackme_defender_hackme_is_unsafe_payload_pattern($ahacks_item) ) {
							continue;
						}
						if ( strpos($reqfound, $ahacks_item) === false ) {} else {
							$found_hack = true;
							$hackme_string = $reqfound_for_save;
							break;
						}
					}
				}
			}
				if ( $found_hack ) {
					$isbanned = 4;
					if ( strlen($client_ip_key) ) {
						_nohackme_defender_hackme_banned_ip($client_ip, $isbanned, $hackme_string);
						echo strip_tags($banned_string, '<b><i><br><p><ul><li><ol><strong><em><div>');
						exit();
					}
				}
		}
	}
}

	function _nohackme_defender_hackme_get_ip(){
	    $candidates = array();
if (!empty($_SERVER['HTTP_CLIENT_IP'])){
$candidates[] = $_SERVER['HTTP_CLIENT_IP'];
}
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
$forwarded = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
foreach ($forwarded as $forwarded_ip) {
$candidates[] = $forwarded_ip;
}
}
if (!empty($_SERVER['REMOTE_ADDR'])){
$candidates[] = $_SERVER['REMOTE_ADDR'];
}

foreach ($candidates as $candidate) {
$ip = _nohackme_defender_hackme_normalize_ip($candidate);
if ( strlen($ip) ) {
return $ip;
}
}

		return '';
	}

	function _nohackme_defender_hackme_collect_request_text() {
		$parts = array();
		foreach ( array('REQUEST_URI', 'QUERY_STRING', 'HTTP_USER_AGENT', 'REQUEST_METHOD', 'CONTENT_TYPE') as $server_key ) {
			if ( isset($_SERVER[$server_key]) && is_string($_SERVER[$server_key]) && strlen($_SERVER[$server_key]) ) {
				$parts[] = $_SERVER[$server_key];
			}
		}

		if ( isset($_GET) && is_array($_GET) && count($_GET) ) {
			_nohackme_defender_hackme_collect_scalar_strings($_GET, $parts);
		}
		if ( isset($_POST) && is_array($_POST) && count($_POST) ) {
			_nohackme_defender_hackme_collect_scalar_strings($_POST, $parts);
		}
		if ( isset($_REQUEST) && is_array($_REQUEST) && count($_REQUEST) ) {
			_nohackme_defender_hackme_collect_scalar_strings($_REQUEST, $parts);
		}

		$raw_body = _nohackme_defender_hackme_get_raw_body();
		if ( strlen($raw_body) ) {
			$parts[] = $raw_body;

			$json = json_decode($raw_body, true);
			if ( is_array($json) ) {
				_nohackme_defender_hackme_collect_scalar_strings($json, $parts);
			}
		}

		if ( !count($parts) ) {
			return '';
		}

		return implode(' ', $parts);
	}

	function _nohackme_defender_hackme_collect_scalar_strings($value, &$parts, $depth = 0) {
		if ( $depth > 8 ) {
			return;
		}

		if ( is_array($value) ) {
			foreach ( $value as $key => $subvalue ) {
				if ( is_string($key) && strlen($key) ) {
					$parts[] = $key;
				}
				_nohackme_defender_hackme_collect_scalar_strings($subvalue, $parts, $depth + 1);
			}
			return;
		}

		if ( is_string($value) && strlen($value) ) {
			$parts[] = $value;
			return;
		}

		if ( is_numeric($value) ) {
			$parts[] = (string) $value;
		}
	}

	function _nohackme_defender_hackme_get_raw_body() {
		$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : '';
		if ( !in_array($method, array('POST', 'PUT', 'PATCH', 'DELETE'), true) ) {
			return '';
		}

		$content_type = isset($_SERVER['CONTENT_TYPE']) ? strtolower((string) $_SERVER['CONTENT_TYPE']) : '';
		if ( strpos($content_type, 'multipart/form-data') !== false ) {
			return '';
		}

		$fp = @fopen('php://input', 'rb');
		if ( !$fp ) {
			return '';
		}

		$body = stream_get_contents($fp, 262144);
		fclose($fp);

		return is_string($body) ? $body : '';
	}

	function _nohackme_defender_hackme_is_unsafe_payload_pattern($pattern) {
		$pattern = trim((string) $pattern);
		if ( preg_match('/^[a-z]{1,3}$/i', $pattern) ) {
			return true;
		}

		return false;
	}

	function _nohackme_defender_hackme_request_path() {
		if ( !isset($_SERVER['REQUEST_URI']) || !is_string($_SERVER['REQUEST_URI']) || !strlen($_SERVER['REQUEST_URI']) ) {
			return '';
		}

		$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		if ( !is_string($path) || !strlen($path) ) {
			return '';
		}

		$path = rawurldecode($path);
		$path = preg_replace('#/+#', '/', $path);
		if ( !is_string($path) || !strlen($path) ) {
			return '';
		}

		return '/' . ltrim($path, '/');
	}

	function _nohackme_defender_hackme_check_suspicious_path($settings_path, $client_ip, $client_ip_key) {
		$path = _nohackme_defender_hackme_request_path();
		if ( !strlen($path) || !strlen($client_ip_key) ) {
			return '';
		}

		$lower_path = mb_strtolower($path);
		if ( _nohackme_defender_hackme_is_sensitive_path($lower_path, $settings_path) ) {
			return 'sensitive_path:' . mb_substr($lower_path, 0, 180);
		}

		if ( !_nohackme_defender_hackme_is_missing_file_probe($lower_path) ) {
			return '';
		}

		return _nohackme_defender_hackme_update_path_probe_counters($settings_path, $client_ip, $client_ip_key, $lower_path);
	}

	function _nohackme_defender_hackme_is_sensitive_path($lower_path, $settings_path = '') {
		$sensitive_needles = _nohackme_defender_hackme_get_sensitive_path_patterns($settings_path);

		foreach ( $sensitive_needles as $needle ) {
			if ( false !== strpos($lower_path, $needle) ) {
				return true;
			}
		}

		$extension = strtolower(pathinfo(parse_url($lower_path, PHP_URL_PATH), PATHINFO_EXTENSION));
		if ( in_array($extension, array('bak', 'old', 'orig', 'save', 'swp', 'sql'), true) ) {
			return true;
		}

		return false;
	}

	function _nohackme_defender_hackme_default_payload_patterns() {
		return array(
			'&#039;&quot;',
			'&quot;&#039;',
			'\&#039;\&quot;',
			'\&quot;\&#039;',
			'chr(',
			'(select ',
			'&#039;;',
			'/*&#039;',
			')from(',
			'0\&quot;',
			'0\&#039;',
			'))))))))))))))',
			'.concat(',
			'socket.gethost',
			'&#039;+&#039;',
			'${',
			'%00',
			'base64_decode(',
			'nslookup ',
			')&amp;(',
			'../../',
			'.write(',
			'$()',
			'&#039; and ',
			'&quot; and ',
			'php://input',
			'expect://',
			'xp_cmdshell',
			'union select',
			'&lt;script&gt;',
			'&lt;iframe&gt;',
			'javascript:',
			'document.cookie',
			'cmd.exe',
			'/bin/bash',
			'alert(',
			'onmouseover=',
			'sleep(',
			'benchmark(',
			'load_file(',
			'into outfile',
			'information_schema',
			'waitfor delay',
			'php://filter',
			'data://',
			'file://',
			'gopher://',
			'..%2f',
			'%2e%2e%2f',
			'%252e%252e%252f',
			'fromcharcode',
			'l9scan',
			'php-cgi.exe',
			'cgi.force_redirect',
			'allow_url_include',
			'auto_prepend_file',
			'pom.properties',
			'&#039; or ',
			'&quot; or ',
			'extractvalue(',
			'updatexml(',
			';select',
			'(select',
			'union all select',
		);
	}

	function _nohackme_defender_hackme_default_sensitive_path_patterns() {
		return array(
			'/telescope/requests',
			'/actuator/env',
			'/trace.axd',
			'/.vscode/sftp.json',
			'/@vite/env',
			'/debug/default/view',
			'/etc/passwd',
			'/etc/p',
			'/proc/self/environ',
			'/php-cgi/',
			'/graphql',
			'/api/gql',
			'/META-INF/maven/',
			'/wp-admin/setup-config.php',
			'/wp-config.php',
			'wp-config.php.bak',
			'/composer.json',
			'/composer.lock',
			'/vendor/phpunit/',
			'/phpunit.xml',
			'/server-status',
			'/.env',
			'.env',
			'/.git',
			'/.svn',
			'/.hg',
			'/core/install.php',
			'/core/update.php',
			'/drupal/core/install.php',
			'/drupal/core/update.php',
			'/sites/default/settings.php',
			'/administrator/index.php',
			'/configuration.php',
			'/joomla/administrator/',
			'/app/etc/env.php',
			'/downloader/',
			'/magento/admin/',
			'/magento/downloader/',
			'/artisan',
			'/storage/logs/laravel.log',
			'/laravel/storage/logs/laravel.log',
			'/vendor/composer/installed.json',
			'/app_dev.php',
			'/_profiler/',
			'/symfony/_profiler/',
			'/typo3/index.php',
			'/typo3/install.php',
			'/umbraco/backoffice/',
			'/ghost/api/admin/',
			'/config.json',
			'/info.php',
			'/debug/',
		);
	}

	function _nohackme_defender_hackme_get_sensitive_path_patterns($settings_path = '') {
		$patterns = _nohackme_defender_hackme_default_sensitive_path_patterns();
		if ( is_string($settings_path) && strlen($settings_path) ) {
			$file = $settings_path . 'suspicious_paths_list';
			if ( is_file($file) ) {
				$stored_patterns = _nohackme_defender_hackme_read_active_patterns_file($file);
				if ( is_array($stored_patterns) && count($stored_patterns) ) {
					$patterns = $stored_patterns;
				}
			}
		}

		return $patterns;
	}

	function _nohackme_defender_hackme_disabled_pattern_prefix() {
		return '# nohackme-disabled: ';
	}

	function _nohackme_defender_hackme_is_disabled_pattern_line($line) {
		$line = trim((string) $line);
		return strpos($line, _nohackme_defender_hackme_disabled_pattern_prefix()) === 0;
	}

	function _nohackme_defender_hackme_pattern_line_value($line) {
		$line = trim((string) $line);
		$prefix = _nohackme_defender_hackme_disabled_pattern_prefix();
		if ( strpos($line, $prefix) === 0 ) {
			$line = trim(substr($line, strlen($prefix)));
		}

		return $line;
	}

	function _nohackme_defender_hackme_read_active_patterns_file($file) {
		$patterns = array();
		$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ( !is_array($lines) ) {
			return $patterns;
		}

		foreach ( $lines as $line ) {
			if ( _nohackme_defender_hackme_is_disabled_pattern_line($line) ) {
				continue;
			}
			$value = _nohackme_defender_hackme_pattern_line_value($line);
			if ( strlen($value) ) {
				$patterns[] = $value;
			}
		}

		return $patterns;
	}

	function _nohackme_defender_hackme_is_missing_file_probe($lower_path) {
		if ( false !== strpos($lower_path, '..') || false !== strpos($lower_path, "\0") ) {
			return true;
		}

		$path_without_query = ltrim($lower_path, '/');
		if ( !strlen($path_without_query) ) {
			return false;
		}

		$real_path = ABSPATH . $path_without_query;
		if ( @file_exists($real_path) ) {
			return false;
		}

		$basename = basename($path_without_query);
		if ( strpos($basename, '.') === 0 ) {
			return true;
		}

		$extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
		if ( !strlen($extension) ) {
			return false;
		}

		$suspicious_extensions = array(
			'phtml',
			'php3',
			'php4',
			'php5',
			'phar',
			'cgi',
			'pl',
			'py',
			'sh',
			'env',
			'ini',
			'conf',
			'config',
			'backup',
			'log',
			'zip',
			'tar',
			'gz',
			'rar',
			'7z',
		);

		return in_array($extension, $suspicious_extensions, true);
	}

	function _nohackme_defender_hackme_update_path_probe_counters($settings_path, $client_ip, $client_ip_key, $lower_path) {
		$counter_path = $settings_path . 'suspicious_path_counters';
		$fp = @fopen($counter_path, 'cb+');
		if ( !$fp ) {
			return '';
		}

		$reason = '';
		if ( @flock($fp, LOCK_EX) ) {
			$contents = stream_get_contents($fp);
			$counters = array();
			if ( is_string($contents) && strlen($contents) ) {
				$unserialized = @unserialize($contents);
				if ( is_array($unserialized) ) {
					$counters = $unserialized;
				}
			}

			$now = time();
			$window_start = $now - 600;
			if ( !isset($counters[$client_ip_key]) || !is_array($counters[$client_ip_key]) ) {
				$counters[$client_ip_key] = array();
			}

			$recent = array();
			foreach ( $counters[$client_ip_key] as $entry ) {
				if ( !is_array($entry) || !isset($entry['time']) || !is_numeric($entry['time']) ) {
					continue;
				}
				if ( intval($entry['time']) >= $window_start ) {
					$recent[] = $entry;
				}
			}

			$recent[] = array(
				'time' => $now,
				'path' => mb_substr($lower_path, 0, 180),
			);

			if ( count($recent) >= 6 ) {
				$reason = 'missing_file_probe:' . count($recent) . ':' . mb_substr($lower_path, 0, 180);
				unset($counters[$client_ip_key]);
			} else {
				$counters[$client_ip_key] = $recent;
			}

			foreach ( $counters as $stored_key => $entries ) {
				if ( !is_array($entries) || !count($entries) ) {
					unset($counters[$stored_key]);
					continue;
				}
				$has_recent = false;
				foreach ( $entries as $entry ) {
					if ( is_array($entry) && isset($entry['time']) && is_numeric($entry['time']) && intval($entry['time']) >= $window_start ) {
						$has_recent = true;
						break;
					}
				}
				if ( !$has_recent ) {
					unset($counters[$stored_key]);
				}
			}

			ftruncate($fp, 0);
			rewind($fp);
			fwrite($fp, serialize($counters));
			fflush($fp);
			@flock($fp, LOCK_UN);
		}

		fclose($fp);
		return $reason;
	}

	function _nohackme_defender_ai_inspector_runtime_ips() {
	static $runtime_ips = null;
	if ( is_array($runtime_ips) && count($runtime_ips) ) {
		return $runtime_ips;
	}

	$runtime_ips = array();
	if ( !function_exists('get_transient') || !function_exists('set_transient') ) {
		return $runtime_ips;
	}

	$cached_ips = get_transient('nohackme_ai_inspector_runtime_ips');
	if ( is_array($cached_ips) && count($cached_ips) ) {
		foreach ( $cached_ips as $cached_ip ) {
			$ip = _nohackme_defender_hackme_normalize_ip($cached_ip);
			if ( strlen($ip) ) {
				$runtime_ips[] = $ip;
			}
		}
		return array_values(array_unique($runtime_ips));
	}

	global $wpdb;
	if ( !isset($wpdb) || !is_object($wpdb) || empty($wpdb->prefix) ) {
		return $runtime_ips;
	}

	$table = $wpdb->prefix . 'ai_recruiter_accounts';
	$table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
	if ( $table_exists !== $table ) {
		return $runtime_ips;
	}

	$rows = $wpdb->get_col("SELECT runtime_access_json FROM `$table` WHERE runtime_access_json<>''");
	if ( !is_array($rows) ) {
		$rows = array();
	}

	foreach ( $rows as $row ) {
		$access = json_decode((string) $row, true);
		if ( !is_array($access) ) {
			continue;
		}

		$candidates = array();
		foreach ( array('host', 'ssh_host') as $field ) {
			if ( !empty($access[$field]) ) {
				$candidates[] = $access[$field];
			}
		}
		if ( !empty($access['vnc_url']) ) {
			$url_host = parse_url((string) $access['vnc_url'], PHP_URL_HOST);
			if ( is_string($url_host) && strlen($url_host) ) {
				$candidates[] = $url_host;
			}
		}

		foreach ( $candidates as $candidate ) {
			$ip = _nohackme_defender_hackme_normalize_ip($candidate);
			if ( strlen($ip) ) {
				$runtime_ips[] = $ip;
			}
		}
	}

	$runtime_ips = array_values(array_unique($runtime_ips));
	set_transient('nohackme_ai_inspector_runtime_ips', $runtime_ips, 10 * MINUTE_IN_SECONDS);
	return $runtime_ips;
}

function _nohackme_defender_is_trusted_internal_request() {
	$allowlist = array(
		'70.35.196.94',
		'212.227.189.125',
		'169.58.18.125',
		'127.0.0.1',
		'::1',
	);
	$allowlist = array_merge($allowlist, _nohackme_defender_ai_inspector_runtime_ips());
	$client_ip = _nohackme_defender_hackme_get_ip();
	return strlen($client_ip) && in_array($client_ip, $allowlist, true);
}

function _nohackme_defender_hackme_read_serialized_file($path, $default = array()) {
	$fp = @fopen($path, 'rb');
	if ( !$fp ) {
		return $default;
	}

	$data = $default;
	if ( @flock($fp, LOCK_SH) ) {
		$contents = stream_get_contents($fp);
		@flock($fp, LOCK_UN);
		if ( is_string($contents) && strlen($contents) ) {
			$unserialized = @unserialize($contents);
			if ( $unserialized !== false || $contents === serialize(false) ) {
				$data = $unserialized;
			}
		}
	}
	fclose($fp);

	return $data;
}
function _nohackme_defender_hackme_write_serialized_file($path, $data) {
	$fp = @fopen($path, 'cb');
	if ( !$fp ) {
		return false;
	}

	$is_written = false;
	if ( @flock($fp, LOCK_EX) ) {
		$serialized = serialize($data);
		ftruncate($fp, 0);
		rewind($fp);
		$is_written = fwrite($fp, $serialized) !== false;
		fflush($fp);
		@flock($fp, LOCK_UN);
	}
	fclose($fp);

	return $is_written;
}
function _nohackme_defender_hackme_update_counters($settings_path, $client_ip, $client_ip_key, $settings) {
	$counter_path = $settings_path . 'cur_ips_counters';
	$fp = @fopen($counter_path, 'cb+');
	if ( !$fp ) {
		return 0;
	}

	$isbanned = 0;
	if ( @flock($fp, LOCK_EX) ) {
		$contents = stream_get_contents($fp);
		$cur_ips = array();
		if ( is_string($contents) && strlen($contents) ) {
			$unserialized = @unserialize($contents);
			if ( is_array($unserialized) ) {
				$cur_ips = $unserialized;
			}
		}

		$cur_minute = intval(date('i', time()));
		if ( isset($cur_ips[$client_ip_key][$cur_minute]) and is_numeric($cur_ips[$client_ip_key][$cur_minute]) ) {
			$cur_ips[$client_ip_key][$cur_minute]++;
		} else {
			$cur_ips[$client_ip_key][$cur_minute] = 0;
		}

		if ( isset($settings['block_if_min']) and is_numeric($settings['block_if_min']) and $settings['block_if_min'] > 20 and $cur_ips[$client_ip_key][$cur_minute] > $settings['block_if_min'] ) {
			$isbanned = 1;
			_nohackme_defender_hackme_banned_ip($client_ip, $isbanned, $cur_ips[$client_ip_key][$cur_minute]);
			unset($cur_ips[$client_ip_key]);
		}

		if ( !$isbanned and isset($settings['block_if_10min']) and is_numeric($settings['block_if_10min']) and $settings['block_if_10min'] > 50 ) {
			$allcount = 0;
			$cur_qty = $cur_minute;
			for ($i = 0; $i < 10; $i++) {
				if ( isset($cur_ips[$client_ip_key][$cur_qty]) and is_numeric($cur_ips[$client_ip_key][$cur_qty]) and $cur_ips[$client_ip_key][$cur_qty] > 0 ) {
					$allcount += $cur_ips[$client_ip_key][$cur_qty];
				}
				$cur_qty--;
				if ( $cur_qty < 0 ) {
					$cur_qty = 59;
				}
			}
			if ( $allcount > $settings['block_if_10min'] ) {
				$isbanned = 2;
				unset($cur_ips[$client_ip_key]);
				_nohackme_defender_hackme_banned_ip($client_ip, $isbanned, $allcount);
			}
		}

		if ( !$isbanned and isset($settings['block_if_50min']) and is_numeric($settings['block_if_50min']) and $settings['block_if_50min'] > 100 ) {
			$allcount = 0;
			$cur_qty = $cur_minute;
			for ($i = 0; $i < 50; $i++) {
				if ( isset($cur_ips[$client_ip_key][$cur_qty]) and is_numeric($cur_ips[$client_ip_key][$cur_qty]) and $cur_ips[$client_ip_key][$cur_qty] > 0 ) {
					$allcount += $cur_ips[$client_ip_key][$cur_qty];
				}
				$cur_qty--;
				if ( $cur_qty < 0 ) {
					$cur_qty = 59;
				}
			}
			if ( $allcount > $settings['block_if_50min'] ) {
				$isbanned = 3;
				unset($cur_ips[$client_ip_key]);
				_nohackme_defender_hackme_banned_ip($client_ip, $isbanned, $allcount);
			}
		}

		ftruncate($fp, 0);
		rewind($fp);
		fwrite($fp, serialize($cur_ips));
		fflush($fp);
		@flock($fp, LOCK_UN);
	}

	fclose($fp);
	return $isbanned;
}
function _nohackme_defender_hackme_normalize_ip($ip) {
	$ip = trim((string) $ip);
	if ( !strlen($ip) ) {
		return '';
	}
	$ip = _nohackme_defender_sanitize_text_field($ip);
	if ( filter_var($ip, FILTER_VALIDATE_IP) === false ) {
		return '';
	}
	$packed = @inet_pton($ip);
	if ( $packed !== false ) {
		$ip = inet_ntop($packed);
	}
	return strtolower($ip);
}
function _nohackme_defender_hackme_get_ip_storage_key($ip) {
	$ip = _nohackme_defender_hackme_normalize_ip($ip);
	if ( !strlen($ip) ) {
		return '';
	}
	if ( filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ) {
		$packed = @inet_pton($ip);
		if ( $packed === false ) {
			return '';
		}
		return 'ipv6_' . bin2hex($packed);
	}
	return $ip;
}
function _nohackme_defender_hackme_get_ip_from_storage_key($storage_key) {
	$storage_key = trim((string) $storage_key);
	if ( strpos($storage_key, 'ipv6_') === 0 ) {
		$hex = substr($storage_key, 5);
		if ( ctype_xdigit($hex) && strlen($hex) === 32 ) {
			$packed = @hex2bin($hex);
			if ( $packed !== false ) {
				$ip = @inet_ntop($packed);
				if ( $ip !== false ) {
					return strtolower($ip);
				}
			}
		}
		return '';
	}
	return _nohackme_defender_hackme_normalize_ip($storage_key);
}
function _nohackme_defender_hackme_banned_ip($client_ip, $reason, $data = ''){
	global $hackme_path;
	$storage_key = _nohackme_defender_hackme_get_ip_storage_key($client_ip);
	if ( !strlen($storage_key) ) {
		return;
	}
	_nohackme_defender_hackme_write_serialized_file($hackme_path . $storage_key, array($reason, $data));
}
function _hackme_ip_in_range($ip, $range) {
$ip = _nohackme_defender_hackme_normalize_ip($ip);
$range = trim((string) $range);
if ( !strlen($ip) || !strlen($range) ) {
	return false;
}

if ( strpos($range, '/') === false ) {
	return $ip === _nohackme_defender_hackme_normalize_ip($range);
}

list($subnet, $prefix_length) = explode('/', $range, 2);
$subnet = _nohackme_defender_hackme_normalize_ip($subnet);
$prefix_length = (int) $prefix_length;
if ( !strlen($subnet) ) {
	return false;
}

$packed_ip = @inet_pton($ip);
$packed_subnet = @inet_pton($subnet);
if ( $packed_ip === false || $packed_subnet === false || strlen($packed_ip) !== strlen($packed_subnet) ) {
	return false;
}

$max_bits = strlen($packed_ip) * 8;
if ( $prefix_length < 0 || $prefix_length > $max_bits ) {
	return false;
}

$full_bytes = intdiv($prefix_length, 8);
$remaining_bits = $prefix_length % 8;

if ( $full_bytes > 0 && substr($packed_ip, 0, $full_bytes) !== substr($packed_subnet, 0, $full_bytes) ) {
	return false;
}

if ( $remaining_bits === 0 ) {
	return true;
}

$mask = (0xFF << (8 - $remaining_bits)) & 0xFF;
return (ord($packed_ip[$full_bytes]) & $mask) === (ord($packed_subnet[$full_bytes]) & $mask);
}
function _nohackme_defender_hackme_isGoogleIp($ip) {
	global $settings_path;

	$google_ips = array();
	$hacks_list_file = $settings_path . 'robots_google_list';
	if (file_exists($hacks_list_file)) {
		$google_ips = file($hacks_list_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}

foreach ($google_ips as $range) {
if (_hackme_ip_in_range($ip, $range)) {
return true;
}
}

return false;
}
	function _nohackme_defender_hackme_isYandexIp($ip) {
		global $settings_path;

	$yandex_ips = array();
	$hacks_list_file = $settings_path . 'robots_yandex_list';
	if (file_exists($hacks_list_file)) {
		$yandex_ips = file($hacks_list_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	}

foreach ($yandex_ips as $range) {
if (_hackme_ip_in_range($ip, $range)) {
return true;
}
}

	  return false;
	}
		function _nohackme_defender_hackme_sync_default_hacks($hacks_list_file, $default_hacks) {
			if ( !is_array($default_hacks) || !count($default_hacks) ) {
				return;
			}

		$current_hacks = file($hacks_list_file, FILE_IGNORE_NEW_LINES);
		if ( !is_array($current_hacks) ) {
			$current_hacks = array();
		}

		$known = array();
		foreach ( $current_hacks as $hack ) {
			$normalized = mb_strtolower(_nohackme_defender_hackme_pattern_line_value($hack));
			if ( strlen($normalized) ) {
				$known[$normalized] = true;
			}
		}

		$missing_hacks = array();
		foreach ( $default_hacks as $hack ) {
			$hack = trim((string) $hack);
			if ( !strlen($hack) ) {
				continue;
			}

			$normalized = mb_strtolower($hack);
			if ( isset($known[$normalized]) ) {
				continue;
			}

			$known[$normalized] = true;
			$missing_hacks[] = $hack;
		}

		if ( !count($missing_hacks) ) {
			return;
		}

		$prefix = count($current_hacks) ? PHP_EOL : '';
			@file_put_contents($hacks_list_file, $prefix . implode(PHP_EOL, $missing_hacks), FILE_APPEND | LOCK_EX);
		}

		function _nohackme_defender_hackme_disable_unsafe_payload_patterns($hacks_list_file) {
			if ( !is_file($hacks_list_file) ) {
				return;
			}

			$current_hacks = file($hacks_list_file, FILE_IGNORE_NEW_LINES);
			if ( !is_array($current_hacks) || !count($current_hacks) ) {
				return;
			}

			$changed = false;
			foreach ( $current_hacks as $index => $hack ) {
				if ( _nohackme_defender_hackme_is_disabled_pattern_line($hack) ) {
					continue;
				}

				$value = _nohackme_defender_hackme_pattern_line_value($hack);
				if ( _nohackme_defender_hackme_is_unsafe_payload_pattern($value) ) {
					$current_hacks[$index] = _nohackme_defender_hackme_disabled_pattern_prefix() . trim((string) $hack);
					$changed = true;
				}
			}

			if ( $changed ) {
				@file_put_contents($hacks_list_file, implode(PHP_EOL, $current_hacks), LOCK_EX);
			}
		}

		function _nohackme_defender_hackme_normalized_pattern_map($patterns) {
			$map = array();
			if ( is_array($patterns) ) {
				foreach ( $patterns as $pattern ) {
				$normalized = mb_strtolower(_nohackme_defender_hackme_pattern_line_value($pattern));
				if ( strlen($normalized) ) {
					$map[$normalized] = true;
				}
			}
		}

		return $map;
	}

	function _nohackme_defender_hackme_migrate_path_patterns($hacks_list_file, $paths_list_file, $path_defaults) {
		if ( !is_file($hacks_list_file) ) {
			return;
		}

		$current_hacks = file($hacks_list_file, FILE_IGNORE_NEW_LINES);
		if ( !is_array($current_hacks) || !count($current_hacks) ) {
			return;
		}

		$path_map = _nohackme_defender_hackme_normalized_pattern_map($path_defaults);
		$payload_hacks = array();
		$migrated_paths = array();
		foreach ( $current_hacks as $hack ) {
			$normalized = mb_strtolower(_nohackme_defender_hackme_pattern_line_value($hack));
			if ( !strlen($normalized) ) {
				continue;
			}
			if ( isset($path_map[$normalized]) ) {
				$migrated_paths[] = trim((string) $hack);
				continue;
			}
			$payload_hacks[] = trim((string) $hack);
		}

		if ( count($migrated_paths) ) {
			_nohackme_defender_hackme_sync_default_hacks($paths_list_file, $migrated_paths);
			@file_put_contents($hacks_list_file, implode(PHP_EOL, $payload_hacks), LOCK_EX);
		}
	}

	function _nohackme_defender_hackme_generate_defaults($settings_path) {
		$ahacks_def = _nohackme_defender_hackme_default_payload_patterns();
		$apaths_def = _nohackme_defender_hackme_default_sensitive_path_patterns();

		$hacks_list_file = $settings_path . 'hacks_list';
		if (!file_exists($hacks_list_file)) {
			$fp = fopen($settings_path . 'hacks_list' , 'w'); fwrite($fp, implode(PHP_EOL, $ahacks_def)); fclose($fp);
			} else {
				_nohackme_defender_hackme_sync_default_hacks($hacks_list_file, $ahacks_def);
			}
			_nohackme_defender_hackme_disable_unsafe_payload_patterns($hacks_list_file);

			$paths_list_file = $settings_path . 'suspicious_paths_list';
		if (!file_exists($paths_list_file)) {
			$fp = fopen($settings_path . 'suspicious_paths_list' , 'w'); fwrite($fp, implode(PHP_EOL, $apaths_def)); fclose($fp);
		} else {
			_nohackme_defender_hackme_sync_default_hacks($paths_list_file, $apaths_def);
		}
		_nohackme_defender_hackme_migrate_path_patterns($hacks_list_file, $paths_list_file, $apaths_def);

	$google_ips = array();
	$google_ips []= '64.233.160.0/19';
	$google_ips []= '66.249.80.0/20';
	$google_ips []= '72.14.192.0/18';

	$hacks_list_file = $settings_path . 'robots_google_list';
	if (!file_exists($hacks_list_file)) {
		$fp = fopen($settings_path . 'robots_google_list' , 'w'); fwrite($fp, implode(PHP_EOL, $google_ips)); fclose($fp);
	}

	$yandex_ips = array();
	$yandex_ips []= '5.45.192.0/18';
	$yandex_ips []= '5.255.192.0/18';
	$yandex_ips []= '37.9.64.0/18';
	$yandex_ips []= '37.140.128.0/18';
	$yandex_ips []= '77.88.0.0/18';
	$yandex_ips []= '84.252.160.0/19';
	$yandex_ips []= '87.250.224.0/19';
	$yandex_ips []= '90.156.176.0/22';
	$yandex_ips []= '93.158.128.0/18';
	$yandex_ips []= '95.108.128.0/17';
	$yandex_ips []= '141.8.128.0/18';
	$yandex_ips []= '178.154.128.0/18';
	$yandex_ips []= '185.32.187.0/24';
	$yandex_ips []= '213.180.192.0/19';

	$hacks_list_file = $settings_path . 'robots_yandex_list';
	if (!file_exists($hacks_list_file)) {
		$fp = fopen($settings_path . 'robots_yandex_list' , 'w'); fwrite($fp, implode(PHP_EOL, $yandex_ips)); fclose($fp);
	}

}
function _nohackme_defender_esc_html($text) {
	$text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = str_replace('&apos;', '&#039;', $text);
	return $text;
}
function _nohackme_defender_sanitize_text_field($str) {
	$str = strip_tags($str);
	$str = preg_replace('/[\r\n\t ]+/', ' ', $str);
	$str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
	$str = trim($str);
	if (!mb_detect_encoding($str, 'UTF-8', true)) {
		$str = utf8_encode($str);
	}

	return $str;
}
