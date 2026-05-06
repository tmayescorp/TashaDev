<?php

namespace WILCITY_SC\ParseShortcodeAtts;

use Wilcity\Term\TermCount;
use WilokeListingTools\Framework\Helpers\TermSetting;

trait CountPostsInTerm
{
	public function countPostsInTerm($oTerm): int
	{
		if (!$oTerm instanceof \WP_Term) {
			return 0;
		}

		if (isset($this->aScAttributes['terms_in_sc'])) {
			$aTaxQuery = $this->aScAttributes['terms_in_sc'];
		} else {
			$aTaxQuery = [];
		}
		$aTaxQuery[$oTerm->taxonomy] = $oTerm->term_id;

		if ($this->aScAttributes['post_type'] === 'flexible') {
			$this->aScAttributes['post_type'] = TermSetting::getDefaultPostType($oTerm->term_id, $oTerm->taxonomy);
		}

		$aArgs = [
//			'post_type'      => $this->aScAttributes['post_type'],
			'posts_per_page' => 1,
			'post_status'    => 'publish'
		];


		$oCount = new TermCount($aTaxQuery, $aArgs);

		return (int)$oCount->count();
	}
}
