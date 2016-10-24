<?php

class shopSmartfiltersPluginBackendSaveController extends waJsonController {

    public function execute()
    {
        try {
            $enabled = waRequest::post('enabled', 0, waRequest::TYPE_INT);
            $order = waRequest::post('order', '', waRequest::TYPE_STRING_TRIM);

            wa('shop')->getPlugin('smartfilters')->saveSettings(array(
                'enabled' => $enabled,
                'order' => $order
            ));


            $this->response['message'] = "Сохранено";
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }
}