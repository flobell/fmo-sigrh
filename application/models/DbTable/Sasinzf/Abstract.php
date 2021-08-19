<?php

/**
 * Clase abstracta de las DbTable Sasinzf
 *
 * @author Pedro Flores - fmo16554@ferrominera.gob.ve
 */
class Application_Model_DbTable_Sasinzf_Abstract extends Fmo_DbTable_Abstract
{

    protected $_schema = 'sch_sasinzf';
    protected $_sequence = false;
    protected $multidbName = 'sasinzf';

    // public function init()
    // {
    //     $this->_db = Zend_Controller_Front::getInstance()
    //         ->getParam('bootstrap')
    //         ->getPluginResource('multidb')
    //         ->getDb($this->multidbName);
    // }

}
