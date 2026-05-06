<?php

namespace WILCITY_SC\ParseShortcodeAtts;

trait ParseColumnClasses
{
	public function parseColumnClasses(): string
	{
		if (!isset($this->aScAttributes['maximum_posts_on_lg_screen'])) {
			return '';
		}

		$classes
			= $this->aScAttributes['maximum_posts_on_lg_screen'] . ' ' .
			$this->aScAttributes['maximum_posts_on_md_screen'] . ' ' .
			$this->aScAttributes['maximum_posts_on_sm_screen'];

		if (isset($this->aScAttributes['maximum_posts_on_xs_screen'])) {
			$classes .= ' ' . $this->aScAttributes['maximum_posts_on_xs_screen'];
		}

		$this->aScAttributes['column_classes'] = $classes;

		return $classes;
	}
}
