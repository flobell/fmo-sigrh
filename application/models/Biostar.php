<?php

/**
 * Clase modelo para las consultas al sistema biostar
 *
 * @author Pedro Flores
 */
class Application_Model_Biostar {

    public static function getAllPrueba(){
        $tblTPrueba = new Application_Model_DbTable_Biostar_Prueba();

        $select = $tblTPrueba->select()->setIntegrityCheck(false)
        ->from(
            array('t1' => $tblTPrueba->info(Zend_Db_Table::NAME)), 
            array(
                'id' => 't1.id',
                'nombre' => 't1.nombre',
                'estado' => 't1.estado',
            ), 
            $tblTPrueba->info(Zend_Db_Table::SCHEMA)
        );

        //Zend_Debug::dd($select->__toString());
        //Zend_Debug::dd($select->assemble()); //Para Visualizar codigo Query
        //Zend_Debug::dd(json_encode($tFichaje->fetchAll($select)->toArray())); //Para probar resultado
        return $tblTPrueba->getAdapter()->fetchAll($select);
    }

    public static function getUsuarioBiostar($cedula){
        $tblUsuario = new Application_Model_DbTable_Biostar_Usuario();
        
        $select = $tblUsuario->select()->setIntegrityCheck(false)
        ->from(
            array('t1' => $tblUsuario->info(Zend_Db_Table::NAME)), 
            array(
                't1.*'
            ), 
            $tblUsuario->info(Zend_Db_Table::SCHEMA)
        )
        ->where("t1.usrid = '$cedula'");

        //Zend_Debug::dd($select->__toString());
        //Zend_Debug::dd($select->assemble()); //Para Visualizar codigo Query
        //Zend_Debug::dd(json_encode($tFichaje->fetchAll($select)->toArray())); //Para probar resultado
        return $tblUsuario->getAdapter()->fetchAll($select);
    }
}