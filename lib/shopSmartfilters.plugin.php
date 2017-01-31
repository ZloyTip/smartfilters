<?php

class shopSmartfiltersPlugin extends shopPlugin {

    const THEME_FILE = 'plugin.smartfilters.html';

    const DISPLAY_TEMPLATE = '1';
    const DISPLAY_HELPER = '2';
    const DISPLAY_THEME = '3';

    /************
     * Хелперы
     ************/

    /**
     * Возвращает HTML с фильтром для категории.
     *
     * @param $category_id
     * @return string
     */
    public static function get($category_id)
    {
        if(wa('shop')->getPlugin('smartfilters')->getSettings('enabled') === self::DISPLAY_HELPER) {
            return self::display($category_id);
        }
        return '';
    }

    /**
     * Возвращает массив фильтров для категории.
     *
     * @param $category_id
     * @return array
     */
    public static function getFiltersForCategory($category_id) {
        static $filters;
        if($filters === null) {
            $filters = array();
        }
        if(!isset($filters[$category_id])) {
            $feature_model = new shopSmartfiltersPluginFeatureModel();
            $filters[$category_id] = $feature_model->getByCategoryId($category_id);
        }

        return ifempty($filters[$category_id], array());
    }

    /**
     * Хелпер для вывода JS в category.html
     *
     * @param $category_id
     * @return string
     */
    public static function categoryTheme($category_id)
    {
        if($filters = self::getFiltersForCategory($category_id)) {
            $view = wa()->getView();
            $view->assign('filters', $filters); // rewrite default var
            $plugin = wa('shop')->getPlugin('smartfilters');
            $view->assign('smartfilters', $plugin->getSettings());
            return $view->fetch($plugin->path.'/templates/hooks/frontendCategoryTheme.html');
        }

        return '';
    }


    /**********************
     * Обработчики хуков
     **********************/


    /**
     * @return string
     */
    public function frontendHead()
    {
        if(waRequest::param('action') == 'category') {
            $e = $this->getSettings('enabled');

            if ($e && ($e !== self::DISPLAY_THEME) && !$this->getSettings('ui_slider')) {
                $view = wa()->getView();
                return $view->fetch($this->path . '/templates/hooks/frontendHead.html');
            } elseif ($e === self::DISPLAY_THEME) {
                $view = wa()->getView();
                return $view->fetch($this->path . '/templates/hooks/frontendHeadTheme.html');
            }
        }

        return '';
    }

    /**
     * @param $category
     * @return string
     */
    public function frontendCategory($category)
    {
        $enabled = $this->getSettings('enabled');
        if($enabled === self::DISPLAY_TEMPLATE) {
            return self::display($category['id']);
        } elseif($enabled === self::DISPLAY_THEME) {
            return self::categoryTheme($category['id']);
        }
        return '';
    }

    /**
     * @param $settings
     * @return string
     */
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

    /**
     * @param $data
     */
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


    /************
     * Всякое
     ************/

    /**
     * @param $category_id
     * @return string
     */
    private static function display($category_id)
    {
        if ($filters = self::getFiltersForCategory($category_id)) {
            $list = new shopSmartfiltersPluginShowAction();
            $list->setFilters($filters);
            return $list->display(false);
        }
        return '';
    }
}