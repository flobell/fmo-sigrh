<?php

/**
 * Clase que controla los datos del sistema de seguridad.
 *
 * @author Jhimm Maita
 * @author Pedro Flores
 * @author Juan Durán
 * @author Milaidy Aular
 */
class Application_Model_Fichaje {

    const ID = 'id';
    const CEDULA = 'cedula';
    const NOMBRE = 'nombre';
    const FECHA = 'fecha';
    const PUERTAS = 'puertas';
    const ID_DISPOSITIVO = 'id_dispositivo';
    const DISPOSITIVO = 'dispositivo';
    const EVENTO = 'evento';

    /** 
     * Retorna el resumen general de asistencia de todas la gerencias en un rango de fecha inicial y final
     * QUERY DE PRUEBAS DE JUAN
     * 
     * @param string $f_desde Fecha inicial en formato yyyy-mm-dd
     * @param string $f_hasta Fecha final en formato yyyy-mm-dd
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getAsistenciaGeneralTrabajador_b($f_desde, $f_hasta) {

        $dias_habiles = self::getDiasHabiles($f_desde, $f_hasta);
        $dias = $dias_habiles->dias_habiles == 0 ? 1 : $dias_habiles->dias_habiles;

        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $sql = "SELECT
                -- Descripción Gerencia
                ca.ceco_ceco AS codigo,
                ca.ceco_descri AS descripcion_gerencia,
                cal.dias_habiles,
                -- Fuerza Laboral
                fl.fuerza_laboral_poz AS trabajadores_activos_poz,
                fl.fuerza_laboral_cpr AS trabajadores_activos_cp,
                fl.fuerza_laboral AS trabajadores_activos,
                -- Vacaciones
                SUM(CASE WHEN dba.datb_activi IN (2, 8) AND dba.datb_lugp != 4 THEN 1 ELSE 0 END) AS trabajadores_vacacion_poz,
                SUM(CASE WHEN dba.datb_activi IN (2, 8) AND dba.datb_lugp = 4 THEN 1 ELSE 0 END) AS trabajadores_vacacion_cp,
                SUM(CASE WHEN dba.datb_activi IN (2, 8) THEN 1 ELSE 0 END) AS vacaciones,
                -- Autorizados
                SUM(CASE WHEN dba.datb_lugp != 4 THEN 1 ELSE 0 END) AS trabajadores_autorizados_poz,
                SUM(CASE WHEN dba.datb_lugp = 4 THEN 1 ELSE 0 END) AS trabajadores_autorizados_cp,
                COUNT(ca.*) AS autorizados,
                -- Carnetizados
                SUM(CASE WHEN ft.cedula IS NOT NULL AND dba.datb_lugp != 4 THEN 1 ELSE 0 END) AS trabajadores_ficha_poz,
                SUM(CASE WHEN ft.cedula IS NOT NULL AND dba.datb_lugp = 4 THEN 1 ELSE 0 END) AS trabajadores_ficha_cp,
                --SUM(CASE WHEN ft.cedula IS NOT NULL THEN 1 ELSE 0 END) AS carnetizados,
                COUNT(ft.cedula) AS carnetizados,
                -- Por Carnetizar
                SUM(CASE WHEN ft.cedula IS NULL AND dba.datb_lugp != 4 THEN 1 ELSE 0 END) AS trabajadores_faltantes_carnetizar_poz,
                SUM(CASE WHEN ft.cedula IS NULL AND dba.datb_lugp = 4 THEN 1 ELSE 0 END) AS trabajadores_faltantes_carnetizar_cpr,
                SUM(CASE WHEN ft.cedula IS NULL THEN 1 ELSE 0 END) AS por_carnetizar,
                -- Asistencia
                asis.asistencia_poz AS asistencia_poz,
                asis.asistencia_cpr AS asistencia_cp,
                asis.asistencia_poz + asis.asistencia_cpr AS asistencia,
                -- Promedio Diario de Asistencia
                asis.promedio_diario_poz,
                asis.promedio_diario_cpr AS promedio_diario_cp,
                asis.promedio_diario,
                -- Asistentes Distintos
                /*
                ad.asistentes_poz,
                ad.asistentes_cpr,
                ad.asistentes,
                */
                -- Porcentaje de Asistencia
                CASE 
                    WHEN asis.asistencia_poz = 0 THEN 0.00
                ELSE
                    ROUND((asis.asistencia_poz * 100) / (SUM(CASE WHEN dba.datb_lugp != 4 THEN 1 ELSE 0 END) * cal.dias_habiles), 2)
                END AS porc_asistencia_poz,
                CASE 
                    WHEN asis.asistencia_cpr = 0 THEN 0.00
                ELSE
                    ROUND((asis.asistencia_cpr * 100) / (SUM(CASE WHEN dba.datb_lugp = 4 THEN 1 ELSE 0 END) * cal.dias_habiles), 2)
                END AS porc_asistencia_cp,
                ROUND(((asis.asistencia_poz + asis.asistencia_cpr) * 100) / (COUNT(ca.*) * cal.dias_habiles), 2) AS porcentaje_asistencia
                -- Tabla Autorizados
                FROM sch_scef.autorizado a
                -- Tabla Datos Básicos
                INNER JOIN sch_rpsdatos.sn_tdatbas dba ON (a.cedula = dba.datb_cedula)
                -- Tabla Centros de Costo
                INNER JOIN sch_rpsdatos.sn_tcecos ca ON (SUBSTR(dba.datb_ceco, 1, 2) = ca.ceco_ceco)
                -- Consulta Calendario
                CROSS JOIN 
                (
                SELECT
                    COUNT(t1.cale_fecdia) AS dias_habiles
                FROM
                    sch_rpsdatos.sn_tcalend t1
                WHERE 
                    t1.cale_fecdia BETWEEN '$f_desde' AND '$f_hasta'
                    AND t1.cale_cndfers = 'N'
                    AND TO_CHAR(cale_fecdia,'dy') NOT IN ('sat','sun')
                ) AS cal
                -- Consulta Fuerza Laboral
                INNER JOIN 
                (
                SELECT 
                    SUBSTR(dbt.datb_ceco, 1, 2) AS gerencia,
                    SUM(CASE WHEN dbt.datb_lugp != 4 THEN 1 ELSE 0 END) AS fuerza_laboral_poz,
                    SUM(CASE WHEN dbt.datb_lugp = 4 THEN 1 ELSE 0 END) AS fuerza_laboral_cpr,
                    COUNT(dbt.*) AS fuerza_laboral
                FROM sch_rpsdatos.sn_tdatbas dbt
                WHERE
                    dbt.datb_activi NOT IN (0, 9)
                    AND dbt.datb_tpno::INTEGER IN (1, 2, 3, 5, 6)
                GROUP BY 1
                ) fl ON (ca.ceco_ceco = fl.gerencia)
                -- Tabla Trabajadores Carnetizados
                LEFT JOIN sch_scef.ficha_trabajador ft ON (a.cedula = ft.cedula AND ft.activo = TRUE)
                -- Consulta Asistencia
                LEFT JOIN
                (
                SELECT
                    foo.cod_gerencia,
                    SUM(foo.asistentes_poz) AS asistencia_poz,
                    SUM(foo.asistentes_cpr) AS asistencia_cpr,
                    ROUND(AVG(foo.asistentes_poz), 0) AS promedio_diario_poz,
                    ROUND(AVG(foo.asistentes_cpr), 0) AS promedio_diario_cpr,
                    ROUND(AVG(foo.asistentes_poz + foo.asistentes_cpr), 0) AS promedio_diario
                FROM 
                (
                    SELECT
                    u.cod_gerencia,
                    u.gerencia,
                    u.dia,
                    SUM(CASE WHEN u.localidad != 4 AND f.cedula IS NOT NULL THEN 1 ELSE 0 END) AS asistentes_poz,
                    SUM(CASE WHEN u.localidad = 4 AND f.cedula IS NOT NULL THEN 1 ELSE 0 END) AS asistentes_cpr
                    FROM
                    (
                    SELECT DISTINCT 
                        c.ceco_ceco AS cod_gerencia, 
                        c.ceco_descri AS gerencia,
                        CASE WHEN db.datb_lugp != 4 THEN 1 ELSE 4 END AS localidad,
                        r.dia
                    FROM sch_rpsdatos.sn_tcecos c
                    INNER JOIN sch_rpsdatos.sn_tdatbas db ON (c.ceco_ceco = SUBSTR(db.datb_ceco, 1, 2) AND db.datb_activi NOT IN (0,9))
                    CROSS JOIN 
                    (
                        SELECT dia::DATE AS dia
                        FROM generate_series('$f_desde', '$f_hasta','1 day'::INTERVAL) dia
                    ) AS r
                    ) AS u
                    LEFT JOIN 
                    (
                    SELECT DISTINCT 
                        SUBSTR(db2.datb_ceco, 1, 2) AS cod_gerencia, 
                        cf.cedula,
                        CASE WHEN db2.datb_lugp != 4 THEN 1 ELSE 4 END AS localidad,
                        cf.fecha::DATE AS fecha
                    FROM sch_sigrh.control_fichaje cf
                    INNER JOIN sch_rpsdatos.sn_tdatbas db2 ON (cf.cedula = db2.datb_cedula)
                    WHERE cf.fecha::DATE BETWEEN '$f_desde' AND '$f_hasta'
                    ) f ON (u.dia = f.fecha AND u.cod_gerencia = f.cod_gerencia AND u.localidad = f.localidad)
                    GROUP BY 1, 2, 3
                ) AS foo
                GROUP BY 1
                ) AS asis ON (ca.ceco_ceco = asis.cod_gerencia)
                -- Consulta Asistentes Distintos
                /*
                LEFT JOIN
                (
                SELECT
                    ct.ceco_ceco,
                    SUM(CASE WHEN dbt.datb_lugp != 4 THEN 1 ELSE 0 END) AS asistentes_poz,
                    SUM(CASE WHEN dbt.datb_lugp = 4 THEN 1 ELSE 0 END) AS asistentes_cpr,
                    COUNT(cft.*) AS asistentes
                FROM 
                (
                    SELECT DISTINCT cedula
                    FROM sch_sigrh.control_fichaje cfc
                    WHERE cfc.fecha::DATE BETWEEN '$f_desde' AND '$f_hasta'
                ) AS cft
                INNER JOIN sch_rpsdatos.sn_tdatbas dbt ON (cft.cedula = dbt.datb_cedula)
                INNER JOIN sch_rpsdatos.sn_tcecos ct ON (SUBSTR(dbt.datb_ceco, 1, 2) = ct.ceco_ceco)
                GROUP BY 1
                ) AS ad ON (ca.ceco_ceco = ad.ceco_ceco)
                */
                GROUP BY 
                1, 2, 3, 4, 5, 6, 
                asis.asistencia_poz, 
                asis.asistencia_cpr, 
                asis.promedio_diario, 
                asis.promedio_diario_poz, 
                asis.promedio_diario_cpr--, 
                /*
                ad.asistentes_poz,
                ad.asistentes_cpr,
                ad.asistentes
                */
                ORDER BY 1;";
        //Zend_Debug::dd($sql);  
        return $tDatosBasicos->getAdapter()->fetchAll($sql, array(), Zend_Db::FETCH_OBJ);
    }

    /** 
     * Retorna el resumen general de asistencia de todas la gerencias en un rango de fecha inicial y final
     * QUERY OFICIAL DEL SR.HECTOR
     * 
     * @param string $f_desde Fecha inicial en formato yyyymmdd
     * @param string $f_hasta Fecha final en formato yyyymmdd
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getAsistenciaGeneralTrabajador($f_desde, $f_hasta) {

        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $query1 = $tDatosBasicos->select()->setIntegrityCheck(false)
        ->from(
            array('b1' => $tDatosBasicos->info(Zend_Db_Table::NAME)), 
            array(
                'codigo' => new Zend_Db_Expr("substr(b1.datb_ceco,1,2)"),
                'descripcion_gerencia' => 'b2.ceco_descri',
                'dias_habiles' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(datb_ceco,1,2), 1,  to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))"),
                'trabajadores_activos_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 2,  null,  null)"),
                'trabajadores_activos_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 3,  null,  null)"),
                'trabajadores_autorizados_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 4,  null,  null)"),
                'trabajadores_autorizados_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 5,  null,  null)"),
                'trabajadores_ficha_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 6,  null,  null)"),
                'trabajadores_ficha_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 7,  null,  null)"),
                'trabajadores_vacacion_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 8,  null,  null)"),
                'trabajadores_vacacion_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 9,  null,  null)"),
                'asistencia_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 10, to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))"),
                'asistencia_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 11, to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))"),
                'promedio_diario_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 12, to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))"),
                'promedio_diario_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 13, to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))"),
                'porc_asistencia_poz' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 32, to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))"),
                'porc_asistencia_cp' => new Zend_Db_Expr("sch_sigrh.resumen_gerencia(substr(b1.datb_ceco,1,2), 33, to_date('$f_desde','yyyymmdd'),  to_date('$f_hasta','yyyymmdd'))")
            ), 
            $tDatosBasicos->info(Zend_Db_Table::SCHEMA)
        )
        ->join(array('b2' => $tCentroCosto->info(Zend_Db_Table::NAME)), 'b2.ceco_ceco = substr(b1.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->where('b1.datb_activi  not in (9)')
        ->where("b1.datb_tpno in ('1', '2', '3', '4', '5', '6')")
        ->where('b2.ceco_ceco = substr(b1.datb_ceco,1,2)')
        ->where('length(b2.ceco_ceco) = 2')
        ->group(
            array(
                'substr(b1.datb_ceco,1,2)',
                'b2.ceco_descri',
            )
        );


        $query2 = "SELECT 
            null,
            'TOTAL GENERAL',
            null,
            sch_sigrh.resumen_gerencia(null, 18, null, null),
            sch_sigrh.resumen_gerencia(null, 19, null, null),
            sch_sigrh.resumen_gerencia(Null, 22, null, null),
            sch_sigrh.resumen_gerencia(Null, 23, null, null),
            sch_sigrh.resumen_gerencia(Null, 24, null, null),
            sch_sigrh.resumen_gerencia(null, 25, null, null),
            sch_sigrh.resumen_gerencia(null, 26, null, null),
            sch_sigrh.resumen_gerencia(null, 27, null, null),
            sch_sigrh.resumen_gerencia(null, 28, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd')),
            sch_sigrh.resumen_gerencia(null, 29, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd')),
            sch_sigrh.resumen_gerencia(null, 16, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd')),
            sch_sigrh.resumen_gerencia(null, 17, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd')),
            sch_sigrh.resumen_gerencia(null, 34, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd')),
            sch_sigrh.resumen_gerencia(null, 35, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd'))
        ";

        $select = $tDatosBasicos->select()
        ->union(array($query1, $query2))
        ->order(1);

        //Zend_Debug::dd($select->__toString());
        //Zend_Debug::dd($select->assemble()); //Para Visualizar codigo Query
        //Zend_Debug::dd(json_encode($tFichaje->fetchAll($select)->toArray())); //Para probar resultado
        return $tDatosBasicos->getAdapter()->fetchAll($select);
    }

    /** 
     * Retorna el resumen general de asistencia detallado de una gerencia en un rango de fecha inicial y final
     * 
     * @param string $gerencia Centro de costo de dos digitos que identifica la gerencia
     * @param string $f_desde Fecha inicial en formato yyyy-mm-dd
     * @param string $f_hasta Fecha final en formato yyyy-mm-dd
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getDetalleAsistenciaGerencial($gerencia, $f_desde, $f_hasta) {

        $tFichaje = new Application_Model_DbTable_Sigrh_Fichaje();

        $query = "SELECT 	ft.gerencia AS gerencia,
                    ft.descripcion AS descripcion_gerencia,
                    (
                    CASE 
                        WHEN to_char(ft.fecha,'d') = '1' THEN 'DOM'
                        WHEN to_char(ft.fecha,'d') = '2' THEN 'LUN'
                        WHEN to_char(ft.fecha,'d') = '3' THEN 'MAR'
                        WHEN to_char(ft.fecha,'d') = '4' THEN 'MIE'
                        WHEN to_char(ft.fecha,'d') = '5' THEN 'JUE'
                        WHEN to_char(ft.fecha,'d') = '6' THEN 'VIE'
                        WHEN to_char(ft.fecha,'d') = '7' THEN 'SAB' 
                    END
                    ) AS dia,
                    to_char(ft.fecha,'dd-mm-yyyy') AS fecha,
                    sch_sigrh.resumen_gerencia(ft.gerencia::TEXT, 2, null, null)::INT AS trab_activos_poz,      
                    sch_sigrh.resumen_gerencia(ft.gerencia::TEXT, 3, null, null)::INT  AS trab_activos_cp,
                    sch_sigrh.resumen_gerencia(ft.gerencia::TEXT, 4, null, null)::INT  AS trab_autorizados_poz,
                    sch_sigrh.resumen_gerencia(ft.gerencia::TEXT, 5, null, null)::INT  AS trab_autorizados_cp,   
                    COALESCE(SUM(CASE WHEN ft.localidad <> 4 THEN ft.total_trab END)::INT,0) AS trab_asistencia_poz,
                    COALESCE(SUM(CASE WHEN ft.localidad = 4 THEN ft.total_trab END )::INT,0) AS trab_asistencia_cp, 
                    SUM(ft.total_trab) AS total_asistencia
                FROM	(
                    SELECT DISTINCT SUBSTR(t2.datb_ceco,1,2)::INT AS gerencia,
                        t3.ceco_descri AS descripcion,
                        to_date(to_char(t1.fecha,'yyyymmdd'),'yyyymmdd')  AS fecha,
                        t2.datb_lugp AS localidad,
                        COUNT(DISTINCT t1.cedula)::INT AS total_trab        
                    FROM sch_sigrh.control_fichaje AS t1
                        INNER JOIN sch_rpsdatos.sn_tdatbas AS t2 ON (t2.datb_cedula = t1.cedula)
                        INNER JOIN sch_rpsdatos.sn_tcecos AS t3 ON (t3.ceco_ceco = substr(t2.datb_ceco,1,2))
                    WHERE to_char(t1.fecha,'yyyy-mm-dd') BETWEEN '$f_desde' AND '$f_hasta'
                        AND length(t3.ceco_ceco) = 2  
                        AND substr(t2.datb_ceco,1,2) = '$gerencia'
                    GROUP BY substr(t2.datb_ceco,1,2),
                        t3.ceco_descri,
                        to_char(t1.fecha,'yyyymmdd'),
                        t2.datb_lugp
                    ) AS ft
                GROUP BY ft.gerencia,
                    ft.descripcion,
                        ft.fecha
                ORDER BY ft.fecha ASC
                ";

        return $tFichaje->getAdapter()->fetchAll($query);
    }

    /** 
     * Retorna los porcentajes de asistencias de los trabajadores en un rango de fecha inicial y final,
     * con OPCIONALES de filtrado, segun fichaje, gerencia y lugar.
     * 
     * Posee seguridad de acceso segun el centro de costo proveniente del esquema de sch_sasinzf
     * 
     * @param string $f_desde Fecha inicial en formato yyyy-mm-dd
     * @param string $f_hasta Fecha final en formato yyyy-mm-dd
     * @param string $fichaTrabajador Numero de ficha del trabajador
     * @param string $gerencia Centro de costo de dos digitos que identifica la gerencia
     * @param string $lugar valor '1' para PUERTO ORDAZ o '2' para CIUDAD PIAR
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getAsistenciaTrabajador($f_desde, $f_hasta, $fichaTrabajador = NULL, $gerencia = NULL, $lugar = NULL) {

        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $select = $tDatosBasicos->select()->setIntegrityCheck(false)
        ->from(
            array('t1' => $tDatosBasicos->info(Zend_Db_Table::NAME)), 
            array(
                'gerencia' => new Zend_Db_Expr('substr(t1.datb_ceco,1,2)'),
                'descripcion_gerencia' => "TRIM(' ' FROM t3.ceco_descri)",
                'descripcion_departamento' => "TRIM(' ' FROM t4.ceco_descri)",
                'ficha' => "TRIM(' ' FROM t1.datb_nrotrab)",
                'cedula' => 't1.datb_cedula',
                'nombre' => new Zend_Db_Expr("TRIM(' ' FROM REPLACE(rpad(t1.datb_nombre||' '|| t1.datb_apellid,40),$$'$$,' '))"),
                'nomina' => "TRIM(' ' FROM t2.tpno_descri)",
                'actividad' => new Zend_Db_Expr('t5.list_descri::VARCHAR(15)'),
                'fecha_ingreso' => new Zend_Db_Expr("to_char(t1.datb_fecing,'dd/mm/yyyy')"),
                'lugar_pago' => new Zend_Db_Expr("CASE when t1.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END"),
                'dias_habiles' => new Zend_Db_Expr("sch_sigrh.dia_labor(2,t1.datb_cedula,t1.datb_fecing,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'dias_asistencia' => new Zend_Db_Expr("sch_sigrh.dia_labor(3,t1.datb_cedula,null,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'dias_vacacion' => new Zend_Db_Expr("sch_sigrh.dia_vacacion(1,t1.datb_cedula,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'dias_vacacion_fichaje'=> new Zend_Db_Expr("sch_sigrh.dia_vacacion(2,t1.datb_cedula,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'porcentaje_asistencia' => new Zend_Db_Expr(
                    "CASE WHEN (sch_sigrh.dia_labor(2,t1.datb_cedula,t1.datb_fecing,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))) > 0
                    THEN round(((sch_sigrh.dia_labor(3,t1.datb_cedula,null,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd')) 
                    + sch_sigrh.dia_vacacion(2,t1.datb_cedula,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd')))
                    / sch_sigrh.dia_labor(2,t1.datb_cedula,t1.datb_fecing,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))*100),2) 
                    ELSE 0.00 
                    END"
                ),
                'horas_asistencia' => new Zend_Db_Expr("sch_sigrh.hora_labor(1,datb_cedula,to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd'))"),
                'horas_asistencia_vacacion' => new Zend_Db_Expr("sch_sigrh.hora_labor(2,t1.datb_cedula, to_date('$f_desde','yyyymmdd'), to_date('$f_hasta','yyyymmdd'))"),
            ),
            $tDatosBasicos->info(Zend_Db_Table::SCHEMA)
        )
        ->joinInner(array('t2' => $tTipoNomina->info(Zend_Db_Table::NAME)), 't2.tpno_tpno = t1.datb_tpno', array(), $tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t3' => $tCentroCosto->info(Zend_Db_Table::NAME)), 't3.ceco_ceco = substr(t1.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t4' => $tCentroCosto->info(Zend_Db_Table::NAME)), 't4.ceco_ceco = t1.datb_ceco', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t5' => 'sq_tlist'), 't1.datb_activi = t5.list_codigo::integer', array(), 'sch_rpsdatos')
        ->where("t1.datb_activi NOT IN (9)")
        ->where("t1.datb_tpno IN ('1','2','3','5','6')")
        ->where("t1.datb_fecing <= to_date('$f_hasta','yyyy-mm-dd')")
        ->where("length(t3.ceco_ceco) = 2")
        ->where("t5.list_apli = 'SNV610'")
        ->where("t5.list_list = '5'");

        //SEGURIDAD DE SASINZF POR CENTRO DE COSTO
        $cedusuario = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::CEDULA};
        $datosPermiso = Application_Model_PermisoCeco::getAllPermisoCeco($cedusuario)->toArray();
        $select->where(
                't1.datb_ceco LIKE ANY (ARRAY[?])', array_map(
                        function($value) {
                    if (strlen($value['ceco_ceco']) == 1)
                        return '%';
                    return $value['ceco_ceco'] . '%';
                }, $datosPermiso
                )
        );

        //FITLRO POR FICHA
        if ($fichaTrabajador != NULL)
            $select->where("t1.datb_nrotrab = '$fichaTrabajador'");
        //FITLRO POR GERENCIA
        if ($gerencia != NULL)
            $select->where("substr(t1.datb_ceco,1,2) = '$gerencia'");
        //FITLRO POR LUGAR
        if ($lugar != NULL) {
            if ($lugar == '1') {
                $select->where('t1.datb_lugp NOT IN (4)');
            } //PUERTO ORDAZ
            if ($lugar == '2') {
                $select->where('t1.datb_lugp = 4');
            } //CIUDAD PIAR
        }

        $sqlB = $tDatosBasicos->select()
        ->setIntegrityCheck(FALSE)
        ->from(array('t' => $select), array(
            'gerencia' => new Zend_Db_Expr("t.gerencia"),
            'descripcion_gerencia' => new Zend_Db_Expr("t.descripcion_gerencia"),
            'descripcion_departamento' => new Zend_Db_Expr("t.descripcion_departamento"),
            'ficha' => new Zend_Db_Expr("t.ficha"),
            'cedula' => new Zend_Db_Expr("t.cedula"),
            'nombre' => new Zend_Db_Expr("t.nombre"),
            'nomina' => new Zend_Db_Expr("t.nomina"),
            'actividad' => new Zend_Db_Expr("t.actividad"),
            'fecha_ingreso' => new Zend_Db_Expr("t.fecha_ingreso"),
            'dias_habiles' => new Zend_Db_Expr("t.dias_habiles"),
            'dias_asistencia' => new Zend_Db_Expr("t.dias_asistencia"),
            'dias_vacacion' => new Zend_Db_Expr("t.dias_vacacion"),
            'dias_vacacion_fichaje' => new Zend_Db_Expr("t.dias_vacacion_fichaje"),
            'porcentaje_asistencia' => new Zend_Db_Expr("t.porcentaje_asistencia"),
            'horas_promedio_asistencia' => new Zend_Db_Expr("to_char((CASE WHEN t.dias_asistencia > 0 THEN (t.horas_asistencia/t.dias_asistencia) ELSE '00:00' END),'hh24:mi')"),
            'horas_promedio_fichaje_vacacion' => new Zend_Db_Expr("to_char((CASE WHEN t.dias_vacacion_fichaje > 0 THEN (t.horas_asistencia_vacacion/t.dias_vacacion_fichaje) ELSE '00:00' END),'hh24:mi')")
        ));

        //Zend_Debug::dd($select->__toString());
        //Zend_Debug::dd($select->assemble()); //Para Visualizar codigo Query
        //Zend_Debug::dd(json_encode($tFichaje->fetchAll($select)->toArray())); //Para probar resultado
        return $tDatosBasicos->getAdapter()->fetchAll($sqlB);
    }

    /** 
     * Retorna el detalle de fichadas de un trabajador en especifico en un rango de fecha inicial y final especifico
     * 
     * @param string $cedula Numero de cedula del trabajador
     * @param string $f_desde Fecha inicial en formato yyyy-mm-dd
     * @param string $f_hasta Fecha final en formato yyyy-mm-dd
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getDetalleAsistenciaTrabajador($cedula, $f_desde, $f_hasta) {

        $tFichaje = new Application_Model_DbTable_Sigrh_Fichaje();
        $tDispositivos = new Application_Model_DbTable_Sigrh_DispositivoFichaje();

        $select = $tFichaje->select()->setIntegrityCheck(false)
        ->from(
            array('t1'=> $tFichaje->info(Zend_Db_Table::NAME)),
            array(
                'dia'                       => new Zend_Db_Expr("to_char(t1.fecha,'day')::VARCHAR"),
                'fecha'                     => new Zend_Db_Expr("to_char(t1.fecha,'dd-mm-yyyy')"),   
                'fecha_completa'            => 't1.fecha',
                'localidad'                 => 't2.localidad',
                'serial'                    => 't2.serial',
                'descripcion'               => 't2.descripcion',
                'evento'                    => 't1.evento'      
            ),
            $tFichaje->info(Zend_Db_Table::SCHEMA)
        )   
        ->joinleft(array('t2'=>$tDispositivos->info(Zend_Db_Table::NAME)),'t2.serial = t1.id_dispositivo', array(),$tDispositivos->info(Zend_Db_Table::SCHEMA))
        ->where("t1.cedula IN ($cedula)")        
        ->where("to_char(t1.fecha,'yyyy-mm-dd') BETWEEN '$f_desde' AND '$f_hasta'")                         
        ->order("t1.fecha");   
        
        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        return $tFichaje->getAdapter()->fetchAll($select);
    }

    /** 
     * Retorna retorna la descripcion de una gerencia segun su centro de costo
     * 
     * @param string $gerencia Centro de costo de dos digitos que identifica la gerencia
     * @return Zend_Db_Table_Row
     */
    public static function getDescripcionGerencia($gerencia){

        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();

        $select = $tCentroCosto->select()->setIntegrityCheck(false)
        ->from(
            array('t1'=> $tCentroCosto->info(Zend_Db_Table::NAME)),
            array(
                'gerencia'              => 't1.ceco_ceco',
                'descripcion_gerencia'  => 't1.ceco_descri'
            ),
            $tCentroCosto->info(Zend_Db_Table::SCHEMA)
        )
        ->where("substr(t1.ceco_ceco,1,2) = '$gerencia'")
        ->where("length(t1.ceco_ceco) = 2");

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        return $tCentroCosto->getAdapter()->fetchRow($select);   
    }

    /** 
     * Retorna informacion de datos basicos de un trabajador segun su cedula
     * 
     * @param string $cedula Cedula del trabajador
     * @return Zend_Db_Table_Row
     */
    public static function getDetalleTrabajador($cedula){

        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();
        $tCargo = new Fmo_DbTable_Rpsdatos_Cargo();
        $tLista = new Fmo_DbTable_Rpsdatos_Lista();
        $tUsuariosBiostar = new Application_Model_DbTable_Sigrh_UsuariosBiostar();


        $select = $tDatosBasicos->select()->setIntegrityCheck(false)
        ->from(
            array('t1'=> $tDatosBasicos->info(Zend_Db_Table::NAME)),
            array(
                'descripcion_gerencia'      => new Zend_Db_Expr("TRIM(' ' FROM t2.ceco_descri)"),
                'descripcion_departamento'  => new Zend_Db_Expr("TRIM(' ' FROM t3.ceco_descri)"), 
                'descripcion_cargo'         => new Zend_Db_Expr("TRIM(' ' FROM t5.carg_descri)"),  
                'ficha'                     => 't1.datb_nrotrab',
                'cedula'                    => 't1.datb_cedula',
                'nombre'                    => new Zend_Db_Expr("TRIM(' ' FROM REPLACE(rpad(t1.datb_nombre||' '|| t1.datb_apellid,40),$$'$$,' '))"), 
                'actividad'                 => new Zend_Db_Expr("t6.list_descri::VARCHAR(15)"), 
                'nomina'                    => 't4.tpno_descri',
                'fecha_ingreso'             => new Zend_Db_Expr("to_char(t1.datb_fecing,'dd-mm-yyyy')"),
                'fecha_emision_ini'        => new Zend_Db_Expr("to_char(t7.fecha_emision_ini,'dd-mm-yyyy')"),
                'emisiones_tarj'            => 't7.emisiones_tarj',
                'usr_status'                => new Zend_Db_Expr("CASE WHEN t7.usr_status = 'N' THEN 'Activa' ELSE 'Desactivada' END")
            ),
            $tDatosBasicos->info(Zend_Db_Table::SCHEMA)
        )   
        ->joinInner(array('t2'=>$tCentroCosto->info(Zend_Db_Table::NAME)),'t2.ceco_ceco = substr(t1.datb_ceco,1,2)', array(),$tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t3'=>$tCentroCosto->info(Zend_Db_Table::NAME)),'t3.ceco_ceco = t1.datb_ceco', array(),$tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t4'=>$tTipoNomina->info(Zend_Db_Table::NAME)),'t4.tpno_tpno = t1.datb_tpno', array(),$tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t5'=>$tCargo->info(Zend_Db_Table::NAME)),'t5.carg_carg = t1.datb_carg', array(),$tCargo->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t6'=>$tLista->info(Zend_Db_Table::NAME)),'t6.list_codigo::INTEGER = t1.datb_activi', array(),$tLista->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t7'=>$tUsuariosBiostar->info(Zend_Db_Table::NAME)),'t7.cedula = t1.datb_cedula', array(),$tUsuariosBiostar->info(Zend_Db_Table::SCHEMA))    
        ->where("t1.datb_cedula IN ($cedula)")
        ->where("t6.list_apli = 'SNV610'")
        ->where("t6.list_list = '5'");

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        return $tDatosBasicos->getAdapter()->fetchRow($select);   
    }


    /** 
     * Retorna la informacion detallada de vacaciones de un trabajador en un rango de fecha inicial y final
     * 
     * @param string $cedula Cedula del trabajador
     * @param string $f_desde Fecha inicial en formato dd-mm-yyyy
     * @param string $f_hasta Fecha final en formato dd-mm-yyyy
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getDetalleVacacionesTrabajador($cedula,$f_desde,$f_hasta){

        $tVacaciones = new Application_Model_DbTable_Rpsdatos_Vacaciones();

        $select = $tVacaciones->select()->setIntegrityCheck(false)
        ->from(
            array('ft'=> $tVacaciones->info(Zend_Db_Table::NAME)),
            array(
                'cedula'                => "ft.vaca_cedula",
                'fecha_disfrute'        => new Zend_Db_Expr("to_char(ft.vaca_fecdisf::DATE,'dd-mm-yyyy')"),
                'fecha_final'           => new Zend_Db_Expr("to_char(ft.vaca_fecincr::DATE,'dd-mm-yyyy')")   
            ),
            $tVacaciones->info(Zend_Db_Table::SCHEMA)
        )
        ->where("ft.vaca_cedula IN ($cedula)")
        ->where("ft.vaca_status = 'L'")
        ->where("('$f_desde' BETWEEN ft.vaca_fecdisf::DATE AND ft.vaca_fecincr::DATE OR '$f_hasta' BETWEEN ft.vaca_fecdisf::DATE AND ft.vaca_fecincr::DATE)");

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        return $tVacaciones->getAdapter()->fetchAll($select);   
    }

    /** 
     * Retorna el porcentaje general de asistencia de las gerencias segun un rango de fecha inicial y final,
     * con OPCIONAL de filtrado de lugar de pago
     * 
     * @param string $f_desde Fecha inicial en formato dd-mm-yyyy
     * @param string $f_hasta Fecha final en formato dd-mm-yyyy
     * @param string $lugar_pago lugar de pago donde se realiza la consulta '1' para PUERTO ORDAZ, cualquier otro valos CIUDAD PIAR
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getPorcentajeAsistencia($f_desde, $f_hasta, $lugar_pago) {

        $dias_habiles = self::getDiasHabiles($f_desde, $f_hasta);
        $dias = $dias_habiles->dias_habiles == 0 ? 1 : $dias_habiles->dias_habiles;

        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $select = $tDatosBasicos->select()->setIntegrityCheck(false)
        ->from(
            array('t1' => $tDatosBasicos->info(Zend_Db_Table::NAME)), 
            array(
                'gerencia' => new Zend_Db_Expr('substr(t1.datb_ceco,1,2)'),
                'descripcion_gerencia' => "TRIM(' ' FROM t3.ceco_descri)",
                'descripcion_departamento' => "TRIM(' ' FROM t4.ceco_descri)",
                'ficha' => "TRIM(' ' FROM t1.datb_nrotrab)",
                'cedula' => 't1.datb_cedula',
                'nombre' => new Zend_Db_Expr("TRIM(' ' FROM REPLACE(rpad(t1.datb_nombre||' '|| t1.datb_apellid,40),$$'$$,' '))"),
                'nomina' => "TRIM(' ' FROM t2.tpno_descri)",
                'actividad' => new Zend_Db_Expr('t5.list_descri::VARCHAR(15)'),
                'fecha_ingreso' => new Zend_Db_Expr("to_char(t1.datb_fecing,'dd/mm/yyyy')"),
                'dias_habiles' => new Zend_Db_Expr("sch_sigrh.dia_labor(2,t1.datb_cedula,t1.datb_fecing,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'dias_asistencia' => new Zend_Db_Expr("sch_sigrh.dia_labor(3,t1.datb_cedula,null,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'dias_vacacion' => new Zend_Db_Expr("sch_sigrh.dia_vacacion(1,t1.datb_cedula,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'dias_vacacion_fichaje'=> new Zend_Db_Expr("sch_sigrh.dia_vacacion(2,t1.datb_cedula,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))"),
                'porc_asistencia' => new Zend_Db_Expr(
                    "CASE WHEN (sch_sigrh.dia_labor(2,t1.datb_cedula,t1.datb_fecing,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))) > 0
                    THEN round(((sch_sigrh.dia_labor(3,t1.datb_cedula,null,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd')) 
                    + sch_sigrh.dia_vacacion(2,t1.datb_cedula,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd')))
                    / sch_sigrh.dia_labor(2,t1.datb_cedula,t1.datb_fecing,to_date('$f_desde','yyyy-mm-dd'), to_date('$f_hasta','yyyy-mm-dd'))*100),2) 
                    ELSE 0.00 
                    END"
                )
            ),
            $tDatosBasicos->info(Zend_Db_Table::SCHEMA) 
        )
        ->joinInner(array('t2' => $tTipoNomina->info(Zend_Db_Table::NAME)), 't2.tpno_tpno = t1.datb_tpno', array(), $tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t3' => $tCentroCosto->info(Zend_Db_Table::NAME)), 't3.ceco_ceco = substr(t1.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t4' => $tCentroCosto->info(Zend_Db_Table::NAME)), 't4.ceco_ceco = t1.datb_ceco', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t5' => 'sq_tlist'), 't1.datb_activi = t5.list_codigo::integer', array(), 'sch_rpsdatos')
        ->where("t1.datb_activi NOT IN (0,9)")
        ->where("t1.datb_tpno IN ('1','2','3','5','6')")
        ->where("t1.datb_fecing <= to_date('$f_hasta','yyyy-mm-dd')")
        ->where("length(t3.ceco_ceco) = 2")
        ->where("t5.list_apli = 'SNV610'")
        ->where("t5.list_list = '5'")
        ->order(array('substr(t1.datb_ceco,1,2)', 't4.ceco_descri', 't1.datb_nrotrab'));
        //Zend_Debug::dd($select->__toString());

        $sqlTran = $tCentroCosto->select()->setIntegrityCheck(false)
        ->from(
            array('t1' => $select), 
            array(
                'codigo'               => 't1.gerencia',
                'descripcion_gerencia' => 't1.descripcion_gerencia',
                'lugar_pago'           => new Zend_Db_Expr("CASE WHEN db.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END"),
                'dias_habiles'         => new Zend_Db_Expr($dias_habiles->dias_habiles),
                'cant_1_25'            => new Zend_Db_Expr("count(CASE WHEN t1.porc_asistencia BETWEEN 0 AND 25 THEN 1 end)"),
                'cant_26_50'           => new Zend_Db_Expr("count(CASE WHEN t1.porc_asistencia BETWEEN 26 AND 50 THEN 1 end)"),
                'cant_51_75'           => new Zend_Db_Expr("count(CASE WHEN t1.porc_asistencia BETWEEN 51 AND 75 THEN 1 end)"),
                'cant_76_mas'          => new Zend_Db_Expr("count(CASE WHEN t1.porc_asistencia >=  76 THEN 1 end)"),
                'total'                => new Zend_Db_Expr("count(t1.cedula)")
            )
        )
        ->join(array('db' => $tDatosBasicos->info(Zend_Db_Table::NAME)), 't1.cedula = db.datb_cedula AND db.datb_activi NOT IN (0, 9)', null, $tDatosBasicos->info(Zend_Db_Table::SCHEMA))
        //->where('db.datb_lugp NOT IN (4, 6)')
        ->group(new Zend_Db_Expr("1, 2, 3"))
        ->order(1, 2);

        if ($lugar_pago == 1) {
            $sqlTran->where('db.datb_lugp not in (4)');
        }
        if ($lugar_pago == 4) {
            $sqlTran->where('db.datb_lugp = 4');
        }

        //Zend_Debug::dd($sqlTran->__toString());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tDatosBasicos->getAdapter()->fetchAll($select));  
        return $tDatosBasicos->getAdapter()->fetchAll($sqlTran);
    }

    /** 
     * Retorna la cantidad de dias habiles/laborables de la empresa segun un rango de fecha inicial y final
     * 
     * @param string $f_desde Fecha inicial en formato dd-mm-yyyy
     * @param string $f_hasta Fecha final en formato dd-mm-yyyy
     * @return Zend_Db_Table_Row
     */
    private static function getDiasHabiles($f_desde, $f_hasta) {
        $tCalendario = new Application_Model_DbTable_Rpsdatos_Calendario();

        $select = $tCalendario->select()->setIntegrityCheck(false)
        ->from(
            array('t1' =>  $tCalendario->info(Zend_Db_Table::NAME)), 
            array(
                'dias_habiles' => new Zend_Db_Expr('COUNT(t1.cale_fecdia)')
            ),
            $tCalendario->info(Zend_Db_Table::SCHEMA)
        )
        ->where("t1.cale_fecdia between '$f_desde' AND '$f_hasta'")
        ->where("t1.cale_cndfers = 'N'")
        ->where("to_char(t1.cale_fecdia,'dy') NOT IN ('sat','sun')");


        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tCalendario->getAdapter()->fetchRow($select));  
        return $diasHabiles = $tCalendario->getAdapter()->fetchRow($select);
    }


    /** 
     * Retorna la fecha maxima de fichadas cargadas en sistema
     * 
     * @return Zend_Db_Table_Row
     */
    public static function getMaxFechaCargada() {

        $tFichaje = new Application_Model_DbTable_Sigrh_Fichaje();

        $select = $tFichaje->select()->setIntegrityCheck(false)
        ->from(
            array('ft' => $tFichaje->info(Zend_Db_Table::NAME)), 
            array(
                'fecha_cargada' => new Zend_Db_Expr("to_char(MAX(ft.fecha),'dd-mm-yyyy')"),
                'fecha_hora_cargada' => new Zend_Db_Expr("to_char(MAX(ft.fecha),'dd/mm/yyyy HH:MI AM')")
            ),
            $tFichaje->info(Zend_Db_Table::SCHEMA)
        );

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchRow($select));  
        return $tFichaje->getAdapter()->fetchRow($select);
    }

    /** 
     * Retorna la hoja de tiempo del trabajador según un rango de fecha dado y su ficha
     * 
     * @param string $f_desde Fecha inicial en formato dd-mm-yyyy
     * @param string $f_hasta Fecha final en formato dd-mm-yyyy
     * @param string $ficha ficha del trabajador
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getHojaDeTiempo($f_desde, $f_hasta, $ficha){


        $tFichaje = new Application_Model_DbTable_Sigrh_Fichaje();
        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $select = $tFichaje->select()->setIntegrityCheck(false)
        ->from(
            array('f1' => 'control_fichaje_dia'), 
            array(
                'cedula'            => 'f3.datb_cedula',
                'dia'               => new Zend_Db_Expr("to_char(f1.fecdia,'day')"),
                'fecha_dia'         => new Zend_Db_Expr("to_char(f1.fecdia,'dd-mm-yyyy')"),
                'turno'             => 'f1.turno',     
                'entrada_nomina'    => new Zend_Db_Expr("to_char(f1.fecdia_ini,'dd-mm-yyyy')"),
                'entrada_nomina_hora'    => new Zend_Db_Expr("to_char(f1.fecdia_ini,'HH12:MI AM')"),
                'salida_nomina'     => new Zend_Db_Expr("to_char(f1.fecdia_fin,'dd-mm-yyyy')"),
                'salida_nomina_hora'=> new Zend_Db_Expr("to_char(f1.fecdia_fin,'HH12:MI AM')"),
                'descanso_legal'    => 'f1.turteo',
                'codigo_nomina'     => new Zend_Db_Expr("f4.gene_descri"),
                'horas_nomina'      => new Zend_Db_Expr("to_char(f1.hora_nomina,'HH24:MI')"), 
                'entrada_biostar'   => new Zend_Db_Expr("to_char(f1.fecha_ini_fichaje,'dd-mm-yyyy')"),
                'entrada_biostar_hora'   => new Zend_Db_Expr("to_char(f1.fecha_ini_fichaje,'HH12:MI AM')"),
                'salida_biostar'    => new Zend_Db_Expr("to_char(f1.fecha_fin_fichaje,'dd-mm-yyyy')"),
                'salida_biostar_hora'    => new Zend_Db_Expr("to_char(f1.fecha_fin_fichaje,'HH12:MI AM')"),
                'codigo_biostar'    => new Zend_Db_Expr("f2.descripcion_biostar"),
                'horas_biostar'     => new Zend_Db_Expr("to_char(f1.hora_biostar,'HH24:MI')"),
            ),
           'sch_sigrh'
        )
        ->joinInner(array('f2' => 'control_fichaje_motivo'),' f2.codigo_biostar = f1.codigo_biostar', array(), 'sch_sigrh')
        ->joinInner(array('f3' => $tDatosBasicos->info(Zend_Db_Table::NAME)),'f3.datb_cedula = f1.cedula', array(), $tDatosBasicos->info(Zend_Db_Table::SCHEMA))
        ->joinFull(array('f4' => 'sn_tgene'),'f4.gene_gene = f1.codigo_nomina', array(), 'sch_rpsdatos')
        ->joinInner(array('f5' => $tCentroCosto->info(Zend_Db_Table::NAME)),'f5.ceco_ceco = substr(f3.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('f6' => $tCentroCosto->info(Zend_Db_Table::NAME)),'f6.ceco_ceco = f3.datb_ceco', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('f7' => $tTipoNomina->info(Zend_Db_Table::NAME)),'f7.tpno_tpno = f3.datb_tpno', array(), $tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('f8' => 'sq_tlist'),'f8.list_codigo::integer = f3.datb_activi', array(), 'sch_rpsdatos')
        ->where("f1.fecdia BETWEEN '$f_desde' AND '$f_hasta'")
        ->where("f3.datb_nrotrab = '$ficha'")
        ->where("length(f5.ceco_ceco) = 2")
        ->where("f8.list_apli = 'SNV610'")
        ->where("f8.list_list = '5'")
        ->order(array("f1.fecdia"));

        // $prueba = 
        // "SELECT monday AS dia
        // ,0 AS fecha_dia
        // ,0 AS turno   
        // ,0 AS entrada_nomina
        // ,0 AS salida_nomina
        // ,0 AS descanso_legal
        // ,0 AS codigo_nomina
        // ,0 AS horas_nomina
        // ,0 AS entrada_biostar
        // ,0 AS salida_biostar
        // ,0 AS codigo_biostar
        // ,0 AS horas_biostar";

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchRow($select));  
        return $tFichaje->getAdapter()->fetchAll($select);
    }

    /** 
     * Retorna el listado de tiempo de trabajos laborados de los trabajadores segun una fecha inicial y final
     * y opcionalmente una ficha
     * 
     * @param string $f_desde Fecha inicial en formato dd-mm-yyyy
     * @param string $f_hasta Fecha final en formato dd-mm-yyyy
     * @param string $ficha ficha del trabajador
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getConsultaDiariaControl($f_desde, $f_hasta, $gerencia, $ficha = NULL){

        $tFichaje = new Application_Model_DbTable_Sigrh_Fichaje();
        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $select = $tFichaje->select()->setIntegrityCheck(false)
        ->from(
            array('f1' => 'control_fichaje_dia'), 
            array(
                'gerencia'              => 'f5.ceco_descri',
                'departamento'          => 'f6.ceco_descri',
                'ficha'                 => 'f3.datb_nrotrab',
                'cedula'                => 'f3.datb_cedula',
                'nombre'                => new Zend_Db_Expr("TRIM(' ' FROM REPLACE(rpad(f3.datb_nombre||' '|| f3.datb_apellid,40),$$'$$,' '))"),
                'dia'                   => new Zend_Db_Expr("to_char(f1.fecdia,'day')"),
                'fecha_dia'             => new Zend_Db_Expr("to_char(f1.fecdia,'dd-mm-yyyy')"),
                'turno'                 => 'f1.turno',     
                'entrada_nomina'        => 'f1.fecdia_ini',//new Zend_Db_Expr("to_char(f1.fecdia_ini,'dd-mm-yyyy HH12:MI AM')"),
                'salida_nomina'         => 'f1.fecdia_fin',//new Zend_Db_Expr("to_char(f1.fecdia_fin,'dd-mm-yyyy HH12:MI AM')"),
                'descanso_legal'        => 'f1.turteo',
                'descripcion_nomina'    => 'f4.gene_descri',
                'horas_nomina'          => new Zend_Db_Expr("to_char(f1.hora_nomina,'HH24:MI')"), 
                'entrada_biostar'       => 'f1.fecha_ini_fichaje',//new Zend_Db_Expr("to_char(f1.fecha_ini_fichaje,'dd-mm-yyyy HH12:MI AM')"),
                'salida_biostar'        => 'f1.fecha_fin_fichaje',//new Zend_Db_Expr("to_char(f1.fecha_fin_fichaje,'dd-mm-yyyy HH12:MI AM')"),
                'descripcion_biostar'   => 'f2.descripcion_biostar',
                'horas_biostar'         => new Zend_Db_Expr("to_char(f1.hora_biostar,'HH24:MI')"),
            ),
           'sch_sigrh'
        )
        ->joinInner(array('f2' => 'control_fichaje_motivo'),' f2.codigo_biostar = f1.codigo_biostar', array(), 'sch_sigrh')
        ->joinInner(array('f3' => $tDatosBasicos->info(Zend_Db_Table::NAME)),'f3.datb_cedula = f1.cedula', array(), $tDatosBasicos->info(Zend_Db_Table::SCHEMA))
        ->joinFull(array('f4' => 'sn_tgene'),'f4.gene_gene = f1.codigo_nomina', array(), 'sch_rpsdatos')
        ->joinInner(array('f5' => $tCentroCosto->info(Zend_Db_Table::NAME)),'f5.ceco_ceco = substr(f3.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('f6' => $tCentroCosto->info(Zend_Db_Table::NAME)),'f6.ceco_ceco = f3.datb_ceco', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('f7' => $tTipoNomina->info(Zend_Db_Table::NAME)),'f7.tpno_tpno = f3.datb_tpno', array(), $tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('f8' => 'sq_tlist'),'f8.list_codigo::integer = f3.datb_activi', array(), 'sch_rpsdatos')
        ->where("f1.fecdia BETWEEN '$f_desde' AND '$f_hasta'")
        ->where('f3.datb_activi NOT IN (9)')
        ->where("length(f5.ceco_ceco) = 2")
        ->where("f8.list_apli = 'SNV610'")
        ->where("f8.list_list = '5'")
        ->order(array('f5.ceco_descri', 'f3.datb_cedula', "f1.fecdia"));

        if($ficha == NULL){
            if($gerencia){
                //SEGURIDAD DE SASINZF POR CENTRO DE COSTO
                $cedusuario = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::CEDULA};
                $datosPermiso = Application_Model_PermisoCeco::getAllPermisoCeco($cedusuario)->toArray();
                foreach($datosPermiso as $permiso){
                    if(strlen($permiso['ceco_ceco']) == 1){
                        $select->where("f3.datb_ceco LIKE ANY (ARRAY['".$gerencia."%'])");  
                    }
                    if(strlen($permiso['ceco_ceco']) == 2 && $permiso['ceco_ceco'] == $gerencia){
                        $select->where("f3.datb_ceco LIKE ANY (ARRAY['".$gerencia."%'])");
                    }
                    if(strlen($permiso['ceco_ceco']) == 5 && substr($permiso['ceco_ceco'],0,2) == $gerencia){
                        $select->where("f3.datb_ceco LIKE ANY (ARRAY['".$permiso['ceco_ceco']."%'])");
                    }
                    
                }
            }
        }
        else {
            $trabajador = Fmo_Model_Personal::findOneByFicha($ficha);
            if(Application_Model_PermisoCeco::puedeVerTrabajador($trabajador)){
                $select->where("f3.datb_nrotrab = '$ficha'");
            }
            else { 
                return NULL; 
            }
        }

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchRow($select));  
        //Zend_Debug::dd(count($tFichaje->getAdapter()->fetchAll($select)));
        return $tFichaje->getAdapter()->fetchAll($select);
    }

    /** 
     * Retorna el listado de las fichadas los trabajadores segun una fecha inicial y final
     * y opcionalmente una ficha
     * 
     * @param string $f_desde Fecha inicial en formato yyyy-mm-dd
     * @param string $f_hasta Fecha final en formato yyyy-mm-dd
     * @param string $ficha ficha del trabajador
     * @param string $dispositivo dispositivo fichado
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public static function getConsultaDetalleTrabajadores($f_desde, $f_hasta, $gerencia, $ficha = NULL, $dispositivo = NULL){

        $tFichaje = new Application_Model_DbTable_Sigrh_Fichaje();
        $tDispositivos = new Application_Model_DbTable_Sigrh_DispositivoFichaje();
        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $select = $tFichaje->select()->setIntegrityCheck(false)
        ->from(
            array('t1'=> $tFichaje->info(Zend_Db_Table::NAME)),
            array(
                'gerencia'          => 't4.ceco_descri',
                'departamento'      => 't5.ceco_descri',
                'ficha'             => 't3.datb_nrotrab',
                'cedula'            => 't3.datb_cedula',
                'nombre'            => new Zend_Db_Expr("TRIM(' ' FROM REPLACE(t3.datb_nombre || ' ' || t3.datb_apellid, $$'$$, ' '))"),
                'nomina'            => new Zend_Db_Expr("TRIM(' ' FROM t6.tpno_descri)"),
                'actividad'         => new Zend_Db_Expr("TRIM(' ' FROM t7.list_descri)"),
                'fecha_completa'    => 't1.fecha',
                'dia'               => new Zend_Db_Expr("CASE 
                                        WHEN to_char(t1.fecha,'day') ILIKE '%monday%' THEN 'LUN'
                                        WHEN to_char(t1.fecha,'day') ILIKE '%tuesday%' THEN 'MAR'
                                        WHEN to_char(t1.fecha,'day') ILIKE '%wednesday%' THEN 'MIE'
                                        WHEN to_char(t1.fecha,'day') ILIKE '%thursday%' THEN 'JUE'
                                        WHEN to_char(t1.fecha,'day') ILIKE '%friday%' THEN 'VIE'
                                        WHEN to_char(t1.fecha,'day') ILIKE '%saturday%' THEN  'SAB'
                                        WHEN to_char(t1.fecha,'day') ILIKE '%sunday%' THEN 'DOM'
                                        ELSE to_char(t1.fecha,'day') 
                                    END"),
                'fecha'             => new Zend_Db_Expr("to_char(t1.fecha,'dd-mm-yyyy')"),  
                'hora'              => new Zend_Db_Expr("to_char(t1.fecha,'HH12:MI AM')"),
                'descripcion'       => 't2.descripcion',
                'evento'            => new Zend_Db_Expr("UPPER(t1.evento)")
            ),
            $tFichaje->info(Zend_Db_Table::SCHEMA)
        )   
        ->joinInner(array('t2' => $tDispositivos->info(Zend_Db_Table::NAME)),'t2.serial = t1.id_dispositivo', array(),$tDispositivos->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t3' => $tDatosBasicos->info(Zend_Db_Table::NAME)),'t3.datb_cedula = t1.cedula', array(),$tDatosBasicos->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t4' => $tCentroCosto->info(Zend_Db_Table::NAME)),'t4.ceco_ceco = substr(t3.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t5' => $tCentroCosto->info(Zend_Db_Table::NAME)),'t5.ceco_ceco = t3.datb_ceco', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t6' => $tTipoNomina->info(Zend_Db_Table::NAME)),'t6.tpno_tpno = t3.datb_tpno', array(), $tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t7' => 'sq_tlist'),'t7.list_codigo::integer = t3.datb_activi', array(), 'sch_rpsdatos')      
        ->where("to_char(t1.fecha,'yyyy-mm-dd') BETWEEN '$f_desde' AND '$f_hasta'")     
        ->where('t3.datb_activi NOT IN (9)')
        ->where("length(t4.ceco_ceco) = 2")
        ->where("t7.list_apli = 'SNV610'")
        ->where("t7.list_list = '5'")
        ->order(
            array(
                't4.ceco_descri', 
                't5.ceco_descri',
                't3.datb_nrotrab',
                't1.fecha',
            )
        );

        if($dispositivo != NULL){
            $select->where("t2.serial = $dispositivo");
        }

        if($ficha == NULL){
            //SEGURIDAD DE SASINZF POR CENTRO DE COSTO
            $cedusuario = Fmo_Model_Seguridad::getUsuarioSesion()->{Fmo_Model_Personal::CEDULA};
            $datosPermiso = Application_Model_PermisoCeco::getAllPermisoCeco($cedusuario)->toArray();
            foreach($datosPermiso as $permiso){
                if(strlen($permiso['ceco_ceco']) == 1){
                    $select->where("t3.datb_ceco LIKE ANY (ARRAY['".$gerencia."%'])");  
                }
                if(strlen($permiso['ceco_ceco']) == 2 && $permiso['ceco_ceco'] == $gerencia){
                    $select->where("t3.datb_ceco LIKE ANY (ARRAY['".$gerencia."%'])");
                }
                if(strlen($permiso['ceco_ceco']) == 5 && substr($permiso['ceco_ceco'],0,2) == $gerencia){
                    $select->where("t3.datb_ceco LIKE ANY (ARRAY['".$permiso['ceco_ceco']."%'])");
                }
            }
        } else {
            $trabajador = Fmo_Model_Personal::findOneByFicha($ficha);
            if(Application_Model_PermisoCeco::puedeVerTrabajador($trabajador)){
                $select->where("t3.datb_nrotrab = '$ficha'");
            }
            else { 
                return NULL; 
            }
        }



        // if($count){
        //     $selectCount = $tFichaje->select()->setIntegrityCheck(false)
        //     ->from(
        //         array('t' => $select),
        //         array(
        //             'count' => new Zend_Db_Expr("COUNT(t.*)"),
        //         ),
        //         NULL
        //     );
        //     //Zend_Debug::dd($tFichaje->getAdapter()->fetchRow($selectCount));
        //     echo "<script> console.log('PHP_MEMORY_USAGE_PEAK: ',",round(memory_get_peak_usage()/1024, 2),");</script>"; //MEMORIA ACTUAL
        //     echo "<script> console.log('PHP_MEMORY_USAGE: ',",round(memory_get_usage()/1000000,2),");</script>"; //MEMORIA ACTUAL
        //     return $tFichaje->getAdapter()->fetchRow($selectCount)->count;
        // }
        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        //Zend_Debug::dd(count($tFichaje->getAdapter()->fetchAll($select)));
        //Zend_Debug::dd($tFichaje->fetchAll($select)->toArray());
        return $tFichaje->getAdapter()->fetchAll($select);
    }

    public static function getResumenAsistenciaGerencia($f_desde, $f_hasta, $localidad = NULL){

        $tFichajeDia = new Application_Model_DbTable_Sigrh_FichajeDia();
        $tFichajeMotivo = new Application_Model_DbTable_Sigrh_ControlFichajeMotivo();
        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        $select = $tFichajeDia->select()->setIntegrityCheck(false)
        ->from(
            array('f1'=> $tFichajeDia->info(Zend_Db_Table::NAME)),
            array(
                'descripcion_gerencia' => 'b1.ceco_descri',
                'lugar_pago' => new Zend_Db_Expr("(CASE WHEN b3.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END)"),
                // 'fecha_dia' => new Zend_Db_Expr("(to_char(f1.fecdia,'dd-mm-yyyy'))"),
                'total' => new Zend_Db_Expr("(COUNT(DISTINCT b3.datb_cedula))"),
            ),
            $tFichajeDia->info(Zend_Db_Table::SCHEMA)
        )
        ->joinleft(array('f2' => $tFichajeMotivo->info(Zend_Db_Table::NAME)),'f1.codigo_biostar = f2.codigo_biostar', array(),$tFichajeMotivo->info(Zend_Db_Table::SCHEMA))
        ->joinleft(array('f3' => 'sn_tgene'),'f3.gene_gene = f1.codigo_nomina', array(), 'sch_rpsdatos')
        ->joinInner(array('b3' => $tDatosBasicos->info(Zend_Db_Table::NAME)),'f1.cedula = b3.datb_cedula', array(), $tDatosBasicos->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('b1' => $tCentroCosto->info(Zend_Db_Table::NAME)),'b1.ceco_ceco = substr(b3.datb_ceco,1,2)', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('b2' => $tCentroCosto->info(Zend_Db_Table::NAME)),'b2.ceco_ceco = b3.datb_ceco', array(), $tCentroCosto->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t1' => $tTipoNomina->info(Zend_Db_Table::NAME)),'t1.tpno_tpno = b3.datb_tpno', array(), $tTipoNomina->info(Zend_Db_Table::SCHEMA))
        ->joinInner(array('t2' => 'sq_tlist'),'t2.list_codigo::INTEGER = b3.datb_activi', array(), 'sch_rpsdatos')     
        ->where("f1.fecdia::date BETWEEN '$f_desde' AND '$f_hasta'")
        ->where("LENGTH(b1.ceco_ceco) = 2  ")
        ->where("list_apli = 'SNV610' ")
        ->where("list_list = '5'")
        ->group(
            array(
                "b1.ceco_descri",
                // "to_char(f1.fecdia,'dd-mm-yyyy')",
                "(CASE WHEN b3.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END)",
            )
        )
        ->order(
            array(
                "b1.ceco_descri",
                // "to_char(f1.fecdia,'dd-mm-yyyy')",
                "(CASE WHEN b3.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END)",
            )
        );

        if($localidad != NULL){ 
            if(intval($localidad) == 4) { 
                $select->where("b3.datb_lugp = $localidad"); 
            }
            else { 
                $select->where("b3.datb_lugp <> 4"); 
            }
        }

        //Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        //Zend_Debug::dd(count($tFichaje->getAdapter()->fetchAll($select)));
        //Zend_Debug::dd($tFichaje->fetchAll($select)->toArray());
        return $tFichajeDia->getAdapter()->fetchAll($select);
    }

    public static function getResumenAsistenciaGrafico($f_desde, $f_hasta, $localidad = NULL){

        $tFichajeDia = new Application_Model_DbTable_Sigrh_FichajeDia();
        $tFichajeMotivo = new Application_Model_DbTable_Sigrh_ControlFichajeMotivo();
        $tDatosBasicos = new Fmo_DbTable_Rpsdatos_DatoBasico();
        $tCentroCosto = new Fmo_DbTable_Rpsdatos_CentroCosto();
        $tTipoNomina = new Fmo_DbTable_Rpsdatos_Nomina();

        // SELECT 
        // (to_char(f1.fecdia,'dd-mm-yyyy')) AS "Fecha Dia",
        // (COUNT(DISTINCT f2.datb_cedula)) AS "Total"
        // FROM sch_sigrh.control_fichaje_dia AS f1
        // INNER JOIN sch_rpsdatos.sn_tdatbas AS f2 ON (f1.cedula = f2.datb_cedula)
        // WHERE f1.fecdia::date BETWEEN '2020-07-01' AND '2020-07-31'
        // AND f2.datb_lugp <> 4
        // GROUP BY CASE WHEN f2.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END, to_char(f1.fecdia,'dd-mm-yyyy') 
        // ORDER BY to_char(f1.fecdia,'dd-mm-yyyy'), CASE WHEN f2.datb_lugp = 4 THEN 'CIUDAD PIAR' ELSE 'PUERTO ORDAZ' END

        $select = $tFichajeDia->select()->setIntegrityCheck(false)
        ->from(
            array('f1'=> $tFichajeDia->info(Zend_Db_Table::NAME)),
            array(
                'fecha_dia' => new Zend_Db_Expr("to_char(f1.fecdia,'dd-mm-yyyy')"),
                'total' => new Zend_Db_Expr("COUNT(DISTINCT f2.datb_cedula)"),
            ),
            $tFichajeDia->info(Zend_Db_Table::SCHEMA)
        )
        ->joinInner(array('f2' => $tDatosBasicos->info(Zend_Db_Table::NAME)),'f1.cedula = f2.datb_cedula', array(), $tDatosBasicos->info(Zend_Db_Table::SCHEMA)) 
        ->where("f1.fecdia::date BETWEEN '$f_desde' AND '$f_hasta'")
        ->group("f1.fecdia")
        ->order("f1.fecdia");

        if($localidad != NULL){ 
            if(intval($localidad) == 4) { $select->where("f2.datb_lugp = $localidad"); }
            else { $select->where("f2.datb_lugp <> 4");}
        }

        // Zend_Debug::dd($select->assemble());
        //print_r($select->__tostring());
        //Zend_Debug::dd($tFichaje->getAdapter()->fetchAll($select));  
        //Zend_Debug::dd(count($tFichaje->getAdapter()->fetchAll($select)));
        //Zend_Debug::dd($tFichaje->fetchAll($select)->toArray());
        return $tFichajeDia->getAdapter()->fetchAll($select);
    }
}
