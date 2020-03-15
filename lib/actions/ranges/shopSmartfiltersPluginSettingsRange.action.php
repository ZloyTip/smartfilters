<?php


class shopSmartfiltersPluginSettingsRangeAction extends waViewAction
{
    public function execute()
    {
        $rm = new shopSmartfiltersRangeModel();

        $feature_id = waRequest::get('feature_id');

        $range = $rm->getRange($feature_id);
        $this->view->assign('range', $range);
    }
}