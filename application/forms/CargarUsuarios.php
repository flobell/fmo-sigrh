<?php

require_once 'PHPExcel/IOFactory.php';

/**
 * Formulario para cargar usuarios de biostar
 */
class Application_Form_CargarUsuarios extends Fmo_Form_Abstract
{
        const E_ACCION = 'accion';
        const E_ARCHIVO = 'archivo';
        const E_CARGAR = 'cargar';

    public function init()
    {
        // Inicializando formulario
        $this->setAction($this->getView()->url())
        ->setLegend('Cargar usuarios para Activación/Desactivacion masiva')
        ->setMethod(self::METHOD_POST);

        //TIPO DE ACCION ACTIVAR/DESACTIVAR
        $eAccion = new Zend_Form_Element_Select(self::E_ACCION);
        $eAccion->setLabel("Tipo de acción:")->setRequired(true)
        ->addMultiOption("",'Seleccione...')
        ->addMultiOption("N",'Activación')
        ->addMultiOption("Y",'Desactivación');
        $this->addElement($eAccion);

        $eArchivo = new Zend_Form_Element_File(self::E_ARCHIVO);
        $eArchivo->setLabel("Archivo:")->setRequired(true)
        ->setDescription('Seleccione un archivo de Hoja de Cálculo (FORMATOS VÁLIDOS: <b>xls</b>, <b>xlsx</b>, <b>ods</b>)')
        ->addValidator('File_Size', false, array('min' => '1kB', 'max' => '8MB'))
        ->addValidator('File_Extension', false, 'xls,ods,xlsx');
        //->addValidator('File_MimeType', false, 'application/vnd.ms-excel,application/vnd.ms-office,application/vnd.oasis.opendocument.spreadsheet')
        $this->addElement($eArchivo);


        $eCargar = new Zend_Form_Element_Submit(self::E_CARGAR, array('class' => 'submit'));
        $eCargar->setIgnore(true)->setLabel('Cargar');
        $this->addElement($eCargar);


        // Aplicando css de la empresa 
        $this->setCustomDecorators();
    }

    // public function isValid($data){

    //     if (parent::isValid($data)) {

    //     }

    //     return !$this->hasErrors();
    // }

    public function cargarExcel(){

        try{
            Zend_Db_Table::getDefaultAdapter()->beginTransaction();

            $tGrupoMasivo = new Application_Model_DbTable_Sigrh_GrupoMasivo();
            $usr_cre = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::SIGLADO};
            $nuevoGrupo = $tGrupoMasivo->createRow();
            $nuevoGrupo->usr_cre = $usr_cre;        
            $nuevoGrupo->accion = $this->getElement(self::E_ACCION)->getValue();     
            $nuevoGrupo->save();
            $idGrupo = $nuevoGrupo->id;



            $archivo = $this->getElement(self::E_ARCHIVO);    
            if($archivo->receive()){
                
                $excel = PHPExcel_IOFactory::load($archivo->getFileName());
                $registros = $excel->setActiveSheetIndex()->toArray(null, true, false);

                if (empty($registros)) {
                    $archivo->addError('El archivo no contiene registros');
                } else {

                    $tGrupoMasivoUsuario = new Application_Model_DbTable_Sigrh_GrupoMasivoUsuario();
                    foreach($registros as $registro => $fila){
                        if($fila[0]==NULL) break;
                        if($registro>0){
                            $nuevoGrupoUsuario = $tGrupoMasivoUsuario->createRow();
                            $nuevoGrupoUsuario->id_grupo = $idGrupo;
                            $nuevoGrupoUsuario->cedula = $fila[0];
                            $nuevoGrupoUsuario->estado_biostar = $this->getElement(self::E_ACCION)->getValue();
                            $nuevoGrupoUsuario->usr_cre = $usr_cre;
                            $nuevoGrupoUsuario->save();
                        }
                        $registro++;
                    }

                }

            } else {
                $archivo->addError('Fallo al cargar del archivo. (Revise el formato)');
            }

            Zend_Db_Table::getDefaultAdapter()->commit();
        } catch (Exception $e) {
            Zend_Db_Table::getDefaultAdapter()->rollBack();
            throw $e;
        }

        return $idGrupo;
    }
}
