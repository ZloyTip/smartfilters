<?php

return array(
    'enabled' => array(
        'value'            => '3',
        'title'            => 'Включить фильтрацию',
        'options'          => array(
            '0' => 'нет',
            '3' => 'интеграция с темой * рекомендуемый способ *',
            '1' => 'свой шаблон на месте хука frontend_category',
            '2' => 'свой шаблон в произвольном месте при помощи хелпера',
        ),
        'control_type'     => waHtmlControl::SELECT,
    ),
    'order' => array(
        'value'            => '',
        'title'            => 'Сортировка значений',
        'description'      => 'Плагин учитывает стандартную сортировку значений характеристик. Но ві можете также отсортировать их автоматически.',
        'options'          => array(
            '' => 'нет',
            'value_asc' => 'Наименование (А → Я)',
            'value_desc' => 'Наименование (Я ← А)',
        ),
        'control_type'     => waHtmlControl::SELECT,
    ),
    'ui_slider' => array(
        'value'            => '0',
        'title'            => 'Подключить jQuery UI slider',
        'description'      => 'Библиотека нужна для работы слайдеров в стандартном шаблоне. Подлючается через хук <strong>frontend_head</strong>.',
        'options'          => array(
            '1' => 'нет',
            '0' => 'да',
        ),
        'control_type'     => waHtmlControl::SELECT,
    ),
    'hideDisabled' => array(
        'value'            => '0',
        'title'            => 'Скрывать значения',
        'description'      => 'Можно скрывать или отключать значения фильтров, выбор которых приведёт к пустому результату.',
        'options'          => array(
            '0' => 'отключать',
            '1' => 'скрывать',
        ),
        'control_type'     => waHtmlControl::SELECT,
    ),
    'parentLabelSelector' => array(
        'value'            => 'label,.filter-field',
        'title'            => 'Селектор строчки',
        'description'      => 'Выбранный <strong>родительский</strong> элемент будет скрыт или недоступен, если фильтр по данному значению характеристики недоступен.<br>'.
                                'В большинстве случаев нет необходимости редактировать.',
        'control_type'     => waHtmlControl::INPUT,
    ),
    'parentParamSelector' => array(
        'value'            => '.filter-param,p,.filter-block',
        'title'            => 'Селектор блока',
        'description'      => 'Выбранный <strong>родительский</strong> элемент будет скрыт, если недоступен ни один из фильтров по характеристике.<br>'.
                                'В большинстве случаев нет необходимости редактировать.',
        'control_type'     => waHtmlControl::INPUT,
    ),
);
