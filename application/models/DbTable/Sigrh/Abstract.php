<?php

/**
 * Clase abstracta de las DbTable Sigrh
 *
 * @author Pedro Flores - fmo16554@ferrominera.gob.ve
 */
class Application_Model_DbTable_Sigrh_Abstract extends Fmo_DbTable_Abstract
{

    protected $_schema = 'sch_sigrh';
    protected $_sequence = false;
    // protected $multidbName = 'sigrh';

    // public function init()
    // {
    //     $this->_db = Zend_Controller_Front::getInstance()
    //         ->getParam('bootstrap')
    //         ->getPluginResource('multidb')
    //         ->getDb($this->multidbName);
    // }

}
