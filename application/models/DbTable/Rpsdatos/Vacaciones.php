<?php

class Application_Model_DbTable_Rpsdatos_Vacaciones extends Application_Model_DbTable_Rpsdatos_Abstract
{
    // protected $_schema = 'sch_rpsdatos';
    protected $_name = 'sn_tvaca';
    protected $_primary = array('vaca_cedula','vaca_fecvac');
    protected $_sequence = false;

}
