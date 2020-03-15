<?php


class shopSmartfiltersPluginMylangFilters
{
    protected static function checkEnabled()
    {
        if(!wa()->appExists('mylang')) {
            return false;
        }

        wa('mylang');

        if (!mylangHelper::checkSite()) {
            return false;
        }
        return true;
    }

    public static function prepare(&$filters)
    {
        if(!self::checkEnabled()) {
            return;
        }

        $v_ids = array();
        $f_ids = array();
        $c_ids = array();
        foreach ($filters as $k => $f) {
            $f_ids[] = ifset($f['id']);
            if (!(isset($f['type']) && ($f['type'] == "varchar" || $f['type'] == "text" || $f['type'] == "color"))) {
                continue;
            }
            if (!empty($f['values']) && is_array($f['values'])) {
                $v_ids[$f['type']] = array_merge(array_keys($f['values']), ifset($v_ids[$f['type']], array()));
            }
            if($f['id']) {
                $c_ids[$f['id']] = $k;
            }
        }
        $f_ids = array_filter($f_ids);
        if (empty($f_ids)) {
            return;
        }

        $model = new mylangModel();
        $names = $model->getValues('feature', $f_ids, mylangLocale::currentLocale());
        foreach ($names as $k => $f) {
            if(empty($c_ids[$k])) {
                continue;
            }
            $filters[$c_ids[$k]]['name'] = $f['text'];
        }
        $keys = array_keys($v_ids);
        if (empty($keys)) {
            return;
        }
        $values = $model->getPlainValuesSubtype('feature_value', $keys, $v_ids, mylangLocale::currentLocale());
        if (empty($values)) {
            return;
        }
        foreach ($filters as $key => $f) {
            if (!isset($f['values'])) {
                continue;
            }
            foreach ($f['values'] as $k => $v) {
                if (isset($values[$f['type']][$k])) {
                    if ($f['type'] == 'color') {
                        $filters[$key]['values'][$k]->__set('value', $values[$f['type']][$k]);
                    } else {
                        $filters[$key]['values'][$k] = $values[$f['type']][$k];
                    }
                }
            }
        }
    }

}