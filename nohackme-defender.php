<?php
/**
 * Plugin Name: NoHackMe Defender
 * Plugin URI: https://needtome.com/nohackme/
 * Description: NoHackMe Defender ensures your WordPress site's safety by blocking IP addresses upon receiving suspicious requests or too many requests from a single IP in a certain period. Keep hackers at bay with efficient real-time monitoring and security enforcement. Sponsors of plugin development: <a href="https://malinovsky.io">malinovsky.io</a>, <a href="https://gloap.net">gloap.net</a>, <a href="https://gloapm.com">gloapm.com</a>, <a href="https://imgai.art">imgai.art</a>
 * Author: Parad0x <paraz0n3@gmail.com>
 * Version: 1.0.0
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

$upload_dir = wp_upload_dir();
define('PDXNOHACKME_NAME', 'NoHackMe Defender');
define('PDXNOHACKME_BANNED_PATH', $upload_dir['basedir'] . '/hackme_ban/');
define('PDXNOHACKME_SETTINGS_PATH', $upload_dir['basedir'] . '/nohackme/');
define('PDXNOHACKME_PLUGIN_PATH', plugin_dir_path(__FILE__));

register_activation_hook( __FILE__, 'pdxnohackme_activation' );
register_deactivation_hook( __FILE__, 'pdxnohackme_deactivation' );
register_uninstall_hook(__FILE__, 'pdxnohackme_uninstall');
function pdxnohackme_uninstall() {
  	// remove plugin options
  	delete_option('pdxnohackme_options');
  	delete_option('pdxnohackme_license');

  	$dirs = array(PDXNOHACKME_BANNED_PATH, PDXNOHACKME_SETTINGS_PATH);
  	foreach($dirs as $dir) {
    	if (file_exists($dir)) {
      		_pdxglobal_delete_folder_recursively($dir);
    	}
  	}
}

function pdxnohackme_activation(){

	$default_options = get_option('pdxnohackme_options');
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
	update_option('pdxnohackme_options', $default_options);


	$path_to_wp_config = ABSPATH . 'wp-config.php';
	$config_contents = _pdxglobal_get_file_via_wpfs($path_to_wp_config);

	$insert_code = PHP_EOL . "if ( is_file('" . esc_html(PDXNOHACKME_PLUGIN_PATH) . "nohackme.php') ) { require_once( '" . esc_html(PDXNOHACKME_PLUGIN_PATH) . "nohackme.php' ); }" . PHP_EOL;

	if (strpos($config_contents, $insert_code) === false) {
		$config_contents = preg_replace("/<\?php/", "<?php" . PHP_EOL . $insert_code, $config_contents, 1);
		ob_start();
		_pdxglobal_update_file_via_wpfs($path_to_wp_config, $config_contents);
		ob_end_clean();
	}

  	// cron task activation
	if (!wp_next_scheduled('pdxnohackme_daily_event')) {
    	wp_schedule_event(time(), 'daily', 'pdxnohackme_daily_event');
  	}

	// creating folders
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	WP_Filesystem();
	global $wp_filesystem;
	$htaccess_content = "Options -Indexes\nOrder allow,deny\nDeny from all";

	if (!is_dir(PDXNOHACKME_BANNED_PATH)) {
	    wp_mkdir_p(PDXNOHACKME_BANNED_PATH);
	}
	if (!file_exists(PDXNOHACKME_BANNED_PATH . '/.htaccess')) {
		$wp_filesystem->put_contents(PDXNOHACKME_BANNED_PATH . '/.htaccess', $htaccess_content, FS_CHMOD_FILE);
	}

	$save_path = PDXNOHACKME_SETTINGS_PATH;
	if (!is_dir($save_path)) {
	    wp_mkdir_p($save_path);
	}
	if (!file_exists($save_path . '/.htaccess')) {
		$wp_filesystem->put_contents($save_path . '/.htaccess', $htaccess_content, FS_CHMOD_FILE);
	}
  	_pdxglobal_update_file_via_wpfs($save_path . 'settings', $default_options);
}
function pdxnohackme_deactivation(){
	$path_to_wp_config = ABSPATH . 'wp-config.php';
	$config_contents = _pdxglobal_get_file_via_wpfs($path_to_wp_config);

	$insert_code = PHP_EOL . "if ( is_file('" . esc_html(PDXNOHACKME_PLUGIN_PATH) . "nohackme.php') ) { require_once( '" . esc_html(PDXNOHACKME_PLUGIN_PATH) . "nohackme.php' ); }" . PHP_EOL;

	// Remove the code if it exists
	$config_contents = str_replace($insert_code, "", $config_contents);
	$config_contents = str_replace('<?php' . PHP_EOL . PHP_EOL, '<?php' . PHP_EOL, $config_contents);
	$config_contents = str_replace('<?php' . PHP_EOL . PHP_EOL, '<?php' . PHP_EOL, $config_contents);
	_pdxglobal_update_file_via_wpfs($path_to_wp_config, $config_contents);


	// deactivation of cron task
	$timestamp = wp_next_scheduled('pdxnohackme_daily_event');
  if ($timestamp) {
    wp_unschedule_event($timestamp, 'pdxnohackme_daily_event');
  }

  // remove blocked IP
	if (is_dir(PDXNOHACKME_BANNED_PATH)) {
    $objects = scandir(PDXNOHACKME_BANNED_PATH);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        @wp_delete_file(PDXNOHACKME_BANNED_PATH . $object);  // Delete files inside folder
      }
    }
  }

}

// add function to the specified hook
add_action( 'pdxnohackme_daily_event', 'pdxnohackme_do_daily_event' );
function pdxnohackme_do_daily_event(){

	$default_options = get_option('pdxnohackme_options');
	_pdxglobal_check_new_messages($default_options);

	$upload_dir = wp_upload_dir();
	$hackme_path = $upload_dir['basedir'] . '/hackme_ban/';
	$pdxnohackme_options = get_option( 'pdxnohackme_options' );
	if ( isset($pdxnohackme_options['block_time']) and is_numeric($pdxnohackme_options['block_time']) and $pdxnohackme_options['block_time'] > 0 ) {} else {
		$pdxnohackme_options['block_time'] = 1;
	}
	$hackme_time = time() - (86400*$pdxnohackme_options['block_time']);

	if ( is_dir($hackme_path) ) {
		$files = scandir($hackme_path, SCANDIR_SORT_NONE);
		if (isset($files) and is_array($files) and count($files)) { foreach ($files as $file) {
			if ( $file == '.' ) continue;
			if ( $file == '..' ) continue;
			if ( !ctype_digit(substr($file, 0, 1)) ) continue;

			if ( filectime($hackme_path . $file) <= $hackme_time ) {
				@wp_delete_file($hackme_path . $file);
			}
		} }
	}

 // removing counters to prevent excessive growth
	$settings_path = PDXNOHACKME_SETTINGS_PATH;
	if ( is_file($settings_path . 'cur_ips_counters') ) {
		@wp_delete_file($settings_path . 'cur_ips_counters');
	}

}

function pdxnohackme_admin_menu_logs(){
	$block_qty = _pdxnohackme_get_blocks_qty();
  	echo '<div class="wrap"><h1>' . esc_html__('Blocked IP', 'nohackme-defender') . ' (' . esc_html($block_qty) . ')</h1>';
	pdxnohackme_admin_menu_logs_output();
	echo '</div>';
}
function pdxnohackme_admin_menu_logs_output () {
	$SxGeo = _pdxnohackme_get_geo();
	if ( is_string($SxGeo) ) {
		echo '<div class="pdxhighlight">';
		echo wp_kses_post($SxGeo);
		echo '</div>';
	}

	if ( isset($_REQUEST['clean']) && isset($_REQUEST['pdxnohackme_clean_nonce_field']) ) {
	    $nonce = sanitize_text_field(wp_unslash($_REQUEST['pdxnohackme_clean_nonce_field']));
	    if ( wp_verify_nonce($nonce, 'pdxnohackme_clean_action') ) {
			$clean_input = sanitize_text_field(wp_unslash($_REQUEST['clean']));
	        if ( strlen($clean_input) && ctype_digit(substr($clean_input, 0, 1)) ) {
	            $clean_input = preg_replace('/[^0-9.]/', '', $clean_input);
	            if ( is_file(PDXNOHACKME_BANNED_PATH . $clean_input) ) {
	                @wp_delete_file(PDXNOHACKME_BANNED_PATH . $clean_input);
	            }
	            echo '<p>' . esc_html__('IP unlocked', 'nohackme-defender') . '</p>';
	        }
	    }
	}
	$block_qty = _pdxnohackme_get_blocks_qty();

	if ( $block_qty > 0 ) {
		$aips = array();
		$files = scandir(PDXNOHACKME_BANNED_PATH, SCANDIR_SORT_NONE);
	  if (isset($files) and is_array($files) and count($files)) { foreach ( $files as $file) {
	    if ( $file == '.' ) continue;
	    if ( $file == '..' ) continue;
			if ( !ctype_digit(substr($file, 0, 1)) ) continue;

	    $aips [$file]= filectime(PDXNOHACKME_BANNED_PATH . $file);
	  } }

		if (isset($aips) and is_array($aips) and count($aips)) {
			echo '<table border="1" cellpadding="7">';
   		echo '<tr><th>' . esc_html__('IP', 'nohackme-defender') . '</th><th>' . esc_html__('Start of blocking', 'nohackme-defender') . '</th><th>' . esc_html__('User country', 'nohackme-defender') . '</th><th>' . esc_html__('Blocking reason', 'nohackme-defender') . '</th><th>' . esc_html__('Actions', 'nohackme-defender') . '</th></tr>';
			foreach ( $aips as $ip => $start) {
				echo '<tr>';
				echo '<td>';
				$newip = _pdxnohackme_formatIp($ip);
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
				$reason = @unserialize(_pdxglobal_get_file_via_wpfs(PDXNOHACKME_BANNED_PATH . $ip));
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
				wp_nonce_field('pdxnohackme_clean_action', 'pdxnohackme_clean_nonce_field');
				echo '<input type="hidden" value="' . esc_attr($ip) . '" name="clean" /><input class="isbtn isbtn_theme_sm isbtn_theme_blue" type="submit" value="' . esc_html__('Delete', 'nohackme-defender') . '" />';
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
	echo '<p><a href="/qweqewqe?wer=php://input" target="_blank" class="button button-primary">' . esc_html__('Block me', 'nohackme-defender') . '</a></p>';
}

function pdxnohackme_admin_menu_hacklist(){
 	echo '<div class="wrap"><h1>' . esc_html__('List of suspicious requests', 'nohackme-defender') . '</h1>';
	echo '<p class="description">';
	echo esc_html__('In this section, all suspicious requests that the plugin searches for in the GET and POST parameters sent to the site are presented. When any of the presented requests are detected, the IP address is blocked.', 'nohackme-defender');
	echo '</p>';

	$has_premium = _pdxnohackme_check_premium();
	if ( !$has_premium ) {
		echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
		echo '<p class="description premium__text">';
  		echo esc_html__('Changing the list is available only in the paid version', 'nohackme-defender');
		echo wp_kses_post(_pdxnohackme_get_premium_label('pdxnohackme_parent'));
		echo '</p>';
		echo '</div>';
	}
	$file_path = PDXNOHACKME_SETTINGS_PATH . 'hacks_list';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hacks']) && check_admin_referer('pdxnohackme_hacklist_action', 'pdxnohackme_hacklist_nonce_field')) {
		$stripped_hacks = array_map('wp_unslash', $_POST['hacks']);
		$sanitized_hacks = array_map('esc_html', $stripped_hacks);
	    $hacks = array_filter($sanitized_hacks, function($value) {
	        return !empty($value);
	    });

		if ( !$has_premium ) {
   		echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Working with the list is only available in the paid version!', 'nohackme-defender') . '</p></div>';
		} else {
			_pdxnohackme_premium1($file_path, $hacks);
		}
  }

	if(file_exists($file_path) && is_readable($file_path)){
  	$hacks = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read file into array

		echo '<form method="post" action="">';
		wp_nonce_field('pdxnohackme_hacklist_action', 'pdxnohackme_hacklist_nonce_field');
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
  	echo '<button type="button" onclick="pdxnohackme_addRow()">' . esc_html__('Add a row', 'nohackme-defender') . '</button>';
		echo '</div>';
  	echo '<br><hr><br>';
?>
<input type="button" id="restoreDefaults" value="<?php echo esc_attr__('Restore Defaults', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('pdxnohackme_restore_defaults_action')); ?>">
<?php
	  echo '</form>';
		?>
    <script>
		document.getElementById('restoreDefaults').addEventListener('click', function() {
		    var nonce = this.getAttribute('data-nonce');
		    if(confirm('<?php echo esc_js(__('Are you sure you want to restore the default list?', 'nohackme-defender')); ?>')) {
		        var send_data = {
		            'action': 'pdxnohackme_restore_defaults',
		            '_wpnonce': nonce
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          data = JSON.parse(data);
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

    </script>
    <?php
	} else {
  echo '<p>' . esc_html__('List of suspicious requests not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function pdxnohackme_admin_menu_google_ips(){
  echo '<div class="wrap"><h1>' . esc_html__('List of Google spider IP ranges', 'nohackme-defender') . '</h1>';
	echo '<p class="description">';
 echo esc_html__('Ranges from this section are used to form a whitelist of IP addresses if the corresponding checkbox is checked in the plugin settings.', 'nohackme-defender');
	echo '</p>';

	$has_premium = _pdxnohackme_check_premium();
	if ( !$has_premium ) {
		echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
		echo '<p class="description premium__text">';
  	echo esc_html__('Changing the list is only available in the paid version', 'nohackme-defender');
		echo wp_kses_post(_pdxnohackme_get_premium_label('pdxnohackme_parent'));
		echo '</p>';
		echo '</div>';
	}
	$file_path = PDXNOHACKME_SETTINGS_PATH . 'robots_google_list';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hacks']) && check_admin_referer('pdxnohackme_google_ips_action', 'pdxnohackme_google_ips_nonce')) {
		$stripped_hacks = array_map('wp_unslash', $_POST['hacks']);
	    $cleaned_hacks = array_map(function($value) {
	        $sanitized_value = sanitize_text_field($value);
	        return preg_replace('/[^0-9.\/]/', '', $sanitized_value);
	    }, $stripped_hacks);
	    $hacks = array_filter($cleaned_hacks, function($value) {
	        return !empty($value);
	    });

		if ( !$has_premium ) {
   echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Work with the list is available only in the paid version!', 'nohackme-defender') . '</p></div>';
		} else {
			_pdxnohackme_premium1($file_path, $hacks);
		}
  }

	if(file_exists($file_path) && is_readable($file_path)){
  $hacks = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Reading file into an array

		echo '<form method="post" action="">';
		wp_nonce_field('pdxnohackme_google_ips_action', 'pdxnohackme_google_ips_nonce');
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
  echo '<button type="button" onclick="pdxnohackme_addRow()">' . esc_html__('Add row', 'nohackme-defender') . '</button>';
		echo '</div>';
  echo '<br><hr><br>';
?>
<input type="button" id="restoreDefaults" value="<?php echo esc_attr__('Restore Defaults', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('pdxnohackme_restore_defaults_google_action')); ?>">
<?php
	  echo '</form>';
		?>
    <script>
		document.getElementById('restoreDefaults').addEventListener('click', function() {
		    if(confirm('<?php echo esc_js(__("Are you sure you want to restore the default list?", "pdxnohackme")); ?>')) {
		        var send_data = {
		            'action': 'pdxnohackme_restore_defaults_ips_google',
		            '_wpnonce': this.getAttribute('data-nonce')
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          data = JSON.parse(data);
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
    </script>
    <?php
	} else {
  echo '<p>' . esc_html__('List of IP ranges not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function pdxnohackme_admin_menu_yandex_ips(){
  echo '<div class="wrap"><h1>' . esc_html__('List of Yandex spider IP ranges', 'nohackme-defender') . '</h1>';
	echo '<p class="description">';
 	echo esc_html__('Ranges from this section are used to create a whitelist of IP addresses if the corresponding checkbox is checked in the plugin settings.', 'nohackme-defender');
	echo '</p>';
	echo '<p class="description">';
 	echo esc_html__('Актуальный список диапазонов адресов поисковых роботов Яндекс можно увидеть', 'nohackme-defender') . ' <a target="_blank" href="https://yandex.ru/ips">' . esc_html__('на этой странице', 'nohackme-defender') . '</a>';
	echo '</p>';

	$has_premium = _pdxnohackme_check_premium();
	if ( !$has_premium ) {
		echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
		echo '<p class="description premium__text">';
  echo esc_html__('Changing the list is only available in the paid version', 'nohackme-defender');
		echo wp_kses_post(_pdxnohackme_get_premium_label('pdxnohackme_parent'));
		echo '</p>';
		echo '</div>';
	}
	$file_path = PDXNOHACKME_SETTINGS_PATH . 'robots_yandex_list';

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hacks']) && check_admin_referer('pdxnohackme_yandex_ips_action', 'pdxnohackme_yandex_ips_nonce')) {
		$stripped_hacks = array_map('wp_unslash', $_POST['hacks']);
	    $cleaned_hacks = array_map(function($value) {
	        $sanitized_value = sanitize_text_field($value);
	        return preg_replace('/[^0-9.\/]/', '', $sanitized_value);
	    }, $stripped_hacks);
	    $hacks = array_filter($cleaned_hacks, function($value) {
	        return !empty($value);
	    });

		if ( !$has_premium ) {
   echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__('Working with the list is available only in the paid version!', 'nohackme-defender') . '</p></div>';
		} else {
			_pdxnohackme_premium1($file_path, $hacks);
		}
  }

	if(file_exists($file_path) && is_readable($file_path)){
  $hacks = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read file into an array

		echo '<form method="post" action="">';
		wp_nonce_field('pdxnohackme_yandex_ips_action', 'pdxnohackme_yandex_ips_nonce');
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
  echo '<button type="button" onclick="pdxnohackme_addRow()">' . esc_html__('Add row', 'nohackme-defender') . '</button>';
		echo '</div>';
  echo '<br><hr><br>';
?>
<input type="button" id="restoreDefaults" value="<?php echo esc_attr__('Restore Defaults', 'nohackme-defender'); ?>" class="button" data-nonce="<?php echo esc_attr(wp_create_nonce('pdxnohackme_restore_defaults_yandex_action')); ?>">
<?php
	  echo '</form>';
		?>
    <script>
		document.getElementById('restoreDefaults').addEventListener('click', function() {
		    if(confirm('<?php echo esc_js(__("Are you sure you want to restore the default list?", "pdxnohackme")); ?>')) {
		        var send_data = {
		            'action': 'pdxnohackme_restore_defaults_ips_yandex',
		            '_wpnonce': this.getAttribute('data-nonce')
		        };

		        jQuery.post('/wp-admin/admin-ajax.php', send_data, function(data) {
							if(data!=''){
			          data = JSON.parse(data);
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
    </script>
    <?php
	} else {
  echo '<p>' . esc_html__('List of IP ranges not found', 'nohackme-defender') . '</p>';
	}

	echo '</div>';
}

function pdxnohackme_admin_menu_stats(){
  	echo '<div class="wrap"><h1>' . esc_html__('Top by blocking counters', 'nohackme-defender') . '</h1>';
	pdxnohackme_admin_menu_stats_output();
	echo '</div>';
}
function pdxnohackme_admin_menu_stats_output () {
	$SxGeo = _pdxnohackme_get_geo();
	if ( is_string($SxGeo) ) {
		echo '<div class="pdxhighlight">';
		echo wp_kses_post($SxGeo);
		echo '</div>';
	}

	$acounter_1min = array();
	$acounter_10min = array();
	$acounter_60min = array();
	$ips = array();
	if ( is_file(PDXNOHACKME_SETTINGS_PATH . 'cur_ips_counters') ) {
		$data = @_pdxglobal_get_file_via_wpfs(PDXNOHACKME_SETTINGS_PATH . 'cur_ips_counters');
		if ( strlen($data) ) {
			$ips = @unserialize($data);
		}
	}
	if (isset($ips) and is_array($ips) and count($ips)) {
  	echo '<p>Unique IP addresses today: ' . count($ips) . '</p>';
		foreach ( $ips as $ip => $data) {
			if (isset($data) and is_array($data) and count($data)) {
				$qty = 0;
				foreach ( $data as $min => $ip_qty) {
					$qty += $ip_qty;
				}
				if ( $qty > 10 ) {
					$acounter_60min [$ip]= $qty;
				}

				$qty = 0;
				foreach ( $data as $min => $ip_qty) {
					if ( $ip_qty > $qty ) {
						$qty = $ip_qty;
					}
				}
				if ( $qty > 3 ) {
					$acounter_1min [$ip]= $qty;
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
				$newip = _pdxnohackme_formatIp($ip);
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
				$newip = _pdxnohackme_formatIp($ip);
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
				$newip = _pdxnohackme_formatIp($ip);
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
	$block_qty = _pdxnohackme_get_blocks_qty();
  add_menu_page( 'noHackMe', 'noHackMe', 'manage_options', 'pdxnohackme_parent', 'pdxnohackme_admin_menu', '', 101 );
  add_action( 'admin_init', 'pdxnohackme_register_settings' );

 add_submenu_page( 'pdxnohackme_parent', esc_html__('Blocked IP', 'nohackme-defender'), esc_html__('Blocked IP', 'nohackme-defender') . (($block_qty > 0)?(' <span class="update-plugins">' . $block_qty . '</span>'):(' (0)')), 'manage_options', 'pdxnohackme_logs', 'pdxnohackme_admin_menu_logs' );
 add_submenu_page( 'pdxnohackme_parent', esc_html__('Statistics', 'nohackme-defender'), esc_html__('Statistics', 'nohackme-defender'), 'manage_options', 'pdxnohackme_stats', 'pdxnohackme_admin_menu_stats' );
 add_submenu_page( 'pdxnohackme_parent', esc_html__('Suspicious requests', 'nohackme-defender'), esc_html__('Suspicious requests', 'nohackme-defender'), 'manage_options', 'pdxnohackme_hacklist', 'pdxnohackme_admin_menu_hacklist' );
 add_submenu_page( 'pdxnohackme_parent', esc_html__('Google IP Ranges', 'nohackme-defender'), esc_html__('Google IP Ranges', 'nohackme-defender'), 'manage_options', 'pdxnohackme_google_ips', 'pdxnohackme_admin_menu_google_ips' );
 add_submenu_page( 'pdxnohackme_parent', esc_html__('Yandex IP Ranges', 'nohackme-defender'), esc_html__('Yandex IP Ranges', 'nohackme-defender'), 'manage_options', 'pdxnohackme_yandex_ips', 'pdxnohackme_admin_menu_yandex_ips' );
} );
function pdxnohackme_register_settings() {
  register_setting( 'pdxnohackme_settings_group', 'pdxnohackme_options', 'pdxnohackme_sanitize_options' );
	register_setting('pdxnohackme_premium_group', 'pdxnohackme_license', 'pdxnohackme_sanitize_premium_options');
}
function pdxnohackme_admin_menu() {
	if ( isset($_GET['tab']) ) {
		if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field($_GET['nonce']), 'pdxnohackme_admin_action')) {
	        wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'nohackme-defender'));
	    }
	}

    $active_tab = isset($_GET['tab']) ? esc_html(sanitize_key($_GET['tab'])) : 'settings';

    echo '<div class="wrap">';
    echo '<h1>' . esc_html(PDXNOHACKME_NAME) . '</h1>';

    // Tabs
    ?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=pdxnohackme_parent&tab=settings&nonce=<?php echo esc_html(wp_create_nonce('pdxnohackme_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Settings', 'nohackme-defender'); ?></a>
    <?php if (!_pdxnohackme_premium_exists()) { ?>
        <a href="?page=pdxnohackme_parent&tab=premium&nonce=<?php echo esc_html(wp_create_nonce('pdxnohackme_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'premium' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Premium Features', 'nohackme-defender'); ?></a>
    <?php } ?>
        <a href="?page=pdxnohackme_parent&tab=plugins&nonce=<?php echo esc_html(wp_create_nonce('pdxnohackme_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'plugins' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Other plugins', 'nohackme-defender'); ?></a>
    <?php if (_pdxnohackme_show_branding()) { ?>
        <a href="?page=pdxnohackme_parent&tab=development&nonce=<?php echo esc_html(wp_create_nonce('pdxnohackme_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'development' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('Website Development', 'nohackme-defender'); ?></a>
        <a href="?page=pdxnohackme_parent&tab=seo&nonce=<?php echo esc_html(wp_create_nonce('pdxnohackme_admin_action')); ?>" class="nav-tab <?php echo $active_tab == 'seo' ? 'nav-tab-active' : ''; ?>"><?php echo esc_html__('SEO', 'nohackme-defender'); ?></a>
    <?php } ?>
    </h2>
    <?php

    // Content of tabs
    if ($active_tab == 'settings') {
        pdxnohackme_get_settings_page();
    } elseif ($active_tab == 'premium') {
        pdxnohackme_get_premium_page();
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

function pdxnohackme_get_settings_page( ) {
	echo '<div>&nbsp;</div>';
	$pdxnohackme_options = get_option('pdxnohackme_options');
	$has_premium = _pdxnohackme_check_premium();

	if ( !$has_premium ) {
		_pdxnohackme_get_premium_desc('pdxnohackme_parent');
	}

	echo '<form method="post" action="options.php">';
	settings_fields('pdxnohackme_settings_group');

	echo '<table class="form-table">';
	echo '<tbody>';

	// Temporarily disable tracking and blocking
	$tmpval = 'cancel';
 echo '<tr class="highlight"><th scope="row">' . esc_html__('Temporarily disable plugin', 'nohackme-defender') . '</th><td>';
	echo '<input type="checkbox" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="1"' . (isset($pdxnohackme_options[$tmpval]) && $pdxnohackme_options[$tmpval] == 1 ? ' checked' : '') . '>';
	echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
 echo '<p class="description">' . esc_html__('If the checkbox is checked, the plugin will stop tracking and blocking IP addresses, and will also provide access to the site from IP addresses that are already blocked.', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	$tmpval = 'cancel_block';
 echo '<tr><th scope="row">' . esc_html__('Cancel current IP blocking if there is a GET parameter', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="' . (isset($pdxnohackme_options[$tmpval]) && strlen($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '" style="width: 100%;">';
 echo '<p class="description">' . esc_html__('Specify the GET parameter that can be entered to remove the current IP from the list of blocked ones.', 'nohackme-defender');
	if ( isset($pdxnohackme_options[$tmpval]) && strlen($pdxnohackme_options[$tmpval]) ) {
		$maybe_get_param = $pdxnohackme_options[$tmpval];
	} else {
		$maybe_get_param = _pdxnohackme_generateRandomString();
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
	$editor_content = (isset($pdxnohackme_options[$tmpval]) && strlen($pdxnohackme_options[$tmpval]) ? $pdxnohackme_options[$tmpval] : $tmpdef);

	$args = array(
	  'textarea_name' => 'pdxnohackme_options[' . $tmpval . ']',
	  'textarea_rows' => 3,
	  'teeny' => true, // Set to true to create a minimal editor, false for full editor
	);

	wp_editor($editor_content, $editor_id, $args);

	echo '</td></tr>';

	// How many days to block IP
	$tmpval = 'block_time';
	echo '<tr><th scope="row">' . esc_html__('How many days to block IP', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="' . (isset($pdxnohackme_options[$tmpval]) && is_numeric($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '" size="3">';
	echo '</td></tr>';

 echo '<tr><td colspan="2"><h2>' . esc_html__('Whitelists', 'nohackme-defender') . '</h2><p class="description">' . esc_html__('Whitelists allow disabling the check for the number of requests to the site within a certain time period for specific IPs.', 'nohackme-defender') . '</p></td></tr>';

	// Exclude IP addresses
	$ip = preg_replace('/[^0-9.]/', '', $_SERVER['REMOTE_ADDR']);

	$tmpval = 'exclude_ip';
  echo '<tr><th scope="row">' . esc_html__('Whitelist of IP addresses', 'nohackme-defender') . '</th><td>';
	echo '<textarea id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" cols="33" rows="7">' . (isset($pdxnohackme_options[$tmpval]) && strlen($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '</textarea>';
	// translators: %s: IP address of the current user
  	echo '<p class="description">' . esc_html__('The specified IP addresses will not be blocked by the plugin.', 'nohackme-defender') . '<br>' . esc_html__('Each IP from a new line', 'nohackme-defender') . '.<br>' . sprintf(esc_html__('Your current IP: %s', 'nohackme-defender'), esc_html($ip));
	if ( isset($_SERVER['SERVER_ADDR']) and strlen($_SERVER['SERVER_ADDR']) ) {
		// translators: %s: IP address of your site
  		echo '<br>' . sprintf(esc_html__('IP address of your site: %s', 'nohackme-defender'), esc_html(preg_replace('/[^0-9.]/', '', sanitize_text_field($_SERVER['SERVER_ADDR']))));
	}
	echo '</p>';
	echo '</td></tr>';

	$tmpval = 'exclude_google_ips';
 echo '<tr' . ((!$has_premium)?(' class="premium"'):('')) . '><th scope="row">' . esc_html__('Do not block Google robots', 'nohackme-defender') . '</th><td>';
	echo '<input type="checkbox"' . ((!$has_premium)?(' disabled readonly'):('')) . ' id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="1"' . (isset($pdxnohackme_options[$tmpval]) && $pdxnohackme_options[$tmpval] == 1 ? ' checked' : '') . '>';
	echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
 echo '<p class="description">' . esc_html__('The list includes well-known IP address ranges of Google.', 'nohackme-defender') . '<br><a href="/wp-admin/admin.php?page=pdxnohackme_google_ips">' . esc_html__('Change the list of ranges', 'nohackme-defender') . '</a></p>';
	if ( !$has_premium ) {
		echo wp_kses_post(_pdxnohackme_get_premium_label('pdxnohackme_parent'));
	}
	echo '</td></tr>';

	$tmpval = 'exclude_yandex_ips';
 echo '<tr' . ((!$has_premium)?(' class="premium"'):('')) . '><th scope="row">' . esc_html__('Do not block Yandex robots', 'nohackme-defender') . '</th><td>';
	echo '<input type="checkbox"' . ((!$has_premium)?(' disabled readonly'):('')) . ' id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="1"' . (isset($pdxnohackme_options[$tmpval]) && $pdxnohackme_options[$tmpval] == 1 ? ' checked' : '') . '>';
	echo '<label for="pdxmeta_options_' . esc_html($tmpval) . '"></label>';
 echo '<p class="description">' . esc_html__('The list of addresses is taken from the official Yandex page:', 'nohackme-defender') . ' <a href="https://yandex.ru/ips" target="_blank">https://yandex.ru/ips</a><br><a href="/wp-admin/admin.php?page=pdxnohackme_yandex_ips">' . esc_html__('Change the list of ranges', 'nohackme-defender') . '</a></p>';
	if ( !$has_premium ) {
		echo wp_kses_post(_pdxnohackme_get_premium_label('pdxnohackme_parent'));
	}
	echo '</td></tr>';

	$tmpval = 'exclude_user_agent';
 echo '<tr><th scope="row">' . esc_html__('White list User Agent', 'nohackme-defender') . '</th><td>';
	echo '<textarea id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" cols="33" rows="7">' . (isset($pdxnohackme_options[$tmpval]) && strlen($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '</textarea>';
 echo '<p class="description">' . esc_html__('Requests with the specified User Agent will not be blocked by the plugin.', 'nohackme-defender') . ' <span class="is_a is_link" onclick="showOverlayBlock(\'#pdxnohackme_useragent_list\');">' . esc_html__('View a list of popular User Agents', 'nohackme-defender') . '</span>' . '<br>' . esc_html__('Each User Agent on a new line. It is not necessary to specify the entire User Agent - the plugin checks for the presence of the specified string in the request User Agent', 'nohackme-defender') . '.<br><strong>' . esc_html__('ATTENTION!!! An attacker can specify any User Agent for their request. Thus, they can easily pretend to be a Google or Yandex robot if you add these robots\' User Agents to the whitelist.', 'nohackme-defender') . '</strong>';
	echo '</p>';
	echo '<div id="pdxnohackme_useragent_list" style="display: none;">';
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
		echo '<div class="pdxnohackme_useragent flex_item flex_item2">
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
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="' . (isset($pdxnohackme_options[$tmpval]) && is_numeric($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '" size="3">';
	echo '<p class="description">' . esc_html__('Block IP if there are this many or more requests within one minute', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	// Max number of requests per 10 minutes
	$tmpval = 'block_if_10min';
	echo '<tr><th scope="row">' . esc_html__('Per 10 minutes', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="' . (isset($pdxnohackme_options[$tmpval]) && is_numeric($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '" size="3">';
	echo '<p class="description">' . esc_html__('Block IP if there are this many or more requests within ten minutes', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	// Max number of requests per 50 minutes
	$tmpval = 'block_if_50min';
	echo '<tr><th scope="row">' . esc_html__('Per 50 minutes', 'nohackme-defender') . '</th><td>';
	echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_options[' . esc_html($tmpval) . ']" value="' . (isset($pdxnohackme_options[$tmpval]) && is_numeric($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '" size="3">';
	echo '<p class="description">' . esc_html__('Block IP if there are this many or more requests within fifty minutes', 'nohackme-defender') . '</p>';
	echo '</td></tr>';

	echo '</tbody>';
	echo '</table>';

	echo '<p class="submit">';
	echo '<input type="submit" class="button-primary" value="' . esc_html__('Save Changes', 'nohackme-defender') . '" />';
	echo '</p>';

	echo '</form>';
}
function pdxnohackme_get_premium_page( ) {
	echo '<div>&nbsp;</div>';
	$premium_type = 0;
	if ( _pdxnohackme_check_premium() ) {
		$premium_type = 777;
	}
	$premium_cost = 5;

	switch ($premium_type) {
		case 1:
			// Field for entering the license key
			$pdxnohackme_options = get_option('pdxnohackme_license');
			echo '<form method="post" action="options.php">';
			settings_fields('pdxnohackme_premium_group');
			echo '<table class="form-table">';
			echo '<tbody>';
			$tmpval = 'license_key';
		 	echo '<tr class="highlight"><th scope="row">' . esc_html__('License key', 'nohackme-defender') . '</th><td>';
			echo '<input type="text" id="pdxmeta_options_' . esc_html($tmpval) . '" name="pdxnohackme_license[' . esc_html($tmpval) . ']" value="' . (isset($pdxnohackme_options[$tmpval]) ? esc_attr($pdxnohackme_options[$tmpval]) : '') . '" class="regular-text">';
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
			echo esc_html__('For inquiries about purchasing the paid version of the plugin, write to the email', 'nohackme-defender') . ' <a href="mailto:paraz0n3@gmail.com?subject=Purchase of the premium version of the ' . esc_html(PDXNOHACKME_NAME) . ' plugin">paraz0n3@gmail.com</a>';
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
 echo esc_html__('Edit the list of data that, when detected, the request is considered suspicious and its blocking is performed.', 'nohackme-defender');
	echo '</li>';
	echo '</ul>';
}
function pdxnohackme_sanitize_premium_options($input) {
	$input['license_key'] = sanitize_text_field($input['license_key']);

	return $input;
}
function pdxnohackme_sanitize_options( $input ) {
  $input['block_time'] = intval( $input['block_time'] );

	$hackme_settings = array();

	$outp = 0;
	if ( isset($input['cancel']) and is_numeric($input['cancel']) and $input['cancel'] == 1 ) {
		$outp = 1;
	}
	$hackme_settings ['cancel']= $outp;
  $input['cancel'] = $outp;

	$outp = 0;
	if ( _pdxnohackme_check_premium() ) {
		$outp = _pdxnohackme_premium_save($input, 'exclude_google_ips');
	}
	$hackme_settings ['exclude_google_ips']= $outp;
  $input['exclude_google_ips'] = $outp;

	$outp = 0;
	if ( _pdxnohackme_check_premium() ) {
		$outp = _pdxnohackme_premium_save($input, 'exclude_yandex_ips');
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

	$save_path = PDXNOHACKME_SETTINGS_PATH;
	if ( !is_dir($save_path) ) {
		wp_mkdir_p($save_path);
	}
	_pdxglobal_update_file_via_wpfs($save_path . 'settings', $hackme_settings);

  return $input;
}

function pdxnohackme_enqueue_admin_scripts($hook_suffix) {
	$plugin_url = plugin_dir_url( __FILE__ );
	$plugin_path = plugin_dir_path( __FILE__ );
	if ( strpos($hook_suffix, 'pdxnohackme_') === false ) {} else {
		$base_admin_style_ver = filemtime( $plugin_path . 'css/base_admin.css' );
		$admin_style_ver = filemtime( $plugin_path . 'css/admin-style.css' );
		$base_admin_script_ver = filemtime( $plugin_path . 'js/base_admin.js' );
		$admin_script_ver = filemtime( $plugin_path . 'js/admin.js' );

		wp_enqueue_style('pdxnohackme_base_admin_style', $plugin_url . 'css/base_admin.css', array(), $base_admin_style_ver);
		wp_enqueue_style('pdxnohackme_admin_style', $plugin_url . 'css/admin-style.css', array(), $admin_style_ver);
		wp_enqueue_script('pdxnohackme-base-admin-script', $plugin_url . 'js/base_admin.js', array('jquery'), $base_admin_script_ver, true);
		wp_enqueue_script('pdxnohackme-admin-script', $plugin_url . 'js/admin.js', array('jquery'), $admin_script_ver, true);
	}
}
add_action('admin_enqueue_scripts', 'pdxnohackme_enqueue_admin_scripts');


function pdxnohackme_load_textdomain() {
  load_plugin_textdomain('nohackme-defender', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action('plugins_loaded', 'pdxnohackme_load_textdomain');

function pdxnohackme_check_wpconfig() {
	$path_to_wp_config = ABSPATH . 'wp-config.php';
	$config_contents = _pdxglobal_get_file_via_wpfs($path_to_wp_config);

	// The string that needs to be found
	$needle = "nohackme-defender/nohackme.php";

	 // Check if the string is contained in wp-config.php
	if (!strstr($config_contents, $needle)) {
   // If not found, add a notification to the admin panel
			add_action('admin_notices', 'pdxnohackme_admin_notice_wpconfig');
	}
}
// Calling a function when the admin panel is loaded
add_action('admin_init', 'pdxnohackme_check_wpconfig');
function pdxnohackme_admin_notice_wpconfig() {
	?>
	<div class="notice notice-warning">
			<p><?php esc_html_e('To work with the NoHackMe Defender plugin, you need to add the following lines to your wp-config.php file located in the root of the site:', 'nohackme-defender'); ?></p>
			<p><textarea rows="3" style="width: 100%;" onclick="this.select();">if ( is_file('<?php echo esc_html(PDXNOHACKME_PLUGIN_PATH); ?>nohackme.php') ) { require_once( '<?php echo esc_html(PDXNOHACKME_PLUGIN_PATH); ?>nohackme.php' ); }</textarea></p>
			<p><?php esc_html_e('Add them to the wp-config.php file after the line', 'nohackme-defender'); ?> &lt;?php <?php esc_html_e('(on the next line)', 'nohackme-defender'); ?>.</p>
   <p><?php esc_html_e('Unfortunately, it was not possible to do this automatically.', 'nohackme-defender'); ?></p>
	</div>
	<?php
}

if ( is_admin() ) {
	if ( defined('PDXGLOBAL_NOTICE_SHOWED') ) { } else {
		$options = get_option('pdxglobal_options');
		// Check if the user has previously closed the notification
	  if (isset($options['daily_notice_dismissed']) && $options['daily_notice_dismissed']) {
	  } else {
			if ( isset($options['notices']['msg']) and strlen($options['notices']['msg']) ) {
				add_action('admin_notices', 'pdxnohackme_show_admin_notice');
			}
		}
	}
}
// Displaying a notification
function pdxnohackme_show_admin_notice() {
	if ( defined('PDXGLOBAL_NOTICE_SHOWED') ) { } else {
		$options = get_option('pdxglobal_options');

		if ( isset($options['notices']['msg']) and strlen($options['notices']['msg']) ) {
			echo '<div class="notice notice-success is-dismissible" id="pdxglobal-notice">' . wp_kses_post(stripslashes($options['notices']['msg'])) . '</div>';
			define('PDXGLOBAL_NOTICE_SHOWED', true);
		}
	}
}
if ( !function_exists('pdxglobal_dismiss_daily_notice') ) {
	function pdxglobal_dismiss_daily_notice() {
	  // User rights check
	  if (!current_user_can('manage_options')) return;

	  // Get current plugin settings
	  $options = get_option('pdxglobal_options');

	  // Set the flag about closing the notification
	  $options['daily_notice_dismissed'] = 1;

	  // Saving modified settings
	  update_option('pdxglobal_options', $options);

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

function pdxnohackme_add_settings_link($links) {
  $settings_link = '<a href="' . admin_url('admin.php?page=pdxnohackme_parent') . '">' . esc_html__('Settings', 'nohackme-defender') . '</a>';
  array_push($links, $settings_link);
  return $links;
}
add_filter('plugin_action_links_pdxnohackme/pdxnohackme.php', 'pdxnohackme_add_settings_link');

if (is_file(PDXNOHACKME_PLUGIN_PATH . 'pdxnohackme_premium.php')) {
  include_once PDXNOHACKME_PLUGIN_PATH . 'pdxnohackme_premium.php';
}
require_once PDXNOHACKME_PLUGIN_PATH . 'general.php';
require_once PDXNOHACKME_PLUGIN_PATH . 'funcs.php';
require_once PDXNOHACKME_PLUGIN_PATH . 'ajax.php';
