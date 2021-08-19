<?php

/**
 * Formulario para consulta de resumen de asistencia por gerencia
 */
class Application_Form_ResumenAsistenciaGerencia extends Fmo_Form_Abstract
{
        const E_FECHA_DESDE = 'fecha_desde';
        const E_FECHA_HASTA = 'fecha_hasta';
        const E_LOCALIDAD   = 'localidad';
        const E_BUSCAR      = 'buscar';
        

    public function init()
    {

        $fechaTope = str_replace('-', '/', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada);

        // Inicializando formulario
        $this->setAction($this->getView()->url())
        ->setLegend('[LISTADO] Resumen de Asistencia por Gerencia ')
        ->setMethod(self::METHOD_POST);

        $txtFechaDesde = new Fmo_Form_Element_DatePicker(self::E_FECHA_DESDE);
        $txtFechaDesde->setLabel("Fecha desde:")->setRequired()
        ->setValue($fechaTope)
        ->setAttrib("readonly", "")
        ->setAttrib("placeholder", "DD/MM/AAAA");
        $this->addElement($txtFechaDesde);

        $txtFechaHasta = new Fmo_Form_Element_DatePicker(self::E_FECHA_HASTA);
        $txtFechaHasta->setLabel("Fecha hasta:")->setRequired()
        ->setValue($fechaTope)
        ->setAttrib("readonly", "")
        ->setAttrib("placeholder", "DD/MM/AAAA");
        $this->addElement($txtFechaHasta);

        $sltLocalidad = new Zend_Form_Element_Select(self::E_LOCALIDAD);
        $sltLocalidad->setLabel('Localidad:')->setRequired()
        ->addMultiOption('0', 'TODOS')
        ->addMultiOption('1', 'PUERTO ORDAZ')
        ->addMultiOption('4', 'CIUDAD PIAR');
        $this->addElement($sltLocalidad);

        $btnBuscar = new Zend_Form_Element_Submit(self::E_BUSCAR, array('class' => 'submit'));
        $btnBuscar->setLabel('Consultar');
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

            if(strtotime($fechaDesde) > strtotime($fechaHasta)) {
                $this->getElement(self::E_FECHA_DESDE)->addError("'Fecha desde' no puede ser mayor a 'Fecha hasta'");
            }

        }


        return !$this->hasErrors();
    }
}