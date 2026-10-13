<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- PDXGlobal helpers are shared across the author's WordPress plugins.

if ( !function_exists('_pdxglobal_get_contacts') ) {
    function _pdxglobal_get_contacts() {
        echo '<a href="mailto:paraz0n3@gmail.com" class="pdxglobal_contact-link"><strong>' . esc_html__( 'Email:', 'nohackme-defender' ) . '</strong><br>paraz0n3@gmail.com</a>';
        echo '<a href="https://www.linkedin.com/in/roman-klymenko-parad0x/" target="_blank" rel="noreferrer noopener" class="pdxglobal_contact-link"><strong>' . esc_html__( 'LinkedIn:', 'nohackme-defender' ) . '</strong><br>' . esc_html__( 'Roman Klymenko', 'nohackme-defender' ) . '</a>';
        echo '<a href="viber://chat?number=%2B380935523587" class="pdxglobal_contact-link"><strong>' . esc_html__( 'Viber:', 'nohackme-defender' ) . '</strong><br>+380935523587</a>';
        echo '<a href="https://t.me/klymenko_roman" target="_blank" rel="noreferrer noopener" class="pdxglobal_contact-link"><strong>' . esc_html__( 'Telegram:', 'nohackme-defender' ) . '</strong><br>klymenko_roman</a>';
    }
}
if ( !function_exists('_pdxglobal_get_plugins_page') ) {
    function _pdxglobal_get_plugins_page() {
        echo '<div class="pdxglobal_container"><div class="pdxglobal_sidebar">';
        _pdxglobal_get_contacts();
        echo '</div><div class="pdxglobal_content">';
        echo '<h3>' . esc_html__( 'WordPress plugins', 'nohackme-defender' ) . '</h3>';
        echo '<ul>';
        echo '<li><a href="https://wordpress.org/plugins/nohackme-defender/" target="_blank" rel="noreferrer noopener">NoHackMe Defender</a> — ' . esc_html__( 'WordPress security plugin.', 'nohackme-defender' ) . '</li>';
        echo '<li><a href="https://needtome.com/utm-phone-switcher/" target="_blank" rel="noreferrer noopener">UTM Phone Switcher</a> — ' . esc_html__( 'Campaign-based phone number replacement.', 'nohackme-defender' ) . '</li>';
        echo '</ul>';
        echo '</div></div>';
    }
}
if ( !function_exists('_pdxglobal_get_dev_page') ) {
    function _pdxglobal_get_dev_page() {
        echo '<div class="pdxglobal_container"><div class="pdxglobal_sidebar">';
        _pdxglobal_get_contacts();
        echo '</div><div class="pdxglobal_content">';
?>
<h2><?php echo esc_html__( 'Need a website or plugin?', 'nohackme-defender' ); ?></h2>
<p><?php echo esc_html__( 'I have been developing websites on CMS WordPress and Drupal since 2008. Over this time, I have developed and optimized over 500 websites.', 'nohackme-defender' ); ?></p>
<h3><?php echo esc_html__( 'I Guarantee', 'nohackme-defender' ); ?></h3>
<p><?php echo esc_html__( 'Therefore, for the websites I have developed, I can guarantee:', 'nohackme-defender' ); ?></p>
<ul>
<li>PageSpeed <strong><?php echo esc_html__( 'Accessibility: 100', 'nohackme-defender' ); ?></strong>;</li>
<li>PageSpeed <strong><?php echo esc_html__( 'Best Practices: 100', 'nohackme-defender' ); ?></strong>;</li>
<li>PageSpeed <strong><?php echo esc_html__( 'SEO: 100', 'nohackme-defender' ); ?></strong>;</li>
<li>PageSpeed <strong><?php echo esc_html__( 'Performance: above 96', 'nohackme-defender' ); ?></strong>;</li>
<li><?php echo esc_html__( 'successful passing of', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'technical SEO audit', 'nohackme-defender' ); ?></strong>;</li>
<li><?php echo esc_html__( 'all functionality for', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'SEO settings', 'nohackme-defender' ); ?></strong>;</li>
<li><?php echo esc_html__( 'no errors in', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'HTML and CSS validation', 'nohackme-defender' ); ?></strong>;</li>
<li><?php echo esc_html__( 'high website performance speed', 'nohackme-defender' ); ?>.</li>
</ul>
<p><?php echo esc_html__( 'I also offer:', 'nohackme-defender' ); ?></p>
<ul>
<li><?php echo esc_html__( '3 months of hosting on my server for free', 'nohackme-defender' ); ?>;</li>
<li>1 month of <strong><?php echo esc_html__( 'website maintenance', 'nohackme-defender' ); ?></strong> <?php echo esc_html__( 'for free', 'nohackme-defender' ); ?>.</li>
</ul>
<h3><?php echo esc_html__( 'I Have Experience', 'nohackme-defender' ); ?></h3>
<ul>
<li><?php echo esc_html__( 'in developing websites containing', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'over 50,000,000 publications', 'nohackme-defender' ); ?></strong>;</li>
<li><?php echo esc_html__( 'in working with', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'Asterisk telephony', 'nohackme-defender' ); ?></strong><?php echo esc_html__( ', setting up Asterisk, creating robots for auto dialing and collecting results', 'nohackme-defender' ); ?>;</li>
<li><?php echo esc_html__( 'in developing', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'text and video chats', 'nohackme-defender' ); ?></strong> <?php echo esc_html__( 'based on signal server nodejs and coturn', 'nohackme-defender' ); ?>;</li>
<li><?php echo esc_html__( 'in developing', 'nohackme-defender' ); ?> <strong><?php echo esc_html__( 'interactive online learning systems', 'nohackme-defender' ); ?></strong> <?php echo esc_html__( 'for student and teacher', 'nohackme-defender' ); ?>.</li>
</ul>
<h3><?php echo esc_html__( 'Portfolio', 'nohackme-defender' ); ?></h3>
<p><?php echo esc_html__( 'Some examples of my work:', 'nohackme-defender' ); ?></p>
<div class="pdxglobal_portfolio">
  <!-- Block for obuvnov.ru -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://obuvnov.ru" target="_blank" rel="noreferrer noopener"><h4>https://obuvnov.ru</h4></a>
      <p><?php echo esc_html__( 'Mobile PageSpeed 100.', 'nohackme-defender' ); ?></p>
      <p><?php echo esc_html__( 'Product aggregator. More than 50,000,000 products processed daily from YML files.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_video">
	        <a href="https://www.youtube.com/watch?v=H0dq7qwO9-0" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Watch video', 'nohackme-defender' ); ?></a>
    </div>
  </div>

  <!-- Block for gloap.net -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://gloap.net/en/" target="_blank" rel="noreferrer noopener"><h4>https://gloap.net/en/</h4></a>
      <p><?php echo esc_html__( 'Mobile PageSpeed 100.', 'nohackme-defender' ); ?></p>
      <p><?php echo esc_html__( 'A platform for interaction between seafarers and crewing agencies. Over 200,000 seafarer resumes, job listings, response systems, and instant message exchange, mass mailings and automatic mass phone calling of seafarers via Asterisk, Telegram bots for instant notifications, creation and downloading of various documents in PDF and Word formats, and much more.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_video">
	        <a href="https://www.youtube.com/watch?v=0mpd-2ZyOVM" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Watch video', 'nohackme-defender' ); ?></a>
    </div>
  </div>

  <!-- Block for malinovsky.io -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://malinovsky.io" target="_blank" rel="noreferrer noopener"><h4>https://malinovsky.io</h4></a>
      <p><?php echo esc_html__( 'Mobile PageSpeed 100.', 'nohackme-defender' ); ?></p>
      <p><?php echo esc_html__( 'Marketing agency’s portfolio website.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_video">
	        <a href="https://www.youtube.com/watch?v=f4D9j6rp1ps" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Watch video', 'nohackme-defender' ); ?></a>
    </div>
  </div>

  <!-- Block for boomschool.by -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://boomschool.by" target="_blank" rel="noreferrer noopener"><h4>https://boomschool.by</h4></a>
      <p><?php echo esc_html__( 'Mobile PageSpeed 99.', 'nohackme-defender' ); ?></p>
      <p><?php echo esc_html__( 'Information site of the music school.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_video">
	        <a href="https://www.youtube.com/watch?v=yn6AypCyY_4" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Watch video', 'nohackme-defender' ); ?></a>
    </div>
  </div>

  <!-- Block for kra.name AI Worker -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://kra.name/ru/" target="_blank" rel="noreferrer noopener"><h4>kra.name — AI Worker</h4></a>
      <p><?php echo esc_html__( 'AI Employee platform for customer communication automation across WhatsApp, Telegram, imo, MAX, email, and website chat. Includes conversation scenarios, knowledge bases, learning from edited replies, contact import, website and CRM API integrations, response quality checks, and handoff to human managers.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_links">
      <p><strong><?php echo esc_html__( 'Project overview', 'nohackme-defender' ); ?></strong></p>
      <p><a href="https://kra.name/ru/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Open kra.name', 'nohackme-defender' ); ?></a></p>
    </div>
  </div>

  <!-- Block for o-keto.com and related nutrition tools -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://o-keto.com/en/" target="_blank" rel="noreferrer noopener"><h4>o-keto.com — Nutrition Platform</h4></a>
      <p><?php echo esc_html__( 'Custom WordPress and WooCommerce platform for nutrition, recipes, products, nutrients, quizzes, digital books, and health education. The project brings together the AI nutritionist and recipe assistant, recipe-book generation, AI-assisted publishing, multilingual workflows, video production, meal planning, food diary, and ecommerce.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_links">
      <p><strong><?php echo esc_html__( 'Related sections', 'nohackme-defender' ); ?></strong></p>
      <ul>
        <li><a href="https://o-keto.com/en/recipes/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Recipes', 'nohackme-defender' ); ?></a></li>
        <li><a href="https://o-keto.com/en/products/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Products', 'nohackme-defender' ); ?></a></li>
        <li><a href="https://o-keto.com/en/nutrients/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Nutrients', 'nohackme-defender' ); ?></a></li>
        <li><a href="https://o-keto.com/en/quiz/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Quizzes', 'nohackme-defender' ); ?></a></li>
        <li><a href="https://o-keto.com/en/shop/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Shop', 'nohackme-defender' ); ?></a></li>
      </ul>
    </div>
  </div>

  <!-- Block for ProTradeJournal and related trading products -->
  <div class="pdxglobal_project">
    <div class="pdxglobal_description">
      <a href="https://protradejournal.net/" target="_blank" rel="noreferrer noopener"><h4>ProTradeJournal — Trading Platform</h4></a>
      <p><?php echo esc_html__( 'Combined trading platform with a historical simulator and journal, browser and desktop trading terminal, and automated crypto-robot workflows. Includes strategy backtesting, charts, watchlists, screeners, demo trading, investor portfolios, alerts, trade history, exchange integrations, robot connections, and performance analytics.', 'nohackme-defender' ); ?></p>
    </div>
    <div class="pdxglobal_links">
      <p><strong><?php echo esc_html__( 'Related products', 'nohackme-defender' ); ?></strong></p>
      <ul>
        <li><a href="https://protradejournal.net/" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Trading simulator and journal', 'nohackme-defender' ); ?></a></li>
        <li><a href="https://protradejournal.net/trading" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Trading terminal', 'nohackme-defender' ); ?></a></li>
        <li><a href="https://protradejournal.net/robots" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'Trading robots', 'nohackme-defender' ); ?></a></li>
      </ul>
    </div>
  </div>

  <p><?php echo esc_html__( 'More works can be seen in my', 'nohackme-defender' ); ?> <a href="https://docs.google.com/document/d/11w_tmtQbJ7ebcQHQi7jE9Y5WClfVHyMtxbR3-GW3b-s/edit?usp=sharing" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'WordPress resume', 'nohackme-defender' ); ?></a> <?php echo esc_html__( 'and', 'nohackme-defender' ); ?> <a href="https://docs.google.com/document/d/1tbHaxGPeN9je1qozgkQcX3djzaTrpR_MLvghjqMAuFM/edit?usp=sharing" target="_blank" rel="noreferrer noopener"><?php echo esc_html__( 'AI & automation resume', 'nohackme-defender' ); ?></a>. <?php echo esc_html__( 'Even more works are available upon request.', 'nohackme-defender' ); ?></p>
<h3><?php echo esc_html__( 'Cost', 'nohackme-defender' ); ?></h3>
<p><?php echo esc_html__( 'The cost of developing both a website and an individual plugin is calculated individually, based on an hourly rate of 30 dollars.', 'nohackme-defender' ); ?></p>
<?php
        echo '</div></div>';
    }
}
if ( !function_exists('_pdxglobal_get_seo_page') ) {
    function _pdxglobal_get_seo_page() {
        echo '<div class="pdxglobal_container"><div class="pdxglobal_sidebar">';
        echo '<a href="https://malinovsky.io" class="pdxglobal_contact-link" target="_blank" rel="noreferrer noopener"><strong>' . esc_html__( 'Site:', 'nohackme-defender' ) . '</strong><br>https://malinovsky.io</a>';
        echo '<a href="mailto:contact@malinovsky.io" class="pdxglobal_contact-link"><strong>' . esc_html__( 'Email:', 'nohackme-defender' ) . '</strong><br>contact@malinovsky.io</a>';
        echo '<a href="tel:+995595386825" class="pdxglobal_contact-link"><strong>' . esc_html__( 'Phone:', 'nohackme-defender' ) . '</strong><br>+(995) 595-386-825</a>';
        echo '<a href="https://www.google.com/maps/d/embed?mid=1Gc7VzEdTYAG0-eVinYfXmrmqVA-yrffi&ehbc=2E312F" class="pdxglobal_contact-link" target="_blank" rel="noreferrer noopener"><strong>' . esc_html__( 'Address:', 'nohackme-defender' ) . '</strong><br>' . esc_html__( '6000, Georgia, st. Batumi, Tamar Mepe Avenue, N 22a', 'nohackme-defender' ) . '</a>';
        echo '</div><div class="pdxglobal_content">';
?>
<h3><?php echo esc_html__( 'SEO services for your website', 'nohackme-defender' ); ?></h3>
<h4><?php echo esc_html__( 'Digital Marketing for Software Development Companies', 'nohackme-defender' ); ?></h4>
<p><?php echo esc_html__( 'Specialists in SEO and PPC services for the tech industry. We understand your language and fully comprehend your business needs.', 'nohackme-defender' ); ?></p>
<?php
        echo '</div></div>';
    }
}
if ( !function_exists('_pdxglobal_get_curl') ) {
    function _pdxglobal_get_curl($link, $cookie = '', $headers = array(), $sslverify = true) {
        $args = array(
            'timeout'     => 90,
            'user-agent'  => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
            'headers'     => $headers,
            'redirection' => 5,
            'sslverify'   => $sslverify
        );

        if ( strlen($cookie) ) {
            $args['cookies'] = array('name' => 'cookie', 'value' => $cookie, 'expires' => time() + 1000);
        }

        $response = wp_remote_get($link, $args);

        if ( is_wp_error($response) ) {
            return '';
        }

        $alldata = wp_remote_retrieve_body($response);

        return $alldata;
    }
}
if (!function_exists('_pdxglobal_delete_folder_recursively')) {
    function _pdxglobal_delete_folder_recursively($dir) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        WP_Filesystem();
        global $wp_filesystem;

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        _pdxglobal_delete_folder_recursively($dir . "/" . $object);
                    } else {
                        wp_delete_file($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            $wp_filesystem->rmdir($dir, true);
        }
    }
}
if ( !function_exists('_pdxglobal_check_new_messages') ) {
    function _pdxglobal_check_new_messages($plugin_options) {
        $options = get_option('pdxglobal_options');
        if (isset($options) and is_array($options) ) { } else {
            $options = array();
        }
        $options_update = false;

        // check if there are any new notifications
        $current_locale = get_locale();
        $new_message = _pdxglobal_get_curl('https://needtome.com/wp-content/uploads/plugins_notices/' . $current_locale);
        if ( isset($new_message) and strlen($new_message) ) {} else {
            $new_message = _pdxglobal_get_curl('https://needtome.com/wp-content/uploads/plugins_notices/en_US');
        }
        if ( isset($new_message) and strlen($new_message) ) {
            $new_hash = md5(str_replace(' ', '', wp_strip_all_tags($new_message)));
            if ( !isset($options['notices']['hash']) or $options['notices']['hash'] != $new_hash ) {
            // new notification
                if ( !isset($options['notices']) ) {
                    $options['notices']= array();
                }
                $options['notices']['hash']= $new_hash;
                $options['notices']['msg']= $new_message;
                $options['daily_notice_dismissed']= false;
                $options_update = true;
            }
        }
        if ( isset($plugin_options['activation_time']) and is_numeric($plugin_options['activation_time']) and $plugin_options['activation_time'] > 0 and (time() - $plugin_options['activation_time']) < 2 * DAY_IN_SECONDS ) {
            if ( $options_update and isset($options['notices']['hash']) and isset($options['daily_notice_dismissed']) and $options['daily_notice_dismissed'] === false ) {
                $options['daily_notice_dismissed']= true;
            }
        }

        if ( $options_update ) {
            update_option('pdxglobal_options', $options);
        }
    }
}
if (!function_exists('_pdxglobal_update_file_via_wpfs')) {
    function _pdxglobal_update_file_via_wpfs($file_path, $file_contents) {
        $chmod = defined('FS_CHMOD_FILE') ? FS_CHMOD_FILE : 0644;

        if (is_array($file_contents)) {
            $file_contents = serialize($file_contents);
        }

        if (is_string($file_path) && $file_path !== '') {
            $result = @file_put_contents($file_path, $file_contents);

            if ($result !== false) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- This branch only runs after native file_put_contents succeeded; the WP_Filesystem fallback below applies its own permissions.
                @chmod($file_path, $chmod);
                return true;
            }
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');

        WP_Filesystem();
        global $wp_filesystem;

        if (is_object($wp_filesystem) && method_exists($wp_filesystem, 'put_contents')) {
            return $wp_filesystem->put_contents(
                $file_path,
                $file_contents,
                $chmod
            );
        }

        return false;
    }
}
if (!function_exists('_pdxglobal_get_file_via_wpfs')) {
    function _pdxglobal_get_file_via_wpfs($path_to_file) {
        if (is_string($path_to_file) && $path_to_file !== '' && is_readable($path_to_file)) {
            $contents = file_get_contents($path_to_file);

            if ($contents !== false) {
                return $contents;
            }
        }

        require_once(ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
        global $wp_filesystem;

        if (is_object($wp_filesystem) && method_exists($wp_filesystem, 'exists') && method_exists($wp_filesystem, 'get_contents') && $wp_filesystem->exists($path_to_file)) {
            return $wp_filesystem->get_contents($path_to_file);
        } else {
            return false;
        }
    }
}
