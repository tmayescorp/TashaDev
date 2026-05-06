<?php

namespace WilokeListingTools\Register;

use WilokeListingTools\AlterTable\AlterTablePaymentHistory;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\Message;
use WilokeListingTools\Framework\Helpers\SemanticUi;
use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;
use WilokeListingTools\Models\PlanRelationshipModel;

class RegisterSaleDetailSubMenu
{
	use WilokeSubmissionConfiguration;
	public $slug;
	public $paymentID;
	public $aPaymentDetails;

	public function __construct()
	{
		$this->slug = $this->detailSlug;
		add_action('admin_menu', [$this, 'register'], 20);
	}

	public function fetchPaymentDetails()
	{
		global $wpdb;
		$tblName = $wpdb->prefix . AlterTablePaymentHistory::$tblName;

		$this->aPaymentDetails = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $tblName WHERE ID=%d",
				$this->paymentID
			),
			ARRAY_A
		);
	}

	public function register()
	{
		add_submenu_page($this->parentSlug, esc_html__('Session Details', 'wiloke'),
			esc_html__('Session Details', 'wiloke'), 'administrator', $this->slug, [$this, 'sessionDetailArea']);
	}

	public function sessionDetailArea()
	{
		$this->paymentID = isset($_REQUEST['paymentID']) ? $_REQUEST['paymentID'] : '';
		if (empty($this->paymentID)) {
			\WilokeMessage::message(
				[
					'msg'    => esc_html__('Please select a Session first', 'wiloke-listing-tools'),
					'status' => 'error'
				]
			);

			return false;
		}
		$this->fetchPaymentDetails();
		?>
        <div id="wiloke-submission-wrapper" class="wrap">
			<?php if (empty($this->aPaymentDetails)) : ?>
                <p><?php esc_html_e('This order does not exist', 'wiloke'); ?></p>
			<?php
			else:
				$userID = $this->aPaymentDetails['userID'];
				$oUser = get_userdata($userID);
				?>
                <form id="wiloke-submission-change-customer-order" class="form ui" action="" method="POST">
                    <h2 class="ui dividing header"><?php \Wiloke::ksesHTML(sprintf(__('Order #%d details.', 'wiloke'),
							$this->aPaymentDetails['ID']), false); ?></h2>
					<?php
					SemanticUi::renderDescField(
						[
							'desc_id' => 'wiloke-submission-message-after-update',
							'desc'    => esc_html__('Your update has been successfully', 'wiloke'),
							'status'  => 'ui positive hidden'
						]
					);

					SemanticUi::renderHiddenField(
						[
							'name'  => 'paymentID',
							'value' => $this->aPaymentDetails['ID']
						]
					);
					SemanticUi::renderHiddenField(
						[
							'name'  => 'planID',
							'value' => $this->aPaymentDetails['planID']
						]
					);

					?>
                    <h3 class="ui dividing header"><?php esc_html_e('Order Information', 'wiloke'); ?></h3>
					<?php

					do_action('wiloke-submission/app/session-detail/after_order_information_open',
						$this->aPaymentDetails);

					echo '<div class="two fields">';
					SemanticUi::renderTextField(
						[
							'id'          => 'planName',
							'name'        => 'planName',
							'post_type'   => get_post_type($this->aPaymentDetails['planID']),
							'heading'     => esc_html__('Plan Name', 'wiloke'),
							'desc'        => !empty($this->aPaymentDetails['planID']) ? sprintf(__('<a href="%s" target="_blank">Click to view plan detail</a>',
								'wiloke'),
								admin_url('post.php?post=' . $this->aPaymentDetails['planID'] . '&action=edit')) : '',
							'desc_status' => 'info',
							'value' => apply_filters('wilcity/filter/register-sale-detail-submenu/session-detail-area',
								empty($this->aPaymentDetails['planID']) ? PaymentMetaModel::get($this->aPaymentDetails['ID'],
									'planName') : get_post_type($this->aPaymentDetails['planID']),$this->aPaymentDetails),
							'is_readonly' => true
						]
					);
					SemanticUi::renderTextField(
						[
							'id'          => 'gateway',
							'name'        => 'gateway',
							'heading'     => esc_html__('Gateway', 'wiloke'),
							'value'       => $this->aPaymentDetails['gateway'],
							'is_readonly' => true
						]
					);
					echo '</div>';

					echo '<div class="field">';
					SemanticUi::renderTextField(
						[
							'id'          => 'billingType',
							'name'        => 'billingType',
							'heading'     => esc_html__('Billing Type', 'wiloke'),
							'value'       => $this->aPaymentDetails['billingType'],
							'is_readonly' => true
						]
					);
					echo '</div>';

					echo '<div class="two fields">';
					SemanticUi::renderHiddenField(
						[
							'name'  => 'userID',
							'value' => $this->aPaymentDetails['userID']
						]
					);

					SemanticUi::renderTextField(
						[
							'id'          => 'customer_name',
							'name'        => 'customer_name',
							'heading'     => esc_html__('Customer', 'wiloke'),
							'value'       => $oUser->display_name,
							'is_readonly' => true,
							'desc_status' => 'info',
							'desc'        => \Wiloke::ksesHTML(sprintf(__('<a href="%s">Check Customer Information</a>',
								'wiloke'), esc_url(admin_url('user-edit.php?user_id=' . $userID))), true)
						]
					);

					SemanticUi::renderTextField(
						[
							'id'          => 'customer_email',
							'name'        => 'customer_email',
							'heading'     => esc_html__('Customer Email', 'wiloke'),
							'value'       => $oUser->user_email,
							'is_readonly' => true,
							'desc_status' => 'info',
							'desc'        => \Wiloke::ksesHTML(sprintf(__('<a href="mailto:%s">Mail to customer</a>',
								'wiloke'), $oUser->user_email), true)
						]
					);
					echo '</div>';

					echo '<div class="fields two">';
					SemanticUi::renderTextField(
						[
							'id'          => 'order_date',
							'name'        => 'order_date',
							'heading'     => esc_html__('Order date', 'wiloke'),
							'value'       => $this->aPaymentDetails['createdAt'],
							'is_readonly' => true
						]
					);

					SemanticUi::renderTextField(
						[
							'id'          => 'order_status',
							'name'        => 'order_status',
							'heading'     => $this->aPaymentDetails['billingType'] == wilokeListingToolsRepository()
								->get('payment:billingTypes', true)
								->sub('nonrecurring') ? esc_html__('Order Status',
								'wiloke') : esc_html__('Subscription Status', 'wiloke'),
							'value'       => $this->aPaymentDetails['status'],
							'is_readonly' => true
						]
					);
					echo '</div>';

					do_action('wiloke-submission/app/session-detail/before_order_information_close',
						$this->aPaymentDetails);

					if (in_array($this->aPaymentDetails['gateway'], ['banktransfer', 'free'])) :
						if (!GetWilokeSubmission::isNonRecurringPayment($this->aPaymentDetails['billingType']) &&
							$this->aPaymentDetails['status'] == 'active') :
							?>
                            <h3 class="ui dividing header"><?php esc_html_e('Next Billing Date', 'wiloke'); ?></h3>
							<?php
							echo '<div class="fields two">';
							$nextBillingDateGMT = PaymentMetaModel::getNextBillingDateGMT($this->aPaymentDetails['ID']);
							SemanticUi::renderTextField(
								[
									'id'          => 'next_billing_date',
									'name'        => 'next_billing_date',
									'heading'     => esc_html__('Ended at', 'wiloke'),
									'value'       => empty($nextBillingDateGMT) ? esc_html__('Now',
										'wiloke') : Time::toAtom($nextBillingDateGMT),
									'is_readonly' => true
								]
							);

							SemanticUi::renderButton(
								[
									'heading' => esc_html__('Adding Next Billing date', 'wiloke'),
									'id'      => 'wiloke-charge-the-next-billing-date',
									'name'    => esc_html__('Create new Invoice', 'wiloke-listing-tools'),
									'class'   => 'green'
								]
							);
							echo '</div>';
						endif;
						?>
                        <h3 class="ui dividing header"><?php esc_html_e('Change Sale / Subscription Status',
								'wiloke-listing-tools'); ?></h3>
						<?php
						echo '<div class="fields two">';
						SemanticUi::renderSelectField(
							[
								'id'        => 'change_to_new_order_status',
								'name'      => 'change_to_new_order_status',
								'post_type' => get_post_type($this->aPaymentDetails['planID']),
								'heading'   => esc_html__('New Status', 'wiloke-listing-tools'),
								'value'     => '',
								'options'   => GetWilokeSubmission::isNonRecurringPayment($this->aPaymentDetails['billingType']) ? wilokeListingToolsRepository()->get('sales:status') : wilokeListingToolsRepository()->get('subscriptions:status')
							]
						);
						SemanticUi::renderButton(
							[
								'heading' => esc_html__('Change Sale / Subscription Status', 'wiloke-listing-tools'),
								'id'      => GetWilokeSubmission::isNonRecurringPayment($this->aPaymentDetails['billingType']) ? 'wiloke-submission-change-banktransfer-nonrecurring-payment-status' : 'wiloke-submission-change-banktransfer-recurring-payment-status',
								'name'    => esc_html__('Execute', 'wiloke-listing-tools'),
								'class'   => 'green'
							]
						);
						echo '</div>';
					else:
						switch ($this->aPaymentDetails['status']) {
							case 'active':
							case 'succeeded':
								if (GetWilokeSubmission::isNonRecurringPayment($this->aPaymentDetails['billingType'])) {
									SemanticUi::renderButton(
										[
											'heading' => esc_html__('Refund', 'wiloke-listing-tools'),
											'id'      => 'wiloke-submission-refund-sale',
											'name'    => esc_html__('Execute', 'wiloke-listing-tools'),
											'class'   => 'red'
										]
									);
								} else {
									if ($this->aPaymentDetails['gateway'] != 'woocommerce') {
										SemanticUi::renderButton(
											[
												'heading' => esc_html__('Cancel Subscription', 'wiloke-listing-tools'),
												'id'      => 'wiloke-submission-cancel-subscription',
												'name'    => esc_html__('Execute', 'wiloke-listing-tools'),
												'class'   => 'red'
											]
										);
									} else {
										SemanticUi::renderLink(
											[
												'heading' => '',
												'id'      => '',
												'name'    => 'Order Details',
												'class'   => 'green',
												'href'    => admin_url('post.php?action=edit&post=' .
													$this->aPaymentDetails['wooOrderID']),
												'target'  => '_blank'
											]
										);
									}
								}
								break;
							case 'dispute':
								$oDisputeInfo = PaymentMetaModel::getDisputeInfo($this->aPaymentDetails['ID']);
								if ($this->aPaymentDetails['gateway'] == 'stripe') {
									SemanticUi::renderTextField(
										[
											'id'          => 'stripe_customer_email',
											'name'        => 'stripe_customer_email',
											'heading'     => esc_html__('Stripe Customer Email', 'wiloke'),
											'value'       => $oDisputeInfo->data->object->billing_details->email,
											'is_readonly' => true,
											'desc_status' => 'red',
											'desc'        => 'This payment has been proceeded through this email'
										]
									);

									SemanticUi::renderTextField(
										[
											'id'          => 'stripe_dispute_id',
											'name'        => 'stripe_dispute_id',
											'heading'     => esc_html__('Stripe Dispute ID', 'wiloke'),
											'value'       => $oDisputeInfo->id,
											'is_readonly' => true,
											'desc_status' => 'red',
											'desc'        => 'To know more about Stripe Dispute, please read <a href="https://stripe.com/docs/disputes/categories" target="_blank">Stripe Fraudulent</a>'
										]
									);
								}
								break;
						}

					endif;
					## End / Change Order Status
					?>
                </form>
			<?php endif; ?>
        </div>
        <!-- Display listing-->
        <h3 class="ui dividing header"><?php esc_html_e('Listing Detail', 'wiloke'); ?></h3>
		<?php
		$total = PlanRelationshipModel::getObjectIDTotal($this->paymentID);
		$paged = isset($_REQUEST['paged']) && !empty($_REQUEST['paged']) ? absint($_REQUEST['paged']) : 1;
		$postPerPages = isset($_REQUEST['posts_per_page']) &&
		!empty($_REQUEST['posts_per_page']) ? $_REQUEST['posts_per_page'] : 10;
		$pagination = absint(ceil($total / $postPerPages));
		$aListingID = PlanRelationshipModel::getObjectIDs($this->paymentID, $paged, $postPerPages);

		?>
        <table class="ui celled table">
            <thead>
            <tr>
                <th><?php echo esc_html__('ID', 'wiloke') ?></th>
                <th><?php echo esc_html__('Listing', 'wiloke'); ?></th>
                <th><?php echo esc_html__('Expired At', 'wiloke') ?></th>
                <th><?php echo esc_html__('Status', 'wiloke') ?></th>
            </tr>
            </thead>
            <tbody>
			<?php
			if (!empty($aListingID)) :
				foreach ($aListingID as $item) {
					$listingID = intval($item['ID']);
					?>
                    <tr>
                        <td data-label="ID"><?php echo intval($item['ID']); ?></td>
                        <td data-label="Title">
                            <a href="<?php echo get_edit_post_link($listingID); ?>">
								<?php echo get_the_title($listingID); ?>
                            </a>
                        </td>
                        <td data-label="Expired At">
							<?php
							if ($this->aPaymentDetails['billingType'] == 'RecurringPayment') {
								echo Time::toAtom($nextBillingDateGMT);
							} else {
								echo esc_html__('Forever', 'wiloke');
							}
							?>
                        </td>
                        <td data-label="Status"><?php echo get_post_status($listingID); ?></td>
                    </tr>
				<?php }
			endif; ?>
            </tbody>
        </table>
		<?php if (!empty($pagination)): ?>
        <tr>
            <th colspan="11">
                <div class="ui pagination menu" style="padding-top: 0 !important;">
					<?php for ($i = 1; $i <= $pagination; $i++) :
						$activated = $paged == $i ? 'active' : '';
						?>
                        <a class="<?php echo esc_attr($activated); ?> item"
                           href="<?php echo esc_url(add_query_arg
						   (
							   [
								   'paged'        => $i,
								   'postsPerPage' => $postPerPages,
								   'page'         => 'detail',
								   'paymentID'    => $this->paymentID],
							   admin_url('admin.php')));
						   ?>"><?php echo
							esc_html($i); ?></a>
					<?php endfor; ?>
                </div>
            </th>
        </tr>
	<?php
	endif;
	}
}
