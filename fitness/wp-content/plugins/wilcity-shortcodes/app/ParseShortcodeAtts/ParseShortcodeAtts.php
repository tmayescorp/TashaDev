<?php

namespace WILCITY_SC\ParseShortcodeAtts;

class ParseShortcodeAtts
{
    use ParseTermArgs;
    use PrepareHeading;
    use BuildTermQueryArgs;
    use MergeIsAppRenderingAtts;
    use ParseColumnClasses;
    use ParsePostType;
    use ParseTermsInSc;
    use CountPostsInTerm;
    use ParseTermLink;
    use ParseImageSize;
    use GetPostsInTerm;
    private $aScAttributes = [];

    public function __construct(array $aScAttributes)
    {
        $this->aScAttributes = $aScAttributes;
    }

    public function getSCAttributes()
    {
        return $this->aScAttributes;
    }

    public function toArray($key)
    {
        if (!isset($this->aScAttributes[$key])) {
            return [];
        }

        $aItems = is_array($this->aScAttributes[$key]) ? $this->aScAttributes[$key] : explode(',',
            $this->aScAttributes[$key]);

        return array_filter($aItems, function ($item) {
            return !empty($item);
        });
    }

    private function parseId()
    {
        if (isset($this->aScAttributes['wrapper_id']) && !empty($this->aScAttributes['wrapper_id'])) {
            return $this->aScAttributes['wrapper_id'];
        }

        return uniqid('wil-wrapper-id');
    }

    private function parseArgs()
    {
        $this->mergeIsAppRenderingAttr();
        $this->parseColumnClasses();
        $this->parsePostType();
        $this->parseTermArgs();
        $this->getTermsInSc();
        $this->prepareHeading();
        $this->parseImageSize();
        $this->parseId();
    }

    public function parse()
    {
        $this->parseArgs();

        return $this->aScAttributes;
    }
}
