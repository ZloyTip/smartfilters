<?php

class shopSmartfiltersPluginBackendSaveController extends waJsonController {

    public function execute()
    {
        try {
            $enabled = waRequest::post('enabled', 0, waRequest::TYPE_INT);
            $order = waRequest::post('order', '', waRequest::TYPE_STRING_TRIM);
            $ui_slider = waRequest::post('ui_slider', 0, waRequest::TYPE_INT);

            wa('shop')->getPlugin('smartfilters')->saveSettings(array(
                'enabled' => $enabled,
                'order' => $order,
                'ui_slider' => $ui_slider
            ));


            $this->response['message'] = "Сохранено";
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}