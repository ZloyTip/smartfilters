<?php
try {
    $model = new waModel();
    $model->query('ALTER TABLE shop_category DROP smartfilters');
    $model->query('ALTER TABLE shop_category DROP smartfilters_name');
} catch(waDbException $e) {
    waLog::log('Unable to remove "smartfilters" column.');
}