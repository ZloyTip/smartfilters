<?php

try {
    $model = new waModel();
    $model->query('ALTER TABLE  `shop_category` ADD  `smartfilters_name` TEXT NULL AFTER  `smartfilters`');
} catch(waDbException $e) {
    waLog::log('Unable to add "smartfilters_name" column.');
}