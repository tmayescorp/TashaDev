<?php

namespace WilokeListingTools\Framework\Helpers;

use WILCITY_SC\SCHelpers;
use WilokeListingTools\Controllers\ReviewController;
use WilokeListingTools\Framework\Helpers\Collection\ArrayCollectionFactory;
use WilokeListingTools\Frontend\BusinessHours;
use WilokeListingTools\Frontend\SingleListing;
use WilokeListingTools\Frontend\User;
use WilokeListingTools\Models\FollowerModel;
use WilokeListingTools\Models\ReviewMetaModel;
use WilokeListingTools\Models\UserModel;

class UserSkeleton
{
    private $userID;
    
    public function __construct($userID)
    {
        $this->userID = $userID;
    }
    
    public function getUserID(): ?int
    {
        return $this->userID;
    }
    
    public function getID(): ?int
    {
        return $this->userID;
    }
    
    public function getDisplayName(): ?string
    {
        return User::getField('display_name', $this->userID);
    }
    
    public function getPhone(): ?string
    {
        return User::getPhone($this->userID);
    }
    
    public function getEmail(): ?string
    {
        return User::getField('user_email', $this->userID);
    }
    
    public function getAvatar(): ?string
    {
        return User::getAvatar($this->userID);
    }
    
    public function getAddress(): ?string
    {
        return User::getAddress($this->userID);
    }
    
    public function getTotalFollowings(): ?int
    {
        return FollowerModel::countFollowings($this->userID);
    }
    
    public function getTotalFollowers(): ?int
    {
        return FollowerModel::countFollowers($this->userID);
    }
    
    public function getAuthorPostsUrl(): string
    {
        return get_author_posts_url($this->userID);
    }
    
    public function getAuthorLink(): string
    {
        return $this->getAuthorPostsUrl();
    }
    
    public function getUserMeta($key)
    {
        return GetSettings::getUserMeta($this->userID, $key);
    }
    
    public function pluck($pluck)
    {
        if (empty($this->userID)) {
            return [];
        }
        
        $aPluck = is_array($pluck) ? $pluck : explode(',', $pluck);
        
        $aPluck = array_map(function ($rawMethod) {
            $method = trim($rawMethod);
            $method = explode('_', $method);
            $method = array_map(function ($item) {
                return ucfirst($item);
            }, $method);
            
            return [
                'key'    => $rawMethod,
                'method' => 'get'.implode('', $method)
            ];
        }, $aPluck);
        
        $aData = [];
        foreach ($aPluck as $aMethod) {
            if (method_exists($this, $aMethod['method'])) {
                $val = $this->{$aMethod['method']}();
            } else {
                $val = $this->getUserMeta($aMethod['key']);
            }
            
            $aData[$aMethod['key']] = $val;
        }
        
        return $aData;
    }
    
    public function getSkeleton($pluck)
    {
        return $this->pluck($pluck);
    }
}
