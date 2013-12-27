<?php

class Ebanx_Ebanx_Adminhtml_Model_System_Config_Source_Installments
{
    protected $_options = array(1, 2, 3, 4, 5, 6);

    public function toOptionArray()
    {
        $arr = array();

        foreach ($this->_options as $n)
        {
            $arr[] = array(
                'value' => $n
              , 'label' => $n
            );
        }

        return $arr;
    }
}
