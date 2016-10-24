<?php

class shopSmartfiltersPluginSettingsAction extends waViewAction {

    public function execute()
    {
        /**
         * @var shopSmartfiltersPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin( 'smartfilters');
        $enabled             = $plugin->getSettings('enabled');
        $order               = $plugin->getSettings('order');

        $features_model = new shopFeatureModel();
        $features = $features_model->getByField('type', 'varchar', true);


        $modified_template = wa('shop')->getDataPath('plugins/smartfilters/Show.html');

        $this->view->assign('modified_template', file_exists($modified_template));
        $this->view->assign('enabled', $enabled);;
        $this->view->assign('features', $features);
        $this->view->assign('order', $order);
    }
}
