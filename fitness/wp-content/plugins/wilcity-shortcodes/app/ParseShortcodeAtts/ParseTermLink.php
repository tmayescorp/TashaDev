<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Framework\Helpers\QueryHelper;
use WilokeListingTools\Framework\Helpers\TermSetting;

trait ParseTermLink
{
	public function parseTermLink($oTerm)
	{
		if ($this->aScAttributes['term_redirect'] !== 'search_page') {
			$termLink = get_term_link($oTerm->term_id);
			if (!empty($this->aScAttributes['post_type']) && $this->aScAttributes['post_type'] !== 'flexible') {
				$termLink = add_query_arg(
					[
						'postType' => $this->aScAttributes['post_type']
					],
					$termLink
				);
			}

			return $termLink;
		}
		if (empty($this->aScAttributes['post_type']) || $this->aScAttributes['post_type'] === 'flexible') {
			$this->aScAttributes['post_type'] = TermSetting::getDefaultPostType($oTerm->term_id, $oTerm->taxonomy);
		}

		$aSearchArgs = [
			'postType'       => $this->aScAttributes['post_type'],
			$oTerm->taxonomy => $oTerm->term_id
		];

		$aTaxonomies = TermSetting::getListingTaxonomyKeys($this->aScAttributes['post_type']);
		foreach ($aTaxonomies as $taxonomy) {
			if ($taxonomy === $oTerm->taxonomy) {
				continue;
			}

			if (isset($this->aScAttributes[$taxonomy]) && !empty($this->aScAttributes[$taxonomy])) {
				$aTerms = SCHelpers::getAutoCompleteVal($this->aScAttributes[$taxonomy]);
				$aSearchArgs[$taxonomy] = $aTerms[0];
			}
		}

		return QueryHelper::buildSearchPageURL($aSearchArgs);
	}
}
