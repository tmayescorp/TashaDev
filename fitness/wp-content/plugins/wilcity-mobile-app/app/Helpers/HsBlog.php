<?php


namespace WILCITY_APP\Helpers;


class HsBlog
{
	public static function fetchPost($postId): array
	{
		if (!class_exists('\WilokeThemeOptions')) {
			return  [
				'status' => 'error',
				'msg'    => esc_html__('You must active Wilcity theme', 'wilcity-mobile-app')
			];
		}


		$endpoint = trailingslashit(\WilokeThemeOptions::getOptionDetail('hsblog_base_url')) .
			trailingslashit(WILCITY_HSBLOG_NAMESPACE) .
			trailingslashit(WILCITY_HSBLOG_WILCITY_ENDPOINT) . 'posts/'.$postId;
		$rawResponse = wp_remote_get($endpoint);

		$aResults = [
			'status' => 'error',
			'msg'    => esc_html__('There is no posts', 'wilcity-mobile-app')
		];

		if (!empty($rawResponse) && !is_wp_error($rawResponse)) {
			$aResults = json_decode(wp_remote_retrieve_body($rawResponse), true);
		}

		return empty($aResults) ? [] : $aResults;
	}

	public static function fetchPosts($aArgs): array
	{
		$endpoint = trailingslashit(\WilokeThemeOptions::getOptionDetail('hsblog_base_url')) .
			trailingslashit(WILCITY_HSBLOG_NAMESPACE) .
			trailingslashit(WILCITY_HSBLOG_WILCITY_ENDPOINT) . 'articles';

		$aArgs = wp_parse_args($aArgs, [
			'paged'          => 1,
			'posts_per_page' => 10
		]);

		$rawResponse = wp_remote_get(
			$endpoint,
			[
				'body' => $aArgs
			]
		);

		$aResults = [
			'status' => 'error',
			'msg'    => esc_html__('There is no posts', 'wilcity-mobile-app')
		];

		if (!empty($rawResponse) && !is_wp_error($rawResponse)) {
			$aResults = json_decode(wp_remote_retrieve_body($rawResponse), true);
		}

		return empty($aResults) ? [] : $aResults;
	}
}
