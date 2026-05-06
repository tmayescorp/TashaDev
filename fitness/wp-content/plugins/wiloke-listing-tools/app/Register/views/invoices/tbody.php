<?php

use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Models\PaymentModel;

?>
<tbody>
<?php if (empty($this->aInvoices)) : ?>
    <tr>
        <td colspan="11" class="text-center"><strong><?php esc_html_e('There are no invoices yet.',
                    'wiloke-listing-tools'); ?></strong></td>
    </tr>
<?php else: ?>
    <?php
    foreach ($this->aInvoices as $aInfo) :
        $aPaymentInfo = PaymentModel::getPaymentInfo($aInfo['paymentID']);
        if ($aInfo['gateway'] == 'woocommerce') {
            $target = '_blank';
            $editLink = add_query_arg(
                array(
                    'post'   => $aPaymentInfo['wooOrderID'],
                    'action' => 'edit'
                ),
                admin_url('post.php')
            );
        } else {
            $target = '_self';
            $editLink = add_query_arg(
                array(
                    'page'      => $this->detailSlug,
                    'paymentID' => $aPaymentInfo['ID']
                ),
                admin_url('admin.php')
            );
        }

        $fullName = get_user_meta($aInfo['userID'], 'first_name', true) . ' ' . get_user_meta($aInfo['userID'], 'last_name', true);
        $fullName = trim($fullName);
        if (empty($fullName)) {
            $fullName = get_user_meta($aInfo['userID'], 'nickname', true);
        }
        ?>
        <tr class="item">
            <td class="invoices-checkbox invoice-small check-column manage-column">
                <input class="wiloke_checkbox_item"
                       type="checkbox" value="<?php echo esc_attr($aInfo['ID']); ?>"
                       name="delete[]">
            </td>
            <td class="invoices-id invoice-small check-column manage-column">
                <a href="#"><?php echo esc_html($aInfo['ID']); ?></a>
            </td>
            <td class="invoices-customer invoice-medium manage-column column-primary"
                data-colname="<?php esc_html_e('Customer', 'wiloke-listing-tools'); ?>">
                <a title="<?php esc_html_e('View customer information', 'wiloke-listing-tools'); ?>"
                   href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $aInfo['userID'])); ?>">
                    <?php echo esc_html($fullName); ?>
                </a>
            </td>
            <td class="invoices-payment-id invoice-small check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>"
                   title="<?php esc_html_e('View Session Details', 'wiloke-listing-tools'); ?>">
                    <?php echo esc_html($aInfo['paymentID']); ?>
                </a>
            </td>
            <td class="invoices-plan-id invoice-medium check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>"
                   target="<?php echo esc_attr($target); ?>">
                    <?php echo !empty($aInfo['planID']) ? get_the_title($aInfo['planID']) :
                        PaymentMetaModel::get($aInfo['paymentID'], 'planName'); ?></a>
            </td>
            <td class="invoices-gateway invoice-medium check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>"><?php echo esc_html($aInfo['gateway']); ?></a>
            </td>
            <td class="invoices-sub-total invoice-small check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>">
                    <?php echo esc_html(GetWilokeSubmission::renderPrice($aInfo['subTotal'],
                        $aInfo['currency'])); ?></a>
            </td>
            <td class="invoices-currency invoice-small check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>">
                    <?php echo esc_html(GetWilokeSubmission::renderPrice($aInfo['discount'],
                        $aInfo['currency'])); ?></a>
            </td>
            <td class="invoices-tax invoice-small check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>">
                    <?php echo esc_html(GetWilokeSubmission::renderPrice($aInfo['tax'], $aInfo['currency'])); ?></a>
            </td>
            <td class="invoices-total invoice-small check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>">
                    <?php echo esc_html(GetWilokeSubmission::renderPrice($aInfo['total'], $aInfo['currency'])); ?></a>
            </td>
            <td class="invoices-created_at invoice-large check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>"><?php echo !empty($aInfo['updated_at']) ? esc_html
                    ($aInfo['updated_at']) : esc_html($aInfo['created_at']); ?></a>

                <a class="button ui basic green" style="margin-left:20px" href="<?php echo add_query_arg(['page'   =>
                                                                                               $this->detailSlug, 'invoiceID' => $aInfo['ID'],
                                                   'action' => 'admin_download_invoice'],
                    admin_url('admin.php')); ?>">Download Invoice</a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
