<?php
return apply_filters(
    'wilcity/filter/wiloke-listing-tools/invoice-theme-options',
    [
        'title'            => 'Invoice Settings',
        'id'               => 'invoice_settings',
        'icon'             => 'dashicons dashicons-book-alt',
        'subsection'       => false,
        'customizer_width' => '500px',
        'fields'           => [
            [
                'id'          => 'invoice_logo',
                'type'        => 'media',
                'title'       => 'Invoice Logo',
                'description' => 'Leave empty to use Logo that uploaded under General Setting',
            ],
            [
                'id'      => 'invoice_size',
                'type'    => 'select',
                'title'   => 'Invoice Size',
                'options' => [
                    'A4'     => 'A4',
                    'Letter' => 'Letter',
                    'Legal'  => 'Legal'
                ],
                'default' => 'A4'
            ],
            [
                'id'      => 'invoice_type',
                'type'    => 'text',
                'title'   => 'Invoice Type',
                'default' => 'Sale Invoice'
            ],
            [
                'id'      => 'invoice_reference',
                'type'    => 'text',
                'title'   => 'Invoice Reference',
                'default' => 'IVC-%invoiceID%',
            ],
            [
                'id'     => 'invoice_seller_section_settings_open',
                'type'   => 'section',
                'title'  => 'Seller Settings',
                'indent' => true
            ],
            [
                'id'      => 'invoice_billing_from_title',
                'type'    => 'text',
                'title'   => 'Billing From Title',
                'default' => 'Billing From'
            ],
            [
                'id'      => 'invoice_seller_company_name',
                'type'    => 'text',
                'title'   => 'Company Name',
                'default' => 'Sample Company Name'
            ],
            [
                'id'      => 'invoice_seller_company_address',
                'type'    => 'text',
                'title'   => 'Company Address',
                'default' => '172 HoanKiem street'
            ],
            [
                'id'      => 'invoice_seller_company_city_country',
                'type'    => 'text',
                'title'   => 'Company City and Country',
                'default' => 'Hanoi, Vietnam'
            ],
            [
                'id'     => 'invoice_seller_section_settings_close',
                'type'   => 'section',
                'title'  => '',
                'indent' => false
            ],
            [
                'id'     => 'invoice_purchaser_section_settings_open',
                'type'   => 'section',
                'title'  => 'Purchaser Settings',
                'indent' => true
            ],
            [
                'id'      => 'invoice_billing_to_title',
                'type'    => 'text',
                'title'   => 'Billing To Title',
                'default' => 'Billing To'
            ],
            [
                'id'     => 'invoice_purchaser_section_settings_close',
                'type'   => 'section',
                'title'  => '',
                'indent' => false
            ],
            [
                'id'      => 'invoice_badge',
                'type'    => 'text',
                'title'   => 'Badge Name',
                'default' => 'Payment Paid'
            ],
            [
                'id'      => 'invoice_notice_title',
                'type'    => 'text',
                'title'   => 'Notice Title',
                'default' => 'Important Notice'
            ],
            [
                'id'      => 'invoice_notice_description',
                'type'    => 'textarea',
                'title'   => 'Notice Description',
                'default' => 'No item will be replaced or refunded if you don\'t have the invoice with you'
            ],
            [
                'id'      => 'invoice_download_file_name',
                'type'    => 'text',
                'title'   => 'Download File Name',
                'default' => 'INV-%invoiceID%-%invoiceDate%'
            ]
        ]
    ]
);
