<?php

/**
 * Clase modelo para las consultas de configuracion del sistema
 *
 * @author Pedro Flores
 */
class Application_Model_Motivo {

    public static function getAllMotivos(){
        $tblFichajeMotivo = new Application_Model_DbTable_Sigrh_ControlFichajeMotivo();

        $select = $tblFichajeMotivo->select()->setIntegrityCheck(false)
        ->from(
            array('t1' => $tblFichajeMotivo->info(Zend_Db_Table::NAME)), 
            array(
                'codigo_biostar' => 't1.codigo_biostar',
                'descripcion_biostar' => 't1.descripcion_biostar',
                'indicador_asistencia' => 't1.indicador_asistencia',
                'hrs_asistencia' => 't1.hrs_asistencia',
                'tipo_tiempo' => new Zend_Db_Expr("
                    CASE 
                        WHEN t1.tipo_tiempo = 'hour' THEN 'horas'
                        WHEN t1.tipo_tiempo = 'minute' THEN 'minutos'
                    END
                "),
                'fecha_mod' => new Zend_Db_Expr("to_char(t1.fecha_mod,'dd-mm-yyyy HH12:MI:ss AM')"),
                'usr_mod' => 't1.usr_mod'
            ), 
            $tblFichajeMotivo->info(Zend_Db_Table::SCHEMA)
        )
        ->order("t1.codigo_biostar ASC");

        //Zend_Debug::dd($select->__toString());
        //Zend_Debug::dd($select->assemble()); //Para Visualizar codigo Query
        //Zend_Debug::dd(json_encode($tFichaje->fetchAll($select)->toArray())); //Para probar resultado
        return $tblFichajeMotivo->getAdapter()->fetchAll($select);
    }

}