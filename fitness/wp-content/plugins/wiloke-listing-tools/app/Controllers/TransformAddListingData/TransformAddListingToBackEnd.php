<?php

namespace WilokeListingTools\Controllers\TransformAddListingData;

class TransformAddListingToBackEnd implements TransformAddListingInterface
{
    private $fieldType;
    private $output;
    private $maximum;

    public function __construct($fieldType, $maximum = 100)
    {
        $this->fieldType = $fieldType;
        $this->maximum   = absint($maximum);
    }

    public function input($input)
    {
        if (empty($input)) {
            $this->output = null;
        } else {
            $this->fieldType = str_replace('wil-', '', $this->fieldType);
            switch ($this->fieldType) {
                case 'multiple-checkbox':
                    if (is_array($input)) {
                        $this->output = array_slice($input, 0, $this->maximum);
                    } else {
                        $this->output = $input;
                    }
                    break;
                case 'select-tree':
                    if (!is_array($input)) {
                        $this->output = $input;
                    } else {
                        if (isset($input['id'])) {
                            $this->output = $input['id'];
                        } else {
                            $this->output = array_slice($input, 0, $this->maximum);
                            $this->output  = array_map(function ($item) {
                                return isset($item['id']) ? $item['id'] : $item;
                            }, $this->output);
                        }
                    }
                    break;
                case 'uploader':
                    if (isset($input['id'])) {
                        $this->output = $input;
                    } else {
                        $this->output = array_reduce(
                            $input,
                            function ($accumulator, $item) {
                                return $accumulator + [$item['id'] => $item['src']];
                            },
                            []
                        );
                    }
                    break;
                default:
                    $this->output = $input;
                    break;
            }
        }

        return $this;
    }

    /**
     * What expect format you want to return. Leave empty if you want to use get the default value
     *
     * @param string $format
     *
     * @return $this
     */
    public function format($format = 'default')
    {
        switch ($format) {
            case 'array':
                $this->output = (array)$this->output;
                break;
            case 'int':
                $this->output = absint($this->output);
                break;
            case 'bool':
            case 'boolean':
                $this->output = $this->output === true || in_array($this->output, ['true', 'enable', 'yes']);
                break;
            case 'yesno':
                $this->output = $this->output === 'true' || $this->output === true ? 'yes' : 'no';
                break;
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

    public function withKey($key)
    {
        if ($this->fieldType === 'uploader' && $this->maximum === 1) {
            if (!empty($this->output)) {
                return [$key => $this->output['src'], $key . '_id' => $this->output['id']];
            } else {
                return [$key => $this->output];
            }

        }

        return [$key => $this->output];
    }
}
