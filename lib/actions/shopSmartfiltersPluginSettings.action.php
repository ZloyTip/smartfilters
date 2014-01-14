<?php

class shopSmartfiltersPluginSettingsAction extends waViewAction {

    public function execute()
    {
        $enabled = wa('shop')->getPlugin('smartfilters')->getSettings('enabled');
        $disabled_features = wa('shop')->getPlugin('smartfilters')->getSettings('disabled_features');
        $disabled_features = explode(',', $disabled_features);
        $order = wa('shop')->getPlugin('smartfilters')->getSettings('order');

        $features_model = new shopFeatureModel();
        $features = $features_model->getByField('type', 'varchar', true);

        if(file_exists(dirname(__FILE__).'/../../templates/actions/show/Show.html'))
            $template = file_get_contents(dirname(__FILE__).'/../../templates/actions/show/Show.html');
        else
            $template = file_get_contents(dirname(__FILE__).'/../../templates/actions/show/Show.default.html');

        $this->view->assign('template', $template);
        $this->view->assign('enabled', $enabled);
        $this->view->assign('features', $features);
        $this->view->assign('disabled_features', $disabled_features);
        $this->view->assign('order', $order);
    }
}
