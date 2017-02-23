<?php

class shopSmartfiltersPluginSettingsAction extends waViewAction {

    public function execute()
    {
        /**
         * @var shopSmartfiltersPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin( 'smartfilters');
        $controls = $plugin->getControls(array(
            'id' => 'smartfilters',
            'namespace' => 'shop_smartfilters',
            'title_wrapper' => '%s',
            'description_wrapper' => '<br><span class="hint">%s</span>',
            'control_wrapper' => '<div class="name">%s</div><div class="value">%s %s</div>'
        ));

        $modified_template = wa('shop')->getDataPath('plugins/smartfilters/Show.html');

        $this->view->assign('modified_template', file_exists($modified_template));
        $this->view->assign('controls', $controls);
    }
}
