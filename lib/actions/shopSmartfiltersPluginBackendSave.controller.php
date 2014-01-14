<?php

class shopSmartfiltersPluginBackendSaveController extends waJsonController {

    public function execute()
    {
        try {
            $enabled = (int) waRequest::post('enabled');
            $disabled_features = waRequest::post('disabled_features', array(), waRequest::TYPE_ARRAY_INT);
            $disabled_features = implode(',', $disabled_features);
            $order = waRequest::post('order', '', waRequest::TYPE_STRING_TRIM);

            wa('shop')->getPlugin('smartfilters')->saveSettings(array(
                'enabled' => $enabled,
                'disabled_features' => $disabled_features,
                'order' => $order
            ));

            $template = waRequest::post('template');
            if(!$template) throw new waException('Не определён шаблон');

            $f = fopen(dirname(__FILE__).'/../../templates/actions/show/Show.html', 'w');
            if(!$f) throw new waException('Не удаётся сохранить шаблон. Проверьте права на запись wa-apps/shop/plugins/smartfilters/templates/actions/show/Show.html');
            if(!fwrite($f, $template)) throw new waException('Не удаётся сохранить шаблон. Проверьте права на запись wa-apps/shop/plugins/smartfilters/templates/actions/show/Show.html');
            fclose($f);

            $this->response['message'] = "Сохранено";
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}