<?php

class shopSmartfiltersPluginFeatureModel extends shopFeatureModel
{
    private $category_id;
    private $product_ids;
    private $features;

    public function getByCategoryId($category_id)
    {
        $this->category_id = (int)$category_id;
        $filters = $this->getPossibleFilterValues();

        if ($data = waRequest::get()) {
            foreach ($filters as $code => &$filter) {
                if($code == 'price') continue;
                $enabledFilters = $this->getEnabledFilters($code, $data);

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

        if(empty($category['smartfilters'])) {
            return array();
        }

        $collection = new shopProductsCollection('category/'.$category['id']);
        $this->product_ids = array_keys($collection->getProducts('id,price,compare_price,currency', 0, 1000));

        if (!$this->product_ids) {
            return array();
        }

        static $order;
        if(!$order) {
            $order = wa('shop')->getPlugin('smartfilters')->getSettings('order');
        }

        $filter_ids = explode(',', $category['smartfilters']);
        $filter_names = explode(',', $category['smartfilters_name']);
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
        if ($features) {
            $features = $feature_model->getValues($features);
        }
        $category_value_ids = $collection->getFeatureValueIds();

        $filters = array();
        $this->features = array();
        foreach ($filter_ids as $k => $fid) {
            if ($fid == 'price') {
                $range = $collection->getPriceRange();
                if ($range['min'] != $range['max']) {
                    $filters['price'] = array(
                        'min' => shop_currency($range['min'], null, null, false),
                        'max' => shop_currency($range['max'], null, null, false),
                    );
                }
            } elseif (isset($features[$fid]) && isset($category_value_ids[$fid])) {
                if(!empty($filter_names[$k])) {
                    $features[$fid]['name'] = $filter_names[$k];
                }
                $code = $features[$fid]['code'];
                $this->features[$code] = $features[$fid];
                $filters[$code] = $features[$fid];
                $filters[$code]['disabled'] = array();
                $min = $max = $unit = null;
                if($order) {
                    natcasesort($filters[$code]['values']);

                    if($order == 'value_desc') {
                        $filters[$code]['values'] = array_reverse($filters[$code]['values']);
                    }
                }
                foreach ($filters[$code]['values'] as $v_id => $v) {
                    if (!in_array($v_id, $category_value_ids[$fid])) {
                        unset($filters[$code]['values'][$v_id]);
                    } else {
                        if ($v instanceof shopRangeValue) {
                            $begin = $this->getFeatureValue($v->begin);
                            if ($min === null || $begin < $min) {
                                $min = $begin;
                            }
                            $end = $this->getFeatureValue($v->end);
                            if ($max === null || $end > $max) {
                                $max = $end;
                                if ($v->end instanceof shopDimensionValue) {
                                    $unit = $v->end->unit;
                                }
                            }
                        } else {
                            $tmp_v = $this->getFeatureValue($v);
                            if ($min === null || $tmp_v < $min) {
                                $min = $tmp_v;
                            }
                            if ($max === null || $tmp_v > $max) {
                                $max = $tmp_v;
                                if ($v instanceof shopDimensionValue) {
                                    $unit = $v->unit;
                                }
                            }
                        }
                    }
                }
                if (!$filters[$code]['selectable'] && ($filters[$code]['type'] == 'double' ||
                        substr($filters[$code]['type'], 0, 6) == 'range.' ||
                        substr($filters[$code]['type'], 0, 10) == 'dimension.')) {

                    if ($min == $max) {
                        unset($filters[$code]);
                    } else {
                        $type = preg_replace('/^[^\.]*\./', '', $filters[$code]['type']);
                        if ($type != 'double') {
                            $filters[$code]['base_unit'] = shopDimension::getBaseUnit($type);
                            $filters[$code]['unit'] = shopDimension::getUnit($type, $unit);
                            if ($filters[$code]['base_unit']['value'] != $filters[$code]['unit']['value']) {
                                $dimension = shopDimension::getInstance();
                                $min = $dimension->convert($min, $type, $filters[$code]['unit']['value']);
                                $max = $dimension->convert($max, $type, $filters[$code]['unit']['value']);
                            }
                        }
                        $filters[$code]['min'] = $min;
                        $filters[$code]['max'] = $max;
                    }
                }
            }
        }
        return $filters;
    }



    /**
     * @param shopDimensionValue|double $v
     * @return double
     */
    protected function getFeatureValue($v)
    {
        if ($v instanceof shopDimensionValue) {
            return $v->value_base_unit;
        }
        if (is_object($v)) {
            return $v->value;
        }
        return $v;
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

        foreach ($data as $feature_code => $values) {
            if (!is_array($values)) {
                $values = array($values);
            }


            if (isset($this->features[$feature_code])) {


                if (isset($values['min']) || isset($values['max']) || isset($values['unit'])) {
                    if (ifset($values['min'], '') === '' && ifset($values['max'], '') === '') {
                        continue;
                    } else {
                        $unit = ifset($values['unit']);
                        $min = $max = null;
                        if (isset($values['min']) && $values['min'] !== '') {
                            $min = $values['min'];
                            if ($unit) {
                                $min = shopDimension::getInstance()->convert($min, $this->features[$feature_code]['type'], null,
                                    $unit);
                            }
                        }
                        if (isset($values['max']) && $values['max'] !== '') {
                            $max = $values['max'];
                            if ($unit) {
                                $max = shopDimension::getInstance()->convert($max, $this->features[$feature_code]['type'],
                                    null, $unit);
                            }
                        }
                        $fm = shopFeatureModel::getValuesModel($this->features[$feature_code]['type']);
                        $values = $fm->getValueIdsByRange($this->features[$feature_code]['id'], $min, $max);
                    }
                } else {
                    foreach ($values as & $v) {
                        $v = (int)$v;
                    }
                }


                if($values) {
                    $feature_join_index++;

                    $joins[] = sprintf(
                        " LEFT JOIN %s %s ON %s",
                        'shop_product_features',
                        'filter' . $feature_join_index,
                        'p.id = filter' . $feature_join_index . '.product_id AND ' .
                        'filter' . $feature_join_index . '.feature_id = ' . (int)$this->features[$feature_code]['id']
                    );
                    $where[] = 'filter' . $feature_join_index . ".feature_value_id IN (" . implode(',', $values) . ")";
                }
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