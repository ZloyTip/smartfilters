<?php

class shopSmartfiltersPluginFeatureModel extends shopFeatureModel
{
    private $category_id;
    private $product_ids;
    private $features;

    public function getByCategoryId($category_id)
    {
        $this->category_id = (int)$category_id;
        $possible_filter_values = $this->getPossibleFilterValues();
        if (!$possible_filter_values) {
            return array();
        }

        $feature_model = new shopFeatureModel();
        $this->features = $feature_model->select('*')
            ->where('id IN (:ids)', array('ids' => array_keys($possible_filter_values)))
            ->fetchAll('code');

        $model = new shopFeatureValuesVarcharModel();

        $order = wa('shop')->getPlugin('smartfilters')->getSettings('order');

        $filters = array();
        foreach ($this->features as $row) {
            /**
             * @TODO: Processing of other types.
             * $model = shopFeatureModel::getValuesModel($row['type']);
             */
            if($row['type'] != 'varchar')
                continue;

            //$model = shopFeatureModel::getValuesModel($row['type']);type']);
            $query = $model->select('id, value')
                ->where('id IN(?)', array($possible_filter_values[$row['id']]));
            switch($order) {
                case 'value_asc':
                    $query->order('value ASC');
                    break;
                case 'value_desc':
                    $query->order('value DESC');
                    break;
            }
            $values = $query->fetchAll('id', true);

            $filters[$row['code']] = array(
                'name' => $row['name'],
                'code' => $row['code'],
                'values' => $values,
                'disabled' =>  array()
            );
        }

        $data = waRequest::get();
        if ($data) {
            foreach ($filters as &$filter) {
                $enabledFilters = $this->getEnabledFilters($filter['code'], $data);

                if ($enabledFilters === false) {
                    foreach ($filter['values'] as $val_id => $val) {
                        $filter['disabled'][$val_id] = true;
                    }
                } elseif (is_array($enabledFilters)) {
                    foreach ($filter['values'] as $val_id =>  $val) {
                        $filter['disabled'][$val_id] = in_array($val_id, $enabledFilters) ? false : true;
                    }
                }
            }
        }
        return $filters;
    }

    private function getPossibleFilterValues()
    {
        $category_model = new shopCategoryModel();


        $category = $category_model->getById($this->category_id);

        if ($category['include_sub_categories']) {
            $subcategories = $category_model->descendants($category, true)
                ->where('type = ' . shopCategoryModel::TYPE_STATIC)->fetchAll('id');
            $ids = array_keys($subcategories);
        } elseif ($category['id']) {
            $ids = array($category['id']);
        }

        if (!$ids) {
            return array();
        }

        $sql = 'SELECT p.id FROM shop_product p JOIN shop_category_products cp ON p.id = cp.product_id '.
            'WHERE p.status > 0 AND cp.category_id IN (:category_ids)';

        $this->product_ids = $this->query($sql, array('category_ids' => $ids))->fetchAll(null, true);
        if (!$this->product_ids) {
            return array();
        }

        $product_feature_model = new shopProductFeaturesModel();
        $disabled_features = wa('shop')->getPlugin( 'smartfilters')->getSettings('disabled_features');
        $disabled_features = explode(',', $disabled_features);
        $res = $product_feature_model->getValuesByCategory($ids);
        foreach ($disabled_features as $df_id) {
            unset($res[$df_id]);
        }

        return $res;
    }


    public function getEnabledFilters($key, $data)
    {
        $delete = array('page', 'sort', 'order', $key);
        foreach ($delete as $k) {
            if (isset($data[$k])) {
                unset($data[$k]);
            }
        }

        if (!count($data)) {
            return true;
        }

        $where = array();
        $joins = array();

        if (isset($data['price_min']) && $data['price_min'] !== '') {
            $where[] = 'p.price >= ' . (int)$data['price_min'];
            unset($data['price_min']);
        }
        if (isset($data['price_max']) && $data['price_max'] !== '') {
            $where[] = 'p.price <= ' . (int)$data['price_max'];
            unset($data['price_max']);
        }
        $feature_join_index = 0;

        foreach ($data as $feature_id => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }

            if (isset($this->features[$feature_id])) {
                $feature_join_index++;
                $joins[] = sprintf(
                    " LEFT JOIN %s %s ON %s",
                    'shop_product_features',
                    'filter' . $feature_join_index,
                    'p.id = filter' . $feature_join_index . '.product_id AND '.
                    'filter' . $feature_join_index . '.feature_id = ' . (int)$this->features[$feature_id]['id']
                );
                foreach ($values as & $v) {
                    $v = (int)$v;
                }
                $where[] = 'filter' . $feature_join_index . ".feature_value_id IN (" . implode(',', $values) . ")";
            }
        }

        if (!$feature_join_index) {
            return true;
        }

        $where[] = 'p.id IN (:product_ids)';
        $sql = "SELECT p.id FROM shop_product p " . implode('', $joins) . " WHERE " . implode(' AND ', $where) . " GROUP BY p.id";

        $product_ids = $this->query($sql, array('product_ids' => $this->product_ids))->fetchAll(null, true);

        if (!$product_ids) {
            return false;
        }

        $sql = "SELECT DISTINCT feature_value_id FROM shop_product_features WHERE product_id IN(:product_ids) AND feature_id = :feature_id";
        $res = $this->query($sql, array('product_ids' => $product_ids, 'feature_id' => (int)$this->features[$key]['id']))
            ->fetchAll(null, true);

        $res = array_map('intval', $res);
        return $res;
    }
}