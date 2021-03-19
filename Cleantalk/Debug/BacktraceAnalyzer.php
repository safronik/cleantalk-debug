<?php

namespace Cleantalk\Debug;

/**
 * Class BacktraceAnalyzer
 * @package Cleantalk\Debug
 */
class BacktraceAnalyzer {
    
    /**
     * @var array
     */
    private $backtrace;
    
    /**
     * @var string
     */
    private $root;
    
    public $current;
    
    public function __construct(){
        
        $args = func_get_args();
        $backtrace = $args[0];
        $root = $args[1];
        
        $this->backtrace = $backtrace;
        $this->root = $root;
    }
    
    public function selectElementByFunctionName( $function_name ){
        foreach( $this->backtrace as $element ){
            if( $element['function'] === $function_name ){
                $this->current = $element;
                break;
            }
        }
        
        return $this;
    }
    
    public function selectElementByArgumentValue( $argument_value ){
        foreach( $this->backtrace as $element ){
            if( $element['args'] ){
                foreach( $element['args'] as $arg ){
                    if( $arg === $argument_value ){
                        $this->current = $element;
                        break 2;
                    }
                }
            }
        }
        
        return $this;
    }
    
}