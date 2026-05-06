<?php

namespace Wilcity\Ultils\ListItems;

use phpDocumentor\Reflection\Types\Boolean;

abstract class RenderableAbstract
{
    protected $wrapperClasses;
    protected $content;
    protected $wrapperEl = 'ul';
    /**
     * @var RenderableAbstract[] $aElements
     */
    protected $aElements;
    protected $aConfiguration;
    /**
     * @var RenderableAbstract $childClass
     */
    protected $childClass;
    
    /**
     * @param $classes
     *
     * @return RenderableAbstract
     */
    public function setWrapperClasses($classes): RenderableAbstract
    {
        $this->wrapperClasses = $classes;
        
        return $this;
    }
    
    /**
     * @param $content
     *
     * @return RenderableAbstract
     */
    public function setContent($content): RenderableAbstract
    {
        $this->content = $content;
        
        return $this;
    }
    
    /**
     * @param RenderableAbstract $element
     *
     * @return RenderableAbstract
     */
    public function addElement(RenderableAbstract $element): RenderableAbstract
    {
        $this->aElements[] = $element;
        
        return $this;
    }
    
    /**
     * @param array $aConfiguration
     *
     * @return RenderableAbstract
     */
    public function setConfiguration(array $aConfiguration): RenderableAbstract
    {
        $this->aConfiguration = $aConfiguration;
        
        return $this;
    }
    
    /**
     * @param $class
     *
     * @return bool
     */
    protected function isClassExists($class): bool
    {
        $this->childClass = 'Wilcity\Ultils\ListItems\\'.ucfirst($class);
        if (class_exists($this->childClass)) {
            return true;
        }
        
        return class_exists($this->childClass);
    }
    
    /**
     * @param      $result
     * @param bool $isNegative
     *
     * @return bool
     */
    protected function compareConditional($result, $isNegative = false): bool
    {
        return $isNegative ? $result === false : $result;
    }
    
    /**
     * Sometimes, We only want to print a field if it's passed a conditional (EG: Print login field if the user is
     * not logged into the site), This method helps you to do that.
     * We will add a key called conditional to field configuration. The  value of this key can be an array callback
     * or function callback. EG: [...'condition' => 'is_user_logged_in'] or [...'condition' => ['isAdmin', $userID]]
     * or [...'condition' => [['\WilcityUser', 'method'], $userID]]
     *
     *
     * @param $aChild
     *
     * @return bool
     */
    protected function isPassedConditional($aChild): bool
    {
        if (!isset($aChild['conditional'])) {
            return true;
        }
        
        if (is_string($aChild['conditional'])) {
            $isNegativeCompare     = strpos($aChild['conditional'], '!') !== false;
            $aChild['conditional'] = str_replace('!', '', $aChild['conditional']);
            if (function_exists($aChild['conditional'])) {
                return $this->compareConditional($aChild['conditional'](), $isNegativeCompare);
            }
            
            return true;
        }
        
        $callback = $aChild['conditional'][0];
        $param    = isset($aChild['conditional'][1]) ? $aChild['conditional'][1] : "";
        
        if (is_string($callback)) {
            $isNegativeCompare = strpos($callback, '!') !== false;
            $callback          = str_replace('!', '', $callback);
            if (function_exists($callback)) {
                return $this->compareConditional($callback($param), $isNegativeCompare);
            }
        }

	    $isNegativeCompare = strpos($callback[0], '!') !== false;
	    $callback[0]       = str_replace('!', '', $callback[0]);
        if (class_exists($callback[0]) && method_exists($callback[0], $callback[1])) {
            return $this->compareConditional(call_user_func([$callback[0], $callback[1]], $param), $isNegativeCompare);
        }
        
        return true;
    }
    
    protected function isPassedConditionals($aChild): bool
    {
        if (!isset($aChild['conditionals'])) {
            return true;
        }
        
        $relation = isset($aChild['relation']) ? strtoupper($aChild['relation']) : 'AND';
        unset($aChild['relation']);
        
        foreach ($aChild['conditionals'] as $aConditional) {
            $status = $this->isPassedConditional(['conditional' => $aConditional]);
            if ($relation === 'AND' && !$status) {
                return false;
            }
            
            if ($relation === 'OR' && $status) {
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * @return RenderableAbstract
     * @throws \Exception
     */
    protected function handleConfiguration($isTop = false): RenderableAbstract
    {
        if (isset($this->aConfiguration['wrapperClasses'])) {
            $this->setWrapperClasses($this->aConfiguration['wrapperClasses']);
        }
        
        if (isset($this->aConfiguration['content'])) {
            $this->setContent($this->aConfiguration['content']);
        }
        
        if ($isTop) {
            $aConfiguration = isset($this->aConfiguration['type']) ? [$this->aConfiguration] : $this->aConfiguration;
        } else {
            if (isset($this->aConfiguration['children']) && is_array($this->aConfiguration['children'])) {
                $aConfiguration = $this->aConfiguration['children'];
            }
        }
        
        if ($aConfiguration) {
            foreach ($aConfiguration as $aChild) {
                if ($this->isClassExists($aChild['type'])) {
                    if ($this->isPassedConditional($aChild) && $this->isPassedConditionals($aChild)) {
                        
                        /**
                         * @var RenderableAbstract $oInit
                         */
                        $childClass = $this->childClass;
                        $oInit      = new $childClass;
                        $oInit->setConfiguration($aChild);
                        $this->addElement($oInit);
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        throw new \Exception('The class '.$aChild['type'].' does not exist');
                    }
                }
            }
        }
        
        return $this;
    }
    
    /**
     * @param      $key
     * @param      $default
     *
     */
    protected function getAttribute($key, $default = "")
    {
        $val = "";
        if (property_exists($this, $key)) {
            $val = $this->$key;
        }
        
        if (empty($val)) {
            if (isset($this->aConfiguration[$key])) {
                $val = $this->aConfiguration[$key];
            }
        }
        
        if (empty($val)) {
            return $default;
        }
        
        return $val;
    }
    
    /**
     * @return RenderableAbstract
     * @throws \Exception
     */
    public function beforeRenderElements(): RenderableAbstract
    {
        $this->handleConfiguration(true);
        
        return $this;
    }
    
    /**
     * @return RenderableAbstract
     * @throws \Exception
     */
    public function beforeRenderElement(): RenderableAbstract
    {
        $this->handleConfiguration();
        
        $aOutput = [];
        if ($this->aElements) {
            foreach ($this->aElements as $child) {
                $aOutput[] = $child->render();
            }
        }
        
        $children = implode('', $aOutput);
        if (!empty($children)) {
            $breakElement = isset($this->aConfiguration['breakElement']) ? $this->aConfiguration['breakElement'] : " ";
            if (isset($this->aConfiguration['isChildrenAbove'])) {
                $this->content = $children.$breakElement.$this->content;
            } else {
                $this->content .= $breakElement.$children;
            }
        }
        
        return $this;
    }
    
    public function getElements()
    {
        return $this->aElements;
    }
    
    public function hasElements()
    {
        return !empty($this->getElements());
    }
    
    /**
     * @param $wrapperEl
     *
     * @return RenderableAbstract
     */
    public function setWrapperEl($wrapperEl): RenderableAbstract
    {
        $this->wrapperEl = $wrapperEl;
        
        return $this;
    }
}
