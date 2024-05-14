<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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
_pdxnohackme_hackme_generate_defaults($settings_path);

$skip_check = false;
if ( isset($_SERVER['REQUEST_URI']) and strlen($_SERVER['REQUEST_URI']) ) {
	$check_str = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field($_SERVER['REQUEST_URI']));
	if ( strpos($check_str, '/wp-admin/') === false ) {} else {
		$skip_check = true;
	}
	if ( strpos($check_str, '/wp-admin/admin-ajax.php') === false ) {} else {
		$skip_check = true;
	}
}
if ( !$skip_check ) {

	$hackme_path = ABSPATH . 'wp-content/uploads/hackme_ban/';
	if ( !is_dir($hackme_path) ) {
		mkdir($hackme_path, 0777, true);
	}
//	$anotice_path = ABSPATH . 'wp-content/uploads/anotices/';
//	if ( !is_dir($anotice_path) ) {
//		mkdir($anotice_path, 0777, true);
//	}


	$hackme_string = '';
	$banned_string = 'Your request is temporarily blocked on this site. Try to come tomorrow.';

	$settings = array();
	if ( is_file($settings_path . 'settings') ) {
		$settings = @unserialize(file_get_contents($settings_path . 'settings'));
	}
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
				$exclude_ips [$tmpid]= str_replace('.', '', $tmpval);
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
			$current_user_agent = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field($_SERVER['HTTP_USER_AGENT']));
			foreach ($exclude_uas as $excluded_ua) {
				if (strpos($current_user_agent, $excluded_ua) !== false) {
					$skip_by_ua = true;
					break;
				}
			}
		}

		$client_ip = _pdxnohackme_hackme_get_ip();
		$check_client_ip = str_replace('.', '', $client_ip);
		if ( !$skip_by_ua and isset($settings['exclude_google_ips']) and is_numeric($settings['exclude_google_ips']) and $settings['exclude_google_ips'] == 1 ) {
			if ( _pdxnohackme_hackme_isGoogleIp($client_ip) ) {
				$skip_by_ua = true;
			}
		}

		if ( !$skip_by_ua and isset($settings['exclude_yandex_ips']) and is_numeric($settings['exclude_yandex_ips']) and $settings['exclude_yandex_ips'] == 1 ) {
			if ( _pdxnohackme_hackme_isYandexIp($client_ip) ) {
				$skip_by_ua = true;
			}
		}

		if ( $skip_by_ua or in_array($client_ip, $exclude_ips)) {
		   // disabled for this IP
		} else {
			if ( is_numeric($check_client_ip) and $check_client_ip > 0 ) {
				if ( is_file($hackme_path . $client_ip) ) {
					if ( isset($settings['cancel_block']) and strlen($settings['cancel_block']) and isset($_GET[$settings['cancel_block']]) ) {
						@unlink($hackme_path . $client_ip);
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
					$cur_ips = array();
					if ( is_file($settings_path . 'cur_ips_counters') ) {
						$cur_ips = @unserialize(file_get_contents($settings_path . 'cur_ips_counters'));
					}
					if (isset($cur_ips) and is_array($cur_ips) and count($cur_ips)) { } else {
						$cur_ips = array();
					}
					$cur_minute = intval(date('i', time()));
					if ( isset($cur_ips[$client_ip][$cur_minute]) and is_numeric($cur_ips[$client_ip][$cur_minute]) ) {
						$cur_ips[$client_ip][$cur_minute]++;
					} else {
						$cur_ips[$client_ip][$cur_minute]= 0;
					}

					if ( isset($settings['block_if_min']) and is_numeric($settings['block_if_min']) and $settings['block_if_min'] > 20 and $cur_ips[$client_ip][$cur_minute] > $settings['block_if_min'] ) {
						// banned, reason 1 minute
						$isbanned = 1;
						_pdxnohackme_hackme_banned_ip($client_ip, $isbanned, $cur_ips[$client_ip][$cur_minute]);
						unset($cur_ips[$client_ip]);
					}
					if ( !$isbanned and isset($settings['block_if_10min']) and is_numeric($settings['block_if_10min']) and $settings['block_if_10min'] > 50 ) {
						$allcount = 0;
						$cur_qty = $cur_minute;
						for ($i = 0; $i < 10; $i++) {
							if ( isset($cur_ips[$client_ip][$cur_qty]) and is_numeric($cur_ips[$client_ip][$cur_qty]) and $cur_ips[$client_ip][$cur_qty] > 0 ) {
								$allcount += $cur_ips[$client_ip][$cur_qty];
							}
							$cur_qty--;
							if ( $cur_qty < 0 ) {
								$cur_qty = 59;
							}
						}
						if ( $allcount > $settings['block_if_10min'] ) {
							// banned, reason 10 minutes
							unset($cur_ips[$client_ip]);
							$isbanned = 2;
							_pdxnohackme_hackme_banned_ip($client_ip, $isbanned, $allcount);
						}
					}
					if ( !$isbanned and isset($settings['block_if_50min']) and is_numeric($settings['block_if_50min']) and $settings['block_if_50min'] > 100 ) {
						$allcount = 0;
						$cur_qty = $cur_minute;
						for ($i = 0; $i < 50; $i++) {
							if ( isset($cur_ips[$client_ip][$cur_qty]) and is_numeric($cur_ips[$client_ip][$cur_qty]) and $cur_ips[$client_ip][$cur_qty] > 0 ) {
								$allcount += $cur_ips[$client_ip][$cur_qty];
							}
							$cur_qty--;
							if ( $cur_qty < 0 ) {
								$cur_qty = 59;
							}
						}
						if ( $allcount > $settings['block_if_50min'] ) {
							// banned, reason 60 minutes
							unset($cur_ips[$client_ip]);
							$isbanned = 3;
							_pdxnohackme_hackme_banned_ip($client_ip, $isbanned, $allcount);
						}
					}
					$fp = fopen($settings_path . 'cur_ips_counters' , 'w'); fwrite($fp, serialize($cur_ips)); fclose($fp);
				}
				if ( $isbanned ) {
					echo strip_tags($banned_string, '<b><i><br><p><ul><li><ol><strong><em><div>');
					exit();
				}
			}
		}

		$skip_check_hack = false;
		if ( isset($_SERVER['REQUEST_URI']) and strlen($_SERVER['REQUEST_URI']) ) {
			$check_str = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field($_SERVER['REQUEST_URI']));
			if ( strpos($check_str, '/wp-admin/') === false ) {} else {
				$skip_check_hack = true;
			}
		}


		$ahacks = array();
		$hacks_list_file = $settings_path . 'hacks_list';
		if (file_exists($hacks_list_file)) {
		  $ahacks = file($hacks_list_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		}

		$found_hack = false;
		if ( !$skip_check_hack and isset($ahacks) and is_array($ahacks) and count($ahacks) and isset($_REQUEST) and is_array($_REQUEST) and count($_REQUEST)) {
			$reqfound = '';
			$reqfound_for_save = '';
			if ( isset($_SERVER['QUERY_STRING']) and strlen($_SERVER['QUERY_STRING']) ) {
				$reqfound .= $_SERVER['QUERY_STRING'];
			}
			foreach ( $_REQUEST as $reqid => $reqval) {
				if ( is_array($reqval) ) {
					foreach ( $reqval as $subreqval) {
						if ( is_string($subreqval) and strlen($subreqval) ) {
							$reqfound .= $subreqval;
							$reqfound_for_save .= $subreqval;
						}
					}
				} else if ( is_string($reqval) and strlen($reqval) ) {
					$reqfound .= $reqval;
					$reqfound_for_save .= $reqval;
				}
			}
			if ( strlen($reqfound) ) {
				$reqfound = _pdxnohackme_esc_html(mb_strtolower($reqfound));
				$reqfound_for_save = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field(mb_strtolower($reqfound_for_save)));
				foreach ( $ahacks as $ahacks_item) {
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
			if ( is_numeric($check_client_ip) and $check_client_ip > 0 ) {
				_pdxnohackme_hackme_banned_ip($client_ip, $isbanned, $hackme_string);
				exit();
			}
		}
	}
}

function _pdxnohackme_hackme_get_ip(){
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])){
        $ip = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field($_SERVER['HTTP_CLIENT_IP']));
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']));
    } else {
        $ip = _pdxnohackme_esc_html(_pdxnohackme_sanitize_text_field($_SERVER['REMOTE_ADDR']));
    }
    $ip = preg_replace('/[^0-9.]/', '', $ip);
    return $ip;
}
function _pdxnohackme_hackme_banned_ip($client_ip, $reason, $data = ''){
	global $hackme_path;
	$fp = fopen($hackme_path . $client_ip , 'w'); fwrite($fp, serialize(array($reason, $data))); fclose($fp);
}
function _hackme_ip_in_range($ip, $range) {
  [$range, $netmask] = explode('/', $range, 2);
  $range_decimal = ip2long($range);
  $ip_decimal = ip2long($ip);
  $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
  $netmask_decimal = ~ $wildcard_decimal;

  return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}
function _pdxnohackme_hackme_isGoogleIp($ip) {
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
function _pdxnohackme_hackme_isYandexIp($ip) {
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
function _pdxnohackme_hackme_generate_defaults($settings_path) {
	$ahacks_def = array();
	$ahacks_def []= '&#039;&quot;';
	$ahacks_def []= '&quot;&#039;';
	$ahacks_def []= '\&#039;\&quot;';
	$ahacks_def []= '\&quot;\&#039;';
	$ahacks_def []= 'chr(';
	$ahacks_def []= '(select ';
	$ahacks_def []= '&#039;;';
	$ahacks_def []= '/*&#039;';
	$ahacks_def []= '*/';
	$ahacks_def []= ')from(';
	$ahacks_def []= '0\&quot;';
	$ahacks_def []= '0\&#039;';
	$ahacks_def []= '))))))))))))))';
	$ahacks_def []= '.concat(';
	$ahacks_def []= 'socket.gethost';
	$ahacks_def []= '&#039;+&#039;';
	$ahacks_def []= '${';
	$ahacks_def []= '%00';
	$ahacks_def []= 'base64_decode(';
	$ahacks_def []= '/etc/p';
	$ahacks_def []= 'nslookup ';
	$ahacks_def []= ')&amp;(';
	$ahacks_def []= '../../';
	$ahacks_def []= '.write(';
	$ahacks_def []= '$()';
	$ahacks_def []= '&#039; and ';
	$ahacks_def []= '&quot; and ';
	$ahacks_def []= 'php://input';
	$ahacks_def []= 'expect://';
	$ahacks_def []= 'xp_cmdshell';
	$ahacks_def []= 'union select';
	$ahacks_def []= '&lt;script&gt;';
	$ahacks_def []= '&lt;iframe&gt;';
	$ahacks_def []= 'javascript:';
	$ahacks_def []= 'document.cookie';
	$ahacks_def []= 'cmd.exe';
	$ahacks_def []= '/bin/bash';
	$ahacks_def []= 'alert(';
	$ahacks_def []= 'onmouseover=';

	$hacks_list_file = $settings_path . 'hacks_list';
	if (!file_exists($hacks_list_file)) {
		$fp = fopen($settings_path . 'hacks_list' , 'w'); fwrite($fp, implode(PHP_EOL, $ahacks_def)); fclose($fp);
	}

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
function _pdxnohackme_esc_html($text) {
	$text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$text = str_replace('&apos;', '&#039;', $text);
	return $text;
}
function _pdxnohackme_sanitize_text_field($str) {
	$str = strip_tags($str);
	$str = preg_replace('/[\r\n\t ]+/', ' ', $str);
	$str = preg_replace('/[\x00-\x1F\x7F]/u', '', $str);
	$str = trim($str);
	if (!mb_detect_encoding($str, 'UTF-8', true)) {
		$str = utf8_encode($str);
	}

	return $str;
}
