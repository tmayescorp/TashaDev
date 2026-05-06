<?php
return [
    [
        'key'   => 'toggle_quick_search_form',
        'label' => 'Toggle Quick Search Form',
        'type'  => 'checkbox2',
        'value' => 'yes'
    ],
    [
        'key'     => 'taxonomy_suggestion',
        'label'   => 'Suggestions',
        'desc'    => 'Show the Terms in the Listing Location/Listing Category/Listing Tag at the firs time',
        'type'    => 'select',
        'value'   => 'listing_cat',
        'options' => [
            'listing_cat'      => 'Listing Category',
            'listing_location' => 'Listing Location',
            'listing_tag'      => 'Listing Tag'
        ]
    ],
    [
        'key'     => 'isShowParentOnly',
        'label'   => 'Is Show Parent Only?',
        'type'    => 'select',
        'value'   => 'no',
        'options' => [
            'yes' => 'Yes',
            'no'  => 'No',
        ]
    ],
    [
        'key'   => 'taxonomy_suggestion_title',
        'label' => 'Taxonomy Suggestion Title',
        'type'  => 'text',
        'value' => 'Categories'
    ],
    [
        'key'   => 'number_of_term_suggestions',
        'label' => 'Maximum Terms will be shown',
        'type'  => 'text',
        'value' => 6
    ],
    //	array(
    //		'key'       => 'exclude_post_types',
    //		'label'     => 'Exclude Listing Types',
    //		'type'      => 'multiple-select',
    //		'isMultiple'=> 'yes',
    //		'options'   => \WilokeListingTools\Framework\Helpers\General::getPostTypeKeys(false)
    //	),
    [
        'key'     => 'suggestion_order_by',
        'label'   => 'Suggestion Order By',
        'type'    => 'select',
        'options' => [
            'count' => 'Count',
            'id'    => 'ID',
            'slug'  => 'Slug',
            'name'  => 'Name',
            'none'  => 'None',
            'rand'  => 'Random'
        ]
    ],
    [
        'key'     => 'suggestion_order',
        'label'   => 'Suggestion Order',
        'type'    => 'select',
        'options' => [
            'DESC' => 'DESC',
            'ASC'  => 'ASC'
        ]
    ]
];
