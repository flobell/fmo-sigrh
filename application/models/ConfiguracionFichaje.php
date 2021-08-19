<?php

/**
 * Clase modelo para las consultas de configuracion del sistema
 *
 * @author Pedro Flores
 */
class Application_Model_ConfiguracionFichaje {



    public static function setHorasAsistencia($horas){
        $tblFichajeMotivo = new Application_Model_DbTable_Sigrh_ControlFichajeMotivo();

        $usuario = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::SIGLADO};

        $values = array(
            'hrs_asistencia' => $horas,
            'fecha_mod' => new Zend_Db_Expr("now()"),
            'usr_mod' => $usuario
        );

        $where = $tblFichajeMotivo->getAdapter()->quoteInto("indicador_asistencia = ?",'S');
         
        $tblFichajeMotivo->update($values, $where);
    }

    public static function setTipoTiempoAsistencia($tipo){
        $tblFichajeMotivo = new Application_Model_DbTable_Sigrh_ControlFichajeMotivo();

        $usuario = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::SIGLADO};
        
        $values = array(
            'hrs_asistencia' => '1',
            'tipo_tiempo' => $tipo,
            'fecha_mod' => new Zend_Db_Expr("now()"),
            'usr_mod' => $usuario
        );
         
        $where = $tblFichajeMotivo->getAdapter()->quoteInto("indicador_asistencia = ?",'S');
         
        $tblFichajeMotivo->update($values, $where);
    }
}