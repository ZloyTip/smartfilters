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
        try {
            if(wa('shop')->getPlugin('smartfilters')->getSettings('enabled') === self::DISPLAY_HELPER) {
                return self::display($category_id);
            }
        } catch (waException $e) {
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
            shopSmartfiltersPluginMylangFilters::prepare($filters[$category_id]);
        }

        return ifempty($filters[$category_id], array());
    }


    /**
     * Хелпер для вывода JS в category.html
     * Нужен, если хук frontend_category не подгружается аяксом.
     *
     * @param $category_id
     * @return string
     */
    public static function categoryTheme($category_id)
    {
        if(!$filters = self::getFiltersForCategory($category_id)) {
            return '';
        }
        try {

            $view = wa()->getView();
            $view->assign('filters', $filters); // rewrite default var
            $plugin = wa('shop')->getPlugin('smartfilters');
            $view->assign('smartfilters', $plugin->getSettings());
            return $view->fetch($plugin->path.'/templates/hooks/frontendCategoryTheme.html');
        } catch (Exception $e) {
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
        if(!$enabled = $this->getSettings('enabled')) {
            return '';
        }

        $result = '';

        if ($enabled === self::DISPLAY_TEMPLATE) {
            $result = self::display($category['id']);
        } elseif ($enabled === self::DISPLAY_THEME) {
            $result = self::categoryTheme($category['id']);
        }

        $filters = self::getFiltersForCategory($category['id']);
        if ($filters && $this->getSettings('color_change')) {

            $view = wa('shop')->getView();
            $products = $view->getVars('products');

            $p = new shopSmartfiltersPluginPrepareProducts();
            $products = $p->prepare($products, $filters);
            $view->assign('products', $products);
        }

        return $result;
    }

    /**
     * @param $data
     */
    public function categorySave(&$data)
    {
        if(!empty($data['id'])) {
            if(!waRequest::post('smartfilters')) {
                return;
            }
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

    public function frontendProducts($params)
    {
        return;
    }

    /**
     * @param shopProductsCollection $collection
     */
    public function productsCollectionPrepared($collection)
    {
        $hash = $collection->getHash();

        if(is_array($hash) && !empty($hash[0]) && ($hash[0] == 'category')) {

            if($this->getSfAvailable()) {
                shopSmartfiltersPluginProductsCollection::prepareCollection($collection);
            }
        }
    }

    /**
     * @param $settings
     * @return string
     */
    public function backendCategoryDialog($settings)
    {
        $handler = new shopSmartfiltersPluginBackendCategoryDialogHandler($this, $settings);
        return $handler->execute();
    }

    public function backendSettings()
    {
        $wa = wa($this->app_id);
        $view = $wa->getView();
        $template = $wa->getAppPath('plugins/smartfilters/templates/hooks/backendSettings.html', $this->app_id);

        return array(
            'sidebar_top_li' => $view->fetch($template)
        );
    }

    public function getSfAvailable()
    {
        if(wa()->getEnv() != 'frontend') {
            return false;
        }
        if(waRequest::get('sf_available')) {
            return true;
        }
        if($this->getSettings('sf_available')) {
            return true;
        }
        return false;
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