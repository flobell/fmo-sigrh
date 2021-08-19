<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class ConfiguracionController extends Fmo_Controller_Action_Abstract
{


    
    /** 
     * Metodo para habilitar las hojas de estilo Bootsrap y librerias javascript actualizada JqueryX
     * 
     */
    public function enableBootstrap(){
        $this->view->bootstrap()->enable();
        $this->view->jQueryX()->enable();
        $this->view->bootstrap()->jsEnable();
    }


    /**
     * Acción por defecto
     */
    public function indexAction()
    {   

        //$this->redirect('public/manual.pdf');
        //$this->_helper->redirector($this->baseUrl('public/SIGRH - Manual de Usuario.pdf'));
        
    }

    public function fichajeAction()
    {   
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        $formulario = new Application_Form_ConfiguracionFichaje();
        $this->view->formulario = $formulario;
        $request = $this->getRequest();

        $motivos = Application_Model_Motivo::getAllMotivos();
        $this->view->motivos = $motivos;

        
        try{
            if($request->isPost()) {
                $post = $request->getPost();

                if (isset($post[Application_Form_ConfiguracionFichaje::E_GUARDAR])) {
                    $value = $post[Application_Form_ConfiguracionFichaje::E_CONF_1_2];
                    //Zend_Debug::dd($value);
                    Application_Model_ConfiguracionFichaje::setHorasAsistencia($value);
                    $this->addMessageSuccessful("Cambio guardado con exito!");
                    $this->redirect('default/configuracion/fichaje');
                }

                if (isset($post[Application_Form_ConfiguracionFichaje::E_CONF_1])) {
                    $value = $post[Application_Form_ConfiguracionFichaje::E_CONF_1];
                    //Zend_Debug::dd($value);
                    Application_Model_ConfiguracionFichaje::setTipoTiempoAsistencia($value);
                    $this->addMessageSuccessful("Cambio guardado con exito!");
                    $this->redirect('default/configuracion/fichaje');
                }
                

            }
        } catch(Exception $ex){
            $this->addMessageError($ex->getMessage());
            $this->redirect('default/configuracion/fichaje');
        }
        
    }
    
}