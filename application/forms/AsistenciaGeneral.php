<?php

/**
 * Formulario para consulta de asistencia
 */
class Application_Form_AsistenciaGeneral extends Fmo_Form_Abstract
{
        const E_FECHA_DESDE = 'fecha_desde';
        const E_FECHA_HASTA = 'fecha_hasta';
        const E_FICHA       = 'ficha';
        const E_TIPO        = 'tipo';
        const E_BUSCAR      = 'buscar';
        
        //     const E_LOCALIDAD   = 'localidad';
        //     const E_TIPO        = 'tipo';

    public function init()
    {

        $fechaTope = str_replace('-', '/', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada);

        // Inicializando formulario
        $this->setAction($this->getView()->url())
        ->setLegend('Resumen General de Asistencia por Gerencia')
        ->setMethod(self::METHOD_POST);

        $txtFechaDesde = new Fmo_Form_Element_DatePicker(self::E_FECHA_DESDE);
        $txtFechaDesde->setLabel("Fecha desde:")
        ->setRequired()
        ->setValue($fechaTope)
        ->setAttrib("readonly", "")
        ->setAttrib("placeholder", "DD/MM/AAAA");
        $this->addElement($txtFechaDesde);

        $txtFechaHasta = new Fmo_Form_Element_DatePicker(self::E_FECHA_HASTA);
        $txtFechaHasta->setLabel("Fecha hasta:")
        ->setRequired()
        ->setValue($fechaTope)
        ->setAttrib("readonly", "")
        ->setAttrib("placeholder", "DD/MM/AAAA");
        $this->addElement($txtFechaHasta);

        // $txtTipo = new Zend_Form_Element_Radio(self::E_TIPO);
        // $txtTipo->setLabel("Tipo de consulta:")
        // ->setRequired()
        // ->setMultiOptions(array("1" => "Asistencia","0" => "Inasistencia"));
        // $this->addElement($txtTipo);

     
        $btnBuscar = new Zend_Form_Element_Submit(self::E_BUSCAR, array('class' => 'submit'));
        $btnBuscar->setLabel('Buscar');
        $this->addElement($btnBuscar);

        // Aplicando css de la empresa 
        $this->setCustomDecorators();
    }

    public function isValid($data){

        if (parent::isValid($data)) {
            
            $fechaTope = date('d-m-Y', strtotime(str_replace('/', '-', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada)));
            $fechaDesde = date('d-m-Y', strtotime(str_replace('/', '-', $data[self::E_FECHA_DESDE])));
            $fechaHasta = date('d-m-Y', strtotime(str_replace('/', '-', $data[self::E_FECHA_HASTA])));
            
            if(strtotime($fechaDesde) > strtotime($fechaTope)) {
                $this->getElement(self::E_FECHA_DESDE)->addError('Excede fecha máxima: '.str_replace('-', '/',$fechaTope));
            }

            if(strtotime($fechaHasta) > strtotime($fechaTope)) {
                $this->getElement(self::E_FECHA_HASTA)->addError('Excede fecha máxima: '.str_replace('-', '/',$fechaTope));
            }

        }


        return !$this->hasErrors();
    }
}