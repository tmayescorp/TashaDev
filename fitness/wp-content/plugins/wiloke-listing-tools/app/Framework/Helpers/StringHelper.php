<?php

namespace WilokeListingTools\Framework\Helpers;

/**
 * Class StringHelper
 * @package WilokeListingTools\Framework\Helpers
 */
class StringHelper
{
	/**
	 * @param $string
	 * @return mixed
	 */
	public static function replaceEntityString($string)
	{
		$string = str_replace(
			[
				'&lt;',
				'&#60;',
				'&gt;',
				'&#62;',
				'&amp;',
				'&#38;',
				'&quot;',
				'&#034;',
				'&apos;',
				'&#039;',
				'&excl;',
				'&#33;',
				'&num;',
				'&#35;',
				'&dollar;',
				'&#36;',
				'&percnt;',
				'&#37;',
				'&lpar;',
				'&#40;',
				'&rpar;',
				'&#41;',
				'&ast;',
				'&#42;',
			],
			[
				'<',
				'<',
				'>',
				'>',
				'&',
				'&',
				'"',
				'"',
				'\'',
				'\'',
				'!',
				'!',
				'#',
				'#',
				'$',
				'$',
				'%',
				'%',
				'(',
				'(',
				')',
				')',
				'*',
				'*',
			],
			$string);

		return $string;
	}
}
