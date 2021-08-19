<?php

/**
 * Clase que controla los datos del recurso de seguridad.
 *
 * @author Jhimm Maita
 */

class Application_Model_PermisoCeco
{

    /**
     * Método que permite crear un registro de perfiles.
     *
     * @param 
     *
     * @return integer
     */
    
    public static function getAllPermisoCeco($cedusuario)
    {
 
        $tPermisoCeco = new Application_Model_DbTable_Sasinzf_PermisoCeco();
        
        $sql = $tPermisoCeco->select()
            ->from(array('p' => $tPermisoCeco->info(Zend_Db_Table::NAME)), 
                array( 
                    "p.cedula",                       
                    "p.ceco_ceco", 
                    "ceco" => new Zend_Db_Expr("substr(p.ceco_ceco,1,2)"),
                    "p.fecha_inicio",
                    "p.fecha_fin",
                    "p.id_sistema",
                    "p.ps_usrcre",
                    "p.fh_usrcre"
                ), $tPermisoCeco->info(Zend_Db_Table::SCHEMA))
            ->where("p.cedula = ?", $cedusuario)
            ->where("p.id_sistema = 'sigrh'");    
                
            //->order('p.orden ASC');
    
        //print_r($sql->__tostring());
        
        return $tPermisoCeco->fetchAll($sql);
    
    }   


    public static function puedeVerGerencia($gerencia){
        $usuario = Fmo_Model_Seguridad::getUsuarioSesion();
        $tPermisoCeco = new Application_Model_DbTable_Sasinzf_PermisoCeco();

        $select = $tPermisoCeco->select()->setIntegrityCheck(false)
        ->from(
            array('p' => $tPermisoCeco->info(Zend_Db_Table::NAME)), 
            array( 
                "cedula"        =>"p.cedula",                       
                "ceco_ceco"     =>"p.ceco_ceco", 
                "ceco"          => new Zend_Db_Expr("substr(p.ceco_ceco,1,2)"),
                "fecha_inicio"  =>"p.fecha_inicio",
                "fecha_fin"     =>"p.fecha_fin",
                "id_sistema"    =>"p.id_sistema",
                "ps_usrcre"     =>"p.ps_usrcre",
                "fh_usrcre"     =>"p.fh_usrcre"
            ), 
            $tPermisoCeco->info(Zend_Db_Table::SCHEMA)
        )
        ->where("p.cedula = $usuario->cedula")
        ->where("p.id_sistema = 'sigrh'");   
        
        //Zend_Debug::dd($select->assemble()); 
        $permisos = $tPermisoCeco->getAdapter()->fetchAll($select);
        // Zend_Debug::dd($permisos);
        // Zend_Debug::dd($trabajador->id_centro_costo.'<br>'.
        // substr($trabajador->id_centro_costo,0,2).'<br>'.
        // substr($trabajador->id_centro_costo,0,5));
        foreach($permisos as $permiso){
            if($permiso->ceco_ceco == '0') return true;
            if($permiso->ceco == $gerencia) return true;
        }

        return false;
        //$datosPermiso = getAllPermisoCeco($usuario->cedula)->toArray();

    }


    public static function puedeVerTrabajador($trabajador){
        $usuario = Fmo_Model_Seguridad::getUsuarioSesion();
        $tPermisoCeco = new Application_Model_DbTable_Sasinzf_PermisoCeco();

        $select = $tPermisoCeco->select()->setIntegrityCheck(false)
        ->from(
            array('p' => $tPermisoCeco->info(Zend_Db_Table::NAME)), 
            array( 
                "cedula"        =>"p.cedula",                       
                "ceco_ceco"     =>"p.ceco_ceco", 
                "ceco"          => new Zend_Db_Expr("substr(p.ceco_ceco,1,2)"),
                "fecha_inicio"  =>"p.fecha_inicio",
                "fecha_fin"     =>"p.fecha_fin",
                "id_sistema"    =>"p.id_sistema",
                "ps_usrcre"     =>"p.ps_usrcre",
                "fh_usrcre"     =>"p.fh_usrcre"
            ), 
            $tPermisoCeco->info(Zend_Db_Table::SCHEMA)
        )
        ->where("p.cedula = $usuario->cedula")
        ->where("p.id_sistema = 'sigrh'");   
        
        //Zend_Debug::dd($select->assemble()); 
        $permisos = $tPermisoCeco->getAdapter()->fetchAll($select);
        // Zend_Debug::dd($permisos);
        // Zend_Debug::dd($trabajador->id_centro_costo.'<br>'.
        // substr($trabajador->id_centro_costo,0,2).'<br>'.
        // substr($trabajador->id_centro_costo,0,5));
        foreach($permisos as $permiso){
            if($permiso->ceco_ceco == '0') return true;
            if($permiso->ceco == substr($trabajador->id_centro_costo,0,2)) return true;
            if($permiso->ceco_ceco == substr($trabajador->id_centro_costo,0,5)) return true;
        }

        return false;
        //$datosPermiso = getAllPermisoCeco($usuario->cedula)->toArray();

    }


}
