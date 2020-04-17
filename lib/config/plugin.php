<?php

return array(
    'name' => 'Smart Filters',
    'description' => 'Отличное дополнение фильтров в категории',
    'vendor'=>'972539',
    'version'=>'2.5.4',
    'img'=>'img/smartfilters.gif',
	'handlers'=> array(
        'frontend_category' => 'frontendCategory',
        'frontend_head' => 'frontendHead',
        'frontend_products' => 'frontendProducts',
        'backend_category_dialog' => 'backendCategoryDialog',
        //'backend_settings' => 'backendSettings',
        'category_save' => 'categorySave',
        'products_collection.prepared' => 'productsCollectionPrepared',
    ),
    'shop_settings' => true,
);
//EOF
