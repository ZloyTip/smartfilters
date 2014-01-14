<?php


class shopSmartfiltersPluginShowAction extends waViewAction {

    public function setFilters($filters)
    {
        $this->view->assign('smartfilters', $filters);
    }

    public function execute()
    {
        /**
         * Не находит шаблон, если вызывать без хука.
         * Наверное, баг.
         */
        $template_dir = dirname(__FILE__).'/../../templates/actions/show/';
        if(!file_exists($template_dir.'Show.html')) {
            $this->setTemplate($template_dir.'Show.default.html');
        } else {
            $this->setTemplate($template_dir.'Show.html');
        }
    }
}