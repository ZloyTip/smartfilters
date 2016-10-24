<?php

class shopSmartfiltersPlugin extends shopPlugin {

    const THEME_FILE = 'plugin.smartfilters.html';

    public function getFiltersForCategory($category_id) {
        if ($category_id) {
            $feature_model = new shopSmartfiltersPluginFeatureModel();
            return $feature_model->getByCategoryId($category_id);
        }

        return array();
    }

    public function frontendCategory($category)
    {
        if($this->getSettings('enabled') === '1')
            return self::display($category['id']);
        return '';
    }

    public function backendCategoryDialog($settings)
    {

        $feature_model = new shopFeatureModel();
        $selectable_and_boolean_features = $feature_model->select('*')->
        where("(selectable=1 OR type='boolean' OR type='double' OR type LIKE 'dimension\.%' OR type LIKE 'range\.%') AND parent_id IS NULL")->
        fetchAll('id');
        $filter = $settings['smartfilters'] !== null ? explode(',', $settings['smartfilters']) : null;
        $feature_filter = array();
        $features['price'] = array(
            'id' => 'price',
            'name' => 'Price'
        );
        $features += $selectable_and_boolean_features;

        $_smartfilters_name = $settings['smartfilters_name'] !== null ? explode(',', $settings['smartfilters_name']) : array();
        $smartfilters_name = array();
        if (!empty($filter)) {
            foreach ($filter as $k => $feature_id) {
                $smartfilters_name[$feature_id] = ifempty($_smartfilters_name[$k]);
                $feature_id = trim($feature_id);
                if (isset($features[$feature_id])) {
                    $feature_filter[$feature_id] = $features[$feature_id];
                    $feature_filter[$feature_id]['checked'] = true;
                    unset($features[$feature_id]);
                }
            }
        }
        $data = array(
            'allow_smartfilters' => (bool)$filter,
            'smartfilters' => $feature_filter + $features,
            'smartfilters_name' => $smartfilters_name
        );


        $view = wa()->getView();
        $view->assign($data);

        return $view->fetch($this->path.'/templates/hooks/backendCategoryDialog.html');
    }

    public function categorySave(&$data)
    {
        if(!empty($data['id'])) {
            if (waRequest::post('allow_smartfilters')) {
                $smartfilters = implode(',', waRequest::post('smartfilters'));
                $smartfilters_name = implode(',', waRequest::post('smartfilters_name'));
            } else {
                $smartfilters = null;
                $smartfilters_name = null;
            }
            $model = new shopCategoryModel();

            if(waRequest::post('smartfilters_descendants')) {
                $parent = $model->getById($data['id']);

                $model->query('UPDATE '.$model->getTableName().' '
                    .'SET smartfilters = s:smartfilters, smartfilters_name = s:smartfilters_name '.
                    'WHERE left_key >= i:left_key AND right_key <= i:right_key',
                    array(
                        'smartfilters' => $smartfilters,
                        'smartfilters_name' => $smartfilters_name,
                        'left_key'  => $parent['left_key'],
                        'right_key' => $parent['right_key'],
                    )
                );
            } else {
                $model->updateById($data['id'], array(
                    'smartfilters' => $smartfilters,
                    'smartfilters_name' => $smartfilters_name,
                ));
            }
        }
    }

    public static function get($category_id)
    {
        if(wa('shop')->getPlugin('smartfilters')->getSettings('enabled') !== '1')
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

