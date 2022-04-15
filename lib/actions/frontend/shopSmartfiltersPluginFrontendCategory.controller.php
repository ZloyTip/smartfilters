<?php


class shopSmartfiltersPluginFrontendCategoryController extends waJsonController
{

    public function execute()
    {
        try {

            $category_id = waRequest::param('category_id');
            $this->response = shopSmartfiltersPlugin::getFiltersForCategory($category_id);

        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }
}