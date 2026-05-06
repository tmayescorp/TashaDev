<?php

namespace WilokeListingTools\Framework\Helpers;

use WilokeListingTools\Frontend\User;

class AuthorSkeleton extends AbstractSkeleton
{
    protected $id;

    public function setID($id): void
    {
        $this->id = $id;
    }

    /**
     * @return int|null
     */
    public function getID(): ?int
    {
        return absint($this->id);
    }

    protected function getDisplayName(): ?string
    {
        return User::getField('display_name', $this->getID());
    }

    /**
     * @return string|null
     */
    public function getAvatar(): ?string
    {
        return User::getAvatar($this->id);
    }

    protected function getAuthorLink(): string
    {
        return get_author_posts_url($this->getID());
    }

    public function getSkeleton($authorID, $aPluck, $aAtts = [])
    {
        if (empty($aPluck)) {
            $aPluck = [
                'ID',
                'avatar',
                'displayName',
            ];
        } else {
            $aPluck = is_array($aPluck) ? $aPluck : explode(',', $aPluck);
            $aPluck = array_map(function ($key) {
                return $key;
            }, $aPluck);
        }

        $this->aAtts = $aAtts;
        $this->setID($authorID);

        /**
         * @hooked WilcityRedis\Controllers@removeCachingPluckItems
         */
        $aPluck = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/AuthorSkeleton/pluck',
            $aPluck,
            $authorID,
            $this->aAtts
        );

        $aAuthor = $this->pluck($aPluck);

        /**
         * @hooked WilcityRedis\Controllers@getPostSkeleton 5
         * @hooked WilcityRedis\Controllers@setPostSkeleton 10
         */
        $aAuthor = apply_filters(
            'wilcity/filter/wiloke-listing-tools/app/Framework/Helpers/AuthorSkeleton/review',
            $aAuthor,
            $authorID,
            $this->aAtts
        );

        return $aAuthor;
    }
}
