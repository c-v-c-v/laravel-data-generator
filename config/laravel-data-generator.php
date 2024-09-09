<?php

use Cv\LaravelDataGenerator\BaseData;

return [
    'base_data_class' => BaseData::class,
    'default_column_comment' => [
        'id' => '主键',
        'created_at' => '创建时间',
        'updated_at' => '修改时间',
    ],
];
