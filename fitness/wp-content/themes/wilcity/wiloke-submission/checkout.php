<?php
/*
 * Template Name: Wilcity Checkout
 */

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Framework\Helpers\HTML;
use WilokeListingTools\Framework\Helpers\Message;

get_header();
global $wiloke;
$planID = \WilokeListingTools\Framework\Store\Session::getPaymentPlanID();
?>
    <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix', 'wilcity-root')); ?>" class="wil-content">
        <section class="wil-section bg-color-gray-2" style="padding-top: 20px;">
            <div class="container">
                <?php while (have_posts()) : the_post(); ?>
                    <?php if (!GetWilokeSubmission::isGatewaySupported('banktransfer')): ?>
                        <div class="heading_module__156eJ wil-text-left" style="margin-bottom: 10px; margin-top: 10px;">
                            <h1 class="heading_mask__pcO5T" style="font-size: 50px"><?php the_title(); ?></h1>
                            <div class="heading_content__2mtYE"><?php the_content(); ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (GetWilokeSubmission::getField('mode') == 'sandbox') : ?>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <?php
                                WilokeMessage::message(
                                    [
                                        'status'     => 'danger',
                                        'msgIcon'    => 'la la-warning',
                                        'hasMsgIcon' => true,
                                        'msg'        => __('Warning: In order to use Wiloke Submission, You have to setup <strong>WebHook</strong> for each Payment Gateway. You can find Setting
up WebHook tutorial by going to the <a style="color: red" href="https://documentation.wilcity.com/" target="_blank">documentation</a> -> Search for WebHook. This message will not show up after switching to Live mode',
                                            'wilcity')
                                    ]
                                );
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div id="wilcity-print-msg" class="hidden row">
                        <div class="col-md-12 col-lg-12">
                            <?php
                            WilokeMessage::message(
                                [
                                    'status'     => 'danger',
                                    'msgIcon'    => 'la la-frown-o',
                                    'hasMsgIcon' => true,
                                    'msg'        => ''
                                ]
                            );
                            ?>
                        </div>
                    </div>

                    <?php do_action('wilcity/checkout/before-package-detail'); ?>

                    <?php if (!empty($planID)) : ?>
                        <div id="wilcity-our-bank-account" class="mt-20">
                            <div class="promo-item_module__24ZhT mb-15">
                                <div class="promo-item_group__2ZJhC">
                                    <h3 class="promo-item_title__3hfHG"><?php esc_html_e('Your Package Details',
                                            'wilcity'); ?></h3>
                                </div>
                            </div>
                        </div>

                        <div class="table-module">
                            <?php
                            $aColumnTitles = [
                                'name'     => [
                                    'name'  => esc_html__('Plan Name', 'wilcity'),
                                    'class' => 'column-name'
                                ],
                                'discount' => [
                                    'name'  => esc_html__('Discount', 'wilcity'),
                                    'class' => 'column-discount'
                                ]
                            ];

                            if (!GetWilokeSubmission::isNonRecurringPayment() &&
                                !empty($aPlanSettings['trial_period'])
                            ) {
                                $aColumnTitles['trial_period'] = [
                                    'name'  => esc_html__('Trial Period (Days)', 'wilcity'),
                                    'class' => 'column-trial-period-days'
                                ];
                            }

                            if (GetWilokeSubmission::isTax()) {
                                $aColumnTitles['tax'] = [
                                    'name'  => GetWilokeSubmission::getField('tax_title'),
                                    'class' => 'column-tax'
                                ];
                            }

                            $aColumnTitles = array_merge($aColumnTitles, [
                                'subTotal' => [
                                    'name'  => esc_html__('Sub Total', 'wilcity'),
                                    'class' => 'column-sub-total'
                                ],
                                'total'    => [
                                    'name'  => esc_html__('Total', 'wilcity'),
                                    'class' => 'column-total'
                                ]
                            ]);

                            $aPlanSettings = GetSettings::getPlanSettings($planID);
                            foreach ($aColumnTitles as $column => $aData) {
                                switch ($column) {
                                    case 'name':
                                        $aColumnValues[$column] = get_the_title($planID);
                                        break;
                                    case 'discount':
                                        $aColumnValues[$column] = GetWilokeSubmission::renderPrice(0);
                                        break;
                                    case 'total':
                                        $aColumnValues[$column] =
                                            GetWilokeSubmission::renderPrice(GetWilokeSubmission::calculateTotal(
                                                $aPlanSettings['regular_price']
                                            ));
                                        break;
                                    case 'subTotal':
                                        $aColumnValues[$column] = GetWilokeSubmission::renderPrice(
                                            $aPlanSettings['regular_price']
                                        );
                                        break;
                                    case 'trial_period':
                                        $aColumnValues[$column] = $aPlanSettings['trial_period'];
                                        break;
                                    case 'tax':
                                        $aColumnValues[$column] = GetWilokeSubmission::renderPrice(
                                            GetWilokeSubmission::calculateTax($aPlanSettings['regular_price'])
                                        );
                                        break;
                                }
                            }
                            HTML::renderTable($aColumnTitles, $aColumnValues);
                            ?>
                        </div>
                        <?php if (GetWilokeSubmission::isNonRecurringPayment()) : ?>
                            <div class="row">
                                <div class="col-md-8 col-lg-6">
                                    <div class="row" data-col-xs-gap="10">
                                        <div class="col-md-8 col-lg-8">
                                            <div class="field_module__1H6kT js-field">
                                                <div class="field_wrap__Gv92k">
                                                    <input id="wilcity-coupon-code" class="field_field__3U_Rt"
                                                           type="text">
                                                    <span
                                                        class="field_label__2eCP7 text-ellipsis"><?php esc_html_e('Coupon',
                                                            'wilcity'); ?></span>
                                                    <span class="bg-color-primary"></span>
                                                </div>
                                            </div>
                                            <div id="wilcity-coupon-msg"></div>
                                        </div>
                                        <div class="col-md-4 col-lg-4">
                                            <a id="wilcity-submit-coupon"
                                               class="wil-btn wil-btn--primary2 wil-btn--md wil-btn--block wil-btn--round"
                                               href="#"><?php esc_html_e('Apply Coupon', 'wilcity'); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <h5><?php esc_html_e('Proceed to checkout', 'wilcity'); ?></h5>
                        <div class="row" data-col-xs-gap="20">
                            <input type="hidden" id="wilcity-valid-coupon-code" name="couponCode">
                            <?php HTML::renderPaymentButtons(); ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-12 col-lg-12">
                                <?php
                                WilokeMessage::message(
                                    [
                                        'status'     => 'danger',
                                        'msgIcon'    => 'la la-frown-o',
                                        'hasMsgIcon' => true,
                                        'msg'        => esc_html__('The plan ID is required', 'wilcity')
                                    ]
                                );
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endwhile;
                wp_reset_postdata(); ?>
            </div>
            <?php do_action('wilcity/after-checkout-page-container')?>
        </section>
    </div>
<?php
do_action('wilcity/before-close-root');
get_footer();
