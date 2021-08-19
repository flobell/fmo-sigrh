<?php

class Application_Model_DbTable_ZfNotificacion extends Application_Model_DbTable_Abstract
{
    protected $_name = 'zf_notificacion';
    protected $_sequence = true;
    protected $_referenceMap = array(
        'Para' => array(
            self::COLUMNS => 'para',
            self::REF_TABLE_CLASS => 'Fmo_DbTable_RpsdatosDatoBasico',
            self::REF_COLUMNS => 'datb_cedula'
        )
    );
}
