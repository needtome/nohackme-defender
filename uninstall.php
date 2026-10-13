<?php
// Если этот файл вызывается напрямую, выйти
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$nohackme_defender_free_plugin_folder = 'nohackme-defender';
$nohackme_defender_premium_plugin_folder = $nohackme_defender_free_plugin_folder . '-premium';

if (is_plugin_active($nohackme_defender_free_plugin_folder . '/' . $nohackme_defender_free_plugin_folder . '.php') or is_plugin_active($nohackme_defender_premium_plugin_folder . '/' . $nohackme_defender_free_plugin_folder . '.php')) {
} else {
    require_once plugin_dir_path(__FILE__) . 'general.php';
    // Удаление опций плагина
    delete_option('nohackme_defender_options');
    delete_option('nohackme_defender_license');

    // Удаление директорий
    $nohackme_defender_dirs = array(
        NOHACKME_DEFENDER_BANNED_PATH,
        NOHACKME_DEFENDER_SETTINGS_PATH
    );
    foreach ($nohackme_defender_dirs as $nohackme_defender_dir) {
        if (file_exists($nohackme_defender_dir)) {
            _pdxglobal_delete_folder_recursively($nohackme_defender_dir);
        }
    }
}
