<?php

namespace WilokeListingTools\Framework\Helpers;

class Select
{
    public static function getSelectTreeItem($aItem)
    {
        if (isset($aItem['id'])) {
            $val = $aItem['id'];
        } else {
            $val = $aItem;
        }
        
        return $val;
    }
    
    public static function getSelectTreeVal($rawVal)
    {
        if (empty($rawVal)) {
            return false;
        }
        
        if (isset($rawVal['id']) || !is_array($rawVal)) {
            $val = self::getSelectTreeItem($rawVal);
        } else {
            $val = [];
            foreach ($rawVal as $item) {
                $val[] = self::getSelectTreeItem($item);
            }
        }
        
        return $val;
    }
    
    public static function buildPostsSelectTree(
        $aPostIDs, $valueFormat = 'object', $maximum = 1,
        $aRequiredPostStatus = ['publish', 'pending'])
    {
        $aValues = [];
        if ($maximum > 1) {
            if (empty($aPostIDs)) {
                return [];
            } else {
                if ($valueFormat === 'object') {
                    foreach ($aPostIDs as $postID) {
                        if (empty($aRequiredPostStatus) ||
                            (!empty($aRequiredPostStatus) &&
                             in_array(get_post_status($postID), $aRequiredPostStatus))) {
                            $aValues[] = [
                                'id'    => absint($postID),
                                'label' => get_the_title($postID)
                            ];
                        } else {
                            $aValues = $postID;
                        }
                    }
                } else {
                    $aValues = $aPostIDs;
                }
            }
        } else {
            $postID = is_array($aPostIDs) ? $aPostIDs[0] : $aPostIDs;
            if (empty($aRequiredPostStatus) ||
                (!empty($aRequiredPostStatus) && in_array(get_post_status($postID), $aRequiredPostStatus))) {
                if ($valueFormat === 'object') {
                    $aValues = [
                        'id'    => absint($postID),
                        'label' => get_the_title($postID)
                    ];
                } else {
                    $aValues = $postID;
                }
                
            }
        }
        
        return $aValues;
    }
    
    public static function buildTermSelectTree(
        $aTermIds, $taxonomy, $valueFormat = 'object', $maximum = 1)
    {
        $aValues = [];
        if ($maximum > 1) {
            if (empty($aTermIds)) {
                return [];
            } else {
                if ($valueFormat === 'object') {
                    foreach ($aTermIds as $termId) {
                        if (get_term_field('term_id', $termId, $taxonomy) == $termId) {
                            $oTerm = get_term($termId, $taxonomy);
                            
                            $aValues[] = [
                                'id'    => $oTerm->term_id,
                                'label' => $oTerm->name
                            ];
                        }
                    }
                } else {
                    $aValues = $aTermIds;
                }
            }
        } else {
            $termId = is_array($aTermIds) ? $aTermIds[0] : $aTermIds;
            if (get_term_field('term_id', $termId, $taxonomy) == $termId) {
                $oTerm = get_term($termId, $taxonomy);
                if ($valueFormat === 'object') {
                    $aValues = [
                        'id'    => absint($oTerm->term_id),
                        'label' => $oTerm->name
                    ];
                } else {
                    $aValues = $oTerm->term_id;
                }
            }
        }
        
        return $aValues;
    }
}
