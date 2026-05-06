<?php

namespace Wilcity\Term;

class TermCount
{
    private $aTerms;
    private $aQueryArgs;
    private $aDefaultArgs;
    private $aCache;
    
    public function __construct($aTerms, array $aArgs)
    {
        // Each term includes ['listing_location' => 'term_ids']
        $this->aTerms = $aTerms;
        $this->prepare();
        $this->aDefaultArgs = $aArgs;
    }
    
    private function determineTermField($aTerms)
    {
        $term = is_array($aTerms) ? $aTerms[0] : $aTerms;
        
        return is_numeric($term) ? 'term_id' : 'slug';
    }
    
    private function prepare()
    {
        foreach ($this->aTerms as $taxonomy => $aTerms) {
            if (taxonomy_exists($taxonomy)) {
                $this->aQueryArgs['tax_query'][] = [
                    'terms'    => !is_array($aTerms) ? explode(',', $aTerms) : $aTerms,
                    'field'    => $this->determineTermField($aTerms),
                    'taxonomy' => $taxonomy
                ];
            }
        }
    }
    
    public function count(): int
    {
        if (empty($this->aQueryArgs)) {
            return 0;
        }
        
        $key = md5(serialize($this->aQueryArgs));
        
        if (isset($this->aCache[$key])) {
            return $this->aCache[$key];
        }
        
        $this->aQueryArgs                   = array_merge($this->aDefaultArgs, $this->aQueryArgs);
        $this->aQueryArgs['posts_per_page'] = 1;
        $this->aQueryArgs['fields']         = 'ids';
        $query = new \WP_Query($this->aQueryArgs);
        
        $this->aCache[$key] = $query->found_posts;
        
        return $this->aCache[$key];
    }
}
