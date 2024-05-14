<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function _pdxnohackme_premium_exists(){ return false; }
function _pdxnohackme_show_branding(){
    if ( defined('PDXTHEME_SKIP_BRANDING') and PDXTHEME_SKIP_BRANDING ) {
        return false;
    }
    return true;
}

function _pdxnohackme_get_blocks_qty() {
  $qty = 0;

  $files = scandir(PDXNOHACKME_BANNED_PATH, SCANDIR_SORT_NONE);
  if (isset($files) and is_array($files) and count($files)) { foreach ( $files as $file) {
    if ( $file == '.' ) continue;
    if ( $file == '..' ) continue;
    if ( !ctype_digit(substr($file, 0, 1)) ) continue;

    $qty++;
  } }

  return $qty;
}

function _pdxnohackme_formatIp($ipString) {
  return $ipString;
  // temporarily disabled
  $length = strlen($ipString);
  if ($length < 4 || $length > 12) {
      return false; // Invalid string length
  }

  for ($i = 1; $i < 4 && $i < $length - 2; $i++) {
      for ($j = 1; $j < 4 && $i + $j < $length - 1; $j++) {
          for ($k = 1; $k < 4 && $i + $j + $k < $length; $k++) {
              $p1 = substr($ipString, 0, $i);
              $p2 = substr($ipString, $i, $j);
              $p3 = substr($ipString, $i + $j, $k);
              $p4 = substr($ipString, $i + $j + $k);

              if (_pdxnohackme_isValidOctet($p1) && _pdxnohackme_isValidOctet($p2) && _pdxnohackme_isValidOctet($p3) && _pdxnohackme_isValidOctet($p4)) {
                  return "$p1.$p2.$p3.$p4";
              }
          }
      }
  }

  return $ipString;
}
function _pdxnohackme_isValidOctet($octet) {
    return $octet <= 255 && $octet >= 0 && !preg_match('/^0\d+$/', $octet);
}

function _pdxnohackme_check_premium () {
  static $check_result = -1;

  if ( $check_result < 0 ) {
    $check_result = 0;

    if ( function_exists('_pdxnohackme_premium_save') ) {
      $check_result = 1;
    }
/*
    $pdxnohackme_options = get_option('pdxnohackme_license');
    if ( isset($pdxnohackme_options['license_key']) and strlen($pdxnohackme_options['license_key']) ) {
      $check_result = 1;
    }
*/
  }

  return $check_result;
}
function _pdxnohackme_get_premium_label ($slug) {
    if ( _pdxnohackme_premium_exists() ) return '';
    return '<a class="premium-label" href="?page=' . esc_attr($slug) . '&tab=premium">' . esc_html__('premium', 'nohackme-defender') . '</a>';
}
function _pdxnohackme_get_premium_desc ($slug) {
    if ( _pdxnohackme_premium_exists() ) return;
    echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
    echo '<div class="premium__text">';
    echo esc_html__('You are using a free version of the plugin. Some functions may be unavailable.', 'nohackme-defender');
    echo '<br>';
    echo esc_html__('To purchase the paid version of the plugin', 'nohackme-defender');
    echo ', <a href="?page=' . esc_attr($slug) . '&tab=premium">' . esc_html__('go to the Premium tab', 'nohackme-defender') . '</a>.';
    echo '</div>';
    echo wp_kses_post(_pdxnohackme_get_premium_label($slug));
    echo '</div>';
}
function _pdxnohackme_generateRandomString($length = 33) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[wp_rand(0, $charactersLength - 1)];
  }
  return $randomString;
}
function _pdxnohackme_get_geo () {
  if ( is_file(PDXNOHACKME_PLUGIN_PATH . '/lib/geo/SxGeo.php') ) {
    include_once PDXNOHACKME_PLUGIN_PATH . '/lib/geo/SxGeo.php';
  	return new SxGeo(PDXNOHACKME_PLUGIN_PATH . '/lib/geo/SxGeo.dat', SXGEO_BATCH | SXGEO_MEMORY);
  }
  return '<p>' . esc_html__('The plugin can automatically detect the visitor\'s country based on their IP address. To do this, download and place the SxGeo.dat and SxGeo.php files in the "lib/geo" folder located inside the plugin folder.', 'nohackme-defender') . '</p><p>' . esc_html__('These files can be downloaded from the', 'nohackme-defender') . ' <a target="_blank" href="https://sypexgeo.net/ru/download/">' . esc_html__('official Sypex Geo project page', 'nohackme-defender') . '</a>.</p><p>' . esc_html__('Also, the archive with the SxGeo.dat and SxGeo.php files can be downloaded', 'nohackme-defender') . ' <a target="_blank" href="https://needtome.com/addons/pdxnohackme_addons.zip">' . esc_html__('via this link', 'nohackme-defender') . '</a>.</p>';
}
