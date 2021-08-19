<?php

/**
 * Clase abstracta de las DbTable Biostar
 *
 * @author Pedro Flores - fmo16554@ferrominera.gob.ve
 */
class Application_Model_DbTable_Biostar_Abstract extends Fmo_DbTable_Abstract
{

    protected $_schema = 'biostar2_ac';
    protected $_sequence = false;
    // protected $multidbName = 'biostar';

    // public function init()
    // {
    //     $this->_db = Zend_Controller_Front::getInstance()
    //         ->getParam('bootstrap')
    //         ->getPluginResource('multidb')
    //         ->getDb($this->multidbName);
    // }

}
