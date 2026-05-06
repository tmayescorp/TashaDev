<?php
/*
 * Template Name: Wilcity Thank You
 */

use \WilokeListingTools\Framework\Store\Session;
use \WilokeListingTools\Models\PaymentMetaModel;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use \WilokeListingTools\Framework\Helpers\HTML;

get_header();
global $wiloke;
?>
    <div class="wil-content">
        <section class="wil-section bg-color-gray-2 pt-30">
            <div class="container">
                <div id="wilcity-thankyout" class="row">
                    <?php do_action('wilcity/wiloke-submission/thankyou/before_content'); ?>
                    <?php
                    if (isset($_REQUEST['gateway']) && $_REQUEST['gateway'] == 'banktransfer') {
                        ?>
                        <h3><?php esc_html_e('Thank for your order!', 'wilcity'); ?></h3>
                        <p><?php esc_html_e('Please send your cheque to the following bank account to complete this payment:',
                                'wilcity'); ?></p>
                        <div id="wilcity-our-bank-account">
                            <?php echo do_shortcode('[wilcity_my_bank_accounts]'); ?>
                        </div>

                        <?php if (isset($_GET['paymentID'])) : ?>
                            <h3><?php esc_html_e('Your Payment Info', 'wilcity'); ?></h3>
                            <?php
                            $aPaymentInfo  = PaymentMetaModel::getPaymentInfo($_GET['paymentID']);
                            $aColumnTitles = [
                                'planName'   => [
                                    'name'  => esc_html__('Plan Name', 'wilcity'),
                                    'class' => 'column-name'
                                ],
                                'subTotal' => [
                                    'name'  => esc_html__('Sub Total', 'wilcity'),
                                    'class' => 'column-subtotal'
                                ],
                                'discount' => [
                                    'name'  => esc_html__('Discount', 'wilcity'),
                                    'class' => 'column-discount'
                                ],
                                'tax'      => [
                                    'name'  => esc_html__('Tax', 'wilcity'),
                                    'class' => 'column-tax'
                                ],
                                'total'    => [
                                    'name'  => esc_html__('Total', 'wilcity'),
                                    'class' => 'column-total'
                                ]
                            ];

                            if (!empty($aPaymentInfo)) {
                                foreach ($aColumnTitles as $column => $aInfo) {
                                    if (isset($aPaymentInfo[$column])) {
                                        $aColumnValues[$column] = $aPaymentInfo[$column];
                                        if (in_array($column, ['total', 'subTotal', 'tax', 'discount'])) {
                                            $aColumnValues[$column] = GetWilokeSubmission::renderPrice
                                            ($aColumnValues[$column]);
                                        }
                                    } else {
                                        $aColumnValues[$column] = 'x';
                                    }
                                }
                                HTML::renderTable($aColumnTitles, $aColumnValues);
                            }
                        endif;
                    } else {
                        if (have_posts()) {
                            while (have_posts()) {
                                the_post();
                                if ($message = Session::getSession('errorPayment', true)) {
                                    Wiloke::ksesHTML($message);
                                } else {
                                    the_content();
                                }
                            }
                        }
                    }
                    ?>
                    <?php do_action('wilcity/wiloke-submission/thankyou/after_content'); ?>
                </div>
            </div>
        </section>
    </div>
<?php
do_action('wilcity/before-close-root');
get_footer();
