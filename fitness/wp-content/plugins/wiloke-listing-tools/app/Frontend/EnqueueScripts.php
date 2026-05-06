<?php

namespace WilokeListingTools\Frontend;


use WilokeListingTools\Framework\Helpers\GetWilokeSubmission;
use WilokeListingTools\Models\UserModel;

class EnqueueScripts {
	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
		add_action('wp_head', array($this, 'printScript'));
	}

	public function printScript(){
		global $wiloke;
		$mapTheme = isset($wiloke->aThemeOptions['map_theme']) ? esc_js($wiloke->aThemeOptions['map_theme']) : 'blurWater';
		if ( $mapTheme == 'custom' ):
			$theme = isset($wiloke->aThemeOptions['map_custom_theme']) && !empty($wiloke->aThemeOptions['map_custom_theme']) ? $wiloke->aThemeOptions['map_custom_theme'] : '[]';
		?>
			<script style="text/javascript">
				window.WILCITY_CUSTOM_MAP = <?php echo $theme; ?>;
			</script>
		<?php
		endif;
	}

	public function enqueueScripts(){
        $publishableKey = GetWilokeSubmission::getField('mode') == 'live' ? GetWilokeSubmission::getField('stripe_publishable_key') :
            GetWilokeSubmission::getField('stripe_sandbox_publishable_key');

		wp_localize_script('wilcity-empty', 'WILCITY_GLOBAL', array(
			'oStripe' => array(
				'publishableKey' => $publishableKey,
				'hasCustomerID'  => UserModel::getStripeID() ? 'yes' : 'no'
			),
			'oGeneral' => array(
				'brandName' => GetWilokeSubmission::getField('brandname')
			)
		));

	}
}
