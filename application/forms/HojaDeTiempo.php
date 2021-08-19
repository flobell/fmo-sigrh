<?php

/**
 * Formulario para hoja de tiempo
 */
class Application_Form_HojaDeTiempo extends Fmo_Form_Abstract
{
        const E_FECHA_DESDE = 'fecha_desde';
        const E_FECHA_HASTA = 'fecha_hasta';
        const E_FICHA       = 'ficha';
        const E_BUSCAR      = 'buscar';


        // Inicializando formulario
        public function init()
    {


        $fechaTope = str_replace('-', '/', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada);


        // Inicializando formulario
        $this->setAction($this->getView()->url())
            ->setLegend('Consulta de Hoja de Tiempo del Trabajador')
            ->setMethod(self::METHOD_POST);

        $txtFechaDesde = new Fmo_Form_Element_DatePicker(self::E_FECHA_DESDE);
        $txtFechaDesde->setLabel("Fecha desde: ")
                ->setValue($fechaTope)
                ->setAttrib("readonly", "")
                ->setAttrib("placeholder", "DD/MM/AAAA");
        $this->addElement($txtFechaDesde);

        $txtFechaHasta = new Fmo_Form_Element_DatePicker(self::E_FECHA_HASTA);
        $txtFechaHasta->setLabel("Fecha hasta: ")
                ->setValue($fechaTope)
                ->setAttrib("readonly", "")
                ->setAttrib("placeholder", "DD/MM/AAAA");
        $this->addElement($txtFechaHasta);
        
        $txtFicha = new Zend_Form_Element_Text(self::E_FICHA);
        $txtFicha->setLabel("Ficha de Trabajador:")
        ->setRequired()
        ->addValidator('Digits', true)
        ->setAttrib('style', 'text-align: center;')
        ->setAttrib('size', '5')
        ->setAttrib('maxlength', '5')
        ->setAttrib("placeholder","12345");
        $this->addElement($txtFicha);

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

            $personal = new Fmo_Model_Personal;
            $cecoTrabajador = $personal->findOneByFicha($data[self::E_FICHA])->id_centro_costo;
            $cedusuario = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::CEDULA};
            $datosPermiso = Application_Model_PermisoCeco::getAllPermisoCeco($cedusuario)->toArray();
            $bandera = false;
            foreach($datosPermiso as $permiso){
                if(strlen($permiso['ceco_ceco']) == 1 && $permiso['ceco_ceco'] == '0') {
                    $bandera = true;
                    break;
                }
    
                if(strlen($permiso['ceco_ceco']) == 2 && $permiso['ceco_ceco'] == substr($cecoTrabajador,0,1)) {
                    $bandera = true;
                    break;
                }
                
                if(strlen($permiso['ceco_ceco']) == 5 && $permiso['ceco_ceco'] == substr($cecoTrabajador,0,4)) {
                    $bandera = true;
                    break;
                }
    
            }

            if(!$bandera){
                $this->getElement(self::E_FICHA)->addError('Permiso denegado para visualizar ficha: '.$data[self::E_FICHA]);
            }

        }

        return !$this->hasErrors();
    }
}