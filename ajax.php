<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('wp_ajax_nohackme_defender_restore_defaults', 'nohackme_defender_restore_defaults');
function nohackme_defender_restore_defaults() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Parameters are specified incorrectly!', 'nohackme-defender'),
  );

  check_ajax_referer('nohackme_defender_restore_defaults_action', '_wpnonce', true);

  $uid = get_current_user_id();

  if ($uid > 0 && user_can($uid, 'administrator')) {
    $file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'hacks_list';
    if (file_exists($file_path)) {
      wp_delete_file($file_path);
      $out['data'] = esc_html__('List updated', 'nohackme-defender');
      $out['res'] = 'success';
    } else {
      $out['data'] = esc_html__('File does not exist.', 'nohackme-defender');
    }
  } else {
    $out['data'] = esc_html__('This functionality is only available to administrators.', 'nohackme-defender');
  }

  echo wp_json_encode($out);
  wp_die();
}

add_action('wp_ajax_nohackme_defender_restore_paths_defaults', 'nohackme_defender_restore_paths_defaults');
function nohackme_defender_restore_paths_defaults() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Parameters are specified incorrectly!', 'nohackme-defender'),
  );

  check_ajax_referer('nohackme_defender_restore_paths_defaults_action', '_wpnonce', true);

  $uid = get_current_user_id();

  if ($uid > 0 && user_can($uid, 'administrator')) {
    $file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'suspicious_paths_list';
    if (file_exists($file_path)) {
      wp_delete_file($file_path);
      $out['data'] = esc_html__('List updated', 'nohackme-defender');
      $out['res'] = 'success';
    } else {
      $out['data'] = esc_html__('File does not exist.', 'nohackme-defender');
    }
  } else {
    $out['data'] = esc_html__('This functionality is only available to administrators.', 'nohackme-defender');
  }

  echo wp_json_encode($out);
  wp_die();
}


add_action('wp_ajax_nohackme_defender_restore_defaults_ips_google', 'nohackme_defender_restore_defaults_ips_google');
function nohackme_defender_restore_defaults_ips_google() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Parameters are specified incorrectly!', 'nohackme-defender'),
  );
  check_ajax_referer('nohackme_defender_restore_defaults_google_action', '_wpnonce');
  if ( isset($_REQUEST['action']) and $_REQUEST['action'] == 'nohackme_defender_restore_defaults_ips_google' ) {
    $uid = get_current_user_id();
    if ( $uid > 0 ) {
      if (user_can($uid, 'administrator')) {
        $file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_google_list';
        if(file_exists($file_path)) {
          wp_delete_file($file_path);
        }
        $out['data'] = esc_html__('List updated', 'nohackme-defender');
        $out['res']= 'success';
      } else {
        $out['data'] = esc_html__('This functionality is only available to administrators.', 'nohackme-defender');
      }
    } else {
      $out['data'] = esc_html__('Your session has expired. Please log in again on the website.', 'nohackme-defender');
    }
  }
  echo wp_json_encode($out);

  wp_die();
}

add_action('wp_ajax_nohackme_defender_restore_defaults_ips_yandex', 'nohackme_defender_restore_defaults_ips_yandex');
function nohackme_defender_restore_defaults_ips_yandex() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Invalid parameters specified!', 'nohackme-defender'),
  );
  check_ajax_referer('nohackme_defender_restore_defaults_yandex_action', '_wpnonce');
  if ( isset($_REQUEST['action']) and $_REQUEST['action'] == 'nohackme_defender_restore_defaults_ips_yandex' ) {
    $uid = get_current_user_id();
    if ( $uid > 0 ) {
      if (user_can($uid, 'administrator')) {
        $file_path = NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_yandex_list';
        if(file_exists($file_path)) {
          wp_delete_file($file_path);
        }
        $out['data'] = esc_html__('List updated', 'nohackme-defender');
        $out['res']= 'success';
      } else {
        $out['data'] = esc_html__('This functionality is only available to administrators.', 'nohackme-defender');
      }
    } else {
      $out['data'] = esc_html__('Your session has expired. Please log in to the website again.', 'nohackme-defender');
    }
  }
  echo wp_json_encode($out);

  wp_die();
}

add_action('wp_ajax_nohackme_defender_update_google_ips_from_web', 'nohackme_defender_update_google_ips_from_web');
function nohackme_defender_update_google_ips_from_web() {
  check_ajax_referer('nohackme_defender_update_google_action', '_wpnonce');

  if ( !current_user_can('manage_options') ) {
    wp_send_json(array(
      'res' => 'error',
      'data' => esc_html__('This functionality is only available to administrators.', 'nohackme-defender'),
    ));
  }

  $ranges = _nohackme_defender_fetch_google_ip_ranges();
  if ( is_wp_error($ranges) ) {
    wp_send_json(array(
      'res' => 'error',
      'data' => $ranges->get_error_message(),
    ));
  }

  $saved = _nohackme_defender_save_ranges_file(NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_google_list', $ranges);
  if ( !$saved ) {
    wp_send_json(array(
      'res' => 'error',
      'data' => esc_html__('Unable to save the Google IP ranges list.', 'nohackme-defender'),
    ));
  }

  wp_send_json(array(
    'res' => 'success',
    'data' => sprintf(
      /* translators: %d: number of IP ranges */
      esc_html__('Google IP ranges updated. Downloaded: %d', 'nohackme-defender'),
      count($ranges)
    ),
  ));
}

add_action('wp_ajax_nohackme_defender_update_yandex_ips_from_web', 'nohackme_defender_update_yandex_ips_from_web');
function nohackme_defender_update_yandex_ips_from_web() {
  check_ajax_referer('nohackme_defender_update_yandex_action', '_wpnonce');

  if ( !current_user_can('manage_options') ) {
    wp_send_json(array(
      'res' => 'error',
      'data' => esc_html__('This functionality is only available to administrators.', 'nohackme-defender'),
    ));
  }

  $ranges = _nohackme_defender_fetch_yandex_ip_ranges();
  if ( is_wp_error($ranges) ) {
    wp_send_json(array(
      'res' => 'error',
      'data' => $ranges->get_error_message(),
    ));
  }

  $saved = _nohackme_defender_save_ranges_file(NOHACKME_DEFENDER_SETTINGS_PATH . 'robots_yandex_list', $ranges);
  if ( !$saved ) {
    wp_send_json(array(
      'res' => 'error',
      'data' => esc_html__('Unable to save the Yandex IP ranges list.', 'nohackme-defender'),
    ));
  }

  wp_send_json(array(
    'res' => 'success',
    'data' => sprintf(
      /* translators: %d: number of IP ranges */
      esc_html__('Yandex IP ranges updated. Downloaded: %d', 'nohackme-defender'),
      count($ranges)
    ),
  ));
}
