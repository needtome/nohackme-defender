<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action('wp_ajax_pdxnohackme_restore_defaults', 'pdxnohackme_restore_defaults');
add_action('wp_ajax_nopriv_pdxnohackme_restore_defaults', 'pdxnohackme_restore_defaults');
function pdxnohackme_restore_defaults() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Parameters are specified incorrectly!', 'nohackme-defender'),
  );

  check_ajax_referer('pdxnohackme_restore_defaults_action', '_wpnonce', true);

  $uid = get_current_user_id();

  if ($uid > 0 && user_can($uid, 'administrator')) {
    $file_path = PDXNOHACKME_SETTINGS_PATH . 'hacks_list';
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


add_action('wp_ajax_pdxnohackme_restore_defaults_ips_google', 'pdxnohackme_restore_defaults_ips_google');
add_action('wp_ajax_nopriv_pdxnohackme_restore_defaults_ips_google', 'pdxnohackme_restore_defaults_ips_google');
function pdxnohackme_restore_defaults_ips_google() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Parameters are specified incorrectly!', 'nohackme-defender'),
  );
  check_ajax_referer('pdxnohackme_restore_defaults_google_action', '_wpnonce');
  if ( isset($_REQUEST['action']) and $_REQUEST['action'] == 'pdxnohackme_restore_defaults_ips_google' ) {
    $uid = get_current_user_id();
    if ( $uid > 0 ) {
      if (user_can($uid, 'administrator')) {
        $file_path = PDXNOHACKME_SETTINGS_PATH . 'robots_google_list';
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

add_action('wp_ajax_pdxnohackme_restore_defaults_ips_yandex', 'pdxnohackme_restore_defaults_ips_yandex');
add_action('wp_ajax_nopriv_pdxnohackme_restore_defaults_ips_yandex', 'pdxnohackme_restore_defaults_ips_yandex');
function pdxnohackme_restore_defaults_ips_yandex() {
  $out = array(
    'res' => 'error',
    'data' => esc_html__('Invalid parameters specified!', 'nohackme-defender'),
  );
  check_ajax_referer('pdxnohackme_restore_defaults_yandex_action', '_wpnonce');
  if ( isset($_REQUEST['action']) and $_REQUEST['action'] == 'pdxnohackme_restore_defaults_ips_yandex' ) {
    $uid = get_current_user_id();
    if ( $uid > 0 ) {
      if (user_can($uid, 'administrator')) {
        $file_path = PDXNOHACKME_SETTINGS_PATH . 'robots_yandex_list';
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
