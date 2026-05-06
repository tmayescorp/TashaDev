<?php

namespace WILCITY_ELEMENTOR\Registers;

trait Helpers
{
	private $aCacheTax    = [];
	private $aPostOptions = [];
	private $aConvertedConfiguration;

	private function findScAttributesFileName($currentFileName)
	{
		$currentFileName = preg_replace_callback('/[A-Z]/', function ($match) {
			return '-' . strtolower($match[0]);
		}, $currentFileName);
		$currentFileName = ltrim($currentFileName, '-');

		return rtrim($currentFileName, '.php');
	}

	private function convertKCToEl($aConfiguration)
	{
		$oInstance = new ElementorAdapterConfiguration($aConfiguration);
		$this->aConvertedConfiguration = $oInstance->convert();

		return $this;
	}

	protected function registerShortcode()
	{
		foreach ($this->aConvertedConfiguration as $aItem) {
			try {
				if ($aItem['cb'] === 'end_controls_section') {
					$this->end_controls_section();
				} else {
					$this->{$aItem['cb']}(
						$aItem['id'],
						$aItem['config']
					);
				}
			}
			catch (\Exception $exception) {
				if (WP_DEBUG) {
					echo $exception->getMessage();
					die;
				}
			}
		}
	}

	protected function getTerms($taxonomy)
	{
		if (isset($this->aCacheTax[$taxonomy])) {
			return $this->aCacheTax[$taxonomy];
		}

		$totals = wp_count_terms($taxonomy);
		if ($totals > 100) {
			$this->aCacheTax[$taxonomy] = 'toomany';

			return $this->aCacheTax[$taxonomy];
		}

		$aRawTerms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);

		$options = ['' => '-----------------'];
		if (!empty($aRawTerms) && !is_wp_error($aRawTerms)) {
			foreach ($aRawTerms as $oTerm) {
				$options[$oTerm->term_id] = $oTerm->name;
			}
		}

		$this->aCacheTax[$taxonomy] = $options;

		return $options;
	}

	protected function getPosts($postType, $maxPosts = 50)
	{
		if (isset($this->aPostOptions[$postType])) {
			return $this->aPostOptions[$postType];
		}

		$query = new \WP_Query(
			[
				'post_type'      => $postType,
				'posts_per_page' => 50,
				'post_status'    => 'publish'
			]
		);
		$aOptions = [];
		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$aOptions[$query->post->ID] = $query->post->post_title;
			}
		} else {
			$aOptions[] = 'No Posts';
		}
		wp_reset_postdata();
		$this->aPostOptions[$postType] = $aOptions;

		return $aOptions;
	}
}
