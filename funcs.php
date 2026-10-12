<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function _nohackme_defender_premium_exists(){ return true; }
function _nohackme_defender_premium1($file_path, $hacks) {
  file_put_contents($file_path, implode("\n", $hacks));
  echo '<div id="message" class="updated fade"><p>' . esc_html__('Changes saved.', 'nohackme-defender') . '</p></div>';
}
function _nohackme_defender_premium_save($input, $val) {
  $outp = 0;
  switch ($val) {
    case 'exclude_google_ips':
    case 'exclude_yandex_ips':
      if ( isset($input[$val]) and is_numeric($input[$val]) and $input[$val] == 1 ) {
        return 1;
      }
      break;
  }
  return $outp;
}
function _nohackme_defender_show_branding(){
    if ( defined('PDXTHEME_SKIP_BRANDING') and PDXTHEME_SKIP_BRANDING ) {
        return false;
    }
    return true;
}

function _nohackme_defender_get_blocks_qty() {
  $qty = 0;

  $files = scandir(NOHACKME_DEFENDER_BANNED_PATH, SCANDIR_SORT_NONE);
  if (isset($files) and is_array($files) and count($files)) { foreach ( $files as $file) {
    if ( $file == '.' ) continue;
    if ( $file == '..' ) continue;
    if ( !strlen(_nohackme_defender_formatIp($file)) ) continue;

    $qty++;
  } }

  return $qty;
}

function _nohackme_defender_formatIp($ipString) {
  $ipString = trim((string) $ipString);
  if ( !strlen($ipString) ) {
      return '';
  }

  if ( strpos($ipString, 'ipv6_') === 0 ) {
      $hex = substr($ipString, 5);
      if ( ctype_xdigit($hex) && strlen($hex) === 32 ) {
          $packed = @hex2bin($hex);
          if ( $packed !== false ) {
              $decoded = @inet_ntop($packed);
              if ( $decoded !== false ) {
                  return strtolower($decoded);
              }
          }
      }
      return '';
  }

  if ( filter_var($ipString, FILTER_VALIDATE_IP) !== false ) {
      $packed = @inet_pton($ipString);
      if ( $packed !== false ) {
          return strtolower(inet_ntop($packed));
      }
      return strtolower($ipString);
  }

  return '';
}
function _nohackme_defender_isValidOctet($octet) {
    return $octet <= 255 && $octet >= 0 && !preg_match('/^0\d+$/', $octet);
}

function _nohackme_defender_check_premium () {
  return 1;
}
function _nohackme_defender_get_premium_label ($slug) {
    if ( _nohackme_defender_premium_exists() ) return '';
    return '<a class="premium-label" href="?page=' . esc_attr($slug) . '&tab=premium&nonce=' . esc_html(wp_create_nonce('nohackme_defender_admin_action')) . '">' . esc_html__('premium', 'nohackme-defender') . '</a>';
}
function _nohackme_defender_get_premium_desc ($slug) {
    if ( _nohackme_defender_premium_exists() ) return;
    echo '<div class="premium premium__desc notice notice-warning is-dismissible">';
    echo '<div class="premium__text">';
    echo esc_html__('You are using a free version of the plugin. Some functions may be unavailable.', 'nohackme-defender');
    echo '<br>';
    echo esc_html__('To purchase the paid version of the plugin', 'nohackme-defender');
    echo ', <a href="?page=' . esc_attr($slug) . '&tab=premium&nonce=' . esc_html(wp_create_nonce('nohackme_defender_admin_action')) . '">' . esc_html__('go to the Premium tab', 'nohackme-defender') . '</a>.';
    echo '</div>';
    echo wp_kses_post(_nohackme_defender_get_premium_label($slug));
    echo '</div>';
}
function _nohackme_defender_get_support_links() {
  $links = array(
    array(
      'label' => 'DonationAlerts',
      'url'   => 'https://www.donationalerts.com/r/needtome',
      'desc'  => __('My current donation page.', 'nohackme-defender'),
    ),
    array(
      'label' => 'Patreon',
      'url'   => 'https://patreon.com/needtome',
      'desc'  => __('Best known for recurring support and membership tiers.', 'nohackme-defender'),
    ),
  );

  $icon = '<svg class="nohackme-donation-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" aria-hidden="true" focusable="false"><path fill="#74c0fc" d="M320 48c-13 0-24 11-24 24v12h-2a66 66 0 0 0-8 132l61 8q8 1 9 10-1 9-10 10h-74a28 28 0 1 0 0 56h24v12a24 24 0 1 0 48 0v-12h2a66 66 0 0 0 8-132l-61-7q-8-2-9-11 1-9 10-10h66a28 28 0 1 0 0-56h-16V72c0-13-11-24-24-24M141 406l-42 42H64c-18 0-32 14-32 32v64c0 18 14 32 32 32h321q45 0 80-26l127-94a40 40 0 1 0-48-64l-119 88H312a24 24 0 1 1 0-48h72a32 32 0 1 0 0-64H232c-34 0-67 14-91 38"/></svg>';

  $out = '<div class="pdxhighlight nohackme-donation-box">';
  $out .= '<div class="nohackme-donation-hero">';
  $out .= '<div class="nohackme-donation-visual">' . $icon . '</div>';
  $out .= '<div class="nohackme-donation-copy">';
  $out .= '<p class="nohackme-donation-title">' . esc_html__('Support the development of the plugin', 'nohackme-defender') . '</p>';
  $out .= '<p class="nohackme-donation-text">' . esc_html__('If NoHackMe Defender helps protect your site, you can support further development and maintenance via the services below.', 'nohackme-defender') . '</p>';
  $out .= '</div>';
  $out .= '</div>';
  $out .= '<ul>';
  foreach ( $links as $link ) {
    $out .= '<li><a href="' . esc_url($link['url']) . '" target="_blank" rel="noopener noreferrer">' . esc_html($link['label']) . '</a> — ' . esc_html($link['desc']) . '</li>';
  }
  $out .= '</ul></div>';

  return $out;
}
function _nohackme_defender_generateRandomString($length = 33) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; $i++) {
    $randomString .= $characters[wp_rand(0, $charactersLength - 1)];
  }
  return $randomString;
}
function _nohackme_defender_get_geo () {
  $geo_directories = array(
    NOHACKME_DEFENDER_GEO_PATH,
    trailingslashit(NOHACKME_DEFENDER_PLUGIN_PATH) . 'lib/geo/',
  );

  foreach ( $geo_directories as $geo_directory ) {
    $geo_api = trailingslashit($geo_directory) . 'SxGeo.php';
    $geo_database = trailingslashit($geo_directory) . 'SxGeo.dat';

    if ( !is_file($geo_api) || !is_file($geo_database) ) {
      continue;
    }

    if ( !class_exists('SxGeo', false) ) {
      include_once $geo_api;
    }

    if ( class_exists('SxGeo', false) && defined('SXGEO_BATCH') && defined('SXGEO_MEMORY') ) {
      return new SxGeo($geo_database, SXGEO_BATCH | SXGEO_MEMORY);
    }
  }

  $upload_dir = wp_upload_dir();
  $geo_directory = trailingslashit($upload_dir['basedir']) . 'nohackme-defender/geo/';
  $legacy_geo_directory = trailingslashit(NOHACKME_DEFENDER_PLUGIN_PATH) . 'lib/geo/';

  return '<p>' . esc_html__('The plugin can automatically detect the visitor\'s country based on their IP address. To enable this, place both SxGeo.php and SxGeo.dat in the GeoIP folder shown below. This folder is outside the plugin, so the files are kept when the plugin is updated.', 'nohackme-defender') . '</p>'
    . '<p><code>' . esc_html($geo_directory) . '</code></p>'
    . '<p>' . esc_html__('For backward compatibility, the plugin also checks the previous folder inside the plugin:', 'nohackme-defender') . ' <code>' . esc_html($legacy_geo_directory) . '</code></p>'
    . '<p>' . esc_html__('Without these files, IP blocking still works, but the country is shown as unknown.', 'nohackme-defender') . '</p>'
    . '<p>' . esc_html__('These files can be downloaded from the', 'nohackme-defender') . ' <a target="_blank" rel="noopener noreferrer" href="https://sypexgeo.net/ru/download/">' . esc_html__('official Sypex Geo project page', 'nohackme-defender') . '</a>.</p>'
    . '<p>' . esc_html__('Also, the archive with the SxGeo.dat and SxGeo.php files can be downloaded', 'nohackme-defender') . ' <a target="_blank" rel="noopener noreferrer" href="https://needtome.com/addons/nohackme_defender_addons.zip">' . esc_html__('via this link', 'nohackme-defender') . '</a>.</p>';
}
function _nohackme_defender_normalize_ip_or_range($value) {
  $value = trim((string) $value);
  if ( !strlen($value) ) {
    return '';
  }

  $value = sanitize_text_field($value);
  if ( strpos($value, '/') === false ) {
    return _nohackme_defender_normalize_plain_ip($value);
  }

  list($ip, $prefix) = explode('/', $value, 2);
  $ip = _nohackme_defender_normalize_plain_ip($ip);
  if ( !strlen($ip) ) {
    return '';
  }

  if ( !is_numeric($prefix) ) {
    return '';
  }

  $prefix = (int) $prefix;
  $max_prefix = ( filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ) ? 128 : 32;
  if ( $prefix < 0 || $prefix > $max_prefix ) {
    return '';
  }

  return $ip . '/' . $prefix;
}
function _nohackme_defender_normalize_plain_ip($value) {
  $value = trim((string) $value);
  if ( !strlen($value) ) {
    return '';
  }

  if ( filter_var($value, FILTER_VALIDATE_IP) === false ) {
    return '';
  }

  $packed = @inet_pton($value);
  if ( $packed !== false ) {
    $value = inet_ntop($packed);
  }

  return strtolower($value);
}
function _nohackme_defender_unique_normalized_ranges($ranges) {
  $normalized = array();

  if ( is_array($ranges) ) {
    foreach ( $ranges as $range ) {
      $range = _nohackme_defender_normalize_ip_or_range($range);
      if ( strlen($range) ) {
        $normalized[$range] = $range;
      }
    }
  }

  return array_values($normalized);
}
function _nohackme_defender_save_ranges_file($file_path, $ranges) {
  $ranges = _nohackme_defender_unique_normalized_ranges($ranges);
  if ( !count($ranges) ) {
    return false;
  }

  return file_put_contents($file_path, implode(PHP_EOL, $ranges) . PHP_EOL, LOCK_EX) !== false;
}
function _nohackme_defender_fetch_google_ip_ranges() {
  $response = wp_safe_remote_get(
    'https://developers.google.com/static/crawling/ipranges/common-crawlers.json',
    array(
      'timeout' => 30,
      'redirection' => 5,
      'headers' => array(
        'Accept' => 'application/json',
      ),
      'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url('/'),
    )
  );

  if ( is_wp_error($response) ) {
    return $response;
  }

  $code = wp_remote_retrieve_response_code($response);
  $body = wp_remote_retrieve_body($response);
  if ( $code !== 200 || !strlen($body) ) {
    return new WP_Error('nohackme_google_fetch_failed', __('Unable to download the Google IP ranges list.', 'nohackme-defender'));
  }

  $json = json_decode($body, true);
  if ( !is_array($json) || !isset($json['prefixes']) || !is_array($json['prefixes']) ) {
    return new WP_Error('nohackme_google_invalid_json', __('Google returned an unexpected response format.', 'nohackme-defender'));
  }

  $ranges = array();
  foreach ( $json['prefixes'] as $prefix ) {
    if ( isset($prefix['ipv4Prefix']) ) {
      $ranges[] = $prefix['ipv4Prefix'];
    }
    if ( isset($prefix['ipv6Prefix']) ) {
      $ranges[] = $prefix['ipv6Prefix'];
    }
  }

  $ranges = _nohackme_defender_unique_normalized_ranges($ranges);
  if ( !count($ranges) ) {
    return new WP_Error('nohackme_google_empty', __('Google IP ranges list is empty.', 'nohackme-defender'));
  }

  return $ranges;
}
function _nohackme_defender_fetch_yandex_ip_ranges() {
  $urls = array(
    'https://yandex.com/ips',
    'https://yandex.ru/ips',
  );

  foreach ( $urls as $url ) {
    $response = wp_safe_remote_get(
      $url,
      array(
        'timeout' => 30,
        'redirection' => 5,
        'user-agent' => 'Mozilla/5.0 (compatible; NoHackMe Defender; ' . home_url('/') . ')',
      )
    );

    if ( is_wp_error($response) ) {
      continue;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    if ( $code !== 200 || !strlen($body) ) {
      continue;
    }

    preg_match_all('/(?:\b(?:\d{1,3}\.){3}\d{1,3}\/\d{1,3}\b)|(?:[A-Fa-f0-9:]+\/\d{1,3})/', $body, $matches);
    if ( !isset($matches[0]) || !is_array($matches[0]) ) {
      continue;
    }

    $ranges = _nohackme_defender_unique_normalized_ranges($matches[0]);
    if ( count($ranges) ) {
      return $ranges;
    }
  }

  return new WP_Error('nohackme_yandex_fetch_failed', __('Unable to download the Yandex IP ranges list.', 'nohackme-defender'));
}
