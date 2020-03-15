<?php


class shopSettingsSmartfiltersAction extends waViewAction
{

    public function execute()
    {
        $fm = new shopFeatureModel();

        $features = $fm->select('*')
            ->where('parent_id IS NULL AND count > 0')
            ->order('name ASC')->fetchAll('id');

        $ranges = array();

        $this->view->assign('features', $features);
        $this->view->assign('ranges', $ranges);
    }

}