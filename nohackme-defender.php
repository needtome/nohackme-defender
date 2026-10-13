<?php
/**
 * Plugin Name: NoHackMe Defender
 * Plugin URI: https://needtome.com/nohackme/
 * Description: NoHackMe Defender ensures your WordPress site's safety by blocking IP addresses upon receiving suspicious requests or too many requests from a single IP in a certain period. Keep hackers at bay with efficient real-time monitoring and security enforcement. Sponsors of plugin development: <a href="https://malinovsky.io">malinovsky.io</a>, <a href="https://gloap.net">gloap.net</a>, <a href="https://gloapm.com">gloapm.com</a>, <a href="https://o-keto.com">o-keto.com</a>
 * Author: Parad0x <paraz0n3@gmail.com>
 * Version: 1.1.10
 * Text Domain: nohackme-defender
 * Domain Path: /languages/
 * Author URI: https://www.linkedin.com/in/roman-klymenko-parad0x/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Keep the PDXGlobal AJAX callback names used by the shared site integration.

$nohackme_defender_upload_dir = wp_upload_dir();
define('NOHACKME_DEFENDER_NAME', 'NoHackMe Defender');
define('NOHACKME_DEFENDER_BANNED_PATH', $nohackme_defender_upload_dir['basedir'] . '/hackme_ban/');
define('NOHACKME_DEFENDER_SETTINGS_PATH', $nohackme_defender_upload_dir['basedir'] . '/nohackme/');
define('NOHACKME_DEFENDER_GEO_PATH', trailingslashit($nohackme_defender_upload_dir['basedir']) . 'nohackme-defender/geo/');
define('NOHACKME_DEFENDER_PLUGIN_PATH', plugin_dir_path(__FILE__));

register_activation_hook( __FILE__, 'nohackme_defender_activation' );
register_deactivation_hook( __FILE__, 'nohackme_defender_deactivation' );

function nohackme_defender_activation(){
	$default_options = get_option('nohackme_defender_options');
	if ( isset($default_options['block_msg']) and strlen($default_options['block_msg']) ) {
	} else {
		$default_options = array();
		$default_options['block_msg']= 'Your request is temporarily blocked on this site. Try to come tomorrow.';
		$default_options['block_time']= 1;
		$default_options['block_if_min']= 300;
		$default_options['block_if_10min']= 1500;
		$default_options['block_if_50min']= 4000;
	}
	$default_options['activation_time']= time();
	update_option('nohackme_defender_options', $default_options);


	$path_to_wp_config = ABSPATH . 'wp-config.php';
	$config_contents = _pdxglobal_get_file_via_wpfs($path_to_wp_config);

	$insert_code = PHP_EOL . "if ( is_file('" . esc_html(NOHACKME_DEFENDER_PLUGIN_PATH) . "nohackme.php') ) { require_once( '" . esc_html(NOHACKME_DEFENDER_PLUGIN_PATH) . "nohackme.php' ); }" . PHP_EOL;

	if (strpos($config_contents, $insert_code) === false) {
		$config_contents = preg_replace("/<\?php/", "<?php" . PHP_EOL . $insert_code, $config_contents, 1);
		ob_start();
		_pdxglobal_update_file_via_wpfs($path_to_wp_config, $config_contents);
		ob_end_clean();
	}

  	// cron task activation
	if (!wp_next_scheduled('nohackme_defender_daily_event')) {
    	wp_schedule_event(time(), 'daily', 'nohackme_defender_daily_event');
  	}

	// creating folders
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	WP_Filesystem();
	global $wp_filesystem;
	$htaccess_content = "Options -Indexes\nOrder allow,deny\nDeny from all";

	if (!is_dir(NOHACKME_DEFENDER_BANNED_PATH)) {
	    wp_mkdir_p(NOHACKME_DEFENDER_BANNED_PATH);
	}
	if (!file_exists(NOHACKME_DEFENDER_BANNED_PATH . '/.htaccess')) {
		$wp_filesystem->put_contents(NOHACKME_DEFENDER_BANNED_PATH . '/.htaccess', $htaccess_content, FS_CHMOD_FILE);
	}

	$save_path = NOHACKME_DEFENDER_SETTINGS_PATH;
	if (!is_dir($save_path)) {
	    wp_mkdir_p($save_path);
	}
	if (!file_exists($save_path . '/.htaccess')) {
		$wp_filesystem->put_contents($save_path . '/.htaccess', $htaccess_content, FS_CHMOD_FILE);
	}
  	_pdxglobal_update_file_via_wpfs($save_path . 'settings', $default_options);
}
function nohackme_defender_deactivation(){
	$path_to_wp_config = ABSPATH . 'wp-config.php';
	$config_contents = _pdxglobal_get_file_via_wpfs($path_to_wp_config);

	$insert_code = PHP_EOL . "if ( is_file('" . esc_html(NOHACKME_DEFENDER_PLUGIN_PATH) . "nohackme.php') ) { require_once( '" . esc_html(NOHACKME_DEFENDER_PLUGIN_PATH) . "nohackme.php' ); }" . PHP_EOL;

	// Remove the code if it exists
	$config_contents = str_replace($insert_code, "", $config_contents);
	$config_contents = str_replace('<?php' . PHP_EOL . PHP_EOL, '<?php' . PHP_EOL, $config_contents);
	$config_contents = str_replace('<?php' . PHP_EOL . PHP_EOL, '<?php' . PHP_EOL, $config_contents);
	_pdxglobal_update_file_via_wpfs($path_to_wp_config, $config_contents);


	// deactivation of cron task
	$timestamp = wp_next_scheduled('nohackme_defender_daily_event');
  if ($timestamp) {
    wp_unschedule_event($timestamp, 'nohackme_defender_daily_event');
  }

  // remove blocked IP
	if (is_dir(NOHACKME_DEFENDER_BANNED_PATH)) {
    $objects = scandir(NOHACKME_DEFENDER_BANNED_PATH);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        @wp_delete_file(NOHACKME_DEFENDER_BANNED_PATH . $object);  // Delete files inside folder
      }
    }
  }

}

// add function to the specified hook
add_action( 'nohackme_defender_daily_event', 'nohackme_defender_do_daily_event' );
function nohackme_defender_do_daily_event(){

	$default_options = get_option('nohackme_defender_options');
	_pdxglobal_check_new_messages($default_options);

	if ( isset($default_options['auto_update_google_ips_daily']) && intval($default_options['auto_update_google_ips_daily']) === 1 ) {
		$google_ranges = _nohackme_defender_fetch_google_ip_ranges();
		if ( !is_wp_error($google_ranges) && is_array($google_ranges) && count($google_ranges) ) {
			_nohackme_defender_save_ranges_file(NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_google_list', $google_ranges);
		}
	}

	if ( isset($default_options['auto_update_yandex_ips_daily']) && intval($default_options['auto_update_yandex_ips_daily']) === 1 ) {
		$yandex_ranges = _nohackme_defender_fetch_yandex_ip_ranges();
		if ( !is_wp_error($yandex_ranges) && is_array($yandex_ranges) && count($yandex_ranges) ) {
			_nohackme_defender_save_ranges_file(NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_yandex_list', $yandex_ranges);
		}
	}

	$upload_dir = wp_upload_dir();
	$hackme_path = $upload_dir['basedir'] . '/hackme_ban/';
	$nohackme_defender_options = get_option( 'nohackme_defender_options' );
	if ( isset($nohackme_defender_options['block_time']) and is_numeric($nohackme_defender_options['block_time']) and $nohackme_defender_options['block_time'] > 0 ) {} else {
		$nohackme_defender_options['block_time'] = 1;
	}
	$hackme_time = time() - (86400*$nohackme_defender_options['block_time']);

	if ( is_dir($hackme_path) ) {
		$files = scandir($hackme_path, SCANDIR_SORT_NONE);
		if (isset($files) and is_array($files) and count($files)) { foreach ($files as $file) {
			if ( $file == '.' ) continue;
			if ( $file == '..' ) continue;
			if ( !strlen(_nohackme_defender_formatIp($file)) ) continue;

			if ( filectime($hackme_path . $file) <= $hackme_time ) {
				@wp_delete_file($hackme_path . $file);
			}
		} }
	}

 // removing counters to prevent excessive growth
	$settings_path = NOHACKME_DEFENDER_SETTINGS_PATH;
	if ( is_file($settings_path . 'cur_ips_counters') ) {
		@wp_delete_file($settings_path . 'cur_ips_counters');
	}

}

function nohackme_defender_admin_menu_logs(){
	$block_qty = _nohackme_defender_get_blocks_qty();
  	echo '<div class="wrap"><h1>' . esc_html__('Blocked IP', 'nohackme-defender') . ' (' . esc_html($block_qty) . ')</h1>';
	nohackme_defender_admin_menu_logs_output();
	echo '</div>';
}
function nohackme_defender_admin_menu_logs_output () {
	$SxGeo = _nohackme_defender_get_geo();
	if ( is_string($SxGeo) ) {
		echo '<div class="pdxhighlight">';
		echo wp_kses_post($SxGeo);
		echo '</div>';
	}

	if ( current_user_can('manage_options') && isset($_POST['nohackme_defender_clean_all']) && isset($_POST['nohackme_defender_clean_all_nonce_field']) ) {
	    $nonce = sanitize_text_field(wp_unslash($_POST['nohackme_defender_clean_all_nonce_field']));
	    if ( wp_verify_nonce($nonce, 'nohackme_defender_clean_all_action') ) {
			$unlocked_qty = nohackme_defender_unlock_all_ips();
			echo '<div id="message" class="notice notice-success is-dismissible"><p>' . esc_html__('All blocked IP addresses were unlocked.', 'nohackme-defender') . ' ' . esc_html__('Deleted:', 'nohackme-defender') . ' ' . intval($unlocked_qty) . '</p></div>';
		}
	}

	if ( current_user_can('manage_options') && isset($_REQUEST['clean']) && isset($_REQUEST['nohackme_defender_clean_nonce_field']) ) {
	    $nonce = sanitize_text_field(wp_unslash($_REQUEST['nohackme_defender_clean_nonce_field']));
	    if ( wp_verify_nonce($nonce, 'nohackme_defender_clean_action') ) {
			$clean_input = sanitize_text_field(wp_unslash($_REQUEST['clean']));
	        $clean_ip = _nohackme_defender_formatIp($clean_input);
	        if ( strlen($clean_ip) ) {
	            $storage_key = ( filter_var($clean_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false )
	            	? 'ipv6_' . bin2hex(inet_pton($clean_ip))
	            	: $clean_ip;
	            if ( is_file(NOHACKME_DEFENDER_BANNED_PATH . $storage_key) ) {
	                @wp_delete_file(NOHACKME_DEFENDER_BANNED_PATH . $storage_key);
	            }
	            echo '<p>' . esc_html__('IP unlocked', 'nohackme-defender') . '</p>';
	        }
	    }
	}
	$block_qty = _nohackme_defender_get_blocks_qty();
	echo '<form method="post" action="" style="margin: 12px 0;">';
	wp_nonce_field('nohackme_defender_clean_all_action', 'nohackme_defender_clean_all_nonce_field');
	echo '<input type="hidden" name="nohackme_defender_clean_all" value="1">';
	echo '<button type="submit" class="button button-primary" onclick="return confirm(\'' . esc_js(__('Unlock all blocked IP addresses?', 'nohackme-defender')) . '\');">' . esc_html__('Разблокировать всех', 'nohackme-defender') . '</button>';
	echo '</form>';

	if ( $block_qty > 0 ) {
		$aips = array();
		$files = scandir(NOHACKME_DEFENDER_BANNED_PATH, SCANDIR_SORT_NONE);
	  if (isset($files) and is_array($files) and count($files)) { foreach ( $files as $file) {
		    if ( $file == '.' ) continue;
		    if ( $file == '..' ) continue;
			$formatted_ip = _nohackme_defender_formatIp($file);
			if ( !strlen($formatted_ip) ) continue;

		    $aips [$formatted_ip]= filectime(NOHACKME_DEFENDER_BANNED_PATH . $file);
	  } }

		if (isset($aips) and is_array($aips) and count($aips)) {
			echo '<table border="1" cellpadding="7">';
   		echo '<tr><th>' . esc_html__('IP', 'nohackme-defender') . '</th><th>' . esc_html__('Start of blocking', 'nohackme-defender') . '</th><th>' . esc_html__('User country', 'nohackme-defender') . '</th><th>' . esc_html__('Blocking reason', 'nohackme-defender') . '</th><th>' . esc_html__('Actions', 'nohackme-defender') . '</th></tr>';
			foreach ( $aips as $ip => $start) {
				echo '<tr>';
				echo '<td>';
				$newip = _nohackme_defender_formatIp($ip);
				echo esc_html($newip);
				echo '</td>';
				echo '<td>';
				echo esc_html(gmdate('Y.m.d H:i:s', $start));
				echo '</td>';
				echo '<td>';
				$country_code = '';
				if ( !is_string($SxGeo) ) {
					$country_code = $SxGeo->getCountry($newip);
				}
				if ( !strlen($country_code) ) {
					$country_code .= 'unknown';
				}
				echo esc_html($country_code);
				echo '</td>';
				echo '<td>';
					$storage_key = ( filter_var($newip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false )
						? 'ipv6_' . bin2hex(inet_pton($newip))
						: $newip;
					$reason = @unserialize(_pdxglobal_get_file_via_wpfs(NOHACKME_DEFENDER_BANNED_PATH . $storage_key));
				if (isset($reason) and is_array($reason) and count($reason) > 1) {
					switch ($reason[0]) {
						case 1:
       						echo '<p>' . esc_html__('Exceeding the number of requests per minute', 'nohackme-defender') . '</p>';
							break;
						case 2:
       						echo '<p>' . esc_html__('Exceeding the number of requests in 10 minutes', 'nohackme-defender') . '</p>';
							break;
						case 3:
       						echo '<p>' . esc_html__('Exceeding the number of requests for 50 minutes', 'nohackme-defender') . '</p>';
							break;
						case 4:
       						echo '<p>' . esc_html__('Suspicious data in requests', 'nohackme-defender') . '</p>';
							break;
					}
					if ( isset($reason[1]) and strlen($reason[1]) ) {
						switch ($reason[0]) {
							case 1:
							case 2:
							case 3:
        						echo '<p>' . esc_html__('Counter at the time of exceeding:', 'nohackme-defender') . ' ' . intval($reason[1]) . '</p>';
								break;
							case 4:
        						echo '<p>' . esc_html__('Suspicious data:', 'nohackme-defender') . '<br><textarea rows="3" cols="33">' . esc_attr(html_entity_decode($reason[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</textarea></p>';
								break;
							default:
        						echo '<p>' . esc_html__('Data:', 'nohackme-defender') . '<br><textarea rows="3" cols="33">' . esc_attr(html_entity_decode($reason[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</textarea></p>';
						}
					}
				} else {
					echo '-';
				}

				echo '</td>';
				echo '<td>';
    			echo '<form action="" method="post">';
				wp_nonce_field('nohackme_defender_clean_action', 'nohackme_defender_clean_nonce_field');
				echo '<input type="hidden" value="' . esc_attr($newip) . '" name="clean" />';
				echo '<input class="isbtn isbtn_theme_sm isbtn_theme_blue" type="submit" value="' . esc_html__('Delete', 'nohackme-defender') . '" />';
				echo '</form>';
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}

	} else {
  		echo '<p>' . esc_html__('Everything is fine. No one is blocked', 'nohackme-defender') . '</p>';
	}
	echo '<hr>';
	echo '<p>' . esc_html__('If you want to check if the plugin will block suspicious data, click this button:', 'nohackme-defender') . '</p>';
	echo '<p><a href="/qweqewqe?wer=' . urlencode('php://input') . '" target="_blank" class="button button-primary">' . esc_html__('Block me', 'nohackme-defender') . '</a></p>';
}

function nohackme_defender_unlock_all_ips() {
	$unlocked_qty = 0;
	if ( !is_dir(NOHACKME_DEFENDER_BANNED_PATH) ) {
		return $unlocked_qty;
	}

	$files = scandir(NOHACKME_DEFENDER_BANNED_PATH, SCANDIR_SORT_NONE);
	if ( !is_array($files) ) {
		return $unlocked_qty;
	}

	foreach ( $files as $file ) {
		if ( $file === '.' || $file === '..' || $file === '.htaccess' ) {
			continue;
		}

		$path = NOHACKME_DEFENDER_BANNED_PATH . $file;
		if ( is_file($path) && @wp_delete_file($path) ) {
			$unlocked_qty++;
		}
	}

	return $unlocked_qty;
}

function nohackme_defender_admin_menu_hacklist(){
	echo '<div class="wrap"><h1>' . esc_html__('Блокировка по содержимому запросов', 'nohackme-defender') . '</h1>';
	echo '<p class="description">' . esc_html__('Проверяются URL, query string, параметры запроса, User-Agent и raw body API-запросов.', 'nohackme-defender') . '</p>';

	$has_premium = _nohackme_defender_check_premium();
	if ( !$has_premium ) {
		nohackme_defender_output_paid_list_notice();
	}
	$file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'hacks_list';

	if ( function_exists('_nohackme_defender_hackme_generate_defaults') ) {
		_nohackme_defender_hackme_generate_defaults(NOHACKME_DEFENDER_SETTINGS_PATH);
	}

	if ( nohackme_defender_is_post_request() && check_admin_referer('nohackme_defender_hacklist_action', 'nohackme_defender_hacklist_nonce_field')) {
		$posted_hacks = nohackme_defender_get_posted_array('hacks');
		$posted_hacks_active = array_map('absint', nohackme_defender_get_posted_array('hacks_active'));
		$stripped_hacks = $posted_hacks;
		$sanitized_hacks = array_map('esc_html', $stripped_hacks);
		$hacks = nohackme_defender_prepare_pattern_lines($sanitized_hacks, $posted_hacks_active);

		$nohackme_defender_options = nohackme_defender_get_options_array();
		$nohackme_defender_options['disable_hack_payloads'] = isset($_POST['disable_hack_payloads']) ? 1 : 0;

		if ( !$has_premium ) {
			echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Working with the list is only available in the paid version!', 'nohackme-defender') . '</p></div>';
		} else {
			_nohackme_defender_premium1($file_path, $hacks);
		}
		nohackme_defender_save_runtime_options($nohackme_defender_options);
	}

	if(file_exists($file_path) && is_readable($file_path)){
		$hacks = nohackme_defender_read_pattern_rows($file_path);
		$nohackme_defender_options = nohackme_defender_get_options_array();

		echo '<form method="post" action="">';
		wp_nonce_field('nohackme_defender_hacklist_action', 'nohackme_defender_hacklist_nonce_field');
		echo '<p><label><input type="checkbox" name="disable_hack_payloads" value="1"' . checked(isset($nohackme_defender_options['disable_hack_payloads']) ? intval($nohackme_defender_options['disable_hack_payloads']) : 0, 1, false) . '> ' . esc_html__('Временно отключить защиту', 'nohackme-defender') . '</label></p>';
		echo '<table class="table__pdxglobal" id="nohackme_payload_patterns_table">';
		echo '<tr><th>№</th><th>' . esc_html__('Active', 'nohackme-defender') . '</th><th>' . esc_html__('Value', 'nohackme-defender') . '</th><th>' . esc_html__('Test', 'nohackme-defender') . '</th><th>' . esc_html__('Actions', 'nohackme-defender') . '</th></tr>';
		foreach($hacks as $index => $row){
			$hack = $row['value'];
			echo '<tr>';
			echo '<td class="counter">'.intval($index + 1).'</td>';
			echo '<td><input type="checkbox" name="hacks_active[' . intval($index) . ']" value="1"' . checked(!empty($row['active']) ? 1 : 0, 1, false) . '></td>';
			echo '<td><input type="text" name="hacks[' . intval($index) . ']" value="' . esc_attr($hack) . '" style="width:100%;"/></td>';
			echo '<td><a href="/qweqewqe?wer=' . urlencode($hack) . '" target="_blank" class="button">' . esc_html__('Block me', 'nohackme-defender') . '</a></td>';
			echo '<td><button type="button" class="button nohackme-delete-row">' . esc_html__('Delete', 'nohackme-defender') . '</button></td>';
			echo '</tr>';
		}
		echo '</table>';
		nohackme_defender_output_list_actions('nohackme_payload_patterns_table', 'hacks', 'nohackme_defender_restore_defaults_action');
		echo '</form>';
		nohackme_defender_output_list_scripts('nohackme_defender_restore_defaults', 'restoreDefaults');
	} else {
		echo '<p>' . esc_html__('List of suspicious requests not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function nohackme_defender_admin_menu_pathlist(){
	echo '<div class="wrap"><h1>' . esc_html__('Блокировка по URL, к которым обращаются', 'nohackme-defender') . '</h1>';
	echo '<p class="description">' . esc_html__('Проверяются отдельные URL/path-шаблоны: чувствительные файлы, чужие CMS, панели администрирования и служебные endpoints.', 'nohackme-defender') . '</p>';

	$has_premium = _nohackme_defender_check_premium();
	if ( !$has_premium ) {
		nohackme_defender_output_paid_list_notice();
	}
	$file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'suspicious_paths_list';

	if ( function_exists('_nohackme_defender_hackme_generate_defaults') ) {
		_nohackme_defender_hackme_generate_defaults(NOHACKME_DEFENDER_SETTINGS_PATH);
	}

	if ( nohackme_defender_is_post_request() && check_admin_referer('nohackme_defender_pathlist_action', 'nohackme_defender_pathlist_nonce_field')) {
		$posted_paths = nohackme_defender_get_posted_array('paths');
		$posted_paths_active = array_map('absint', nohackme_defender_get_posted_array('paths_active'));
		$stripped_paths = $posted_paths;
		$sanitized_paths = array_map('esc_html', $stripped_paths);
		$paths = nohackme_defender_prepare_pattern_lines($sanitized_paths, $posted_paths_active);

		$nohackme_defender_options = nohackme_defender_get_options_array();
		$nohackme_defender_options['disable_suspicious_paths'] = isset($_POST['disable_suspicious_paths']) ? 1 : 0;

		if ( !$has_premium ) {
			echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Working with the list is only available in the paid version!', 'nohackme-defender') . '</p></div>';
		} else {
			_nohackme_defender_premium1($file_path, $paths);
		}
		nohackme_defender_save_runtime_options($nohackme_defender_options);
	}

	if(file_exists($file_path) && is_readable($file_path)){
		$paths = nohackme_defender_read_pattern_rows($file_path);
		$nohackme_defender_options = nohackme_defender_get_options_array();

		echo '<form method="post" action="">';
		wp_nonce_field('nohackme_defender_pathlist_action', 'nohackme_defender_pathlist_nonce_field');
		echo '<p><label><input type="checkbox" name="disable_suspicious_paths" value="1"' . checked(isset($nohackme_defender_options['disable_suspicious_paths']) ? intval($nohackme_defender_options['disable_suspicious_paths']) : 0, 1, false) . '> ' . esc_html__('Временно отключить защиту', 'nohackme-defender') . '</label></p>';
		echo '<table class="table__pdxglobal" id="nohackme_path_patterns_table">';
		echo '<tr><th>№</th><th>' . esc_html__('Active', 'nohackme-defender') . '</th><th>' . esc_html__('Value', 'nohackme-defender') . '</th><th>' . esc_html__('Test', 'nohackme-defender') . '</th><th>' . esc_html__('Actions', 'nohackme-defender') . '</th></tr>';
		foreach($paths as $index => $row){
			$path = $row['value'];
			$test_path = trim((string) $path);
			if ( strlen($test_path) && $test_path[0] !== '/' ) {
				$test_path = '/' . $test_path;
			}
			echo '<tr>';
			echo '<td class="counter">'.intval($index + 1).'</td>';
			echo '<td><input type="checkbox" name="paths_active[' . intval($index) . ']" value="1"' . checked(!empty($row['active']) ? 1 : 0, 1, false) . '></td>';
			echo '<td><input type="text" name="paths[' . intval($index) . ']" value="' . esc_attr($path) . '" style="width:100%;"/></td>';
			echo '<td><a href="' . esc_url(home_url($test_path)) . '" target="_blank" class="button">' . esc_html__('Block me', 'nohackme-defender') . '</a></td>';
			echo '<td><button type="button" class="button nohackme-delete-row">' . esc_html__('Delete', 'nohackme-defender') . '</button></td>';
			echo '</tr>';
		}
		echo '</table>';
		nohackme_defender_output_list_actions('nohackme_path_patterns_table', 'paths', 'nohackme_defender_restore_paths_defaults_action');
		echo '</form>';
		nohackme_defender_output_list_scripts('nohackme_defender_restore_paths_defaults', 'restoreDefaults');
	} else {
		echo '<p>' . esc_html__('List of suspicious URL paths not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function nohackme_defender_get_options_array() {
	$options = get_option('nohackme_defender_options');
	return is_array($options) ? $options : array();
}

function nohackme_defender_save_runtime_options($options) {
	update_option('nohackme_defender_options', $options);
	_pdxglobal_update_file_via_wpfs(NOHACKME_DEFENDER_SETTINGS_PATH . 'settings', $options);
}

function nohackme_defender_disabled_pattern_prefix() {
	return '# nohackme-disabled: ';
}

function nohackme_defender_pattern_line_value($line) {
	$line = trim((string) $line);
	$prefix = nohackme_defender_disabled_pattern_prefix();
	if ( strpos($line, $prefix) === 0 ) {
		$line = trim(substr($line, strlen($prefix)));
	}

	return $line;
}

function nohackme_defender_is_disabled_pattern_line($line) {
	return strpos(trim((string) $line), nohackme_defender_disabled_pattern_prefix()) === 0;
}

function nohackme_defender_read_pattern_rows($file_path) {
	$rows = array();
	$lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	if ( !is_array($lines) ) {
		return $rows;
	}

	foreach ( $lines as $line ) {
		$value = nohackme_defender_pattern_line_value($line);
		if ( !strlen($value) ) {
			continue;
		}
		$rows[] = array(
			'value' => $value,
			'active' => nohackme_defender_is_disabled_pattern_line($line) ? 0 : 1,
		);
	}

	return $rows;
}

function nohackme_defender_prepare_pattern_lines($values, $active_flags) {
	$lines = array();
	if ( !is_array($values) ) {
		return $lines;
	}

	foreach ( $values as $index => $value ) {
		$value = trim((string) $value);
		if ( !strlen($value) ) {
			continue;
		}
		$is_active = isset($active_flags[$index]) && intval($active_flags[$index]) === 1;
		$lines[] = $is_active ? $value : nohackme_defender_disabled_pattern_prefix() . $value;
	}

	return $lines;
}

function nohackme_defender_output_paid_list_notice() {
	echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
	echo '<p class="description premium__text">';
	echo esc_html__('Changing the list is available only in the paid version', 'nohackme-defender');
	echo wp_kses_post(_nohackme_defender_get_premium_label('nohackme_defender_parent'));
	echo '</p>';
	echo '</div>';
}

function nohackme_defender_output_list_actions($table_id, $input_name, $nonce_action) {
	echo '<div class="flex_item">';
	echo '<button type="button" class="button" onclick="nohackmeDefenderAddPatternRow(\'' . esc_js($table_id) . '\', \'' . esc_js($input_name) . '\')">' . esc_html__('Add a row', 'nohackme-defender') . '</button>';
	echo '</div>';
	echo '<br>';
	echo '<input type="submit" value="' . esc_html__('Save changes', 'nohackme-defender') . '" class="button button-primary">';
	echo '<br><hr><br>';
	echo '<input type="button" id="restoreDefaults" value="' . esc_attr__('Restore Defaults', 'nohackme-defender') . '" class="button" data-nonce="' . esc_attr(wp_create_nonce($nonce_action)) . '">';
}

function nohackme_defender_output_list_scripts($ajax_action, $button_id) {
	?>
	<script>
	document.getElementById('<?php echo esc_js($button_id); ?>').addEventListener('click', function() {
		var nonce = this.getAttribute('data-nonce');
		if(confirm('<?php echo esc_js(__('Are you sure you want to restore the default list?', 'nohackme-defender')); ?>')) {
			jQuery.post('/wp-admin/admin-ajax.php', {
				'action': '<?php echo esc_js($ajax_action); ?>',
				'_wpnonce': nonce
			}, function(data) {
				if(data!=''){
					if (typeof data === 'string') {
						data = JSON.parse(data);
					}
					if(data.hasOwnProperty('res') && data.hasOwnProperty('data') ){
						showMsg(data.data);
						if ( data.res == 'success' ) {
							window.setTimeout(function () {location.reload()}, 333);
						}
					}
				}
			});
		}
	});
	function nohackmeDefenderAddPatternRow(tableId, inputName) {
		var table = document.getElementById(tableId);
		if (!table) {
			return;
		}
		var index = Math.max(table.rows.length - 1, 0);
		var row = table.insertRow(-1);
		var counterCell = row.insertCell(0);
		var activeCell = row.insertCell(1);
		var valueCell = row.insertCell(2);
		var testCell = row.insertCell(3);
		var actionsCell = row.insertCell(4);
		counterCell.className = 'counter';
		counterCell.textContent = index + 1;
		activeCell.innerHTML = '<input type="checkbox" name="' + inputName + '_active[' + index + ']" value="1" checked>';
		valueCell.innerHTML = '<input type="text" name="' + inputName + '[' + index + ']" value="" style="width:100%;">';
		testCell.innerHTML = '<span class="description"><?php echo esc_js(__('Save changes before testing a new row.', 'nohackme-defender')); ?></span>';
		actionsCell.innerHTML = '<button type="button" class="button nohackme-delete-row"><?php echo esc_js(__('Delete', 'nohackme-defender')); ?></button>';
		nohackmeDefenderRenumberPatternRows(table);
	}
	function nohackmeDefenderRenumberPatternRows(table) {
		var inputName = '';
		for (var i = 1; i < table.rows.length; i++) {
			var textInput = table.rows[i].querySelector('input[type="text"]');
			if (textInput && textInput.name) {
				inputName = textInput.name.replace(/\[[0-9]+\]$/, '');
				break;
			}
		}
		for (var rowIndex = 1; rowIndex < table.rows.length; rowIndex++) {
			var row = table.rows[rowIndex];
			var index = rowIndex - 1;
			var counter = row.querySelector('.counter');
			var textInput = row.querySelector('input[type="text"]');
			var activeInput = row.querySelector('input[type="checkbox"]');
			if (counter) {
				counter.textContent = rowIndex;
			}
			if (textInput && inputName) {
				textInput.name = inputName + '[' + index + ']';
			}
			if (activeInput && inputName) {
				activeInput.name = inputName + '_active[' + index + ']';
			}
		}
	}
	document.addEventListener('click', function(event) {
		if (!event.target.classList.contains('nohackme-delete-row')) {
			return;
		}
		var row = event.target.closest('tr');
		var table = row ? row.closest('table') : null;
		if (!row || !table) {
			return;
		}
		row.parentNode.removeChild(row);
		nohackmeDefenderRenumberPatternRows(table);
	});
	</script>
	<?php
}

function nohackme_defender_admin_menu_google_ips(){
  echo '<div class="wrap"><h1>' . esc_html__('List of Google spider IP ranges', 'nohackme-defender') . '</h1>';
	echo '<p class="description">';
 echo esc_html__('Ranges from this section are used to form a whitelist of IP addresses if the corresponding checkbox is checked in the plugin settings.', 'nohackme-defender');
	echo '</p>';
	$nohackme_defender_options = get_option('nohackme_defender_options');

	$has_premium = _nohackme_defender_check_premium();
	if ( !$has_premium ) {
		echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
		echo '<p class="description premium__text">';
  	echo esc_html__('Changing the list is only available in the paid version', 'nohackme-defender');
		echo wp_kses_post(_nohackme_defender_get_premium_label('nohackme_defender_parent'));
		echo '</p>';
		echo '</div>';
	}
	$file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_google_list';

	if ( nohackme_defender_is_post_request() && check_admin_referer('nohackme_defender_google_ips_action', 'nohackme_defender_google_ips_nonce')) {
		$stripped_hacks = nohackme_defender_get_posted_array('hacks');
	    $cleaned_hacks = array_map('_nohackme_defender_normalize_ip_or_range', $stripped_hacks);
	    $hacks = array_filter($cleaned_hacks, function($value) {
	        return !empty($value);
	    });

			if ( !$has_premium ) {
   echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Work with the list is available only in the paid version!', 'nohackme-defender') . '</p></div>';
			} else {
				_nohackme_defender_premium1($file_path, $hacks);
			}

			$nohackme_defender_options['auto_update_google_ips_daily'] = ( isset($_POST['auto_update_google_ips_daily']) && intval($_POST['auto_update_google_ips_daily']) === 1 ) ? 1 : 0;
			update_option('nohackme_defender_options', $nohackme_defender_options);
  }

	if(file_exists($file_path) && is_readable($file_path)){
  $hacks = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Reading file into an array

		echo '<form method="post" action="">';
		wp_nonce_field('nohackme_defender_google_ips_action', 'nohackme_defender_google_ips_nonce');
	  echo '<table class="table__pdxglobal">';
   echo '<tr><th>№</th><th>' . esc_html__('Value', 'nohackme-defender') . '</th></tr>';
	  foreach($hacks as $index => $hack){
	    echo '<tr>';
	    echo '<td class="counter">'.intval($index + 1).'</td>';
	    echo '<td><input type="text" name="hacks[' . intval($index) . ']" value="' . esc_attr($hack) . '" style="width:100%;"/></td>';
	    echo '</tr>';
	  }
	  echo '</table>';
		echo '<div class="flex_item">';
   echo '<input type="submit" value="' . esc_html__('Save changes', 'nohackme-defender') . '" class="button button-primary">';
  echo '<button type="button" onclick="nohackme_defender_addRow()">' . esc_html__('Add row', 'nohackme-defender') . '</button>';
		echo '</div>';
  echo '<br><hr><br>';
	?>
	<div class="flex_item">
	<input type="button" id="restoreDefaultsGoogle" value="<?php echo esc_attr__('Restore Defaults', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('nohackme_defender_restore_defaults_google_action')); ?>">
	<input type="button" id="updateGoogleFromWeb" value="<?php echo esc_attr__('Update from official source', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('nohackme_defender_update_google_action')); ?>">
	</div>
	<p class="description"><a href="https://developers.google.com/static/crawling/ipranges/common-crawlers.json" target="_blank" rel="noopener noreferrer">https://developers.google.com/static/crawling/ipranges/common-crawlers.json</a></p>
	<p>
		<label for="nohackme_auto_update_google_ips_daily">
			<input type="checkbox" id="nohackme_auto_update_google_ips_daily" name="auto_update_google_ips_daily" value="1" <?php checked(isset($nohackme_defender_options['auto_update_google_ips_daily']) ? intval($nohackme_defender_options['auto_update_google_ips_daily']) : 0, 1); ?>>
			<?php echo esc_html__('Automatically update the Google IP ranges once per day', 'nohackme-defender'); ?>
		</label>
	</p>
	<p class="description"><?php echo esc_html__('If this checkbox is enabled, all manual changes to this list will be lost during the automatic update. The entire list will be overwritten.', 'nohackme-defender'); ?></p>
	<?php
		  echo '</form>';
		?>
    <script>
		document.getElementById('restoreDefaultsGoogle').addEventListener('click', function() {
			    if(confirm('<?php echo esc_js(__("Are you sure you want to restore the default list?", "nohackme-defender")); ?>')) {
		        var send_data = {
		            'action': 'nohackme_defender_restore_defaults_ips_google',
		            '_wpnonce': this.getAttribute('data-nonce')
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          if (typeof data === 'string') {
									data = JSON.parse(data);
								}
			          if(data.hasOwnProperty('res') && data.hasOwnProperty('data') ){
			            if ( data.res == 'success' ) {
										showMsg(data.data);
										window.setTimeout(function () {location.reload()}, 333);
			            } else if ( data.res == 'error' ) {
			              showMsg(data.data);
			            }
			          }
			        }
		        });
		    }
		});

		document.getElementById('updateGoogleFromWeb').addEventListener('click', function() {
		    var button = this;
			    if(confirm('<?php echo esc_js(__("Download the current Google IP ranges from the official source and replace the local list?", "nohackme-defender")); ?>')) {
		        button.disabled = true;
		        button.value = '<?php echo esc_js(__("Updating...", "nohackme-defender")); ?>';
		        var send_data = {
		            'action': 'nohackme_defender_update_google_ips_from_web',
		            '_wpnonce': button.getAttribute('data-nonce')
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          if (typeof data === 'string') {
									data = JSON.parse(data);
								}
			          if(data.hasOwnProperty('res') && data.hasOwnProperty('data') ){
			            if ( data.res == 'success' ) {
										showMsg(data.data + ' <?php echo esc_js(__("The page will now reload.", "nohackme-defender")); ?>');
										window.setTimeout(function () {location.reload()}, 1200);
			            } else {
			              showMsg(data.data);
										button.disabled = false;
										button.value = '<?php echo esc_js(__("Update from official source", "nohackme-defender")); ?>';
			            }
			          }
			        }
		        });
		    }
		});
    </script>
    <?php
	} else {
  echo '<p>' . esc_html__('List of IP ranges not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function nohackme_defender_admin_menu_yandex_ips(){
  echo '<div class="wrap"><h1>' . esc_html__('List of Yandex spider IP ranges', 'nohackme-defender') . '</h1>';
	echo '<p class="description">';
 	echo esc_html__('Ranges from this section are used to create a whitelist of IP addresses if the corresponding checkbox is checked in the plugin settings.', 'nohackme-defender');
	echo '</p>';
	echo '<p class="description">';
 	echo esc_html__('Актуальный список диапазонов адресов поисковых роботов Яндекс можно увидеть', 'nohackme-defender') . ' <a target="_blank" href="https://yandex.ru/ips">' . esc_html__('на этой странице', 'nohackme-defender') . '</a>';
	echo '</p>';
	$nohackme_defender_options = get_option('nohackme_defender_options');

	$has_premium = _nohackme_defender_check_premium();
	if ( !$has_premium ) {
		echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
		echo '<p class="description premium__text">';
  echo esc_html__('Changing the list is only available in the paid version', 'nohackme-defender');
		echo wp_kses_post(_nohackme_defender_get_premium_label('nohackme_defender_parent'));
		echo '</p>';
		echo '</div>';
	}
	$file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_yandex_list';

	if ( nohackme_defender_is_post_request() && check_admin_referer('nohackme_defender_yandex_ips_action', 'nohackme_defender_yandex_ips_nonce')) {
		$stripped_hacks = nohackme_defender_get_posted_array('hacks');
	    $cleaned_hacks = array_map('_nohackme_defender_normalize_ip_or_range', $stripped_hacks);
	    $hacks = array_filter($cleaned_hacks, function($value) {
	        return !empty($value);
	    });

			if ( !$has_premium ) {
   echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Working with the list is available only in the paid version!', 'nohackme-defender') . '</p></div>';
			} else {
				_nohackme_defender_premium1($file_path, $hacks);
			}

			$nohackme_defender_options['auto_update_yandex_ips_daily'] = ( isset($_POST['auto_update_yandex_ips_daily']) && intval($_POST['auto_update_yandex_ips_daily']) === 1 ) ? 1 : 0;
			update_option('nohackme_defender_options', $nohackme_defender_options);
  }

	if(file_exists($file_path) && is_readable($file_path)){
  $hacks = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read file into an array

		echo '<form method="post" action="">';
		wp_nonce_field('nohackme_defender_yandex_ips_action', 'nohackme_defender_yandex_ips_nonce');
	  echo '<table class="table__pdxglobal">';
   echo '<tr><th>№</th><th>' . esc_html__('Value', 'nohackme-defender') . '</th></tr>';
	  foreach($hacks as $index => $hack){
	    echo '<tr>';
	    echo '<td class="counter">'.intval($index + 1).'</td>';
	    echo '<td><input type="text" name="hacks[' . intval($index) . ']" value="' . esc_attr($hack) . '" style="width:100%;"/></td>';
	    echo '</tr>';
	  }
	  echo '</table>';
		echo '<div class="flex_item">';
   echo '<input type="submit" value="' . esc_html__('Save changes', 'nohackme-defender') . '" class="button button-primary">';
  echo '<button type="button" onclick="nohackme_defender_addRow()">' . esc_html__('Add row', 'nohackme-defender') . '</button>';
		echo '</div>';
  echo '<br><hr><br>';
	?>
	<div class="flex_item">
	<input type="button" id="restoreDefaultsYandex" value="<?php echo esc_attr__('Restore Defaults', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('nohackme_defender_restore_defaults_yandex_action')); ?>">
	<input type="button" id="updateYandexFromWeb" value="<?php echo esc_attr__('Update from official source', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('nohackme_defender_update_yandex_action')); ?>">
	</div>
	<p class="description"><a href="https://yandex.com/ips" target="_blank" rel="noopener noreferrer">https://yandex.com/ips</a></p>
	<p>
		<label for="nohackme_auto_update_yandex_ips_daily">
			<input type="checkbox" id="nohackme_auto_update_yandex_ips_daily" name="auto_update_yandex_ips_daily" value="1" <?php checked(isset($nohackme_defender_options['auto_update_yandex_ips_daily']) ? intval($nohackme_defender_options['auto_update_yandex_ips_daily']) : 0, 1); ?>>
			<?php echo esc_html__('Automatically update the Yandex IP ranges once per day', 'nohackme-defender'); ?>
		</label>
	</p>
	<p class="description"><?php echo esc_html__('If this checkbox is enabled, all manual changes to this list will be lost during the automatic update. The entire list will be overwritten.', 'nohackme-defender'); ?></p>
	<?php
		  echo '</form>';
		?>
    <script>
		document.getElementById('restoreDefaultsYandex').addEventListener('click', function() {
			    if(confirm('<?php echo esc_js(__("Are you sure you want to restore the default list?", "nohackme-defender")); ?>')) {
		        var send_data = {
		            'action': 'nohackme_defender_restore_defaults_ips_yandex',
		            '_wpnonce': this.getAttribute('data-nonce')
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          if (typeof data === 'string') {
									data = JSON.parse(data);
								}
			          if(data.hasOwnProperty('res') && data.hasOwnProperty('data') ){
			            if ( data.res == 'success' ) {
										showMsg(data.data);
										window.setTimeout(function () {location.reload()}, 333);
			            } else if ( data.res == 'error' ) {
			              showMsg(data.data);
			            }
			          }
			        }
		        });
		    }
		});

		document.getElementById('updateYandexFromWeb').addEventListener('click', function() {
		    var button = this;
			    if(confirm('<?php echo esc_js(__("Download the current Yandex IP ranges from the official source and replace the local list?", "nohackme-defender")); ?>')) {
		        button.disabled = true;
		        button.value = '<?php echo esc_js(__("Updating...", "nohackme-defender")); ?>';
		        var send_data = {
		            'action': 'nohackme_defender_update_yandex_ips_from_web',
		            '_wpnonce': button.getAttribute('data-nonce')
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          if (typeof data === 'string') {
									data = JSON.parse(data);
								}
			          if(data.hasOwnProperty('res') && data.hasOwnProperty('data') ){
			            if ( data.res == 'success' ) {
										showMsg(data.data + ' <?php echo esc_js(__("The page will now reload.", "nohackme-defender")); ?>');
										window.setTimeout(function () {location.reload()}, 1200);
			            } else {
			              showMsg(data.data);
										button.disabled = false;
										button.value = '<?php echo esc_js(__("Update from official source", "nohackme-defender")); ?>';
			            }
			          }
			        }
		        });
		    }
		});
    </script>
    <?php
	} else {
  echo '<p>' . esc_html__('List of IP ranges not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function nohackme_defender_admin_menu_stats(){
  	echo '<div class="wrap"><h1>' . esc_html__('Top by blocking counters', 'nohackme-defender') . '</h1>';
	nohackme_defender_admin_menu_stats_output();
	echo '</div>';
}
function nohackme_defender_admin_menu_stats_output () {
	$SxGeo = _nohackme_defender_get_geo();
	if ( is_string($SxGeo) ) {
		echo '<div class="pdxhighlight">';
		echo wp_kses_post($SxGeo);
		echo '</div>';
	}

	$acounter_1min = array();
	$acounter_10min = array();
	$acounter_60min = array();
	$ips = array();
	if ( is_file(NOHACKME_DEFENDER_SETTINGS_PATH . 'cur_ips_counters') ) {
		$data = @_pdxglobal_get_file_via_wpfs(NOHACKME_DEFENDER_SETTINGS_PATH . 'cur_ips_counters');
		if ( strlen($data) ) {
			$ips = @unserialize($data);
		}
	}
	if (isset($ips) and is_array($ips) and count($ips)) {
	  	echo '<p>Unique IP addresses today: ' . count($ips) . '</p>';
		foreach ($ips as $ip => $data) {
		    if (isset($data) && is_array($data) && count($data)) {
		        // Получаем текущее время в минутах (от начала часа)
			        $current_minute = gmdate('i');

		        // Счетчик для 1 минуты
		        $qty = 0;
		        if (isset($data[$current_minute])) {
		            $qty = $data[$current_minute];
		        }
		        if ($qty > 3) {
		            $acounter_1min[$ip] = $qty;
		        }

		        // Счетчик для 10 минут
		        $qty = 0;
		        for ($i = 0; $i < 10; $i++) {
		            $minute_to_check = ($current_minute - $i + 60) % 60;
		            if (isset($data[$minute_to_check])) {
		                $qty += $data[$minute_to_check];
		            }
		        }
		        if ($qty > 10) {
		            $acounter_10min[$ip] = $qty;
		        }

		        // Счетчик для 50 минут
		        $qty = 0;
		        for ($i = 0; $i < 50; $i++) {
		            $minute_to_check = ($current_minute - $i + 60) % 60;
		            if (isset($data[$minute_to_check])) {
		                $qty += $data[$minute_to_check];
		            }
		        }
		        if ($qty > 30) {
		            $acounter_60min[$ip] = $qty;
		        }
		    }
		}
		if (isset($acounter_60min) and is_array($acounter_60min) and count($acounter_60min)) {
			arsort($acounter_60min);
   echo '<h2>' . esc_html__('For 50 minutes', 'nohackme-defender') . '</h2>';
			echo '<table border="1" cellpadding="7">';
   echo '<tr><th>' . esc_html__('IP', 'nohackme-defender') . '</th><th>' . esc_html__('Views', 'nohackme-defender') . '</th><th>' . esc_html__('User country', 'nohackme-defender') . '</th></tr>';
			$cur_qty = 0;
			foreach ( $acounter_60min as $ip => $qty) {
				echo '<tr>';
				echo '<td>';
				$newip = _nohackme_defender_formatIp($ip);
				echo esc_html($newip);
				echo '</td>';
				echo '<td>';
				echo esc_html($qty);
				echo '</td>';
				echo '<td>';
				$country_code = '';
				if ( !is_string($SxGeo) ) {
					$country_code = $SxGeo->getCountry($newip);
				}
				if ( !strlen($country_code) ) {
					$country_code .= 'unknown';
				}
				echo esc_html($country_code);
				echo '</td>';
				echo '</tr>';
				if ( ++$cur_qty > 10 ) {
					break;
				}
			}
			echo '</table>';
		}

		if (isset($acounter_10min) and is_array($acounter_10min) and count($acounter_10min)) {
			arsort($acounter_10min);
   echo '<h2>' . esc_html__('For 10 minutes', 'nohackme-defender') . '</h2>';
			echo '<table border="1" cellpadding="7">';
   echo '<tr><th>' . esc_html__('IP', 'nohackme-defender') . '</th><th>' . esc_html__('Views', 'nohackme-defender') . '</th><th>' . esc_html__('User country', 'nohackme-defender') . '</th></tr>';
			$cur_qty = 0;
			foreach ( $acounter_10min as $ip => $qty) {
				echo '<tr>';
				echo '<td>';
				$newip = _nohackme_defender_formatIp($ip);
				echo esc_html($newip);
				echo '</td>';
				echo '<td>';
				echo esc_html($qty);
				echo '</td>';
				echo '<td>';
				$country_code = '';
				if ( !is_string($SxGeo) ) {
					$country_code = $SxGeo->getCountry($newip);
				}
				if ( !strlen($country_code) ) {
					$country_code .= 'unknown';
				}
				echo esc_html($country_code);
				echo '</td>';
				echo '</tr>';
				if ( ++$cur_qty > 10 ) {
					break;
				}
			}
			echo '</table>';
		}

		if (isset($acounter_1min) and is_array($acounter_1min) and count($acounter_1min)) {
			arsort($acounter_1min);
   echo '<h2>' . esc_html__('One minute', 'nohackme-defender') . '</h2>';
			echo '<table border="1" cellpadding="7">';
   echo '<tr><th>' . esc_html__('IP', 'nohackme-defender') . '</th><th>' . esc_html__('Views', 'nohackme-defender') . '</th><th>' . esc_html__('User country', 'nohackme-defender') . '</th></tr>';
			$cur_qty = 0;
			foreach ( $acounter_1min as $ip => $qty) {
				echo '<tr>';
				echo '<td>';
				$newip = _nohackme_defender_formatIp($ip);
				echo esc_html($newip);
				echo '</td>';
				echo '<td>';
				echo esc_html($qty);
				echo '</td>';
				echo '<td>';
				$country_code = '';
				if ( !is_string($SxGeo) ) {
					$country_code = $SxGeo->getCountry($newip);
				}
				if ( !strlen($country_code) ) {
					$country_code .= 'unknown';
				}
				echo esc_html($country_code);
				echo '</td>';
				echo '</tr>';
				if ( ++$cur_qty > 10 ) {
					break;
				}
			}
			echo '</table>';
		}
	} else {
  		echo esc_html__('No data', 'nohackme-defender');
	}


}

add_action('admin_menu', function(){
	$block_qty = _nohackme_defender_get_blocks_qty();
  add_menu_page( 'noHackMe', 'noHackMe', 'manage_options', 'nohackme_defender_parent', 'nohackme_defender_admin_menu', '', 101 );
  add_action( 'admin_init', 'nohackme_defender_register_settings' );

 add_submenu_page( 'nohackme_defender_parent', esc_html__('Blocked IP', 'nohackme-defender'), esc_html__('Blocked IP', 'nohackme-defender') . (($block_qty > 0)?(' <span class="update-plugins">' . $block_qty . '</span>'):(' (0)')), 'manage_options', 'nohackme_defender_logs', 'nohackme_defender_admin_menu_logs' );
 add_submenu_page( 'nohackme_defender_parent', esc_html__('Statistics', 'nohackme-defender'), esc_html__('Statistics', 'nohackme-defender'), 'manage_options', 'nohackme_defender_stats', 'nohackme_defender_admin_menu_stats' );
 add_submenu_page( 'nohackme_defender_parent', esc_html__('Suspicious requests', 'nohackme-defender'), esc_html__('Suspicious requests', 'nohackme-defender'), 'manage_options', 'nohackme_defender_hacklist', 'nohackme_defender_admin_menu_hacklist' );
 add_submenu_page( 'nohackme_defender_parent', esc_html__('Suspicious URL paths', 'nohackme-defender'), esc_html__('Suspicious URL paths', 'nohackme-defender'), 'manage_options', 'nohackme_defender_pathlist', 'nohackme_defender_admin_menu_pathlist' );
 add_submenu_page( 'nohackme_defender_parent', esc_html__('Google IP Ranges', 'nohackme-defender'), esc_html__('Google IP Ranges', 'nohackme-defender'), 'manage_options', 'nohackme_defender_google_ips', 'nohackme_defender_admin_menu_google_ips' );
 add_submenu_page( 'nohackme_defender_parent', esc_html__('Yandex IP Ranges', 'nohackme-defender'), esc_html__('Yandex IP Ranges', 'nohackme-defender'), 'manage_options', 'nohackme_defender_yandex_ips', 'nohackme_defender_admin_menu_yandex_ips' );
} );
function nohackme_defender_register_settings() {
  register_setting( 'nohackme_defender_settings_group', 'nohackme_defender_options', 'nohackme_defender_sanitize_options' );
}

/**
 * Check the HTTP method without trusting an absent or non-string server value.
 *
 * @return bool
 */
function nohackme_defender_is_post_request() {
	$nohackme_defender_method = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_UNSAFE_RAW);
	if ( null === $nohackme_defender_method && isset($GLOBALS['_SERVER']['REQUEST_METHOD']) ) {
		$nohackme_defender_method = $GLOBALS['_SERVER']['REQUEST_METHOD'];
	}
	if ( !is_string($nohackme_defender_method) ) {
		return false;
	}
	return 'POST' === strtoupper(sanitize_text_field(wp_unslash($nohackme_defender_method)));
}

/**
 * Read a scalar-only array from an authenticated plugin form.
 *
 * @param string $nohackme_defender_key Allowed form field name.
 * @return array
 */
function nohackme_defender_get_posted_array($nohackme_defender_key) {
	$nohackme_defender_allowed_keys = array('hacks', 'hacks_active', 'paths', 'paths_active');
	if ( !in_array($nohackme_defender_key, $nohackme_defender_allowed_keys, true) ) {
		return array();
	}

	$nohackme_defender_values = filter_input(INPUT_POST, $nohackme_defender_key, FILTER_UNSAFE_RAW, FILTER_REQUIRE_ARRAY);
	if ( null === $nohackme_defender_values || false === $nohackme_defender_values ) {
		$nohackme_defender_values = isset($GLOBALS['_POST'][$nohackme_defender_key]) ? $GLOBALS['_POST'][$nohackme_defender_key] : array();
		$nohackme_defender_values = wp_unslash($nohackme_defender_values);
	}
	if ( !is_array($nohackme_defender_values) ) {
		return array();
	}

	$nohackme_defender_clean_values = array();
	foreach ( $nohackme_defender_values as $nohackme_defender_index => $nohackme_defender_value ) {
		if ( !is_scalar($nohackme_defender_value) ) {
			continue;
		}
		$nohackme_defender_index = absint($nohackme_defender_index);
		$nohackme_defender_value = wp_check_invalid_utf8((string) $nohackme_defender_value);
		$nohackme_defender_value = preg_replace('/[\x00-\x1F\x7F]/', '', $nohackme_defender_value);
		$nohackme_defender_clean_values[$nohackme_defender_index] = substr($nohackme_defender_value, 0, 2000);
	}

	return $nohackme_defender_clean_values;
}

/**
 * Get a sanitized, read-only query value for plugin navigation.
 *
 * @param string $nohackme_defender_key Allowed query key.
 * @return string
 */
function nohackme_defender_get_admin_query_value($nohackme_defender_key) {
	$nohackme_defender_allowed_keys = array('tab', 'nonce', 'page', 'lang');
	if ( !in_array($nohackme_defender_key, $nohackme_defender_allowed_keys, true) ) {
		return '';
	}

	$nohackme_defender_value = filter_input(INPUT_GET, $nohackme_defender_key, FILTER_UNSAFE_RAW);
	if ( null === $nohackme_defender_value && isset($GLOBALS['_GET'][$nohackme_defender_key]) ) {
		$nohackme_defender_value = $GLOBALS['_GET'][$nohackme_defender_key];
	}
	if ( !is_string($nohackme_defender_value) ) {
		return '';
	}

	return sanitize_text_field(wp_unslash($nohackme_defender_value));
}

function nohackme_defender_admin_menu() {
	$nohackme_defender_requested_tab = nohackme_defender_get_admin_query_value('tab');
	if ( '' !== $nohackme_defender_requested_tab ) {
		$nohackme_defender_admin_nonce = nohackme_defender_get_admin_query_value('nonce');
		if ( '' === $nohackme_defender_admin_nonce || !wp_verify_nonce($nohackme_defender_admin_nonce, 'nohackme_defender_admin_action')) {
	        wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'nohackme-defender'));
	    }
	}

	    $active_tab = '' !== $nohackme_defender_requested_tab ? sanitize_key($nohackme_defender_requested_tab) : 'settings';
    if ( $active_tab === 'premium' ) {
        $active_tab = 'settings';
    }

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(NOHACKME_DEFENDER_NAME) . '</h1>';

    // Tabs
    ?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=nohackme_defender_parent&tab=settings&nonce=<?php echo esc_html(wp_create_nonce('nohackme_defender_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Settings', 'nohackme-defender'); ?></a>
        <a href="?page=nohackme_defender_parent&tab=plugins&nonce=<?php echo esc_html(wp_create_nonce('nohackme_defender_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'plugins' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Other plugins', 'nohackme-defender'); ?></a>
    <?php if (_nohackme_defender_show_branding()) { ?>
        <a href="?page=nohackme_defender_parent&tab=development&nonce=<?php echo esc_html(wp_create_nonce('nohackme_defender_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'development' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Website Development', 'nohackme-defender'); ?></a>
        <a href="?page=nohackme_defender_parent&tab=seo&nonce=<?php echo esc_html(wp_create_nonce('nohackme_defender_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'seo' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('SEO', 'nohackme-defender'); ?></a>
    <?php } ?>
    </h2>
    <?php

    // Content of tabs
    if ($active_tab == 'settings') {
        nohackme_defender_get_settings_page();
    } elseif ($active_tab == 'plugins') {
        if (function_exists('_pdxglobal_get_plugins_page')) {
            $func = '_pdxglobal_get_plugins_page';
            $func();
        }
    } elseif ($active_tab == 'development') {
        if (function_exists('_pdxglobal_get_dev_page')) {
            $func = '_pdxglobal_get_dev_page';
            $func();
        }
    } elseif ($active_tab == 'seo') {
        if (function_exists('_pdxglobal_get_seo_page')) {
            $func = '_pdxglobal_get_seo_page';
            $func();
        }
    }
    echo '</div>';
}

function nohackme_defender_get_settings_page( ) {
	echo '<div>&nbsp;</div>';
	$nohackme_defender_options = get_option('nohackme_defender_options');

	echo '<form method="post" action="options.php">';
	settings_fields('nohackme_defender_settings_group');

	echo '<table class="form-table">';
	echo '<tbody>';

	// Temporarily disable tracking and blocking
	$tmpval = 'cancel';
 echo '<tr class="highlight"><th scope="row">' . esc_html__('Temporarily disable plugin', 'nohackme-defender') . '</th><td>';
	echo '<input type="checkbox" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="1"' . (isset($nohackme_defender_options[$tmpval]) && $nohackme_defender_options[$tmpval] == 1 ? ' checked' : '') . '>';
	echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
 echo '<p class="description">' . esc_html__('If the checkbox is checked, the plugin will stop tracking and blocking IP addresses, and will also provide access to the site from IP addresses that are already blocked.', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	$tmpval = 'cancel_block';
 echo '<tr><th scope="row">' . esc_html__('Cancel current IP blocking if there is a GET parameter', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="' . (isset($nohackme_defender_options[$tmpval]) && strlen($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '" style="width: 100%;">';
 echo '<p class="description">' . esc_html__('Specify the GET parameter that can be entered to remove the current IP from the list of blocked ones.', 'nohackme-defender');
	if ( isset($nohackme_defender_options[$tmpval]) && strlen($nohackme_defender_options[$tmpval]) ) {
		$maybe_get_param = $nohackme_defender_options[$tmpval];
	} else {
		$maybe_get_param = _nohackme_defender_generateRandomString();
  echo '<br>' . esc_html__('For example:', 'nohackme-defender') . ' <span id="copy_code_this">' . esc_html($maybe_get_param) . '</span> <span class="button button-primary" onclick="copyToClipboard(\'copy_code_this\');">' . esc_html__('Copy', 'nohackme-defender') . '</span>';
	}
	echo '<br>';
 echo esc_html__('That is, if this GET parameter is specified in the field, then in case of blocking your IP, you will be able to unblock it by opening the URL: ', 'nohackme-defender') . ' <span id="copy_url_this">' . esc_html(home_url('/')) . '?' . esc_html($maybe_get_param) . '</span> <span class="button" onclick="copyToClipboard(\'copy_url_this\');">' . esc_html__('Copy', 'nohackme-defender') . '</span>';
	echo '</p>';
	echo '</td></tr>';

	// Message to be displayed to the user if their IP is blocked
	$tmpval = 'block_msg';
	$tmpdef = 'Your request is temporarily blocked on this site. Try to come tomorrow.';
	echo '<tr><th scope="row">' . esc_html__('Message for blocked IPs', 'nohackme-defender') . '</th><td>';

	$editor_id = 'pdxmeta_options_' . $tmpval;
	$editor_content = (isset($nohackme_defender_options[$tmpval]) && strlen($nohackme_defender_options[$tmpval]) ? $nohackme_defender_options[$tmpval] : $tmpdef);

	$args = array(
	  'textarea_name' => 'nohackme_defender_options[' . $tmpval . ']',
	  'textarea_rows' => 3,
	  'teeny' => true, // Set to true to create a minimal editor, false for full editor
	);

	wp_editor($editor_content, $editor_id, $args);

	echo '</td></tr>';

	// How many days to block IP
	$tmpval = 'block_time';
	echo '<tr><th scope="row">' . esc_html__('How many days to block IP', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="' . (isset($nohackme_defender_options[$tmpval]) && is_numeric($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '" size="3">';
	echo '</td></tr>';

 echo '<tr><td colspan="2"><h2>' . esc_html__('Whitelists', 'nohackme-defender') . '</h2><p class="description">' . esc_html__('Whitelists allow disabling the check for the number of requests to the site within a certain time period for specific IPs.', 'nohackme-defender') . '</p></td></tr>';

	// Exclude IP addresses
	$ip = '';
	if ( isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ) {
		$ip = _nohackme_defender_formatIp(sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])));
	}

	$tmpval = 'exclude_ip';
  echo '<tr><th scope="row">' . esc_html__('Whitelist of IP addresses', 'nohackme-defender') . '</th><td>';
	echo '<textarea id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" cols="33" rows="7">' . (isset($nohackme_defender_options[$tmpval]) && strlen($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '</textarea>';
		// translators: %s: IP address of the current user
		$current_ip_text = sprintf(esc_html__('Your current IP: %s', 'nohackme-defender'), esc_html($ip));
	  	echo '<p class="description">' . esc_html__('The specified IP addresses will not be blocked by the plugin.', 'nohackme-defender') . '<br>' . esc_html__('Each IP from a new line', 'nohackme-defender') . '.<br>' . esc_html($current_ip_text) . ' <span class="button button-secondary" onclick="nohackmeDefenderAppendIpToWhitelist(\'' . esc_js($ip) . '\');">' . esc_html__('Добавить в белый список', 'nohackme-defender') . '</span>';
	$server_addr = filter_input(INPUT_SERVER, 'SERVER_ADDR', FILTER_VALIDATE_IP);
	if ( false === $server_addr || null === $server_addr ) {
		$server_addr = isset($GLOBALS['_SERVER']['SERVER_ADDR']) && is_string($GLOBALS['_SERVER']['SERVER_ADDR'])
			? filter_var(wp_unslash($GLOBALS['_SERVER']['SERVER_ADDR']), FILTER_VALIDATE_IP)
			: false;
	}
	if ( is_string($server_addr) && strlen($server_addr) ) {
		$server_ip = _nohackme_defender_formatIp($server_addr);
			// translators: %s: IP address of your site
			$server_ip_text = sprintf(esc_html__('IP address of your site: %s', 'nohackme-defender'), esc_html($server_ip));
	  		echo '<br>' . esc_html($server_ip_text) . ' <span class="button button-secondary" onclick="nohackmeDefenderAppendIpToWhitelist(\'' . esc_js($server_ip) . '\');">' . esc_html__('Добавить в белый список', 'nohackme-defender') . '</span>';
	}
	echo '</p>';
	echo '</td></tr>';

	$tmpval = 'exclude_google_ips';
 echo '<tr><th scope="row">' . esc_html__('Do not block Google robots', 'nohackme-defender') . '</th><td>';
	echo '<input type="checkbox" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="1"' . (isset($nohackme_defender_options[$tmpval]) && $nohackme_defender_options[$tmpval] == 1 ? ' checked' : '') . '>';
	echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
 echo '<p class="description">' . esc_html__('The list includes well-known IP address ranges of Google.', 'nohackme-defender') . '<br><a href="/wp-admin/admin.php?page=nohackme_defender_google_ips">' . esc_html__('Change the list of ranges', 'nohackme-defender') . '</a></p>';
	echo '</td></tr>';

	$tmpval = 'exclude_yandex_ips';
 echo '<tr><th scope="row">' . esc_html__('Do not block Yandex robots', 'nohackme-defender') . '</th><td>';
	echo '<input type="checkbox" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="1"' . (isset($nohackme_defender_options[$tmpval]) && $nohackme_defender_options[$tmpval] == 1 ? ' checked' : '') . '>';
	echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
 echo '<p class="description">' . esc_html__('The list of addresses is taken from the official Yandex page:', 'nohackme-defender') . ' <a href="https://yandex.ru/ips" target="_blank">https://yandex.ru/ips</a><br><a href="/wp-admin/admin.php?page=nohackme_defender_yandex_ips">' . esc_html__('Change the list of ranges', 'nohackme-defender') . '</a></p>';
	echo '</td></tr>';

	$tmpval = 'exclude_user_agent';
 echo '<tr><th scope="row">' . esc_html__('White list User Agent', 'nohackme-defender') . '</th><td>';
	echo '<textarea id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" cols="33" rows="7">' . (isset($nohackme_defender_options[$tmpval]) && strlen($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '</textarea>';
 echo '<p class="description">' . esc_html__('Requests with the specified User Agent will not be blocked by the plugin.', 'nohackme-defender') . ' <span class="is_a is_link" onclick="showOverlayBlock(\'#nohackme_defender_useragent_list\');">' . esc_html__('View a list of popular User Agents', 'nohackme-defender') . '</span>' . '<br>' . esc_html__('Each User Agent on a new line. It is not necessary to specify the entire User Agent - the plugin checks for the presence of the specified string in the request User Agent', 'nohackme-defender') . '.<br><strong>' . esc_html__('ATTENTION!!! An attacker can specify any User Agent for their request. Thus, they can easily pretend to be a Google or Yandex robot if you add these robots\' User Agents to the whitelist.', 'nohackme-defender') . '</strong>';
	echo '</p>';
	echo '<div id="nohackme_defender_useragent_list" style="display: none;">';
	echo '<div class="flex_item flex_item2">';

	$abots = array(
		'Googlebot' => 'Google',
		'Google Web Preview' => 'Google Instant',
		'Mediapartners-Google' => 'Google AdSense',
		'YandexBot' => 'Yandex',
		'YandexImages' => 'YandexImages',
		'YandexVideo' => 'Yandex Video',
		'YandexWebmaster' => 'Yandex Webmaster',
    'Bingbot' => 'Bing',
    'Slurp' => 'Yahoo',
    'DuckDuckBot' => 'DuckDuckGo',
    'Baiduspider' => 'Baidu',
    'Sogou' => 'Sogou',
    'Exabot' => 'Exalead',
    'facebot' => 'Facebook',
    'ia_archiver' => 'Alexa',
    'Twitterbot' => 'Twitter',
    'LinkedInBot' => 'LinkedIn',
    'msnbot' => 'MSN',
    'MJ12bot' => 'Majestic',
    'AhrefsBot' => 'Ahrefs',
    'SemrushBot' => 'SEMrush',
    'DotBot' => 'Moz Dotbot',
    'AddThis' => 'AddThis',
	);
	if (isset($abots) and is_array($abots) and count($abots)) { foreach ( $abots as $abot_id => $abot_desc) {
		echo '<div class="nohackme_defender_useragent flex_item flex_item2">
			<div><input type="text" value="' . esc_attr($abot_id) . '" onclick="copyToCB(\'' . esc_attr($abot_id) . '\');" style="width: 100%;"></div>
			<div> ' . esc_html($abot_desc) . '</div>
	  </div>';
	} }
	echo '</div>';
	echo '</div>';
	echo '</td></tr>';

	echo '<tr><td colspan="2"><h2>' . esc_html__('Max number of requests', 'nohackme-defender') . '</h2></td></tr>';
	// Max number of requests per 1 minute
	$tmpval = 'block_if_min';
	echo '<tr><th scope="row">' . esc_html__('Per 1 minute', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="' . (isset($nohackme_defender_options[$tmpval]) && is_numeric($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '" size="3">';
	echo '<p class="description">' . esc_html__('Block IP if there are this many or more requests within one minute', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	// Max number of requests per 10 minutes
	$tmpval = 'block_if_10min';
	echo '<tr><th scope="row">' . esc_html__('Per 10 minutes', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="' . (isset($nohackme_defender_options[$tmpval]) && is_numeric($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '" size="3">';
	echo '<p class="description">' . esc_html__('Block IP if there are this many or more requests within ten minutes', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	// Max number of requests per 50 minutes
	$tmpval = 'block_if_50min';
	echo '<tr><th scope="row">' . esc_html__('Per 50 minutes', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_options[' . esc_html($tmpval) . ']" value="' . (isset($nohackme_defender_options[$tmpval]) && is_numeric($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '" size="3">';
	echo '<p class="description">' . esc_html__('Block IP if there are this many or more requests within fifty minutes', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	echo '</tbody>';
	echo '</table>';
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup is assembled from escaped strings inside _nohackme_defender_get_support_links().
		echo _nohackme_defender_get_support_links();

	echo '<p class="submit">';
	echo '<input type="submit" class="button-primary" value="' . esc_html__('Save Changes', 'nohackme-defender') . '" />';
	echo '</p>';

	echo '</form>';
}
function nohackme_defender_get_premium_page( ) {
	echo '<div>&nbsp;</div>';
	$premium_type = 0;
	if ( _nohackme_defender_check_premium() ) {
		$premium_type = 777;
	}
	$premium_cost = 5;

	switch ($premium_type) {
		case 1:
			// Field for entering the license key
			$nohackme_defender_options = get_option('nohackme_defender_license');
			echo '<form method="post" action="options.php">';
			settings_fields('nohackme_defender_premium_group');
			echo '<table class="form-table">';
			echo '<tbody>';
			$tmpval = 'license_key';
		 	echo '<tr class="highlight"><th scope="row">' . esc_html__('License key', 'nohackme-defender') . '</th><td>';
			echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="nohackme_defender_license[' . esc_html($tmpval) . ']" value="' . (isset($nohackme_defender_options[$tmpval]) ? esc_attr($nohackme_defender_options[$tmpval]) : '') . '" class="regular-text">';
			echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
		 	echo '<p class="description">' . esc_html__('Enter the license key to activate premium features.', 'nohackme-defender') . '</p>';
			echo '</td></tr>';
			echo '</tbody></table>';
			echo '<p class="submit">';
			echo '<input type="submit" class="button-primary" value="' . esc_html__('Save Changes', 'nohackme-defender') . '" />';
			echo '</p></form>';
			break;
		case 777:
			echo '<div class="pdxhighlight">';
			echo '<p><strong class="premium__text">';
			echo esc_html__('You have a paid version of the plugin.', 'nohackme-defender');
			echo '</strong></p>';
			echo '<p class="premium__text">';
			echo esc_html__('Thank you for your purchase!', 'nohackme-defender');
			echo '</p>';
			echo '</div>';
			echo '<hr>';
			break;
		default:
			echo '<div class="pdxhighlight">';
			echo '<p>';
			echo esc_html__('The cost of the premium version of the plugin', 'nohackme-defender') . ': <strong>$' . esc_html($premium_cost) . '</strong>';
			echo '</p>';
			echo '<p>';
			echo esc_html__('For inquiries about purchasing the paid version of the plugin, write to the email', 'nohackme-defender') . ' <a href="mailto:paraz0n3@gmail.com?subject=Purchase of the premium version of the ' . esc_html(NOHACKME_DEFENDER_NAME) . ' plugin">paraz0n3@gmail.com</a>';
			echo '</p>';
			echo '</div>';
			echo '<hr>';
	}

	echo '<p>';
 echo esc_html__('By purchasing the plugin, you will get the opportunity to use the following premium features:', 'nohackme-defender');
	echo '</p>';
	echo '<ul class="premium__list flex_item flex_item2">';
	echo '<li>';
 echo esc_html__('The ability to add ranges of IP addresses belonging to Google and Yandex search engine crawlers to the whitelist with just two checkboxes.', 'nohackme-defender');
	echo '</li>';
	echo '<li>';
 echo esc_html__('Edit the list of IP ranges owned by Google spiders.', 'nohackme-defender');
	echo '</li>';
	echo '<li>';
 echo esc_html__('Edit the list of IP ranges owned by Yandex spiders.', 'nohackme-defender');
	echo '</li>';
	echo '<li>';
 echo esc_html__('Edit the list of fragments that make a URL, request parameter, User-Agent, or raw API body suspicious and trigger blocking.', 'nohackme-defender');
	echo '</li>';
	echo '</ul>';
}
function nohackme_defender_sanitize_premium_options($input) {
	$input['license_key'] = sanitize_text_field($input['license_key']);

	return $input;
}
function nohackme_defender_sanitize_options( $input ) {
  $input['block_time'] = intval( $input['block_time'] );

	$hackme_settings = array();
	$stored_options = get_option('nohackme_defender_options');
	if ( !is_array($stored_options) ) {
		$stored_options = array();
	}

	$outp = 0;
	if ( isset($input['cancel']) and is_numeric($input['cancel']) and $input['cancel'] == 1 ) {
		$outp = 1;
	}
	$hackme_settings ['cancel']= $outp;
  $input['cancel'] = $outp;

	$outp = isset($stored_options['disable_hack_payloads']) ? intval($stored_options['disable_hack_payloads']) : 0;
	$hackme_settings ['disable_hack_payloads']= $outp;
  $input['disable_hack_payloads'] = $outp;

	$outp = isset($stored_options['disable_suspicious_paths']) ? intval($stored_options['disable_suspicious_paths']) : 0;
	$hackme_settings ['disable_suspicious_paths']= $outp;
  $input['disable_suspicious_paths'] = $outp;

	$outp = 0;
	if ( _nohackme_defender_check_premium() ) {
		$outp = _nohackme_defender_premium_save($input, 'exclude_google_ips');
	}
	$hackme_settings ['exclude_google_ips']= $outp;
  $input['exclude_google_ips'] = $outp;

	$outp = 0;
	if ( _nohackme_defender_check_premium() ) {
		$outp = _nohackme_defender_premium_save($input, 'exclude_yandex_ips');
	}
	$hackme_settings ['exclude_yandex_ips']= $outp;
  $input['exclude_yandex_ips'] = $outp;

	$outp = '';
	if ( isset($input['exclude_ip']) and strlen($input['exclude_ip']) ) {
		$outp = sanitize_textarea_field($input['exclude_ip']);
	}
	$hackme_settings ['exclude_ip']= $outp;
  $input['exclude_ip'] = $outp;

	$outp = '';
	if ( isset($input['exclude_user_agent']) and strlen($input['exclude_user_agent']) ) {
		$outp = sanitize_textarea_field($input['exclude_user_agent']);
	}
	$hackme_settings ['exclude_user_agent']= $outp;
  $input['exclude_user_agent'] = $outp;

	$outp = '';
	if ( isset($input['cancel_block']) and strlen($input['cancel_block']) ) {
		$outp = sanitize_text_field($input['cancel_block']);
	}
	$hackme_settings ['cancel_block']= $outp;
  $input['cancel_block'] = $outp;

	$outp = '';
	if (isset($input['block_msg']) && strlen($input['block_msg'])) {
	  // Use wp_kses_post to allow safe HTML content
	  $outp = wp_kses_post($input['block_msg']);
	}
	$hackme_settings['block_msg'] = $outp;
	$input['block_msg'] = $outp;

	$outp = 0;
	if ( isset($input['block_if_min']) and is_numeric($input['block_if_min']) and $input['block_if_min'] > 0 ) {
		$outp = $input['block_if_min'];
	}
	$hackme_settings ['block_if_min']= $outp;
  $input['block_if_min'] = $outp;

	$outp = 0;
	if ( isset($input['block_if_10min']) and is_numeric($input['block_if_10min']) and $input['block_if_10min'] > 0 ) {
		$outp = $input['block_if_10min'];
	}
	$hackme_settings ['block_if_10min']= $outp;
  $input['block_if_10min'] = $outp;

	$outp = 0;
	if ( isset($input['block_if_50min']) and is_numeric($input['block_if_50min']) and $input['block_if_50min'] > 0 ) {
		$outp = $input['block_if_50min'];
	}
	$hackme_settings ['block_if_50min']= $outp;
  $input['block_if_50min'] = $outp;

	$save_path = NOHACKME_DEFENDER_SETTINGS_PATH;
	if ( !is_dir($save_path) ) {
		wp_mkdir_p($save_path);
	}
	_pdxglobal_update_file_via_wpfs($save_path . 'settings', $hackme_settings);

  return $input;
}

function nohackme_defender_enqueue_admin_scripts($hook_suffix) {
	$plugin_url = plugin_dir_url( __FILE__ );
	$plugin_path = plugin_dir_path( __FILE__ );
	if ( strpos($hook_suffix, 'nohackme_defender_') === false ) {} else {
		$base_admin_style_ver = filemtime( $plugin_path . 'css/base_admin.css' );
		$admin_style_ver = filemtime( $plugin_path . 'css/admin-style.css' );
		$base_admin_script_ver = filemtime( $plugin_path . 'js/base_admin.js' );
		$admin_script_ver = filemtime( $plugin_path . 'js/admin.js' );

		wp_enqueue_style('nohackme_defender_base_admin_style', $plugin_url . 'css/base_admin.css', array(), $base_admin_style_ver);
		wp_enqueue_style('nohackme_defender_admin_style', $plugin_url . 'css/admin-style.css', array(), $admin_style_ver);
		wp_enqueue_script('nohackme_defender-base-admin-script', $plugin_url . 'js/base_admin.js', array('jquery'), $base_admin_script_ver, true);
		wp_enqueue_script('nohackme_defender-admin-script', $plugin_url . 'js/admin.js', array('jquery'), $admin_script_ver, true);
	}
}
add_action('admin_enqueue_scripts', 'nohackme_defender_enqueue_admin_scripts');


function nohackme_defender_load_textdomain() {
	$locale          = determine_locale();
	$polylang_locale = nohackme_defender_get_polylang_admin_locale();

	if ( '' !== $polylang_locale ) {
		$locale = $polylang_locale;

		// Polylang's content-language filter does not change the WordPress user locale.
		// Replace this domain only on the NoHackMe screen so other admin pages are untouched.
		if ( is_textdomain_loaded( 'nohackme-defender' ) ) {
			unload_textdomain( 'nohackme-defender' );
		}
	}

	$wordpress_translation = WP_LANG_DIR . '/plugins/nohackme-defender-' . $locale . '.mo';
	$bundled_translation   = plugin_dir_path( __FILE__ ) . 'languages/nohackme-defender-' . $locale . '.mo';

	if ( is_readable( $wordpress_translation ) ) {
		load_textdomain( 'nohackme-defender', $wordpress_translation );
	} elseif ( is_readable( $bundled_translation ) ) {
		load_textdomain( 'nohackme-defender', $bundled_translation );
	} elseif ( '' !== $polylang_locale && class_exists( 'NOOP_Translations' ) ) {
		// Keep the selected Polylang language authoritative when no catalog exists.
		$GLOBALS['l10n']['nohackme-defender'] = new NOOP_Translations();
	}
}
add_action( 'init', 'nohackme_defender_load_textdomain' );

/**
 * Get the Polylang admin content language for the NoHackMe settings screen.
 *
 * @return string Locale or an empty string when no language filter is selected.
 */
function nohackme_defender_get_polylang_admin_locale() {
	$nohackme_defender_page = nohackme_defender_get_admin_query_value('page');
	if ( ! is_admin() || 'nohackme_defender_parent' !== sanitize_key($nohackme_defender_page) || ! function_exists( 'pll_languages_list' ) ) {
		return '';
	}

	$nohackme_defender_query_language = nohackme_defender_get_admin_query_value('lang');
	if ( '' !== $nohackme_defender_query_language ) {
		$language_slug = sanitize_key($nohackme_defender_query_language);
		if ( 'all' === $language_slug ) {
			return '';
		}
	} else {
		$language_slug = sanitize_key( (string) get_user_meta( get_current_user_id(), 'pll_filter_content', true ) );
	}

	if ( '' === $language_slug ) {
		return '';
	}

	$language_slugs   = pll_languages_list( array( 'fields' => 'slug' ) );
	$language_locales = pll_languages_list( array( 'fields' => 'locale' ) );
	$language_index   = is_array( $language_slugs ) ? array_search( $language_slug, $language_slugs, true ) : false;

	if ( false === $language_index || ! is_array( $language_locales ) || ! isset( $language_locales[ $language_index ] ) ) {
		return '';
	}

	return sanitize_text_field( $language_locales[ $language_index ] );
}

function nohackme_defender_check_wpconfig() {
    if (wp_doing_ajax()) {
        return;
    }

	$path_to_wp_config = ABSPATH . 'wp-config.php';
	$config_contents = _pdxglobal_get_file_via_wpfs($path_to_wp_config);

	// The string that needs to be found
	$needle = "/nohackme.php";

	 // Check if the string is contained in wp-config.php
	if (!strstr($config_contents, $needle)) {
   // If not found, add a notification to the admin panel
			add_action('admin_notices', 'nohackme_defender_admin_notice_wpconfig');
	}
}
// Calling a function when the admin panel is loaded
add_action('admin_init', 'nohackme_defender_check_wpconfig');
function nohackme_defender_admin_notice_wpconfig() {
	?>
	<div class="notice notice-warning">
			<p><?php esc_html_e('To work with the NoHackMe Defender plugin, you need to add the following lines to your wp-config.php file located in the root of the site:', 'nohackme-defender'); ?></p>
			<p><textarea rows="3" style="width: 100%;" onclick="this.select();">if ( is_file('<?php echo esc_html(NOHACKME_DEFENDER_PLUGIN_PATH); ?>nohackme.php') ) { require_once( '<?php echo esc_html(NOHACKME_DEFENDER_PLUGIN_PATH); ?>nohackme.php' ); }</textarea></p>
			<p><?php esc_html_e('Add them to the wp-config.php file after the line', 'nohackme-defender'); ?> &lt;?php <?php esc_html_e('(on the next line)', 'nohackme-defender'); ?>.</p>
   <p><?php esc_html_e('Unfortunately, it was not possible to do this automatically.', 'nohackme-defender'); ?></p>
	</div>
	<?php
}

if ( is_admin() ) {
	if ( defined('PDXGLOBAL_NOTICE_SHOWED') ) { } else {
		$nohackme_defender_global_options = get_option('pdxglobal_options');
		// Check if the user has previously closed the notification
	  if (isset($nohackme_defender_global_options['daily_notice_dismissed']) && $nohackme_defender_global_options['daily_notice_dismissed']) {
	  } else {
			if ( isset($nohackme_defender_global_options['notices']['msg']) and strlen($nohackme_defender_global_options['notices']['msg']) ) {
				add_action('admin_notices', 'nohackme_defender_show_admin_notice');
			}
		}
	}
}
// Displaying a notification
function nohackme_defender_show_admin_notice() {
	static $nohackme_defender_notice_shown = false;
	if ( !$nohackme_defender_notice_shown ) {
		$nohackme_defender_options = get_option('pdxglobal_options');

		if ( isset($nohackme_defender_options['notices']['msg']) and strlen($nohackme_defender_options['notices']['msg']) ) {
			echo '<div class="notice notice-success is-dismissible" id="pdxglobal-notice">' . wp_kses_post(stripslashes($nohackme_defender_options['notices']['msg'])) . '</div>';
			$nohackme_defender_notice_shown = true;
		}
	}
}
if ( !function_exists('pdxglobal_dismiss_daily_notice') ) {
	function pdxglobal_dismiss_daily_notice() {
	  // User rights check
	  if (!current_user_can('manage_options')) return;

	  // Get current plugin settings
	  $nohackme_defender_global_options = get_option('pdxglobal_options');

	  // Set the flag about closing the notification
	  $nohackme_defender_global_options['daily_notice_dismissed'] = 1;

	  // Saving modified settings
	  update_option('pdxglobal_options', $nohackme_defender_global_options);

	  wp_die();
	}
	add_action('wp_ajax_pdxglobal_dismiss_daily_notice', 'pdxglobal_dismiss_daily_notice');
	function pdxglobal_admin_scripts() {
	  ?>
	  <script type="text/javascript">
	    jQuery(document).ready(function($) {
	      $(document).on('click', '#pdxglobal-notice .notice-dismiss', function() {
	        // Sends an AJAX request to the server to save this state
	        $.post(ajaxurl, {
	          action: 'pdxglobal_dismiss_daily_notice'
	        });
	      });
	    });
	  </script>
	  <?php
	}
	add_action('admin_footer', 'pdxglobal_admin_scripts');
}

function nohackme_defender_add_settings_link($links) {
  $settings_link = '<a href="' . admin_url('admin.php?page=nohackme_defender_parent') . '">' . esc_html__('Settings', 'nohackme-defender') . '</a>';
  array_push($links, $settings_link);
  return $links;
}
add_filter('plugin_action_links_nohackme_defender/nohackme_defender.php', 'nohackme_defender_add_settings_link');

require_once NOHACKME_DEFENDER_PLUGIN_PATH . 'general.php';
require_once NOHACKME_DEFENDER_PLUGIN_PATH . 'funcs.php';
require_once NOHACKME_DEFENDER_PLUGIN_PATH . 'ajax.php';
