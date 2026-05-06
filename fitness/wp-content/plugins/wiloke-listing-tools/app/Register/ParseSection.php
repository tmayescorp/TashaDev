<?php

namespace WilokeListingTools\Register;


trait ParseSection {
	protected function parseSection($aSection){
		$aUsedSections['type']    = $aSection['type'];
		$aUsedSections['key']     = $aSection['key'];
		$aUsedSections['heading'] = $aSection['heading'];
		$aUsedSections['desc']    = isset($aSection['desc']) ? $aSection['desc'] : '';
		$aUsedSections['icon']    = $aSection['icon'];

		if ( isset($aSection['isCustomSection']) && $aSection['isCustomSection'] ){
			$aUsedSections['isCustomSection'] = 'yes';
		}
		
		if ( isset($aSection['isGroup']) && ($aSection['isGroup'] == 'yes') ){
			$aUsedSections['fieldGroups'] = $aSection['fieldGroups'];
		}else{
			foreach ($aSection['fieldGroups'] as $aFieldSettings){
				$aUsedSections['fields'][$aFieldSettings['key']]['type'] = $aFieldSettings['type'];
				$aUsedSections['fields'][$aFieldSettings['key']]['value'] = '';

				foreach ($aFieldSettings['fields'] as $aDetailSettings){
					$aUsedSections['fields'][$aFieldSettings['key']][$aDetailSettings['key']] = $aDetailSettings[$aDetailSettings['key']];
				}
			}
		}
		return $aUsedSections;
	}
}
