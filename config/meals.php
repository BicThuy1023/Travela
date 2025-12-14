<?php

return [
    'levels' => [
        'budget' => [
            'label' => 'Bình dân',
            'multiplier' => 0.8,  // 80% so với tiêu chuẩn
        ],
        'standard' => [
            'label' => 'Tiêu chuẩn',
            'multiplier' => 1.0,  // 100% (chuẩn)
        ],
        'premium' => [
            'label' => 'Cao cấp',
            'multiplier' => 1.5,  // 150% so với tiêu chuẩn
        ],
    ],

    'types' => [
        'restaurant' => 'Nhà hàng',
        'buffet' => 'Buffet',
        'local_shop' => 'Quán địa phương',
        'street_food' => 'Ẩm thực đường phố',
    ],

    'meals' => [
        'breakfast' => 'Ăn sáng',
        'lunch' => 'Ăn trưa',
        'dinner' => 'Ăn tối',
    ],
];

