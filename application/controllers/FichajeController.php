<?php

require_once 'PHPExcel.php';

/**
 * Controlador del modulo de control de acceso
 *
 * @author Jhimm Maita
 * @author Pedro Flores
 * @author Milaidy Aular
 */
class FichajeController extends Fmo_Controller_Action_Abstract
{
    /*  DICCIONARIO DE ABREVIADOS
    *  rag: Resumen de Asistencia por Gerencia
    */

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
     * Accion para la vista Consulta de Asistencia -> Consulta
     * 
     */
    public function vwasistenciaconsultaAction(){
        
        $form = new Application_Form_Asistencia();
        $this->view->formulario = $form;
        $request = $this->getRequest();

        if($request->isPost() && $form->isValid($request->getPost())) {
            $post = $request->getPost();
            //$this->forward('vwasistencia', null, null, $post);
            //Zend_Debug::dd($post);
                

            $fecha_desde = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
            $fecha_hasta = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
            $params =  array(
                'desde' => $fecha_desde, 
                    'hasta' => $fecha_hasta
            );
            if($post['ficha'] != NULL) { 
                $params['ficha'] = $post['ficha'];
            }
    
            $this->_helper->redirector('vwasistencia', 'fichaje', 'default', $params);
    
        }


    }

    /** 
     * Accion para la vista Consulta de Asistencia -> Resultado
     * 
     */
    public function vwasistenciaAction()
    {
        //esta accion no usara layout.phtml
        //$this->_helper->layout->disableLayout();
        //Activo bootstrap y jQuery
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        $filtros = $this->getAllParams();
        $fecha_desde = date('Y-m-d', strtotime(str_replace('/', '-', $filtros['desde'])));
        $fecha_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $filtros['hasta'])));
        
        $fichaTrabajador = NULL;
        if(array_key_exists(Application_Form_Asistencia::E_FICHA, $filtros)){
            $fichaTrabajador = $filtros[Application_Form_Asistencia::E_FICHA];
        }

        $gerencia = NULL;
        $descripcion_gerencia = NULL;
        if(array_key_exists('gerencia', $filtros)){
            $gerencia = $filtros['gerencia'];
            $descripcion_gerencia = Application_Model_Fichaje::getDescripcionGerencia($gerencia)->descripcion_gerencia;
            //Zend_Debug::dd($descripcion_gerencia);
        }

        $this->view->criterio = NULL;
        if(array_key_exists('criterio', $filtros)){
            $this->view->criterio = $filtros['criterio'];
        }

        $lugar = NULL;
        if(array_key_exists('lugar', $filtros)){
            $lugar = $filtros['lugar'];
        }

        $datosAsistencia = Application_Model_Fichaje::getAsistenciaTrabajador($fecha_desde,$fecha_hasta,$fichaTrabajador,$gerencia,$lugar);
        
        $this->view->datosAsistencia = $datosAsistencia; 
        $this->view->descripcion_gerencia = $descripcion_gerencia;
        $this->view->lugar = $lugar;
        $this->view->fecha_desde  = date('d-m-Y', strtotime($fecha_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($fecha_hasta));
    }


    /** 
     * Accion para la vista Consulta de Asistencia -> Resultado -> Detalle Vacaciones
     * 
     */
    public function vwdetallevacacionesAction(){
        //esta accion no usara layout.phtml
        //$this->_helper->layout->disableLayout();
        //Activo bootstrap y jQuery
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        //parametros GET
        $cedula = $this->getParam('cedula'); 
        $fecha_desde = date('d-m-Y', strtotime($this->getParam('desde'))); 
        $fecha_hasta = date('d-m-Y', strtotime($this->getParam('hasta'))); 
     
        $datosTrabajador = Application_Model_Fichaje::getDetalleTrabajador($cedula);
        //Zend_Debug::dd($datosTrabajador);
        $datosVacaciones = Application_Model_Fichaje::getDetalleVacacionesTrabajador($cedula,$fecha_desde,$fecha_hasta);                            
        //Zend_Debug::dd($datosVacaciones);

        $this->view->datosTrabajador = $datosTrabajador;
        $this->view->datosVacaciones = $datosVacaciones; 
        $this->view->cedula = $cedula;
        $this->view->fecha_desde  = date('d-m-Y', strtotime($fecha_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($fecha_hasta));
    }
    
    /** 
     * Accion para la vista Consulta de Asistencia -> Resultado -> Detalle Trabajador
     * 
     */
    public function vwdetalleasistenciaAction()
    {
        //esta accion no usara layout.phtml
        //$this->_helper->layout->disableLayout();
        //Activo bootstrap y jQuery
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        //Parametros desde la vista vwasistencia para el detalle del trabajador
        $cedula = $this->getParam('cedula'); //cedula del trabajador
        $fecha_desde = date('Y-m-d', strtotime($this->getParam('desde'))); //fecha desde formateada a yyyy-mm-dd
        $fecha_hasta = date('Y-m-d', strtotime($this->getParam('hasta'))); //fecha hasta formateada a yyyy-mm-dd
        //Zend_Debug::dd($cedula.' '.$fecha_desde.' '.$fecha_hasta); //con esto puedes ver los datos

        $datosTrabajador = Application_Model_Fichaje::getDetalleTrabajador($cedula);                    
        //Zend_Debug::dd($datosTrabajador);
        $datosAsistencia = Application_Model_Fichaje::getDetalleAsistenciaTrabajador($cedula,$fecha_desde,$fecha_hasta);                            
        //Zend_Debug::dd($datosAsistencia);
        $this->view->datosTrabajador = $datosTrabajador;
        $this->view->datosAsistencia = $datosAsistencia; 
        $this->view->cedula = $cedula;
        $this->view->fecha_desde  = date('d-m-Y', strtotime($fecha_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($fecha_hasta));
    } 


    /** 
     * Accion para la vista Resumen General de Asistencia -> Consulta
     * 
     */
    public function vwasistenciageneralconsultaAction(){
        
        $form = new Application_Form_AsistenciaGeneral();
        $this->view->formulario = $form;
        $request = $this->getRequest();

        if($request->isPost() && $form->isValid($request->getPost())) {
            $post = $request->getPost();
            
            
            $fecha_desde = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
            $fecha_hasta = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
            $params =  array(
                'desde' => $fecha_desde, 
                'hasta' => $fecha_hasta
            );

            $this->_helper->redirector('vwasistenciageneral', 'fichaje', 'default', $params);
        }
    }

    /** 
     * Accion para la vista Resumen General de Asistencia -> Resultado
     * 
     */
    public function vwasistenciageneralAction()
    {
        //esta accion no usara layout.phtml
        //$this->_helper->layout->disableLayout();
        //Activo bootstrap y jQuery
        $this->enableBootstrap();
        $this->view->dataTables()->enable();
        //Parametros desde la vista vwasistencia para el detalle del trabajador
       
        $filtros = $this->getAllParams();
        $fecha_desde = date('Ymd', strtotime(str_replace('/', '-', $filtros['desde'])));
        $fecha_hasta = date('Ymd', strtotime(str_replace('/', '-', $filtros['hasta'])));
        //Zend_Debug::dd($cedula.' '.$fecha_desde.' '.$fecha_hasta); //con esto puedes ver los datos
        
        //QUERY DE JUAN
        //$datosBasicos = Application_Model_Fichaje::getAsistenciaGeneralTrabajador_b($fecha_desde, $fecha_hasta);
        //QUERY DE SR HECTOR
        $datosBasicos = Application_Model_Fichaje::getAsistenciaGeneralTrabajador($fecha_desde,$fecha_hasta);                            
        $total = end($datosBasicos);
        //Zend_Debug::dd($total);
        $data = array_pop($datosBasicos);

        //Zend_Debug::dd($datosBasicos);
        $this->view->fecha_desde  = date('d-m-Y', strtotime($fecha_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($fecha_hasta));
        $this->view->datosBasicos = $datosBasicos;
        $this->view->total = $total;
    }  

    /** 
     * Accion para la vista Resumen General de Asistencia -> Resultado -> Detalle
     * 
     */
    public function vwdetallegerencialAction(){

        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        $params = $this->getAllParams();

        $gerencia =  $params['gerencia'];
        $fecha_desde = date('Y-m-d', strtotime(str_replace('/', '-', $params['desde'])));
        $fecha_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $params['hasta'])));

        $detalleGerencial = Application_Model_Fichaje::getDetalleAsistenciaGerencial($gerencia, $fecha_desde, $fecha_hasta);

        //Zend_Debug::dd($detalleGerencial);

        $descripcion_gerencia = null;
        foreach ($detalleGerencial as $row) {
            $descripcion_gerencia = $row->descripcion_gerencia;
            break;
        }

        $this->view->detalleGerencial = $detalleGerencial;
        $this->view->gerencia = $descripcion_gerencia;
        $this->view->fecha_desde = date('d-m-Y', strtotime($fecha_desde));
        $this->view->fecha_hasta = date('d-m-Y', strtotime($fecha_hasta));

    }

    /** 
     * Accion para la vista Porcentaje General de Asistencia -> Consulta 
     * 
     */
    public function vwporcentajeasistenciaconsultaAction(){
        
        $form = new Application_Form_PorcentajeAsistencia();
        $this->view->formulario = $form;
        $request = $this->getRequest();

        if($request->isPost() && $form->isValid($request->getPost())) {
            $post = $request->getPost();
            
            
            $fecha_desde = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
            $fecha_hasta = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
            $params =  array(
                'desde' => $fecha_desde, 
                'hasta' => $fecha_hasta,
                'localidad'=>$post['localidad']
            );

            $this->_helper->redirector('vwporcentajeasistencia', 'fichaje', 'default', $params);
            
            // $this->forward('vwasistenciageneral', null, null, $post);

            // if ($post[Application_Form_Consulta::E_TIPO] == 'c') {
            //     $this->forward('consolidadoventas', null, null, $post);
            // } else {
            //     $this->forward('detalladoventas', null, null, $post);
            // }
        }
    }
    /** 
     * Accion para la vista Porcentaje General de Asistencia -> Resultado 
     * 
     */
    public function vwporcentajeasistenciaAction()
    {
        //esta accion no usara layout.phtml
        //$this->_helper->layout->disableLayout();
        //Activo bootstrap y jQuery
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        $filtros = $this->getAllParams();
        //Zend_Debug::dd($filtros);
        $f_desde = date('Y-m-d', strtotime(str_replace('/', '-', $filtros['desde'])));
        $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $filtros['hasta'])));

        $lugar_pago = $filtros[Application_Form_PorcentajeAsistencia::E_LOCALIDAD];
        $datosAsistencia = Application_Model_Fichaje::getPorcentajeAsistencia($f_desde,$f_hasta,$lugar_pago);
        
        //Zend_Debug::dd($datosAsistencia);
        $this->view->datosAsistencia = $datosAsistencia; 
        
        $this->view->fecha_desde  = date('d-m-Y', strtotime($f_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($f_hasta));
    }
    
    public function vwhojadetiempoconsultaAction(){
        $form = new Application_Form_HojaDeTiempo();
        $this->view->formulario = $form;
        $request = $this->getRequest();

        try{
            if($request->isPost() && $form->isValid($request->getPost())) {
                $post = $request->getPost();
                
                $fecha_desde = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
                $fecha_hasta = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
                $params =  array(
                    'desde' => $fecha_desde, 
                    'hasta' => $fecha_hasta,
                    'ficha' => $post[Application_Form_HojaDeTiempo::E_FICHA]
                );

                $this->_helper->redirector('vwhojadetiempo', 'fichaje', 'default', $params);
            }
        } catch(Exception $ex){
            $this->addMessageError($ex->getMessage());
            $this->redirect('default/fichaje/vwhojadetiempoconsulta');
        }
    }
    
    public function vwhojadetiempoAction(){
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        $params = $this->getAllParams();
        //Zend_Debug::dd($filtros);
        $f_desde = date('Y-m-d', strtotime(str_replace('/', '-', $params['desde'])));
        $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $params['hasta'])));
        $fichaTrabajador = $params['ficha'];

        $personal = new Fmo_Model_Personal;
        $cedula = $personal->findOneByFicha($fichaTrabajador)->cedula;
        //Zend_Debug::dd($cedula);
        $datosTrabajador = Application_Model_Fichaje::getDetalleTrabajador($cedula);
        $datosHoja = Application_Model_Fichaje::getHojaDeTiempo($f_desde,$f_hasta,$fichaTrabajador);
        
        //Zend_Debug::dd($datosHoja);
        $this->view->datosTrabajador = $datosTrabajador;
        $this->view->datosHoja = $datosHoja; 
        $this->view->fecha_desde  = date('d-m-Y', strtotime($f_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($f_hasta));

      

    }


    public function vwasistenciadiariaconsultaAction(){
        $urlVolver = "default/fichaje/vwasistenciadiariadaconsulta";
        $this->view->jQueryX()->enable();
        $this->view->select2()->enable();
        $form = new Application_Form_AsistenciaDiaria();
        $this->view->formulario = $form;
        $request = $this->getRequest();

        if($request->isPost() && $form->isValid($request->getPost())) {
            $post = $request->getPost();
            
            $fecha_desde = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
            $fecha_hasta = date('d-m-Y', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
            $gerencia = $post['gerencia'];
            $params =  array(
                'desde' => $fecha_desde, 
                'hasta' => $fecha_hasta,
                'gerencia' => $gerencia
            );

            if($gerencia && !Application_Model_PermisoCeco::puedeVerGerencia($post[Application_Form_AsistenciaDiaria::E_GERENCIA])){
                $gerencias = Fmo_DbTable_Rpsdatos_CentroCosto::getPairs('ceco_ceco','ceco_descri',"length(ceco_ceco) = 2");
                $this->addMessageWarning('No tiene permiso para visualizar la gerencia: '.$gerencias[$gerencia]);
                $this->redirect($urlVolver);
            }

            if($post[Application_Form_AsistenciaDiaria::E_FICHA] != NULL) { 
                $trabajador = Fmo_Model_Personal::findOneByFicha($post[Application_Form_AsistenciaDiaria::E_FICHA]);
                if(Application_Model_PermisoCeco::puedeVerTrabajador($trabajador)){
                    $params[Application_Form_AsistenciaDiaria::E_FICHA] = $post[Application_Form_AsistenciaDiaria::E_FICHA];
                    $params[Application_Form_AsistenciaDiaria::E_GERENCIA] = substr(Fmo_Model_Personal::findOneByFicha($post[Application_Form_AsistenciaDiaria::E_FICHA])->id_centro_costo,0,2);
                } else {
                    $this->addMessageWarning('No tiene permiso para ver al trabajador de Ficha Nº: '.$post[Application_Form_AsistenciaDiaria::E_FICHA]);
                    $this->redirect($urlVolver);
                }
            }

            $this->_helper->redirector('vwasistenciadiaria', 'fichaje', 'default', $params);
        }
    }

    public function vwasistenciadiariaAction(){
        $this->enableBootstrap();
        $this->view->dataTables()->enable();
       
        $params = $this->getAllParams();
        //Zend_Debug::dd($filtros);
        $f_desde = date('Y-m-d', strtotime(str_replace('/', '-', $params['desde'])));
        $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $params['hasta'])));
        $gerencia = isset($params['gerencia'])?$params['gerencia']:NULL;
        $gerencias = Fmo_DbTable_Rpsdatos_CentroCosto::getPairs('ceco_ceco','ceco_descri',"length(ceco_ceco) = 2");

        $ficha = NULL;
        if(array_key_exists('ficha', $params)){ $ficha = $params['ficha']; }
        $datosListado = Application_Model_Fichaje::getConsultaDiariaControl($f_desde,$f_hasta,$gerencia,$ficha);
        
        //Zend_Debug::dd($datosListado);
        $this->view->datosListado = $datosListado; 
        
        $this->view->fecha_desde  = date('d-m-Y', strtotime($f_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($f_hasta));
        $this->view->gerencia = $gerencia!=NULL?$gerencias[$gerencia]:'TODAS LAS GERENCIAS';

    }

    public function vwasistenciadetalladaconsultaAction(){
        $urlVolver = "default/fichaje/vwasistenciadetalladaconsulta";
        $this->view->jQueryX()->enable();
        $this->view->select2()->enable();
        $form = new Application_Form_AsistenciaDetallada();
        $this->view->formulario = $form;
        $request = $this->getRequest();

        if($request->isPost() && $form->isValid($request->getPost())) {
            $post = $request->getPost();
            
            $fecha_desde = date('Y-m-d', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
            $fecha_hasta = date('Y-m-d', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
            $gerencia = $post[Application_Form_AsistenciaDetallada::E_GERENCIA];
            $params =  array(
                'desde' => $fecha_desde, 
                'hasta' => $fecha_hasta,
                'gerencia' => $gerencia,
                'dispositivo' => isset($post[Application_Form_AsistenciaDetallada::E_DISPOSITIVO])?$post[Application_Form_AsistenciaDetallada::E_DISPOSITIVO]:NULL,
            );

            if($gerencia && !Application_Model_PermisoCeco::puedeVerGerencia($post[Application_Form_AsistenciaDetallada::E_GERENCIA])){
                $gerencias = Fmo_DbTable_Rpsdatos_CentroCosto::getPairs('ceco_ceco','ceco_descri',"length(ceco_ceco) = 2");
                $this->addMessageWarning('No tiene permiso para visualizar la gerencia: '.$gerencias[$gerencia]);
                $this->redirect($urlVolver);
            }
            
            if($post[Application_Form_AsistenciaDetallada::E_FICHA] != NULL) { 
                $trabajador = Fmo_Model_Personal::findOneByFicha($post[Application_Form_AsistenciaDetallada::E_FICHA]);
                if(Application_Model_PermisoCeco::puedeVerTrabajador($trabajador)){
                    $params[Application_Form_AsistenciaDetallada::E_FICHA] = $post[Application_Form_AsistenciaDetallada::E_FICHA];
                    $params[Application_Form_AsistenciaDetallada::E_GERENCIA] = substr(Fmo_Model_Personal::findOneByFicha($post[Application_Form_AsistenciaDetallada::E_FICHA])->id_centro_costo,0,2);
                } else {
                    $this->addMessageWarning('No tiene permiso para ver al trabajador de Ficha Nº: '.$post[Application_Form_AsistenciaDetallada::E_FICHA]);
                    $this->redirect($urlVolver);
                }
            }

            $this->_helper->redirector('vwasistenciadetallada', 'fichaje', 'default', $params);
        }
    }

    public function vwasistenciadetalladaAction(){
        $this->enableBootstrap();
        $this->view->dataTables()->enable();

        $params = $this->getAllParams();
        //Zend_Debug::dd($filtros);
        $f_desde = date('Y-m-d', strtotime(str_replace('/', '-', $params['desde'])));
        $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $params['hasta'])));
        $gerencia = isset($params['gerencia'])?$params['gerencia']:NULL;
        $dispositivo = isset($params['dispositivo'])?$params['dispositivo']:NULL;
        $gerencias = Fmo_DbTable_Rpsdatos_CentroCosto::getPairs('ceco_ceco','ceco_descri',"length(ceco_ceco) = 2");
        //Zend_Debug::dd();

        $ficha = NULL;
        if(array_key_exists('ficha', $params)){ $ficha = $params['ficha']; }
        $datosListado = Application_Model_Fichaje::getConsultaDetalleTrabajadores($f_desde,$f_hasta,$gerencia,$ficha,$dispositivo);

        //$datosListado = NULL;
        //Zend_Debug::dd($datosListado);
        $this->view->datosListado = $datosListado; 
        $this->view->fecha_desde  = date('d-m-Y', strtotime($f_desde));
        $this->view->fecha_hasta  = date('d-m-Y', strtotime($f_hasta));
        $this->view->gerencia = $gerencia!=NULL?$gerencias[$gerencia]:'TODAS LAS GERENCIAS';
        $this->view->nroGerencia = $gerencia;

    }

    public function vwragconsultaAction(){
        // $this->view->jQueryX()->enable();
        // $this->view->bootstrap()->enable();
        // $this->view->bootstrap()->jsEnable();
        // $this->view->highCharts()->enable();
         
        $formulario1 = new Application_Form_ResumenAsistenciaGerencia();
        $this->view->formulario1 = $formulario1;
        $formulario2 = new Application_Form_Grafico();
        $this->view->formulario2 = $formulario2;
        $request = $this->getRequest();

        if($request->isPost()) {
            $post = $request->getPost();

            if(isset($post[Application_Form_ResumenAsistenciaGerencia::E_BUSCAR]) && $formulario1->isValid($post)){
                $f_desde = date('Y-m-d', strtotime(str_replace('/', '-',  $post['fecha_desde'])));
                $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-',  $post['fecha_hasta'])));
                $params =  array('desde' => $f_desde, 'hasta' => $f_hasta);
                if($post['localidad'] != '0'){ $params['localidad'] = $post['localidad']; }
                $params['grafico'] = 0;
                $this->_helper->redirector('vwrag', 'fichaje', 'default', $params);
            }

            if(isset($post[Application_Form_Grafico::E_BUSCAR]) && $formulario2->isValid($post)){
                $f_desde = date('Y-m-d', strtotime(str_replace('/', '-',  $post['g_fecha_desde'])));
                $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-',  $post['g_fecha_hasta'])));
                $params =  array('desde' => $f_desde, 'hasta' => $f_hasta);
                if($post['g_localidad'] != '0'){ $params['localidad'] = $post['g_localidad']; }
                $params['grafico'] = 1;
                $this->_helper->redirector('vwrag', 'fichaje', 'default', $params);
            }

        }
    }

    public function vwragAction(){
        $this->view->jQueryX()->enable();
        $this->view->bootstrap()->enable();
        $this->view->bootstrap()->jsEnable();
        $this->view->dataTables()->enable();
        $this->view->highCharts()->enable();
        $this->view->highCharts()->loadModules(array(Fmo_Helper_HighCharts::MODULE_EXPORTING));

        $params = $this->getAllParams();

        $f_desde = date('Y-m-d', strtotime(str_replace('/', '-', $params['desde'])));
        $f_hasta = date('Y-m-d', strtotime(str_replace('/', '-', $params['hasta'])));

        $localidad = NULL;
        if(array_key_exists('localidad', $params)){ $localidad = $params['localidad']; }

        //Zend_Debug::dd($params);
        if($params['grafico'] == true){
            $datos = Application_Model_Fichaje::getResumenAsistenciaGrafico($f_desde,$f_hasta,$localidad);
            // Zend_Debug::dd($datos);
            $this->view->datos = $datos; 
        }
        else {
            $datos = Application_Model_Fichaje::getResumenAsistenciaGerencia($f_desde,$f_hasta,$localidad);
            // Zend_Debug::dd($datos);
            $this->view->datos = $datos; 
        }

        $this->view->localidad = $localidad;
        $this->view->f_desde = date('d-m-Y', strtotime($f_desde));
        $this->view->f_hasta = date('d-m-Y', strtotime($f_hasta));
        $this->view->grafico = $params['grafico'];
    }
}