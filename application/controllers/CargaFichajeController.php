<?php

/**
 * Description of MotivoController
 *
 * @author juanfd
 */
class CargaFichajeController extends Fmo_Controller_Action_Abstract
{
    private $_crear  = '/default/cargafichaje/crear';
    private $_masivo = '/default/cargafichaje/cargamasiva';

    private function _bootstrap()
    {
        $this->view->bootstrap()->enable();
        $this->view->jQueryX()->enable();
        $this->view->bootstrap()->jsEnable();
        $this->view->dataTables()->enable();
    }

    public function crearAction()
    {
        $this->_bootstrap();
        $form    = new Application_Form_CargaFichajeMasivo();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost();

            try {
                if ($form->isValid($post)) {
                    $trabajador = $form->guardar($post);
                    $this->addMessageSuccessful('Ficha ' . $post[Application_Form_CargaFichajeMasivo::E_FICHA] . ' agregada como autorizado.');
                    $this->addMessageSuccessful('Trabajador: ' . $trabajador->{Fmo_Model_Personal::NOMBRE} . ' ' . $trabajador->{Fmo_Model_Personal::APELLIDO} . '. Cédula: ' . $trabajador->{Fmo_Model_Personal::CEDULA});
                    $this->redirect($this->_crear);
                }
            } catch (Exception $e) {
                switch ($e->getCode()) {
                    case 23505:
                        $msj = 'La ficha <b>' . $post[Application_Form_CargaFichajeMasivo::E_FICHA] . '</b> ya está registrada como autorizado.';
                        break;
                    default:
                        $msj = $e->getMessage();
                        break;
                }

                $this->addMessageError($msj);
            }
        }

        $this->view->form = $form;
    }

    public function cargamasivaAction()
    {
        $this->_bootstrap();
        $res  = null;
        $form = new Application_Form_CargaFichajeMasivo();

        if ($this->getRequest()->isPost()) {
            $post = $this->getRequest()->getPost();

            if ($form->isValid($post)) {
                try {
                    if ($this->getParam(Application_Form_CargaFichajeMasivo::E_CARGAR_ARCHIVO)) {
                        if ($form->getElement(Application_Form_CargaFichajeMasivo::E_ARCHIVO)->getValue()) {
                            $res = $form->cargarArchivo();
                            $this->addMessageSuccessful('¡Archivo procesado exitosamente!');
                            //$this->redirect($this->_masivo);
                            //$this->forward('resultadocarga', 'autorizado', 'default', array('resultado' => $res));
                            //$this->forward('resultadocarga', 'autorizado', 'default');
                        } else {
                            $this->addMessageError('El archivo no fue cargado');
                        }
                    }
                } catch (Exception $e) {
                    $this->addMessageError($e->getMessage());
                }
            } else {
                $form->setDefaults($post);
            }
        }

        $this->view->form = $form;
        $this->view->res  = $res;

        if ($res) {
            $this->view->total      = $res['total'];
            $this->view->correctos  = $res['correctos'];
            $this->view->errados    = $res['errados'];
            $this->view->errores    = isset($res['errors']) ? $res['errors'] : null;
        }
    }

    public function resultadocargaAction()
    {
        try {
            $this->_bootstrap();
            $res                    = $this->getParam('resultado');
            $this->view->total      = $res['total'];
            $this->view->correctos  = $res['correctos'];
            $this->view->errados    = $res['errados'];
            $this->view->errores    = isset($res['errors']) ? $res['errors'] : null;
        } catch (Exception $e) {
            $this->addMessageError($e->getMessage());
        }
    }
}