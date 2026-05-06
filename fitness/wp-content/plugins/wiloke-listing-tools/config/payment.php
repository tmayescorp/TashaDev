<?php
return [
    'changedPlanID'                    => 'changed_plan_id',
    'promotionPaymentRelationship'     => 'promotion_post_id',
    'wooOldOrderID'                    => 'woo_old_order_id',
    'oldPaymentID'                     => 'old_payment_id',
    'focusNonRecurringPayment'         => 'focus_non_recurring_payment',
    'stripeForeverCoupon'              => 'wilcity_free_forever',
    'storePlanRelationshipIDSessionID' => 'store_plan_relationship_ID_sessionID',
    'category'                         => 'payment_category',
    'storePostID'                      => 'store_postID',
    'storePlanID'                      => 'submission_store_planID',
    'storeDiscountValue'               => 'submission_store_discount_value',
    'startFreePlan'                    => 'submission_start_free_plan',
    'planStartedAtGMT'                 => 'submission_plan_started_at_gmt',
    'nextBillingDateGMT'               => 'submission_next_billing_date_gmt',
    'planType'                         => 'submission_plan_type',
    'listingType'                      => 'submission_listing_type',
    'onChangedPlan'                    => 'submission_on_change_plan',
    'stripeSubscriptionID'             => 'stripe_subscription_ID',
    'subscriptionID'                   => 'subscription_id',
    'paypalPaymentID'                  => 'paypal_payment_id',
    'productIDPaymentID'               => 'product_id_payment_id',
    'orderIDPaymentID'                 => 'order_id_payment_id',
    'paypalAgreementID'                => 'paypal_agreement_id',
    'stripeChargedID'                  => 'stripe_charged_id',
    'stripePaymentIntent'              => 'stripe_payment_intent',
    'sessionRelationshipStore'         => 'session_relationship_store',
    'sessionObjectStore'               => 'session_object_store',
    'sessionStartDate'                 => 'session_start_date',
    'sessionEndDate'                   => 'session_end_date',
    'associateProductID'               => 'associate_product_ID',
    'paypalTokenAndStoreData'          => 'paypal_token_id_relationship',
    'paymentTokenID'                   => 'payment_token_id',
    'paymentIntentID'                  => 'payment_intent_id', // it's for one time payment
    'paymentInfo'                      => 'payment_info',
    'paymentDispute'                   => 'payment_dispute',
    'paymentID'                        => 'payment_id',
    'focusapprovedimmediately'         => 'focusapprovedimmediately',
    'paymentStripeEvent'               => 'payment_dispute_info',
    'gateways'                         => [
        'all'          => esc_html__('All', 'wiloke-listing-tools'),
        'paypal'       => esc_html__('PayPal', 'wiloke-listing-tools'),
        'stripe'       => esc_html__('Stripe', 'wiloke-listing-tools'),
        'banktransfer' => esc_html__('Bank Transfer', 'wiloke-listing-tools'),
        'woocommerce'  => esc_html__('WooCommerce', 'wiloke-listing-tools')
    ],
    'planTypes'                        => [
        'all'          => esc_html__('All Plans', 'wiloke-listing-tools'),
        'listing_plan' => esc_html__('Listing Plan', 'wiloke-listing-tools'),
        'event_plan'   => esc_html__('Event Plan', 'wiloke-listing-tools')
    ],
    'billingTypes'                     => [
        'nonrecurring' => 'NonRecurringPayment',
        'recurring'    => 'RecurringPayment'
    ]
];
