<?php

try {
    $app_settings_model = new waAppSettingsModel();
    $disabled_features = trim($app_settings_model->get(array('shop', 'smartfilters'), 'disabled_features'));
    $disabled_features = explode(',',$disabled_features);

    $features_model = new shopFeatureModel();
    $features = $features_model->getByField('type', 'varchar', true);

    $update = 0;
    $enabled_features = array('price');
    foreach ($features as $feature) {
        if(!in_array($feature['id'], $disabled_features)) {
            $enabled_features[]= $feature['id'];
            $update = 1;
        }
    }
    if($update) {
        $model = new shopCategoryModel();
        $model->query('UPDATE shop_category SET smartfilters = ? WHERE 1', implode(',',$enabled_features));
    }

} catch(waDbException $e) {
    waLog::log('Unable to add default smartfilters values');
}