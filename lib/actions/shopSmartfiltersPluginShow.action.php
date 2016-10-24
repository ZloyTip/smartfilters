<?php


class shopSmartfiltersPluginShowAction extends waViewAction
{

    public function setFilters($filters)
    {
        $this->view->assign('smartfilters', $filters);
    }

    public function execute()
    {
        if ($this->getTheme()->getFile(shopSmartfiltersPlugin::THEME_FILE)) {
            $this->setThemeTemplate(shopSmartfiltersPlugin::THEME_FILE);
        } else {
            $template_dir = dirname(__FILE__) . '/../../templates/actions/show/';
            $this->setTemplate($template_dir . 'Show.html');
        }
    }
    //
}