<?php


class shopSmartfiltersPluginProductsCollection extends shopProductsCollection
{

    /**
     * @param shopProductsCollection $collection
     */
    public static function prepareCollection($collection)
    {
        if(!empty($collection->options['skip_smartfilters'])) {
            return;
        }
        $collection->options['skip_smartfilters'] = true;
        //$collection->options['no_plugins'] = true;

        $data = waRequest::get();
        $delete = array('page', 'sort', 'order');
        foreach ($delete as $k) {
            if (isset($data[$k])) {
                unset($data[$k]);
            }
        }

        if(empty($data)) {
            return;
        }
        $collection->addWhere('(p.count > 0 OR p.count IS NULL)');

        static $selectable_features;
        if($selectable_features === null) {
            $codes = array_keys($data);
            $fm = new shopFeatureModel();
            $features = $fm->getByCode($codes);
            $selectable_features = array();
            foreach ($features as $feature) {
                if ($feature['selectable']) {
                    $selectable_features[$feature['code']] = $feature;
                }
            }
        }


        if(empty($selectable_features)) {
            return;
        }

        foreach ($selectable_features as $f) {
            /*
             * Если фильтрация по одной из выборных характеристик, то подменим логику "Where" для этого фильтра.
             */
            if(!empty($data[$f['code']])) {
                $pattern_on = '/^p.id = [a-z0-9]+.product_id AND [a-z0-9]+.feature_id = '.$f['id'].'$/';
                $pattern_where = '/^(:alias.feature_value_id IN \([\d,]+\) AND \(:alias.sku_id IS NULL OR ):alias.sku_id = ([a-z0-9]+).id\)$/';
                foreach ($collection->joins as $index => $join) {
                    if(
                        (ifempty($join['table']) == 'shop_product_features') &&
                        (preg_match($pattern_on, $join['on']))
                    ) {
                        foreach ($collection->where as $i => $where) {
                            $pattern = str_replace(':alias', $join['alias'], $pattern_where);
                            if(preg_match($pattern, $where, $m)) {

                                $where =  $m[1]. '('.$join['alias'].'.sku_id = '.$m[2].'.id AND ('.$m[2].'.count IS NULL OR '.$m[2].'.count > 0)))';
                                $collection->where[$i] = $where;
                            }
                        }
                    }
                }
            }
        }
    }
}