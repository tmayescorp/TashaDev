<?php
return apply_filters('wilcity/filter/wiloke-listing-tools/configs/push-notifications', [
	'admin'     => [
		'someoneSubmittedAListingToYourSite' => [
			'title'  => esc_html__('Author submitted a new listing Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified after author submitted a new listing to your site', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('%userName% just submitted a new listing to your site. Listing Type: %postType%, Listing Name: %postTitle%, Listing ID: %postID%, Submitted Date: %postDate%', 'wiloke-listing-tools'),
		],
		'someoneSubmittedAProductYourSite'   => [
			'title'  => esc_html__('Author submitted a new product Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified after author submitted a new product to your site', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('%userName% just submitted a new product to your site. Product Name: %postTitle%, Product ID: %postID%, Submitted Date: %postDate%', 'wiloke-listing-tools'),
		],
	],
	'customers' => [
		'toggleAll'                   => [
			'title' => esc_html__('Enable Notifications', 'wiloke-listing-tools'),
			'desc'  => esc_html__('Select disable to turn off all notifications', 'wiloke-listing-tools'),
			'msg'   => ''
		],
		'followerPublishedNewListing' => [
			'title'  => esc_html__('Author Posted New Listing Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when author who you are following posts a new listing', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('%userName% just published a new post %postTitle%. %postExcerpt%', 'wiloke-listing-tools'),
		],
		'listingStatus'               => [
			'title'  => esc_html__('Listing Status Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when your listing status is changed. Eg: Your listing has been approved.', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('Your listing %postTitle% has been changed from %beforeStatus% to %afterStatus%', 'wiloke-listing-tools'),
		],
		'privateMessages'             => [
			'title'  => esc_html__('Private Message Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when you receive a private messages', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('%senderName%: %message%', 'wiloke-listing-tools'),
		],
		'eventComment'                => [
			'title'  => esc_html__('Event Comment Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when someone leaves a comment on your event', 'wiloke-listing-tools'),
			'msg'    => esc_html__('%userName% just left a comment on %postTitle%: %commentExcerpt%', 'wiloke-listing-tools'),
			'status' => 'on'
		],
		'review'                      => [
			'title'  => esc_html__('Review Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when someone leaves a review on your listing', 'wiloke-listing-tools'),
			'msg'    => esc_html__('Rating %averageRating% %userName% just left a review on %postTitle%: %reviewExcerpt%', 'wiloke-listing-tools'),
			'status' => 'on'
		],
		'reviewDiscussion'            => [
			'title'       => esc_html__('Review Discussion Notifications', 'wiloke-listing-tools'),
			'settingDesc' => esc_html__('You can use %averageRating% as a placeholder in the notification message', 'wiloke-listing-tools'),
			'desc'        => esc_html__('Get notified when someone leaves a discussion on your review', 'wiloke-listing-tools'),
			'msg'         => esc_html__('%userName% just left a comment on %postTitle%: %reviewExcerpt%', 'wiloke-listing-tools'),
			'status'      => 'on'
		],
		'newFollowers'                => [
			'title'  => esc_html__('New Followers Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when someone new starts following you', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('%userName% is following you now', 'wiloke-listing-tools'),
		],
		'claimApproved'               => [
			'title'  => esc_html__('Claim Approved Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified after your claim is approved', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('Congratulations! %postTitle% claim has been approved', 'wiloke-listing-tools'),
		],
		'productPublished'            => [
			'title'  => esc_html__('Product Published Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified after your product is published', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('Congratulations! %postTitle% is ready for selling', 'wiloke-listing-tools'),
		],
		'productReview'               => [
			'title'  => esc_html__('Product Review Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when someone reviews your product', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('Rating %rating% Comment: %reviewExcerpt%', 'wiloke-listing-tools'),
		],
		'soldProduct'                 => [
			'title'  => esc_html__('Sale Notifications', 'wiloke-listing-tools'),
			'desc'   => esc_html__('Get notified when someone purchases your product', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('Congratulations! You made a sale from %postTitle%. Order ID: %orderID%', 'wiloke-listing-tools'),
		],
		'paymentDispute'              => [
			'title'  => esc_html__('Payment Dispute', 'wiloke-listing-tools'),
			'desc'   => esc_html__('There is a dispute issue in a payment session', 'wiloke-listing-tools'),
			'status' => 'on',
			'msg'    => esc_html__('There was a dispute with %paymentID% Payment ID. Please contact %adminEmail% to resolve this issue', 'wiloke-listing-tools'),
		]
	]
]);
