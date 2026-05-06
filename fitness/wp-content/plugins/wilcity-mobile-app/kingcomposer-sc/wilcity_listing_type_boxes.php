<?php
$atts = shortcode_atts(
	[
		'except_directory_types' => '',
		'items_per_row'          => 3,
		'bg_color'               => '#ffffff'
	],
	$atts
);

$aDirectoryTypes = \WilokeListingTools\Framework\Helpers\General::getPostTypes(false, false);

$aResponse = [];

if (!empty($aDirectoryTypes)) {
	$aExcludes = explode(',', $atts['except_directory_types']);

	foreach ($aDirectoryTypes as $postType => $aDirectoryType) {
		if (empty($aExcludes) || !in_array($postType, $aExcludes)) {
			$aResponse[] = [
				'label'            => $aDirectoryType['name'],
				'iconName'         => $aDirectoryType['icon'],
				'postType'         => $postType,
				'backgroundColor'  => $aDirectoryType['bgColor'],
				'backgroundImgUrl' => empty($aDirectoryType['bgImg']['url']) ? '' : $aDirectoryType['bgImg']['url']
			];
		}
	}
}
$atts['items_per_row'] = abs($atts['items_per_row']);

echo '%SC%' . base64_encode(json_encode(
		[
			'oSettings' => $atts,
			'TYPE'      => 'DIRECTORY_TYPE_BOXES',
			'oResults'  => $aResponse
		]
	)) . '%SC%';

return '';
