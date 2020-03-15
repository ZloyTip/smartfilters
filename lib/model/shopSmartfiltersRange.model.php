<?php


class shopSmartfiltersRangeModel extends waModel
{
    protected $table = 'shop_smartfilters_range';

    /**
     * @param $feature_id
     * @return array
     * @throws waException
     */
    public function getRange($feature_id)
    {
        $range = array();
        $range['feature_id'] = $feature_id;

        $fm = new shopFeatureModel();
        $feature = $fm->getById($range['feature_id']);

        if(!$feature) {
            throw new waException('Характеристика не найдена');
        }

        $range['feature'] = $feature;
        $range['types'] = $this->getTypeFeatureValues($feature);

        return $range;

    }

    /**
     * @param array $feature
     * @return array
     * @throws waException
     */
    protected function getTypeFeatureValues(array $feature)
    {
        $m = new waModel();

        $value_model = shopFeatureModel::getValuesModel($feature['type']);

        $sql = 'SELECT DISTINCT t.name, t.id, pf.feature_value_id FROM shop_product_features pf ' .
            'JOIN shop_product p ON p.id = pf.product_id ' .
            'JOIN shop_type t ON t.id = p.type_id ' .
            'WHERE pf.feature_id = i:id ' .
            'ORDER BY t.id ';

        $_ranged_values = $m->query($sql, $feature)->fetchAll();
        $ranged_values = $values = array();
        foreach ($_ranged_values as $v) {
            if (!isset($ranged_values[$v['id']])) {
                $ranged_values[$v['id']] = array(
                    'name' => $v['name'],
                    'values' => array()
                );
            }
            $ranged_values[$v['id']]['values'][] = $v['feature_value_id'];
        }

        foreach ($ranged_values as $type_id => $v) {
            $type_values = $this->getValues($value_model, $feature['id'], $v['values']);
            $values[$type_id] = array(
                'name' => $v['name'],
                'ranges' => $this->getRangeValues($feature, $type_id, $type_values),
                'values' => $type_values
            );
        }
        return $values;
    }

    protected function getRangeValues($feature, $type_id, $type_values)
    {
        return array();
    }

    /**
     * @param shopFeatureValuesModel $value_model
     * @param $feature_id
     * @param $ids
     * @return array
     */
    protected function getValues($value_model, $feature_id, $ids)
    {
        $selected_values = $value_model->getValues('id', $ids);
        return ifempty($selected_values[$feature_id], array());
    }
}