<?php

/**
 * Formulario para la configuracion de fichaje
 */
class Application_Form_ConfiguracionFichaje extends Fmo_Form_Abstract
{
    const E_CONF_1 = 'conf_1';
    const E_CONF_1_2 = 'conf_1_2';
    const E_GUARDAR = 'conf_1_guardar';


    public function init()
    {

        // Inicializando formulario
        $this->setAction($this->getView()->url())
        ->setLegend('Prueba')
        ->setMethod(self::METHOD_POST);

        $motivoFichaje = Application_Model_DbTable_Sigrh_ControlFichajeMotivo::findOneByColumn('indicador_asistencia', 'S');
        //CONF_1: Tipo de tiempo minimo de asistencia
        $selectConf1 = new Zend_Form_Element_Select(self::E_CONF_1);
        $selectConf1->setLabel("Tipo de tiempo mínimo de asistencia")
        ->setDescription("
            *Cambiar el <b>tipo de tiempo</b>, cambiara por defecto a <b>1</b> el <b>tiempo mínimo</b>
            <br>
            *Esta configuración afecta a los <b>Motivos de Control de Accesso</b>
            <br>
            *Para guardar los cambios en <b>Tiempo mínimo de asistencia</b> debe hacer click en el botón <b>Guardar</b>
        ")
        ->setAttrib("onchange","this.form.submit()")
        ->setValue($motivoFichaje->tipo_tiempo)
        ->addMultiOption("hour","Horas")
        ->addMultiOption("minute","Minutos");
        $this->addElement($selectConf1);

        //CONF_1_2: Tiempo mínimo de asistencia
        $selectConf1_2 = new Zend_Form_Element_Select(self::E_CONF_1_2);
        $selectConf1_2->setLabel("Tiempo mínimo de asistencia")//->setRequired(true);
        ->setValue($motivoFichaje->hrs_asistencia);
        if($motivoFichaje->tipo_tiempo == 'hour'){
            $selectConf1_2->addMultiOption("1","1 Hora")
            ->addMultiOption("2","2 Horas")
            ->addMultiOption("3","3 Horas")
            ->addMultiOption("4","4 Horas")
            ->addMultiOption("5","5 Horas")
            ->addMultiOption("6","6 Horas")
            ->addMultiOption("7","7 Horas")
            ->addMultiOption("8","8 Horas");
        } 
        if($motivoFichaje->tipo_tiempo == 'minute'){
            $selectConf1_2->addMultiOption("1","1 Minuto")
            ->addMultiOption("5","5 Minutos")
            ->addMultiOption("10","10 Minutos")
            ->addMultiOption("15","15 Minutos")
            ->addMultiOption("20","20 Minutos")
            ->addMultiOption("30","30 Minutos")
            ->addMultiOption("40","40 Minutos")
            ->addMultiOption("50","50 Minutos")
            ->addMultiOption("55","55 Minutos");
        } 
        $this->addElement($selectConf1_2);

        $btnGuardar = new Zend_Form_Element_Submit(self::E_GUARDAR, array('class' => 'submit'));
        $btnGuardar->setLabel('Guardar');
        $this->addElement($btnGuardar);

        // Aplicando css de la empresa 
        $this->setCustomDecorators();
    }

    public function isValid($data){

        // if (parent::isValid($data)) {
            
        //     $fechaTope = date('d-m-Y', strtotime(str_replace('/', '-', Application_Model_Fichaje::getMaxFechaCargada()->fecha_cargada)));
        //     $fechaDesde = date('d-m-Y', strtotime(str_replace('/', '-', $data[self::E_FECHA_DESDE])));
        //     $fechaHasta = date('d-m-Y', strtotime(str_replace('/', '-', $data[self::E_FECHA_HASTA])));
            
        //     if(strtotime($fechaDesde) > strtotime($fechaTope)) {
        //         $this->getElement(self::E_FECHA_DESDE)->addError('Excede fecha máxima: '.str_replace('-', '/',$fechaTope));
        //     }

        //     if(strtotime($fechaHasta) > strtotime($fechaTope)) {
        //         $this->getElement(self::E_FECHA_HASTA)->addError('Excede fecha máxima: '.str_replace('-', '/',$fechaTope));
        //     }

        // }


        return !$this->hasErrors();
    }
}