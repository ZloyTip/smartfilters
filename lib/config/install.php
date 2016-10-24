<?php


$model = new waModel();

try {
    $model->query('SELECT smartfilters FROM shop_category WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE  `shop_category` ADD  `smartfilters` TEXT NULL AFTER  `filter`');
}

try {
    $model->query('SELECT smartfilters_name FROM shop_category WHERE 0');
} catch(waDbException $e) {
    $model->query('ALTER TABLE  `shop_category` ADD  `smartfilters_name` TEXT NULL AFTER  `smartfilters`');
}