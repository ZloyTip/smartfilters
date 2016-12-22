<?php

$templates_path = realpath(dirname(__FILE__).'/../../templates/actions/show/'); 
if(file_exists($templates_path.'/Show.html')) {
    $modified_template = wa('shop')->getDataPath('plugins/smartfilters/Show.html');
    waFiles::write($modified_template, file_get_contents($templates_path.'/Show.html'));
    waFiles::delete($templates_path.'/Show.html');
}
rename($templates_path.'/Show.default.html', $templates_path.'/Show.html');
    