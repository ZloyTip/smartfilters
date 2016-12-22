<?php

return array(
    'name' => 'Smart Filters',
    'description' => 'Плагин фильтрации в категориях',
    'vendor'=>'972539',
    'version'=>'1.0.0',
    'img'=>'img/smartfilters.gif',
	'handlers'=> array(
        'frontend_category' => 'frontendCategory',
        'frontend_head' => 'frontendHead',
        'backend_category_dialog' => 'backendCategoryDialog',
        'category_save' => 'categorySave',
    ),
    'shop_settings' => true,
);
//EOF
