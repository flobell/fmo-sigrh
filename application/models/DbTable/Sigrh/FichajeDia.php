<?php

/**
 * Clase de tabla sch_sigrh.fichaje
 *
 * @author Pedro Flores - fmo16554@ferrominera.gob.ve
 */
class Application_Model_DbTable_Sigrh_FichajeDia extends Application_Model_DbTable_Sigrh_Abstract
{
    // protected $_schema   = 'sch_sigrh';
    protected $_name = 'control_fichaje_dia';
    protected $_primary  = array("cedula","fecdia");
    // protected $_sequence = false;
}
 