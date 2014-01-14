<?php

class shopSmartfiltersPlugin extends shopPlugin {

    public function frontendCategory($category)
    {
        if($this->getSettings('enabled'))
            return self::display($category['id']);
        return '';
    }

    public static function get($category_id)
    {
        if(!wa('shop')->getPlugin('smartfilters')->getSettings('enabled'))
            return self::display($category_id);
        return '';
    }

    private static function display($category_id)
    {
        if ($category_id) {
            $feature_model = new shopSmartfiltersPluginFeatureModel();
            $filters = $feature_model->getByCategoryId($category_id);

            $list = new shopSmartfiltersPluginShowAction();
            $list->setFilters($filters);
            return $list->display(false);
        }
        return '';
    }
}

