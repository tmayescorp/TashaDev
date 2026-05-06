<?php

use \WilokeListingTools\Framework\Helpers\GetSettings;
use \WilokeListingTools\Framework\Helpers\HTML;
use \WilokeListingTools\Framework\Store\Session;

function wilcity_render_custom_login_sc($atts)
{
	$action = isset($_GET['action']) ? $_GET['action'] : 'wilcity_login';
	if (strpos($action, 'wilcity') !== 0) {
		$action = 'wilcity_' . $action;
	}

	if (!isset($_POST['action'])) {
		Session::destroySession('rp-status');
		Session::destroySession('login-error');
		Session::destroySession('register-error');
	}

	$userLogin = '';

	$classWrapper = 'log-reg-template_module__2BZGH clearfix';
	if (!empty($atts['extra_class'])) {
		$classWrapper .= ' ' . $atts['extra_class'];
	}
	?>
    <div class="<?php echo esc_attr($classWrapper); ?>">
        <div class="log-reg-template_left__3D6wA">
            <div class="wil-tb full">
                <div class="wil-tb__cell">

                    <!-- log-reg-action_module__h5MhW -->
                    <div class="log-reg-action_module__h5MhW">
                        <div class="log-reg-action_logo__37V3f">
							<?php HTML::renderSiteLogo(); ?>
                        </div>
                        <div class="log-reg-action_formWrap__1HP4n">
							<?php switch ($action) {
								case 'wilcity_verify_otp':
									if (!empty($atts['login_section_title'])) {
										echo '<h2 class="log-reg-action_title__2932Y">' .
											esc_html__('Validate OTP', 'wilcity-shortcodes') . '</h2>';
									}
									break;
								case 'wilcity_login':
									if (!empty($atts['login_section_title'])) {
										echo '<h2 class="log-reg-action_title__2932Y">' .
											Wiloke::ksesHTML($atts['login_section_title'], true) . '</h2>';
									}
									break;
								case 'wilcity_register':
									if (!empty($atts['register_section_title'])) {
										echo '<h2 class="log-reg-action_title__2932Y">' .
											Wiloke::ksesHTML($atts['register_section_title'], true) . '</h2>';
									}
									break;
								case 'wilcity_rp':
									if (!empty($atts['rp_section_title'])) {
										echo '<h2 class="log-reg-action_title__2932Y">' .
											Wiloke::ksesHTML($atts['rp_section_title'], true) . '</h2>';
									}
									break;
							} ?>

							<?php if ($atts['social_login_type'] != 'off' && $action != 'wilcity_verify_otp'): ?>
								<?php do_action('wilcity/wilcity-shortcodes/default-sc/wilcity-custom-login/before/socials-login'); ?>

                                <div id="<?php echo esc_attr(apply_filters('wilcity/filter/id-prefix',
									'wilcity-social-login')); ?>" class="mb-30">
									<?php if ($atts['social_login_type'] == 'fb_default' &&
										\WilokeThemeOptions::isEnable('fb_toggle_login')) :
										$aConfig = [
											'api' => \WilokeThemeOptions::getOptionDetail('fb_api_id')
										];
										?>
                                        <facebook @verify-otp="handleRedirection"
                                                  @loggedin="handleRedirection"
                                                  :configs='<?php echo json_encode($aConfig); ?>'></facebook>
									<?php else: ?>
										<?php echo do_shortcode($atts['social_login_shortcode']); ?>
									<?php endif; ?>

									<?php do_action('wilcity/wilcity-shortcodes/default-sc/wilcity-custom-login/socials-login'); ?>
                                </div>

								<?php do_action('wilcity/wilcity-shortcodes/default-sc/wilcity-custom-login/after/socials-login'); ?>
							<?php endif; ?>
							<?php
							switch ($action) {
								case 'wilcity_rp':
									$aRPStatus = Session::getSession('rp-status', false);
									$aRPStatus = empty($aRPStatus) ? $aRPStatus : maybe_unserialize($aRPStatus);
									if (isset($aRPStatus['status']) && $aRPStatus['status'] == 'success') {
										WilokeMessage::message([
											'status'       => 'success',
											'hasRemoveBtn' => false,
											'hasMsgIcon'   => false,
											'msgIcon'      => 'la la-envelope-o',
											'msg'          => $aRPStatus['msg']
										]);
									} else {
										if (isset($aRPStatus['status']) && $aRPStatus['status'] == 'error') {
											WilokeMessage::message([
												'status'       => 'danger',
												'hasRemoveBtn' => false,
												'hasMsgIcon'   => false,
												'msgIcon'      => 'la la-envelope-o',
												'msg'          => $aRPStatus['msg']
											]);
										}
										?>
                                        <form name="loginform" id="loginform"
                                              action="<?php echo esc_url(add_query_arg(
											      [
												      'action' => 'rp'
											      ],
											      GetSettings::getCustomLoginPage()
										      )); ?>"
                                              method="post">
											<?php
											HTML::renderInputField([
												'name'  => 'user_login',
												'type'  => 'text',
												'label' => esc_html__('Username or Email Address', 'wilcity-shortcodes'),
												'value' => $userLogin
											]);

											HTML::renderHiddenField([
												'name'  => 'action',
												'value' => 'wilcity_rp'
											]);

											HTML::renderHiddenField([
												'name'  => 'form_type',
												'value' => 'custom_login'
											]);
											?>
                                            <button type="submit"
                                                    class="wil-btn mb-20 wil-btn--gradient wil-btn--md wil-btn--round wil-btn--block"><?php esc_html_e('Get New Password',
													'wilcity-shortcodes'); ?></button>
                                        </form>
										<?php
									}
									break;
								case 'wilcity_login':
									if (Session::getSession('login-error')) {
										WilokeMessage::message([
											'status'       => 'danger',
											'hasRemoveBtn' => false,
											'hasMsgIcon'   => false,
											'msgIcon'      => 'la la-envelope-o',
											'msg'          => Session::getSession('login-error', false)
										]);
									}

									?>
                                    <form name="loginform" id="loginform"
                                          action="<?php echo esc_url(GetSettings::getCustomLoginPage()); ?>"
                                          method="post">
										<?php
										foreach (
											wilokeListingToolsRepository()
												->get('register-login:registerFormFields', true)
												->sub('login') as $aField
										) {
											HTML::renderDynamicField($aField);
										}

										HTML::renderHiddenField([
											'name'  => 'action',
											'value' => 'wilcity_login'
										]);

										HTML::renderHiddenField([
											'name'  => 'form_type',
											'value' => 'custom_login'
										]);

										do_action('login_form');
										do_action('wilcity/wiloke-listing-tools/custom-login-form');
										?>
                                        <div class="o-hidden ws-nowrap">
											<?php
											HTML::renderCheckboxField([
												'name'  => 'isRemember',
												'value' => 'forever',
												'label' => esc_html__('Remember Me', 'wilcity-shortcodes')
											]);
											?>
                                            <a class="wil-float-right td-underline"
                                               href="<?php echo esc_url(add_query_arg(['action' => 'rp'],
												   GetSettings::getCustomLoginPage())); ?>"><?php esc_html_e('Lost password',
													'wilcity-shortcodes'); ?></a>
                                        </div>
                                        <button type="submit"
                                                class="wil-btn mb-20 wil-btn--gradient wil-btn--md wil-btn--round wil-btn--block"><?php esc_html_e('Login',
												'wilcity-shortcodes'); ?></button>

                                        <?php
                                        do_action('wilcity/wiloke-listing-tools/after/login-btn');
                                        ?>
                                    </form>
									<?php break; ?>
								<?php
								case 'wilcity_register':
									if (GetSettings::userCanRegister()) :
										if (Session::getSession('register-error')) {
											WilokeMessage::message([
												'status'       => 'danger',
												'hasRemoveBtn' => false,
												'hasMsgIcon'   => false,
												'msgIcon'      => 'la la-envelope-o',
												'msg'          => Session::getSession('register-error', false)
											]);
										}
										?>
                                        <form name="registerform" id="registerform"
                                              action="<?php echo esc_url(add_query_arg(['action' => 'register'],
											      GetSettings::getCustomLoginPage()), 'login_post'); ?>" method="post"
                                              novalidate="novalidate">
											<?php
											foreach (
												wilokeListingToolsRepository()
													->get('register-login:registerFormFields', true)
													->sub('register') as $aField
											) {
												if (isset($_POST[$aField['name']])) {
													$aField['value'] = $_POST[$aField['name']];
												}
												HTML::renderDynamicField($aField);
											}
											HTML::renderHiddenField([
												'name'  => 'action',
												'value' => 'wilcity_register'
											]);

											HTML::renderHiddenField([
												'name'  => 'form_type',
												'value' => 'custom_login'
											]);

											do_action('register_form');
											do_action('wilcity/wiloke-listing-tools/custom-register-form');
											?>
                                            <button type="submit"
                                                    class="wil-btn mb-20 wil-btn--gradient wil-btn--md wil-btn--round wil-btn--block">
												<?php esc_html_e('Register', 'wilcity-shortcodes'); ?></button>
                                        </form>
									<?php endif; ?>
									<?php break;
								default:
									do_action('wilcity/wilcity-shortcodes/default-sc/wilcity-custom-login/form/' .
										$action);
									break;
							};
							?>
                        </div>
						<?php
						if (GetSettings::userCanRegister()) {
							switch ($action) {
								case 'wilcity_login':
									echo esc_html__('Don’t have an account?', 'wilcity-shortcodes') . ' <a href="' .
										wp_registration_url() . '">' . esc_html__('Register', 'wilcity-shortcodes') .
										'</a>';
									break;
								case 'wilcity_register':
									echo '<a href="' . GetSettings::getCustomLoginPage() . '">' .
										esc_html__('Login with username and password?', 'wilcity-shortcodes') . '</a>';
									break;
								case 'wilcity_rp':
									if (!isset($aRPStatus) || !isset($aRPStatus['status']) ||
										$aRPStatus['status'] !== 'success'):
										echo '<a href="' . GetSettings::getCustomLoginPage() . '">' .
											esc_html__('Login', 'wilcity-shortcodes') . '</a> | <a href="' .
											wp_registration_url() . '">' .
											esc_html__('Register', 'wilcity-shortcodes') .
											'</a>';
									endif;
									break;
							}

						}
						?>

                    </div><!-- End / log-reg-action_module__h5MhW -->

                </div>
            </div>
        </div>
        <div class="log-reg-template_right__3aFwI">
			<?php if (!empty($atts['login_bg_color'])) : ?>
                <div class="wil-overlay"
                     style="background-color: <?php echo esc_attr($atts['login_bg_color']); ?>;"></div>
			<?php endif; ?>
			<?php if (!empty($atts['login_bg_img'])) : ?>
                <div class="log-reg-template_bg__7KwPs bg-cover"
                     style="background-image: url(<?php echo esc_url($atts['login_bg_img']); ?>);"></div>
			<?php endif; ?>
            <div class="wil-tb full">
                <div class="wil-tb__cell">
                    <div class="log-reg-features_module__1x06b">
						<?php if (is_array($atts['login_boxes'])) : ?>
							<?php foreach ($atts['login_boxes'] as $aBox) :
								if (is_object($aBox)) {
									$aBox = get_object_vars($aBox);
								}
								?>
                                <div class="textbox-2_module__15Zpj textbox-2_style-3__1U-rY clearfix">
                                    <div class="textbox-2_icon__1xt9q color-primary"><i
                                                style="color: <?php echo esc_attr($aBox['icon_color']); ?>"
                                                class="<?php echo esc_attr($aBox['icon']); ?>"></i></div>
                                    <h3 class="textbox-2_title__301U3"
                                        style="color: <?php echo esc_attr($aBox['text_color']); ?>"><?php Wiloke::ksesHTML($aBox['description']); ?></h3>
                                </div>
							<?php endforeach; ?>
						<?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<?php
}
