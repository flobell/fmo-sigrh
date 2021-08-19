<?php

/**
 * Controlado de consultas ajax
 */
class AjaxController extends Zend_Controller_Action
{

     /**
     * Inicialización del controlador
     */
    public function init() {
        $context = $this->_helper->ajaxContext();
        foreach (get_class_methods(__CLASS__) as $method) {
            if (strpos($method, 'Action') !== false) {
                $context->addActionContext($method, 'json');
            }
        }
        $context->initContext();
    }


    public function asistenciadetalladaAction()
    {
        //esta accion no usara layout.phtml
        $this->_helper->layout->disableLayout();
        //esta accion no renderizara su contenido en asistenciadetallada.phtml
        $this->_helper->viewRenderer->setNoRender();
        
        $desde = $this->getParam("desde");
        $hasta = $this->getParam("hasta");
        $gerencia = $this->getParam("gerencia");


        if($desde && $hasta && $gerencia){

            $detalle = Application_Model_Fichaje::getConsultaDetalleTrabajadores($desde, $hasta, $gerencia);

            echo json_encode($detalle);
        }else{
            echo '';
        }
    }

    public function getfichajeAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $draw   = $this->getParam('draw'); // No recuerdo xD
        $offset = $this->getParam('start'); // En qué página de la tabla estás
        $limit  = $this->getParam('length'); // Cuántos resultados se muestran por página
        $search = $this->getParam('search'); // El texto de búsqueda que ingresaste

        $material             = new Application_Model_Prueba();
        $iTotalRecords        = $material->getCantidadFichaje(); //Cantidad total de registros de la consulta
        $iTotalDisplayRecords = $material->getCantidadFichajeBySearch($search['value']); // Cantidad de registros al realizar la búsqueda
        $fichaje              = $material->getFichajeBySearch($search['value'], false, $limit, $offset);

        $res = array(
        'draw'                 => intval($draw),
        'iTotalRecords'        => $iTotalRecords,
        'iTotalDisplayRecords' => $iTotalDisplayRecords,
        'aaData'               => $fichaje
        );

        echo json_encode($res);
    }

}
