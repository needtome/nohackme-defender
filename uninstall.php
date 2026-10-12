<?php
// Если этот файл вызывается напрямую, выйти
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$free_plugin_folder = 'nohackme-defender';
$premium_plugin_folder = $free_plugin_folder . '-premium';

if (is_plugin_active($free_plugin_folder . '/' . $free_plugin_folder . '.php') or is_plugin_active($premium_plugin_folder . '/' . $free_plugin_folder . '.php')) {
} else {
    require_once plugin_dir_path(__FILE__) . 'general.php';
    // Удаление опций плагина
    delete_option('nohackme_defender_options');
    delete_option('nohackme_defender_license');

    // Удаление директорий
    $dirs = array(
        NOHACKME_DEFENDER_BANNED_PATH,
        NOHACKME_DEFENDER_SETTINGS_PATH
    );
    foreach ($dirs as $dir) {
        if (file_exists($dir)) {
            _pdxglobal_delete_folder_recursively($dir);
        }
    }
}
