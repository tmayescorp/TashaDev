<?php

namespace WILCITY_ELEMENTOR\Registers;

/**
 * Elementor wil_select2_ajax class
 * Build a select2 control with search ajax for any wordpress rest-api
 */
class SelectTwoAjaxControl extends \Elementor\Base_Data_Control
{

	const TYPE = 'wil_select2_ajax';

	/**
	 * Return type for Control: wil_select2_ajax
	 *
	 * @return string
	 */
	public function get_type(): string
	{
		return self::TYPE;
	}

	/**
	 * Enqueue scripts and enqueue style for control function
	 *
	 * @return void
	 */
	public function enqueue()
	{
		// Styles
		wp_register_style('wilselec2ajax', WILCITY_EL_SOURCE_URL . 'css/select2Ajax.css', [], '1.0');
		wp_enqueue_style('wilselec2ajax');

		// Scripts
		wp_register_script('wilselec2ajax', WILCITY_EL_SOURCE_URL . 'js/select2.min.js', [], '1.0');
		wp_register_script('wilselec2ajax-control', WILCITY_EL_SOURCE_URL . 'js/select2Ajax.js',
			['wilselec2ajax', 'jquery'], '1.0.0');
		wp_enqueue_script('wilselec2ajax-control');
	}


	/**
	 * Return default settings function
	 *
	 * @return array
	 */
	protected function get_default_settings(): array
	{
		return [
			'label_block'        => true,
			'multiple'           => true,
			'placeholder'        => esc_html__('Search for a category', 'wilcity-elementor-addon'),
			'minimumInputLength' => 1,
			'endpoint'           => rest_url('wp/v2/categories'),
			// can't use args: search and page on this field
			'api_args'           => [
				'per_page' => 20,
			]
		];
	}


	/**
	 * Render content wil_select2 control output in the editor.
	 *
	 * @return void
	 */
	public function content_template()
	{
		$control_uid = $this->get_control_uid();

		?>
        <div class="wil-elementor-control-field elementor-control-field">
            <label for="<?php echo esc_attr($control_uid); ?>" class="elementor-control-title">{{{ data.label
                }}}</label>
            <# var multiple=( data.multiple ) ? 'multiple' : '' ; #>
            <div class="elementor-control-input-wrapper">
                <select {{ multiple }} data-values="{{  data.controlValue }}" data-setting="{{ data.name }}">
                </select>
            </div>
        </div>

        <# if ( data.description ) { #>
        <div class="elementor-control-field-description">{{{ data.description }}}</div>
        <# } #>
		<?php
	}
}
