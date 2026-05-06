<?php

namespace WilokeListingTools\Framework\Helpers\Collection;

class ArrayCollection implements CollectionInterface
{
    private $input;
    private $output;
    private $helpCenter;
    
    public function __construct($input)
    {
        $this->input = $input;
    }
    
    /**
     * @param $target
     *
     * @return $this
     */
    public function pluck($target)
    {
        $this->output = isset($this->input[$target]) ? $this->input[$target] : null;
        return $this;
    }
    
    public function except($target)
    {
        if (is_array($this->output)) {
            unset($this->output[$target]);
        }
        
        return $this;
    }
    
    /**
     * @param $target
     *
     * @return $this
     */
    public function deepPluck($target)
    {
        $parsedTarget = explode('->', $target);
        
        foreach ($parsedTarget as $pluck) {
            $this->pluck($pluck);
            if (!empty($this->output)) {
                $this->input = $this->output;
            } else {
                return $this;
            }
        }
        
        return $this;
    }
    
    public function format($format = 'default')
    {
        switch ($format) {
            case 'array':
                $this->output = (array)$this->output;
                break;
            case 'int':
                $this->output = intval($this->output);
                break;
            case 'float':
                $this->output = floatval($this->output);
                break;
            case 'boolean':
            case 'bool':
                $this->output = $this->output === true || in_array($this->output, ['yes', 'enable', 'true', 1, '1']);
                break;
            case 'string':
                $this->output = (string)$this->output;
                break;
        }
        
        return $this;
    }
    
    /**
     * This method allows picking up a specify value and then using it as a new key
     *
     * EG: We have an array like this [0 => ['x' => 'z']]. Now we want to convert this array to ['z' => ['x' => 'z']]
     * We can use this method
     *
     * @param $target
     *
     * @return $this
     */
    public function magicKey($target)
    {
        if (empty($this->output)) {
            $this->output = $this->input;
        }
        
        if (is_array($this->output)) {
            if (isset($this->output[$target])) {
                $this->output[$target] = $this->output;
            }
        }
        
        return $this;
    }
    
    /**
     * It's the same magic key, but instead of converting a single array, it will convert a group. Note that this array
     * must be a Numeric array
     *
     * @param $target
     *
     * @return $this
     */
    public function magicKeyGroup($target)
    {
        if (empty($this->output)) {
            $this->output = $this->input;
        }
        
        if (is_array($this->output) && isset($this->output[0]) && isset($this->output[0][$target])) {
            $this->helpCenter = $target;
            $this->output     = array_reduce(
                $this->output,
                function ($aAccumulator, $aItem) {
	                if(isset($aItem[$this->helpCenter])){
		                return $aAccumulator + [$aItem[$this->helpCenter] => $aItem];
	                }

	                return $aAccumulator;
                },
                []
            );
            $this->helpCenter = '';
        }
        
        return $this;
    }
    
    public function output($std = null)
    {
        if ($std === null) {
            return $this->output;
        }
        
        return empty($this->output) ? $std : $this->output;
    }
}
