<?php

/**
 * Controlador principal
 */
class IndexController extends Fmo_Controller_Action_Abstract
{

    /**
     * Acción por defecto
     */
    public function indexAction()
    {   

    }


    public function pruebaAction()
    {   
        //Zend_Debug::dd(Application_Model_Biostar::getUsuarioBiostar('24848217'));
        Zend_Debug::dd(Application_Model_Biostar::getAllPrueba());
    }

}
