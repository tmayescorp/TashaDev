<?php
return apply_filters(
    'wilcity/filter/wiloke-listing-tools/email-settings',
    [
        'title'            => 'Email Contents',
        'id'               => 'email_settings',
        'desc'             => 'In order to use this feature, please go to Appearance -> Install Plugins -> Install and Active Email Template plugin.',
        'icon'             => 'dashicons dashicons-email',
        'subsection'       => false,
        'customizer_width' => '500px',
        'fields'           => [
            [
                'id'      => 'email_from',
                'type'    => 'text',
                'title'   => 'Admin Email',
                'default' => get_option('admin_email')
            ],
            [
                'id'      => 'email_brand',
                'type'    => 'text',
                'title'   => 'Brand',
                'default' => 'Wiloke'
            ],
            [
                'id'      => 'email_welcome_subject',
                'type'    => 'text',
                'title'   => 'Welcome Subject',
                'default' => 'Welcome to %brand%!'
            ],
            [
                'id'       => 'email_welcome',
                'type'     => 'textarea',
                'title'    => 'Welcome',
                'subtitle' => 'Say Welcome when a new account is created.',
                'default'  => 'Welcome to %brand%!'
            ],
            [
                'id'      => 'email_confirm_account_subject',
                'type'    => 'text',
                'title'   => 'Confirmation Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_confirm_account',
                'type'    => 'textarea',
                'title'   => 'Confirmation',
                'default' => 'Confirm your email address to complete your @%userName% account. It\'s easy - just click this link %confirmationLink%'
            ],
            [
                'id'      => 'email_become_an_author_subject',
                'type'    => 'text',
                'title'   => 'Became an author Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_become_an_author',
                'type'    => 'textarea',
                'title'   => 'Became an author',
                'default' => 'Hi %customerName%! Thank for being an author. Go %websiteUrl% and submit a listing now ;).'
            ],
            [
                'id'      => 'email_review_notification',
                'type'    => 'textarea',
                'title'   => 'Review Notification',
                'default' => '%customerReviewName% just left an review on %postLink%. The review title is %reviewTitle%.'
            ],
            [
                'id'      => 'email_report_notification',
                'type'    => 'textarea',
                'title'   => 'Report Notification',
                'default' => '%customerReportName% just report %postTitle% on your site %brand%. The report title is %reportTitle%.'
            ],
            [
                'id'      => 'email_listing_submitted_subject',
                'type'    => 'text',
                'title'   => 'After Submitting Subject',
                'default' => 'Your article has been submitted successfully'
            ],
            [
                'id'       => 'email_listing_submitted',
                'type'     => 'textarea',
                'title'    => 'After Submitting',
                'subtitle' => 'This email will be sent after a customer submits a listing to your site.',
                'default'  => 'Congratulations! Your article - %postTitle% - on %brand% has been submitted successfully. Our staff will review it and contact you shortly.'
            ],
            [
                'id'      => 'email_listing_approved_subject',
                'type'    => 'text',
                'title'   => 'Listing Approved Subject',
                'default' => 'Your listing has been approved'
            ],
            [
                'id'       => 'email_listing_approved',
                'type'     => 'textarea',
                'title'    => 'Listing Approved',
                'subtitle' => 'This email will be sent to Listing Author after his/her listing is approved.',
                'default'  => 'Congratulations! Your article - %postTitle% - on %brand% has been submitted approved. You can view your article at: %postLink%.%breakDown% Thanks for your submission!'
            ],
            [
                'id'      => 'email_listing_almost_expired_subject',
                'type'    => 'text',
                'title'   => 'Listing Almost Expired Subject',
                'default' => 'Your listing is almost expired'
            ],
            [
                'id'      => 'email_listing_almost_expired',
                'type'    => 'textarea',
                'title'   => 'Listing Almost Expired',
                'default' => 'Hi %customerName%! Your article - %postTitle% - on %brand% is almost expired %postExpiration%. Please renew it to keep the article is published on the site.'
            ],
            [
                'id'      => 'email_listing_rejected_subject',
                'type'    => 'text',
                'title'   => 'Listing Rejected Subject',
                'default' => 'Your listing has been rejected'
            ],
            [
                'id'      => 'email_listing_rejected',
                'type'    => 'textarea',
                'title'   => 'Listing Rejected',
                'default' => 'Hi %customerName%! Unfortunately, Your listing - %postTitle% - on %brand% has been rejected expired. Here is the reason: %rejectedReason%'
            ],
            [
                'id'      => 'email_listing_expired_subject',
                'type'    => 'text',
                'title'   => 'Listing Expired Subject',
                'default' => 'Your listing has been expired'
            ],
            [
                'id'      => 'email_listing_expired',
                'type'    => 'textarea',
                'title'   => 'Listing Expired',
                'default' => 'Hi %customerName%! Your article - %postTitle% - on %brand% is almost expired. Luckily, you can renew right on the front-end dashboard. Please go to %websiteUrl% -> Log into the website -> Click on Dashboard -> Listings -> And Review it.'
            ],
            [
                'id'      => 'email_listing_almost_deleted_subject',
                'type'    => 'text',
                'title'   => 'Listing Almost Deleted Subject',
                'default' => 'Your Listing is almost deleted'
            ],
            [
                'id'      => 'email_listing_almost_deleted',
                'type'    => 'textarea',
                'title'   => 'Listing Almost Deleted',
                'default' => 'Hi %customerName%! Your article - %postTitle% - on %brand% is will be deleted on %date% at %hour%. Please renew it to keep the article is published on the site.'
            ],
            [
                'id'      => 'email_claim_submitted_subject',
                'type'    => 'text',
                'title'   => 'Claim Submitted Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_claim_submitted',
                'type'    => 'textarea',
                'title'   => 'Claim Submitted',
                'default' => 'Hi %customerName%! Thank for your claiming on %postTitle%. Our staff is reviewing your request. We will contact you as soon as possible.'
            ],
            [
                'id'      => 'email_claim_approved_subject',
                'type'    => 'text',
                'title'   => 'Claim Approved Subject (To Customer)',
                'default' => ''
            ],
            [
                'id'      => 'email_claim_approved',
                'type'    => 'textarea',
                'title'   => 'Claim Approved (To Customer)',
                'default' => 'Congratulations! Your claim - %postTitle% - on %brand% has been approved. The claim url: %postLink%'
            ],
            [
                'id'      => 'email_to_admin_claim_approved_subject',
                'type'    => 'text',
                'title'   => 'Claim Approved Subject (To Admin)',
                'default' => ''
            ],
            [
                'id'      => 'email_to_admin_claim_approved',
                'type'    => 'textarea',
                'title'   => 'Claim Approved (To Admin)',
                'default' => '%customerName% claimed - %postTitle% - on %brand% successfully. The claim url: %postLink%'
            ],
            [
                'id'      => 'email_claim_rejected_subject',
                'type'    => 'text',
                'title'   => 'Claim Rejected Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_claim_rejected',
                'type'    => 'textarea',
                'title'   => 'Claim Rejected',
                'default' => 'Hi %customerName%!%breakDown%Thank you for using %brand%. We regret to inform you that the listing %postTitle% you have claimed on %brand% has been rejected.%breakDown%Please do keep sending in your suggestions and feedback to %adminEmail% and let us know if there’s anything else we can help with.'
            ],
            [
                'id'      => 'email_order_processing_subject',
                'type'    => 'text',
                'title'   => 'Order is processing Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_order_processing',
                'type'    => 'textarea',
                'title'   => 'Order is processing',
                'default' => 'Dear %customerName%!%breakDown%Your order has been received and now being processed. Your order details are show below for your reference:%breakDown% %orderDetails%%breakDown%To complete this plan, please transfer your payment to the following bank accounts:%breakDown% %adminBankAccount%.'
            ],
            [
                'id'      => 'email_subscription_created_subject',
                'type'    => 'text',
                'title'   => 'Subscription Created Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_subscription_created',
                'type'    => 'textarea',
                'title'   => 'Subscription Created',
                'default' => 'Congratulations! Your subscription on %brand% has been created. The subscription details:%breakDown%%subscriptionDetails%%breakDown%Thank for using our service!'
            ],
            [
                'id'      => 'email_subscription_cancelled',
                'type'    => 'textarea',
                'title'   => 'Subscription Cancelled',
                'default' => '%strong%Well, Dang%close_strong%%breakDown%Hey %customerName% - %breakDown%I suppose this is goodbye...but hopefully just for a little while.%breakDown%Consider this email to be confirmation that your plan %planName% has been canceled, and you will no longer be billed.%breakDown%'
            ],
            [
                'id'      => 'email_subscription_refunded',
                'type'    => 'textarea',
                'title'   => 'Payment Refunded',
                'default' => 'Dear %customerName% - %breakDown% Your refunded has been approved. %breakDown%'
            ],
            [
                'id'      => 'email_changed_plan_subject',
                'type'    => 'text',
                'title'   => 'Changed Plan Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_changed_plan',
                'type'    => 'textarea',
                'title'   => 'Changed Plan',
                'default' => 'Congratulations. Your plan has been changed from %oldPlan% to %newPlan%!'
            ],
            [
                'id'      => 'email_stripe_payment_subject',
                'type'    => 'text',
                'title'   => 'Stripe Payment Title',
                'default' => 'Payment Failed'
            ],
            [
                'id'      => 'email_stripe_payment_failed',
                'type'    => 'textarea',
                'title'   => 'Stripe Payment Failed',
                'default' => 'Unfortunately, Your payment was failed. Payment ID: %paymentID%. Plan Name: %planName%. Gateway: Stripe'
            ],
            [
                'id'      => 'email_bank_transfer_almost_billing_date_subject',
                'type'    => 'text',
                'title'   => 'Bank Transfer Almost Billing Date For Customer Subject',
                'default' => 'Almost billing date bank transfer payment to customer'
            ],
            [
                'id'      => 'email_bank_transfer_almost_billing_date',
                'type'    => 'textarea',
                'title'   => 'Bank Transfer Almost Billing Date For Customer Content',
                'default'     => 'Hi %customerName%. Your subscription is almost next billing date. Payment ID: %paymentID%. Next Billing Date: %nextBillingDate%.',
                'description' => '%paymentID% is paymentID, %nextBillingDate% is the time of next billing date'
            ],
            [
                'id'      => 'email_bank_transfer_out_of_billing_date_admin_subject',
                'type'    => 'text',
                'title'   => 'Bank Transfer Out Of Billing For Admin Subject',
                'default' => 'Out of billing date bank transfer payment to admin'
            ],
            [
                'id'      => 'email_bank_transfer_out_of_billing_date_admin',
                'type'    => 'textarea',
                'title'   => 'Bank Transfer Out Of Billing Date For Admin Content',
                'default' => 'Hi admin, the subscription is out of billing date. Payment ID: %paymentID%.Next Billing Date: %nextBillingDate%. Click %paymentDetailUrl% to see the the payment',
                'description' => '%paymentID% is paymentID, %nextBillingDate% is the time of next billing date, %paymentDetailUrl% is the link to admin payment detail page'
            ],
            [
                'id'      => 'email_bank_transfer_out_of_billing_date_customer_subject',
                'type'    => 'text',
                'title'   => 'Bank Transfer Out Of Billing Date For Customer Subject',
                'default' => 'Out of billing date bank transfer payment to customer'
            ],
            [
                'id'      => 'email_bank_transfer_out_of_billing_date_customer',
                'type'    => 'textarea',
                'title'   => 'Bank Transfer Out Of Billing Date For Customer Content',
                'default' => 'Hi %customerName%. Your subscription is out of billing date. Payment ID: %paymentID%. Next Billing Date: %nextBillingDate%.',
                'description' => '%paymentID% is paymentID, %nextBillingDate% is the time of next billing date'
            ],

            [
                'id'      => 'email_bank_transfer_canceled_admin_subject',
                'type'    => 'text',
                'title'   => 'Bank Transfer Canceled For Admin Subject',
                'default' => 'Cancel bank transfer payment to admin'
            ],
            [
                'id'      => 'email_bank_transfer_canceled_admin',
                'type'    => 'textarea',
                'title'   => 'Bank Transfer Canceled For Admin Content',
                'default' => 'Hi admin, this subscription is canceled. Payment ID: %paymentID%.Next Billing Date: %nextBillingDate%. Click %paymentDetailUrl% to see the the payment',
                'description' => '%paymentID% is paymentID, %nextBillingDate% is the time of next billing date, %paymentDetailUrl% is the link to admin payment detail page'
            ],
            [
                'id'      => 'email_bank_transfer_canceled_customer_subject',
                'type'    => 'text',
                'title'   => 'Bank Transfer Canceled For Customer Subject',
                'default' => 'Cancel bank transfer payment to customer'
            ],
            [
                'id'      => 'email_bank_transfer_canceled_customer',
                'type'    => 'textarea',
                'title'   => 'Bank Transfer Canceled For Customer Content',
                'default' => 'Hi %customerName%. Your subscription is canceled. Payment ID: %paymentID%. Next Billing Date: %nextBillingDate%.',
                'description' => '%paymentID% is paymentID, %nextBillingDate% is the time of next billing date'
            ],
            [
                'id'      => 'email_payment_dispute_subject',
                'type'    => 'textarea',
                'title'   => 'Payment Dispute',
                'default' => 'Payment Dispute'
            ],
            [
                'id'      => 'email_payment_dispute',
                'type'    => 'textarea',
                'title'   => 'Payment Dispute',
                'default' => 'Dear %customerName%. We regret to inform you that your account has been locked because there was a dispute in the following Payment Session: %paymentID%. To resolve this issue, please contact us at %adminEmail%'
            ],
            [
                'id'      => 'email_promotion_submitted_subject',
                'type'    => 'text',
                'title'   => 'Promotion Created Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_promotion_submitted',
                'type'    => 'textarea',
                'title'   => 'Promotion Created',
                'default' => 'Hi %customerName%. We got your request about promote %postTitle% on %brand%. Our staff is reviewing it, We will contact you shortly.'
            ],
            [
                'id'      => 'email_promotion_approved_subject',
                'type'    => 'text',
                'title'   => 'Promotion Approved Subject',
                'default' => ''
            ],
            [
                'id'      => 'email_promotion_approved',
                'type'    => 'textarea',
                'title'   => 'Promotion Approved',
                'default' => 'Congratulations. Your promotion campaign:%promotionTitle% on %brand% has been approved.'
            ],
            [
                'id'      => 'email_promotion_position_expired_subject',
                'type'    => 'text',
                'title'   => 'Promotion Position Expired Subject',
                'default' => 'Your Promotion Plan %promotionPosition% of %promotionTitle% has been expired'
            ],
            [
                'id'      => 'email_promotion_position_expired',
                'type'    => 'textarea',
                'title'   => 'Promotion Position Expired Content',
                'default' => 'Your Listing %postTitle% promotion on %promotionPosition% has been expired.'
            ],
            [
                'id'      => 'email_promotion_expired_subject',
                'type'    => 'text',
                'title'   => 'Promotion Expired Subject',
                'default' => 'Your Promotion %promotionTitle% has been expired'
            ],
            [
                'id'      => 'email_promotion_expired',
                'type'    => 'textarea',
                'title'   => 'Promotion Expired Content',
                'default' => 'Your promotion %promotionTitle% for %postTitle% has been expired. Thanks for using our service!'
            ],
            [
                'id'      => 'email_when_reply_to_customer',
                'type'    => 'textarea',
                'title'   => 'After You reply a message to customer',
                'default' => '[%brand%] replied on your inbox.'
            ],
            [
                'id'      => 'email_send_invoice_subject',
                'type'    => 'text',
                'title'   => 'Invoice Subject',
                'default' => '%brand% Sales'
            ],
            [
                'id'      => 'email_send_invoice',
                'type'    => 'textarea',
                'title'   => 'Invoice Email',
                'default' => ''
            ],
            [
                'id'      => 'email_qr_code',
                'type'    => 'textarea',
                'title'   => 'QRcode Email',
                'default' => '%h2%Check for this Event%close_h2%%breakDown%Please show us QRCode below when visiting the Event.'
            ],
            [
                'id'          => 'email_password_title',
                'type'        => 'textarea',
                'title'       => 'Your Password Subject',
                'description' => 'If you are using App, We should send Customer Password after they are created an account on your site with Facebook',
                'default'     => ''
            ],
            [
                'id'      => 'email_password_content',
                'type'    => 'textarea',
                'title'   => 'Your Password Content',
                'default' => 'Your username is %userName%, your password is %userPassword%'
            ],
        ]
    ]
);
