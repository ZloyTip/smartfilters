<?php


class shopSmartfiltersPluginBackendDownloadController extends waController {

    public function execute()
    {
        if(waRequest::get('modified')) {
            $file_path = wa('shop')->getDataPath('plugins/smartfilters/Show.html');
        } else {
            $file_path = realpath(dirname(__FILE__).'/../../templates/actions/show/Show.html');
        }

        if(empty($file_path) || !file_exists($file_path)) {
            throw new waException('Файл не найден', 404);
        }

        $this->getResponse()->addHeader('Content-Disposition','attachment; filename="'.shopSmartfiltersPlugin::THEME_FILE.'"');
        waFiles::readFile($file_path);
    }
}