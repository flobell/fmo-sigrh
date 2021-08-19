<?php

class Application_Model_DbTable_Rpsdatos_Calendario extends Application_Model_DbTable_Rpsdatos_Abstract
{
    // protected $_schema   = 'sch_rpsdatos';
    protected $_name     = 'sn_tcalend';
    protected $_primary  = array('cale_cias','cale_fecdia','cale_ano');
    protected $_sequence = false;
}
