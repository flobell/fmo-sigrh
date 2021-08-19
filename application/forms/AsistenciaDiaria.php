<?php

/**
 * Formulario para Consulta de Asistencia Diaria
 */
class Application_Form_AsistenciaDiaria extends Fmo_Form_Abstract
{
        const E_FECHA_DESDE = 'fecha_desde';
        const E_FECHA_HASTA = 'fecha_hasta';
        const E_FICHA       = 'ficha';
        const E_GERENCIA    = 'gerencia';
        const E_BUSCAR      = 'buscar';
        
        //     const E_LOCALIDAD   = 'localidad';
        //     const E_TIPO        = 'tipo';

    public function init()
    {

        $fechaTope = str_replace('-', '/', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada);

        //Zend_Debug::dd($fechaTope);
        // Inicializando formulario
        $this->setAction($this->getView()->url())
        ->setLegend('Consulta Diara de Asistencia')
        ->setMethod(self::METHOD_POST);

        $txtFechaDesde = new Fmo_Form_Element_DatePicker(self::E_FECHA_DESDE);
        $txtFechaDesde->setLabel("Fecha desde:")
        ->setValue($fechaTope)
        ->setRequired()
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

        $gerencia = new Zend_Form_Element_Select(self::E_GERENCIA);
        $gerencia->setLabel("Gerencia:")
        ->addMultiOption("",'TODAS LAS GERENCIAS')
        ->addMultiOptions(Fmo_DbTable_Rpsdatos_CentroCosto::getPairs('ceco_ceco','ceco_descri',"length(ceco_ceco) = 2 AND ceco_status = 'A'"));
        // ->setRequired(true);
        $this->addElement($gerencia);

        $txtFicha = new Zend_Form_Element_Text(self::E_FICHA);
        $txtFicha->setLabel("Ficha de Trabajador:")
        ->setDescription("OPCIONAL")
        ->addValidator('Digits', true)
        ->setAttrib('style', 'text-align: center;')
        ->setAttrib('size', '5')
        ->setAttrib('maxlength', '5')
        ->setAttrib("placeholder","12345");
        //->setRequired(true);
        $this->addElement($txtFicha);

        $btnBuscar = new Zend_Form_Element_Submit(self::E_BUSCAR, array('class' => 'submit'));
        $btnBuscar->setLabel('Buscar');
        $this->addElement($btnBuscar);

        // Aplicando css de la empresa 
        $this->setCustomDecorators();
    }

    public function isValid($data){

        if($data[self::E_FICHA] != NULL){
            $this->getElement(self::E_GERENCIA)->setRequired(false);
        }

        if (parent::isValid($data)) {
            
            $fechaTope = date('d-m-Y', strtotime(str_replace('/', '-', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada)));
            $fechaDesde = date('d-m-Y', strtotime(str_replace('/', '-', $data[self::E_FECHA_DESDE])));
            $fechaHasta = date('d-m-Y', strtotime(str_replace('/', '-', $data[self::E_FECHA_HASTA])));

            //VALIDACION DE FECHA DESDE DEBE SER MENOR O IGUAL QUE FECHA HASTA
            if(strtotime($fechaDesde) > strtotime($fechaHasta)) {
                $this->getElement(self::E_FECHA_DESDE)->addError('Fecha desde no puede ser mayor a Fecha hasta');
            }

            //VALIDACION DE FECHA DESDE DEBE SER O IGUAL A LA FECHA TOPE
            if(strtotime($fechaDesde) > strtotime($fechaTope)) {
                $this->getElement(self::E_FECHA_DESDE)->addError('Excede fecha máxima: '.str_replace('-', '/',$fechaTope));
            }

            //VALIDACION DE FECHA HASTA DEBE SER O IGUAL A LA FECHA TOPE
            if(strtotime($fechaHasta) > strtotime($fechaTope)) {
                $this->getElement(self::E_FECHA_HASTA)->addError('Excede fecha máxima: '.str_replace('-', '/',$fechaTope));
            }

            if($data[self::E_FICHA] == NULL){
                //VALIDACION DE RANGO DE FECHA NO DEBE SUPERAR LOS 31 DIAS
                $desde = strtotime(str_replace('/', '-', $data[self::E_FECHA_DESDE]));
                $hasta = strtotime(str_replace('/', '-', $data[self::E_FECHA_HASTA]));
                $timediff = $hasta - $desde;
                $dias = intval(round($timediff / (60 * 60 * 24))+1);
                if($dias > 31){
                    $this->getElement(self::E_FECHA_HASTA)->addError('El rango de fecha no puede superar los 31 días');
                }
            }   

            // //VALIDACION DE FICHA DEBE PERTENECER A LA GERENCIA SELECCIONADA
            // if($data[self::E_FICHA] != NULL){
            //     $personal = new Fmo_Model_Personal;
            //     $gerencia = $personal->findOneByFicha($data[self::E_FICHA])->organigrama[2]->id_centro_costo;
            //     if($gerencia != $data[self::E_GERENCIA]){
            //         $this->getElement(self::E_FICHA)->addError('La ficha introducida no pertenece a la gerencia seleccionada');
            //     }
            // }


        }

        return !$this->hasErrors();
    }
}