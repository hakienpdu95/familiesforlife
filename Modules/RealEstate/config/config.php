<?php

return [
    'name' => 'RealEstate',

    // spec/RealEstateForSale_Technical_Specification.md §4.3
    'gallery' => [
        'collection' => 'real_estate_gallery',
        'max_files'  => 6,   // validate ở Action + client FilePond maxFiles
    ],

    'listings_per_page' => 12,
];
