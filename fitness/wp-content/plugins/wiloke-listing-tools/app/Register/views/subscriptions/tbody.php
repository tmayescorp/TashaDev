<?php

use WilokeListingTools\Framework\Helpers\Time;
use WilokeListingTools\Models\PaymentMetaModel;

?>
<tbody>
<?php if (empty($this->aSales)) : ?>
    <tr>
        <td colspan="8" class="text-center"><strong><?php esc_html_e('There are no subscriptions yet.', 'wiloke');
                ?></strong></td>
    </tr>
<?php else: ?>
    <?php
    foreach ($this->aSales as $aInfo) :
        $editLink = admin_url('admin.php') . '?page=' . $this->detailSlug . '&paymentID=' . $aInfo['ID'];
        $nextBillingDate = PaymentMetaModel::getNextBillingDateGMT($aInfo['ID']);
        $fullName = get_user_meta($aInfo['userID'], 'first_name', true) . ' ' . get_user_meta($aInfo['userID'], 'last_name', true);
        $fullName = trim($fullName);
        if (empty($fullName)) {
            $fullName = get_user_meta($aInfo['userID'], 'nickname', true);
        }
        ?>
        <tr class="item">
            <td class="invoices-id check-column manage-column">
                <input class="wiloke_checkbox_item" type="checkbox" value="<?php echo esc_attr($aInfo['ID']); ?>"
                       name="delete[]">
            </td>
            <td class="invoices-id check-column manage-column">
                <a href="<?php echo esc_url($editLink); ?>"
                   title="<?php esc_html_e('View Invoice Detail', 'wiloke-listing-tools'); ?>">
                    <?php echo esc_html($aInfo['ID']); ?>
                </a>
            </td>
            <td class="invoices-customer manage-column column-primary"
                data-colname="<?php esc_html_e('Customer', 'wiloke-listing-tools'); ?>"><a
                    title="<?php esc_html_e('View customer information', 'wiloke'); ?>"
                    href="<?php echo esc_url(admin_url('user-edit.php?user_id=' .
                        $aInfo['userID'])); ?>"><?php echo esc_html($fullName); ?></a></td>
            <td class="invoices-package manage-column"
                data-colname="<?php esc_html_e('Plan Type', 'wiloke-listing-tools'); ?>"><a
                        href="<?php echo esc_url($editLink); ?>"><?php echo apply_filters('wilcity/filter/register-sale-detail-submenu/session-detail-area',get_post_field('post_type',
				        $aInfo['planID']),$aInfo); ?></a></td>
            <td class="invoices-package manage-column"
                data-colname="<?php esc_html_e('Plan Name', 'wiloke-listing-tools'); ?>"><a
                    href="<?php echo esc_url($editLink); ?>"><?php echo get_the_title($aInfo['planID']); ?></a></td>
            <td class="invoices-date manage-column"
                data-colname="<?php esc_html_e('Status', 'wiloke-listing-tools'); ?>"><a
                    href="<?php echo esc_url($editLink); ?>"><?php echo esc_html($aInfo['status']); ?></a></td>
            <td class="invoices-date manage-column"
                data-colname="<?php esc_html_e('Status', 'wiloke-listing-tools'); ?>"><a
                    href="<?php echo esc_url($editLink); ?>"><?php echo esc_html($aInfo['gateway']); ?></a></td>
            <td class="invoices-date manage-column" data-colname="<?php esc_html_e('Date', 'wiloke-listing-tools');
            ?>"><a href="<?php echo esc_url($editLink); ?>"><?php echo esc_html($aInfo['updatedAt']); ?></a></td>
            <td class="invoices-date manage-column"
                data-colname="<?php esc_html_e('Next Billing Date', 'wiloke-listing-tools'); ?>"><a
                    href="<?php echo esc_url($editLink); ?>"><?php echo Time::toDateFormat($nextBillingDate) . ' ' .
                        Time::toTimeFormat($nextBillingDate); ?></a></td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
</tbody>
