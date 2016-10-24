<?php

return array(
    'name' => 'Smart Filters',
    'description' => 'Плагин фильтрации в категориях',
    'vendor'=>'972539',
    'version'=>'0.0.8',
    'img'=>'img/smartfilters.gif',
	'handlers'=> array(
        'frontend_category' => 'frontendCategory',
        'backend_category_dialog' => 'backendCategoryDialog',
        'category_save' => 'categorySave',
    ),
    'shop_settings' => true,
);
//EOF
