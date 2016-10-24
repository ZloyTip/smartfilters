<?php

// Перенос шаблонов плагина в тему дизайна.

$modified_template = wa('shop')->getDataPath('plugins/smartfilters/Show.html');
if(file_exists($modified_template) && is_readable($modified_template)) {


    $file_content = file_get_contents($modified_template);

    $routing = wa()->getRouting();
    $routes = $routing->getByApp('shop');

    $themes = array();
    foreach ($routes as $domain_routes) {
        foreach($domain_routes as $route) {
            if(!empty($route['theme'])) {
                $themes[$route['theme']] = 1;
            }
        }
    }


    $themes = array_keys($themes);
    foreach ($themes as $theme_id) {

        $theme = new waTheme($theme_id, 'shop');
        if ($theme['type'] == waTheme::ORIGINAL) {
            continue; // Если тему никто до нас редактировал, то и я файл добавлять не буду.
        }

        $theme->addFile(shopSmartfiltersPlugin::THEME_FILE, 'Блок фильтров плагина Smart Filters');

        $file_path = $theme->getPath().'/'.shopSmartfiltersPlugin::THEME_FILE;

        waFiles::write($file_path, $file_content);
    }

    // waFiles::delete($modified_template);
}