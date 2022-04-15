<?php

class shopSmartfiltersPluginFeatureModel extends shopFeatureModel
{
    private $category_id;
    /**
     * @var shopProductsCollection
     */
    private $collection;
    private $features;
    private $sf_available;

    public function getByCategoryId($category_id)
    {
        /**
         * @var shopSmartfiltersPlugin $plugin
         */
        $plugin = wa('shop')->getPlugin('smartfilters');
        $this->category_id = (int)$category_id;

        $s = microtime(1);
        $filters = $this->getPossibleFilterValues();


        if ($data = waRequest::get()) {
            $this->sf_available = $plugin->getSfAvailable();

            foreach ($filters as $code => &$filter) {
                if($code == 'price') {
                    //$filter['nmin'] = 2400;
                    //$filter['nmax'] = 2500;
                }
                if(in_array($code, array('price', 'sf_available'))) continue;

                $enabledFilters = $this->getEnabledFilters($code, $data);


                if ($enabledFilters === false) {
                    foreach ($filter['values'] as $val_id => $val) {
                        $filter['disabled'][$val_id] = true;
                    }
                } elseif (is_array($enabledFilters)) {
                    $min = $max = $unit = null;
                    $new_minmax = false;

                    foreach ($filter['values'] as $val_id =>  $val) {
                        $filter['disabled'][$val_id] = in_array($val_id, $enabledFilters) ? false : true;
                        if(!$filter['disabled'][$val_id]) {
                            $new_minmax = true;

                            $this->_castMinMax($val, $min, $max, $unit);
                        }
                    }

                    if($new_minmax) {
                        if (!empty($filter['unit'])) {
                            $type = preg_replace('/^[^\.]*\./', '', $filter['type']);
                            $dimension = shopDimension::getInstance();
                            $min = $dimension->convert($min, $type, $filter['unit']['value']);
                            $max = $dimension->convert($max, $type, $filter['unit']['value']);
                        }
                        if ($min !== null) {
                            $filter['nmin'] = $min;
                        }
                        if ($max !== null) {
                            $filter['nmax'] = $max;
                        }
                    }
                }
            }
        }
        //wa_dump(round(microtime(1) - $s, 2));
        return $filters;
    }

    private function getPossibleFilterValues()
    {
        $category_model = new shopCategoryModel();
        $category = $category_model->getById($this->category_id);

        if(empty($category['smartfilters'])) {
            return array();
        }

        $collection = new shopProductsCollection('category/'.$category['id'], array(
            'skip_smartfilters' => true,
            'filters' => false,
            'no_plugins' => true,
        ));

        $this->collection = clone $collection;


        $count = $collection->count();
        if (!$count) {
            return array();
        }

        //$products = $collection->getProducts('id,price,compare_price,currency', 0, $limit);
        //$this->product_ids = array_keys($products);

        static $order;
        if($order === null) {
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
            $code = $fid;
            if ($fid == 'price') {
                $range = $collection->getPriceRange();
                if ($range['min'] != $range['max']) {
                    $filters['price'] = array(
                        'min' => shop_currency($range['min'], null, null, false),
                        'max' => shop_currency($range['max'], null, null, false),
                    );
                    if($data = waRequest::get()) {
                        $collection->filters($data);

                        $range = $collection->getPriceRange();
                        $filters['price']['nmin'] = shop_currency($range['min'], null, null, false);
                        $filters['price']['nmax'] = shop_currency($range['max'], null, null, false);
                    }
                }
            } elseif ($fid == 'sf_available') {
                $filters[$fid] = array(
                    'type' => $fid,
                    'code' => $fid,
                    'name' => _wp('In stock'),
                    'values' => array(
                        1 => _wp('Only available')
                    )
                );
            } elseif (isset($features[$fid]) && isset($category_value_ids[$fid])) {
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
                        $this->_castMinMax($v, $min, $max, $unit);
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

            if(!empty($filter_names[$k]) && !empty($filters[$code])) {
                $filters[$code]['name'] = $filter_names[$k];
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

        if (!$this->collection || !count($data)) {
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

        if($this->sf_available) {
            $where[] = '(p.count > 0 OR p.count IS NULL)';
        }

        foreach ($data as $feature_code => $values) {
            if (!is_array($values)) {
                if($values === '') {
                    continue; // skip "Не важно"
                }
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
                        " JOIN %s %s ON %s",
                        'shop_product_features',
                        'filter' . $feature_join_index,
                        'p.id = filter' . $feature_join_index . '.product_id AND ' .
                        'filter' . $feature_join_index . '.feature_id = ' . (int)$this->features[$feature_code]['id']
                    );
                    $where[] = 'filter' . $feature_join_index . ".feature_value_id IN (" . implode(',', $values) . ")";
                }
            }
        }



        // @todo прям очень грубый подход. Да и getSql вроде не всегда был публичным методом.
        // Надо будет пересмотреть.

        $_sql = explode('WHERE', $this->collection->getSql());

        $joins[] = ' JOIN shop_product_features pf ON pf.product_id = p.id ';
        $joins[] = ' JOIN shop_product_skus sp ON sp.product_id = pf.product_id ';

        $where[] = 'pf.feature_id = :feature_id';

        if($this->sf_available) {

            $where[] = '(sp.count IS NULL OR sp.count > 0)';
        } else {
            /* @todo проверить ситуацию, когда значение присвоено только товару с артикулами, недоступными для заказа. */
            $where[] = 'sp.available = 1';
        }

        $sql = "SELECT DISTINCT pf.feature_value_id ".
            $_sql[0].' '.
            implode('', $joins) .
            " WHERE " .
            $_sql[1].
            ($where ? ' AND '.implode(' AND ', $where) : '') . " GROUP BY p.id";


        $res = $this
            ->query($sql, array(
                'feature_id' => (int)$this->features[$key]['id']
            ))
            ->fetchAll(null, true);

        return array_map('intval', $res);
    }

    /**
     * @param $v
     * @param $min
     * @param $max
     * @param $unit
     */
    protected function _castMinMax($v, &$min, &$max, &$unit)
    {
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