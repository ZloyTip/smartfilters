<?php


class shopSmartfiltersCli extends waCliController
{

    public function execute()
    {
        $action = waRequest::param(0);
        if (!$action || ($action === 'help')) {
            $available_methods = array();
            $methods = get_class_methods($this);
            foreach ($methods as $m) {
                if ($m === 'logAction') {
                    continue;
                }
                $pattern = '/Action$/';
                if (preg_match($pattern, $m)) {
                    $available_methods[] = preg_replace($pattern, '', $m);
                }
            }

            $app = preg_replace('/[A-Z].+/', '', __CLASS__);
            $id = preg_replace('/^[a-z]+|Cli$/', '', __CLASS__);
            $id = strtolower($id);

            echo "Usage: php cli.php $app $id [" . implode('|', $available_methods) . "]\n";
            return;
        }
        $method = $action . 'Action';
        if (!method_exists($this, $method)) {
            $msg = sprintf('Action "%s" not exists', $action);
            throw new waException($msg);
        }
        $this->$method();
    }

    protected function productRemoveFeaturesSelectableAction()
    {
        $model = new waModel();
        $model->exec('DELETE pf FROM shop_product_features pf
JOIN shop_product_features_selectable pfs
ON pf.product_id = pfs.product_id AND pf.feature_id = pfs.feature_id
WHERE pf.sku_id IS NULL');
    }
}