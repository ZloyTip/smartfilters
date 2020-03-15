<?php


class shopSmartfiltersPluginBackendCategoryDialogHandler
{
    /**
     * @var array
     */
    protected $category;
    /**
     * @var shopFeatureModel
     */
    protected $feature_model;
    /**
     * @var shopSmartfiltersPlugin
     */
    protected $plugin;

    public function __construct($plugin, $category)
    {
        $this->plugin = $plugin;
        $this->category = $category;

        $this->feature_model = new shopFeatureModel();

    }

    public function execute()
    {
        $checked_features = $this->category['smartfilters'] !== null ?
            explode(',', $this->category['smartfilters']) : array();


        $available_filters = $this->plugin->getSettings('settings_only_available') ?
            $this->getAvailableFilters($checked_features) :
            $this->getAllFilters()
        ;

        $checked_filters = array();

        $names = $this->category['smartfilters_name'] !== null ?
            explode(',', $this->category['smartfilters_name']) : array();

        if (!empty($checked_features)) {
            foreach ($checked_features as $k => $feature_id) {
                $feature_id = trim($feature_id);
                if (isset($available_filters[$feature_id])) {
                    $checked_filters[$feature_id] = $available_filters[$feature_id];
                    $checked_filters[$feature_id]['checked'] = true;
                    $checked_filters[$feature_id]['new_name'] = ifempty($names[$k]);
                    unset($available_filters[$feature_id]);
                }
            }
        }
        $data = array(
            'allow_smartfilters' => (bool)$checked_features,
            'smartfilters' => $checked_filters + $available_filters,
            'smartfilters_show' => $this->plugin->getSettings('settings_show_list')
        );

        return $this->display($data);
    }

    protected function display(array $data)
    {
        $app_id = 'shop';
        $wa = wa($app_id);
        $view = $wa->getView();
        $view->assign($data);
        $template = $wa->getAppPath('plugins/smartfilters/templates/hooks/backendCategoryDialog.html', $app_id);

        return $view->fetch($template);

    }

    protected function getAvailableFilters(array $checked_features)
    {
        $features = array();

        $features['price'] = array(
            'id' => 'price',
            'name' => _wp('Price')
        );

        $features['sf_available'] = array(
            'id' => 'sf_available',
            'name' => _wp('In stock')
        );

        if(empty($this->category['id'])) {
            return $features;
        }



        $collection = new shopProductsCollection('category/'.$this->category['id'], array(
            'skip_smartfilters' => true,
            'no_plugins' => true,
        ));

        $count = $collection->count();
        if (!$count) {
            return $features;
        }
        $max_count = $this->plugin->getSettings('max_count');
        $limit = $max_count > 0 ? $max_count : $count;

        $products = $collection->getProducts('id,price,compare_price,currency', 0, $limit);

        $feature_ids = wao(new shopProductFeaturesModel())
            ->select('DISTINCT feature_id')
            ->where('product_id IN (:product_ids)', array('product_ids' => array_keys($products)))
            ->fetchAll(null, true);

        if(!$feature_ids = array_merge($checked_features, $feature_ids)) {
            return $features;
        }


        $selectable_and_boolean_features = $this->feature_model
            ->select('*')
            ->where("(selectable=1 OR type='boolean' OR type='double' OR type LIKE 'dimension\.%' OR ".
                "type LIKE 'range\.%') AND parent_id IS NULL AND id IN(:ids)", array('ids' => $feature_ids))
            ->fetchAll('id');

        $features += $selectable_and_boolean_features;

        return $features;
    }

    protected function getAllFilters()
    {
        $features = array();

        $features['price'] = array(
            'id' => 'price',
            'name' => _wp('Price')
        );

        $features['sf_available'] = array(
            'id' => 'sf_available',
            'name' => _wp('In stock')
        );

        $selectable_and_boolean_features = $this->feature_model
            ->select('*')
            ->where("(selectable=1 OR type='boolean' OR type='double' OR type LIKE 'dimension\.%' OR ".
                "type LIKE 'range\.%') AND parent_id IS NULL")
            ->fetchAll('id');

        $features += $selectable_and_boolean_features;

        return $features;
    }
}