<?php


class shopSmartfiltersPluginPrepareProducts
{

    public function prepare($products, $filters)
    {

        $product_ids = array();
        foreach ($products as $p_id => $p) {
            if ($p['sku_count'] > 1) {
                $product_ids[] = $p_id;
            }
        }
        if ($product_ids) {
            $max_price = $min_price = null;
            $tmp = $this->getFilterValues($filters, $max_price, $min_price);
            $rows = $this->getSkuRawData($tmp, $product_ids, $min_price, $max_price);

            $product_skus = array();
            shopRounding::roundSkus($rows, $products);
            foreach ($rows as $row) {
                $product_skus[$row['product_id']][] = $row;
            }

            if ($product_skus) {
                foreach ($product_skus as $product_id => $skus) {

                    usort($skus, array($this, 'sortSkus'));

                    $sku = $this->getApliableSku($products[$product_id], $skus, $min_price, $max_price);

                    if ($products[$product_id]['sku_id'] != $sku['id']) {
                        $products[$product_id] = $this->replacePrice($products[$product_id], $sku);
                        $products[$product_id] = $this->replaceImage($products[$product_id], $sku);
                    }
                }

            }
        }
        return $products;
    }


    protected function sortSkus($a, $b)
    {
        if ($a['sort'] == $b['sort']) {
            return 0;
        }
        return ($a['sort'] < $b['sort']) ? -1 : 1;
    }

    /**
     * @param $product
     * @param $skus
     * @param $min_price
     * @param $max_price
     * @return mixed
     */
    protected function getApliableSku($product, $skus, $min_price, $max_price)
    {
        $currency = $product['currency'];
        $k = null;
        foreach ($skus as $i => $sku) {
            if ($min_price) {
                $tmp_price = shop_currency($min_price, true, $currency, false);
                if ($sku['price'] < $tmp_price) {
                    continue;
                }
            }
            if ($max_price) {
                $tmp_price = shop_currency($max_price, true, $currency, false);
                if ($sku['price'] > $tmp_price) {
                    continue;
                }
            }
            if ($product['sku_id'] == $sku['id']) {
                $k = $i;
                break;
            }
            if ($k === null) {
                $k = $i;
            }
        }
        if ($k === null) {
            $k = 0;
        }
        $sku = $skus[$k];
        return $sku;
    }

    private function replacePrice($product, $sku)
    {

        static $default_currency;
        if($default_currency === null) {
            $default_currency = wa('shop')->getConfig()->getCurrency(true);
        }
        $currency = $product['currency'];
        $product['sku_id'] = $sku['id'];
        $product['frontend_url'] .= '?sku=' . $sku['id'];
        $product['price'] = shop_currency($sku['price'], $currency, $default_currency, false);
        $product['frontend_price'] = $sku['price'];
        $product['unconverted_price'] = shop_currency($sku['unconverted_price'], $currency, $default_currency, false);
        $product['compare_price'] = shop_currency($sku['compare_price'], $currency, $default_currency, false);
        $product['frontend_compare_price'] = $sku['compare_price'];
        $product['unconverted_compare_price'] = shop_currency($sku['unconverted_compare_price'], $currency, $default_currency, false);
        return $product;
    }

    private function replaceImage($product, $sku)
    {
        if ($sku['image_id'] && $product['image_id'] != $sku['image_id']) {
            if (isset($sku['ext'])) {
                $product['image_id'] = $sku['image_id'];
                $product['ext'] = $sku['ext'];
                $product['image_filename'] = $sku['image_filename'];
            }
        }
        return $product;
    }

    /**
     * @param $filters
     * @param $product_ids
     * @param $min_price
     * @param $max_price
     * @return array
     * @throws waException
     */
    protected function getSkuRawData($filters, $product_ids, $min_price, $max_price)
    {
        $rows = array();
        if ($filters) {
            $pf_model = new shopProductFeaturesModel();
            $rows = $pf_model->getSkusByFeatures($product_ids, $filters, waRequest::param('drop_out_of_stock') == 2);
            $image_ids = array();
            foreach ($rows as $row) {
                if ($row['image_id']) {
                    $image_ids[] = $row['image_id'];
                }
            }
            if ($image_ids) {
                $image_model = new shopProductImagesModel();
                $images = $image_model->getById($image_ids);
                foreach ($rows as &$row) {
                    if ($row['image_id'] && isset($images[$row['image_id']])) {
                        $row['ext'] = $images[$row['image_id']]['ext'];
                        $row['image_filename'] = $images[$row['image_id']]['filename'];
                    }
                }
                unset($row);
            }
        } elseif ($min_price || $max_price) {
            $ps_model = new shopProductSkusModel();
            $rows = $ps_model->getByField('product_id', $product_ids, true);
        }
        return $rows;
    }

    /**
     * @param $filters
     * @param $max_price
     * @param $min_price
     * @return array
     */
    protected function getFilterValues($filters, &$max_price, &$min_price)
    {
        $min_price = $max_price = null;
        $tmp = array();
        foreach ($filters as $fid => $f) {
            if ($fid == 'price') {
                $min_price = waRequest::get('price_min');
                if (!empty($min_price)) {
                    $min_price = (double)$min_price;
                } else {
                    $min_price = null;
                }
                $max_price = waRequest::get('price_max');
                if (!empty($max_price)) {
                    $max_price = (double)$max_price;
                } else {
                    $max_price = null;
                }
            } else {
                $fvalues = waRequest::get($f['code']);
                if ($fvalues && !isset($fvalues['min']) && !isset($fvalues['max'])) {
                    $tmp[$f['id']] = $fvalues;
                }
            }
        }
        return $tmp;
    }

}