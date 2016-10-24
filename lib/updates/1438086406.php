<?php

try {
    $model = new waModel();
    $model->query('ALTER TABLE  `shop_category` ADD  `smartfilters` TEXT NULL AFTER  `filter`');
} catch(waDbException $e) {
    waLog::log('Unable to add "smartfilters" column.');
}