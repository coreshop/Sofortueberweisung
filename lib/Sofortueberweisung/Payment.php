<?php


class Sofortueberweisung_Payment
{
    protected $sofortueberweisung = null;
    
    public function __construct($configKey)
    {
        $this->sofortueberweisung = new Sofortueberweisung($configKey);
    }
    
    public function __call($method, $args) 
    {
        if($this->sofortueberweisung instanceof Sofortueberweisung)
        {
            if(method_exists($this->sofortueberweisung, $method))
            {
                return call_user_func_array(array($this->sofortueberweisung, $method), $args);
            }
        }
    }
}


