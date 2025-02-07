<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

// namespace del controlador
namespace website\Dte;

/**
 * Controlador de ventas
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-03-27
 */
class Controller_DteVentas extends Controller_Base_Libros
{

    protected $config = [
        'model' => [
            'singular' => 'Venta',
            'plural' => 'Ventas',
        ]
    ]; ///< Configuración para las acciones del controlador

    /**
     * Método para permitir acciones sin estar autenticado
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-08-06
     */
    public function beforeFilter()
    {
        $this->Auth->allow('_api_historial_GET');
        parent::beforeFilter();
    }

    /**
     * Acción que envía el archivo XML del libro de ventas al SII
     * Si no hay documentos en el período se enviará sin movimientos
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-01-26
     */
    public function enviar_sii($periodo)
    {
        $Emisor = $this->getContribuyente();
        // si el libro fue enviado y no es rectifica error
        $DteVenta = new Model_DteVenta($Emisor->rut, $periodo, $Emisor->enCertificacion());
        if ($DteVenta->track_id and empty($_POST['CodAutRec']) and $DteVenta->getEstado()!='LRH' and $DteVenta->track_id!=-1) {
            \sowerphp\core\Model_Datasource_Session::message('Libro del período '.$periodo.' ya fue enviado, ahora sólo puede hacer rectificaciones', 'error');
            $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
        }
        // si el periodo es mayor o igual al actual no se puede enviar
        if ($periodo >= date('Ym')) {
            \sowerphp\core\Model_Datasource_Session::message('No puede enviar el libro de ventas del período '.$periodo.'. Debe esperar al mes siguiente del período para poder enviar.', 'error');
            $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
        }
        // verificar que no existen documentos rechazados sin estado en el periodo
        if ($DteVenta->countDteSinEstadoEnvioSII()) {
            \sowerphp\core\Model_Datasource_Session::message('Existen documentos sin el estado de envío al SII en el libro de ventas del período '.$periodo.'. Debe actualizar los estados de todos los documentos antes de poder generar el libro.', 'error');
            $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
        }
        // verificar que no existan documentos rechazados en el período
        if ($DteVenta->countDteRechazadosSII()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Existen documentos que han sido rechazados por el SII en el libro de ventas del período '.$periodo.'. Debe corregir los casos rechazados antes de poder generar el libro. [faq:48]', 'error'
            );
            $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
        }
        // obtener firma
        $Firma = $Emisor->getFirma($this->Auth->User->id);
        if (!$Firma) {
            \sowerphp\core\Model_Datasource_Session::message('No hay firma electrónica asociada a la empresa (o bien no se pudo cargar). Debe agregar su firma antes de enviar el libro al SII. [faq:174]', 'error');
            $this->redirect('/dte/admin/firma_electronicas');
        }
        // agregar carátula al libro
        $caratula = [
            'RutEmisorLibro' => $Emisor->rut.'-'.$Emisor->dv,
            'RutEnvia' => $Firma->getID(),
            'PeriodoTributario' => substr($periodo, 0, 4).'-'.substr($periodo, 4),
            'FchResol' => $Emisor->enCertificacion() ? $Emisor->config_ambiente_certificacion_fecha : $Emisor->config_ambiente_produccion_fecha,
            'NroResol' =>  $Emisor->enCertificacion() ? 0 : $Emisor->config_ambiente_produccion_numero,
            'TipoOperacion' => 'VENTA',
            'TipoLibro' => 'MENSUAL',
            'TipoEnvio' => 'TOTAL',
        ];
        if (!empty($_POST['CodAutRec'])) {
            $caratula['TipoLibro'] = 'RECTIFICA';
            $caratula['CodAutRec'] = $_POST['CodAutRec'];
        }
        // crear libro
        $Libro = $Emisor->getLibroVentas($periodo);
        $Libro->setCaratula($caratula);
        // se setean resúmenes manuales enviados por post
        if (isset($_POST['TpoDoc'])) {
            $resumen = [];
            $n_tipos = count($_POST['TpoDoc']);
            for ($i=0; $i<$n_tipos; $i++) {
                $cols = [
                    'TpoDoc',
                    'TotDoc',
                    'TotAnulado',
                    'TotOpExe',
                    'TotMntExe',
                    'TotMntNeto',
                    'TotMntIVA',
                    'TotIVAPropio',
                    'TotIVATerceros',
                    'TotLey18211',
                    'TotMntTotal',
                    'TotMntNoFact',
                    'TotMntPeriodo',
                ];
                $row = [];
                foreach ($cols as $col) {
                    if (!empty($_POST[$col][$i])) {
                        $row[$col] = $_POST[$col][$i];
                    }
                }
                $resumen[] = $row;
            }
            $Libro->setResumen($resumen);
        }
        // obtener XML
        $Libro->setFirma($Firma);
        $xml = $Libro->generar();
        if (!$xml) {
            \sowerphp\core\Model_Datasource_Session::message('No fue posible generar el libro de ventas<br/>'.implode('<br/>', \sasco\LibreDTE\Log::readAll()), 'error');
            $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
        }
        // enviar al SII sólo si el libro es de un período menor o igual al 201707
        // esto ya que desde 201708 se reemplaza por RCV
        if ($periodo <= 201707) {
            $track_id = $Libro->enviar();
            $revision_estado = null;
            $revision_detalle = null;
            if (!$track_id) {
                \sowerphp\core\Model_Datasource_Session::message('No fue posible enviar el libro de ventas al SII<br/>'.implode('<br/>', \sasco\LibreDTE\Log::readAll()), 'error');
                $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
            }
            \sowerphp\core\Model_Datasource_Session::message('Libro de ventas período '.$periodo.' envíado al SII.', 'ok');
        }
        // no se envía el libro al SII (se trata de enviar resumen boletas si existe)
        else {
            // se envía resumen de boletas si corresponde (hasta julio 2022)
            if ($periodo <= 202207) {
                $resumenes = $Libro->getResumenBoletas();
                $resumenes_errores = [];
                foreach ($resumenes as $resumen) {
                    try {
                        $r = libredte_api_consume('/sii/rcv/ventas/set_resumen/'.$Emisor->rut.'-'.$Emisor->dv.'/'.$periodo.'?certificacion='.$Emisor->enCertificacion(), [
                            'auth' => [
                                'cert' => [
                                    'cert-data' => $Firma->getCertificate(),
                                    'pkey-data' => $Firma->getPrivateKey(),
                                ],
                            ],
                            'documentos' => [
                                [
                                    'det_tipo_doc' => $resumen['TpoDoc'],
                                    'det_nro_doc' => $resumen['TotDoc'],
                                    'det_mnt_neto' => $resumen['TotMntNeto'],
                                    'det_mnt_iva' => $resumen['TotMntIVA'],
                                    'det_mnt_total' => $resumen['TotMntTotal'],
                                    'det_mnt_exe' => $resumen['TotMntExe'],
                                ],
                            ],
                        ]);
                        if ($r['status']['code']!=200) {
                            $resumenes_errores[] = $r['body'];
                        }
                    } catch (\Exception $e) {
                        $resumenes_errores[] = 'Esta versión de LibreDTE no puede enviar los resúmenes al SII de manera automática, debe copiarlos manualmente en el registro de ventas';
                    }
                }
            }
            // libro generado
            $track_id = -1;
            $revision_estado = 'Libro Local Generado';
            $revision_detalle = 'Este libro fue reemplazado por el Registro de Ventas';
            \sowerphp\core\Model_Datasource_Session::message('Libro de ventas del período '.$periodo.' generado localmente en LibreDTE. Recuerde que este libro se reemplazó con el Registro de Ventas en el SII.', 'ok');
            // si hay errores de resúmenes se muestran
            if (!empty($resumenes_errores)) {
                \sowerphp\core\Model_Datasource_Session::message('Ocurrió algún problema al enviar los resúmenes al SII:<br/>- '.implode('<br/>- ',$resumenes_errores), 'warning');
            }
        }
        // guardar libro de ventas
        $DteVenta->documentos = $Libro->cantidad();
        $DteVenta->xml = base64_encode($xml);
        $DteVenta->track_id = $track_id;
        $DteVenta->revision_estado = $revision_estado;
        $DteVenta->revision_detalle = $revision_detalle;
        $DteVenta->save();
        $this->redirect(str_replace('enviar_sii', 'ver', $this->request->request));
    }

    /**
     * Acción que genera el archivo CSV con el registro de ventas
     * En realidad esto descarga los datos que están localmente y no los del RV del SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-18
     */
    public function descargar_registro_venta($periodo)
    {
        $Emisor = $this->getContribuyente();
        $ventas = $Emisor->getVentas($periodo);
        if (!$ventas) {
            \sowerphp\core\Model_Datasource_Session::message(
                'No hay documentos de venta del período '.$periodo, 'warning'
            );
            $this->redirect(str_replace('descargar_registro_venta', 'ver', $this->request->request));
        }
        foreach ($ventas as &$v) {
            unset($v['anulado']);
        }
        $columnas = Model_DteVenta::$libro_cols;
        unset($columnas['anulado']);
        $columnas['tipo_transaccion'] = 'Tipo Transaccion';
        array_unshift($ventas, $columnas);
        $csv = \sowerphp\general\Utility_Spreadsheet_CSV::get($ventas);
        $this->response->sendContent($csv, 'rv_'.$Emisor->rut.'-'.$Emisor->dv.'_'.$periodo.'.csv');
    }

    /**
     * Acción que genera el archivo CSV con los resúmenes de ventas (ingresados manualmente)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2019-07-18
     */
    public function descargar_resumenes($periodo)
    {
        $Emisor = $this->getContribuyente();
        $Libro = new Model_DteVenta($Emisor->rut, (int)$periodo, $Emisor->enCertificacion());
        if (!$Libro->exists()) {
            \sowerphp\core\Model_Datasource_Session::message(
                'Aun no se ha generado el XML del período '.$periodo, 'error'
            );
            $this->redirect(str_replace('descargar_resumenes', 'ver', $this->request->request));
        }
        $xml = base64_decode($Libro->xml);
        $LibroCompraVenta = new \sasco\LibreDTE\Sii\LibroCompraVenta();
        $LibroCompraVenta->loadXML($xml);
        $resumenes = $LibroCompraVenta->getResumenManual() + $LibroCompraVenta->getResumenBoletas();
        if (!$resumenes) {
            \sowerphp\core\Model_Datasource_Session::message(
                'No hay resúmenes para el período '.$periodo, 'warning'
            );
            $this->redirect(str_replace('descargar_resumenes', 'ver', $this->request->request));
        }
        // generar CSV
        $datos = [['Tipo Docto', 'Numero de Doctos', 'Operaciones Exentas', 'Monto Exento', 'Montos Netos', 'Montos de IVA', 'Monto IVA Propio', 'Monto IVA Terceros', 'Ley 18.211', 'Monto Total']];
        foreach ($resumenes as $r) {
            $datos[] = [
                $r['TpoDoc'],
                $r['TotDoc'],
                $r['TotOpExe'],
                $r['TotMntExe'],
                $r['TotMntNeto'],
                $r['TotMntIVA'],
                $r['TotIVAPropio'],
                $r['TotIVATerceros'],
                $r['TotLey18211'],
                $r['TotMntTotal'],
            ];
        }
        $csv = \sowerphp\general\Utility_Spreadsheet_CSV::get($datos);
        $this->response->sendContent($csv, 'rv_resumenes_'.$periodo.'.csv');
    }

    /**
     * Acción que permite seleccionar el período para explorar el resumen del registro de ventas del SII
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-04-25
     */
    public function registro_ventas()
    {
        if (!empty($_POST['periodo'])) {
            $this->redirect('/dte/dte_ventas/rcv_resumen/'.$_POST['periodo']);
        }
    }

    /**
     * Acción que permite obtener el resumen del registro de venta para un período
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-10
     */
    public function rcv_resumen($periodo)
    {
        $Emisor = $this->getContribuyente();
        try {
            $resumen = $Emisor->getRCV(['operacion' => 'VENTA', 'periodo' => $periodo, 'estado' => 'REGISTRO', 'detalle'=>false]);
        } catch (\Exception $e) {
            \sowerphp\core\Model_Datasource_Session::message($e->getMessage(), 'error');
            $this->redirect('/dte/dte_ventas/ver/'.$periodo);
        }
        $this->set([
            'Emisor' => $Emisor,
            'periodo' => $periodo,
            'resumen' => $resumen,
        ]);
    }

    /**
     * Acción que permite obtener el detalle del registro de venta para un período
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-10
     */
    public function rcv_detalle($periodo, $dte)
    {
        $Emisor = $this->getContribuyente();
        try {
            $detalle = $Emisor->getRCV(['operacion' => 'VENTA', 'periodo' => $periodo, 'dte' => $dte, 'estado' => 'REGISTRO']);
        } catch (\Exception $e) {
            \sowerphp\core\Model_Datasource_Session::message($e->getMessage(), 'error');
            $this->redirect('/dte/dte_ventas/ver/'.$periodo);
        }
        if (!$detalle) {
            \sowerphp\core\Model_Datasource_Session::message('No hay detalle para el período y estado solicitados', 'warning');
            $this->redirect('/dte/dte_ventas/ver/'.$periodo);
        }
        $this->set([
            'Emisor' => $Emisor,
            'periodo' => $periodo,
            'DteTipo' => new \website\Dte\Admin\Mantenedores\Model_DteTipo($dte),
            'detalle' => $detalle,
        ]);
    }

    /**
     * Acción que permite obtener el detalle de documentos emitidos con cierto evento del receptor
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2017-09-12
     */
    public function eventos_receptor($periodo, $evento)
    {
        $Emisor = $this->getContribuyente();
        $DteVenta = new Model_DteVenta($Emisor->rut, $periodo, $Emisor->enCertificacion());
        $this->set([
            'Emisor' => $Emisor,
            'periodo' => $periodo,
            'Evento' => (object)['codigo'=>$evento, 'glosa'=>$evento?\sasco\LibreDTE\Sii\RegistroCompraVenta::$eventos[$evento]:'Sin evento registrado'],
            'documentos' => $DteVenta->getDocumentosConEventoReceptor($evento),
        ]);
    }

    /**
     * Acción que genera un resumen de las ventas de un año completo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-04-25
     */
    public function resumen($anio = null)
    {
        $Emisor = $this->getContribuyente();
        if (!empty($_POST['anio'])) {
            $this->redirect('/dte/dte_ventas/resumen/'.(int)$_POST['anio']);
        }
        if ($anio) {
            // obtener libros de cada mes con su resumen
            $DteVentas = (new Model_DteVentas())->setContribuyente($Emisor);
            $resumen = $DteVentas->getResumenAnual($anio);
            // crear operaciones
            $operaciones = [];
            foreach ($resumen as $r) {
                $operaciones[$r['TpoDoc']] = (new \website\Dte\Admin\Mantenedores\Model_DteTipos())->get($r['TpoDoc'])->operacion;
            }
            // asignar variable a vista
            $this->set([
                'anio' => $anio,
                'resumen' => $resumen,
                'operaciones' => $operaciones,
                'totales_mensuales' => $DteVentas->getTotalesMensuales($anio),
            ]);
        }
    }

    /**
     * Servicio web que entrega el historial de montos agrupados por mes de un receptor
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-08-06
     */
    public function _api_historial_GET($receptor, $fecha, $emisor, $dte = null, $folio = null, $total = null)
    {
        extract($this->getQuery([
            'formato' => 'json',
            'periodo_glosa' => true,
            'periodos' => 12,
        ]));
        // verificar usuario autenticado
        $Emisor = new Model_Contribuyente((int)$emisor);
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            if ($dte === null or $folio === null or $total === null) {
                $this->Api->send($User, 401);
            }
            if (is_numeric($folio)) {
                $Documento = new Model_DteEmitido($Emisor->rut, $dte, $folio, (int)$Emisor->enCertificacion());
            } else {
                $Documento = new Model_DteTmp($Emisor->rut, $receptor, $dte, $folio);
            }
            if (!$Documento->exists()) {
                $this->Api->send($User, 401);
            }
            if ($Documento->fecha != $fecha or $Documento->total != $total or $Documento->receptor != $receptor) {
                $this->Api->send($User, 401);
            }
        }
        if ($dte === null or $folio === null or $total === null) {
            if (!$Emisor->usuarioAutorizado($User, '/dte/dte_ventas/ver')) {
                $this->Api->send('No está autorizado a operar con la empresa solicitada', 403);
            }
        }
        // obtener historial
        $historial = $Emisor->getHistorialVentas($receptor, $fecha, $periodos);
        if ($periodo_glosa) {
            $historial_nuevo = [];
            foreach ($historial as $periodo => $monto) {
                $mes = substr(\sowerphp\general\Utility_Date::$meses[((int)substr($periodo,4))-1],0,3);
                $historial_nuevo[$mes] = $monto;
            }
            $historial = $historial_nuevo;
        }
        // entregar historial como un gráfico PNG
        if ($formato == 'png') {
            $n_historial = count($historial);
            $title = $n_historial > 1 ? ('Historial últimos '.$n_historial.' meses') : 'Historial último mes';
            $chart = new \Libchart\View\Chart\VerticalBarChart(250,195);
            $dataSet = new \Libchart\Model\XYDataSet();
            foreach ($historial as $periodo => $valor) {
                $dataSet->addPoint(new \Libchart\Model\Point($periodo, $valor));
            }
            $chart->setDataSet($dataSet);
            $chart->setTitle($title);
            $chart->getPlot()->setLogoFileName(false);
            $chart->getPlot()->setOuterPadding(new \Libchart\View\Primitive\Padding(0, 0, 0, 0));
            $chart->getPlot()->setTitlePadding(new \Libchart\View\Primitive\Padding(0, 0, 0, 0));
            $chart->getPlot()->setGraphPadding(new \Libchart\View\Primitive\Padding(0, 15, 25, 57));
            $chart->getPlot()->setCaptionPadding(new \Libchart\View\Primitive\Padding(0, 0, 0, 0));
            $chart->getPlot()->getPalette()->setBackgroundColor([
                new \Libchart\View\Color\Color(255, 255, 255),
                new \Libchart\View\Color\Color(255, 255, 255),
                new \Libchart\View\Color\Color(255, 255, 255),
                new \Libchart\View\Color\Color(255, 255, 255)
            ]);
            $chart->getPlot()->getPalette()->setAxisColor([
                new \Libchart\View\Color\Color(0, 0, 0),
                new \Libchart\View\Color\Color(0, 0, 0)
            ]);
            $chart->getPlot()->getPalette()->setBarColor([
                new \Libchart\View\Color\Color(100, 100, 100)
            ]);
            $chart->getConfig()->setShowPointCaption(false);
            ob_clean();
            $chart->render();
            $grafico = ob_get_contents();
            ob_clean();
            $this->Api->response()->type('image/png');
            $this->Api->send($grafico);
        }
        // entregar historial como JSON
        else {
            return $historial;
        }
    }

    /**
     * Servicio web que entrega un resumen de ventas por cada tipo de documento
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-02-28
     */
    public function _api_resumen_POST($emisor)
    {
        // verificar usuario autenticado
        $User = $this->Api->getAuthUser();
        if (is_string($User)) {
            $this->Api->send($User, 401);
        }
        // obtener historial
        $Emisor = new Model_Contribuyente($emisor);
        if (!$Emisor->usuarioAutorizado($User, '/dte/dte_ventas')) {
            $this->Api->send('No está autorizado a operar con la empresa solicitada', 403);
        }
        return (new Model_DteVentas())->setContribuyente($Emisor)->getResumen($this->Api->data);
    }

    /**
     * Acción que permite descargar todo el registro de compras del SII pero
     * eligiendo el tipo de formato, ya sea por defecto en formato RCV o en
     * formato IECV (esto permite importar el archivo en LibreDTE u otra app)
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2020-02-19
     */
    public function rcv_csv($periodo, $tipo = 'rcv')
    {
        $Emisor = $this->getContribuyente();
        try {
            $detalle = $Emisor->getRCV([
                'operacion' => 'VENTA',
                'periodo' => $periodo,
                'tipo' => $tipo,
                'formato' => $tipo == 'rcv_csv' ? 'csv' : 'json',
            ]);
        } catch (\Exception $e) {
            \sowerphp\core\Model_Datasource_Session::message($e->getMessage(), 'error');
            $this->redirect('/dte/dte_ventas/ver/'.$periodo);
        }
        if (!$detalle) {
            \sowerphp\core\Model_Datasource_Session::message('No hay detalle para el período y estado solicitados', 'warning');
            $this->redirect('/dte/dte_ventas/ver/'.$periodo);
        }
        if ($tipo == 'rcv_csv') {
            $this->response->sendContent($detalle, 'rv_'.$Emisor->rut.'_'.$periodo.'_'.$tipo.'.csv');
        } else {
            array_unshift($detalle, array_keys($detalle[0]));
            $csv = \sowerphp\general\Utility_Spreadsheet_CSV::get($detalle);
            $this->response->sendContent($csv, 'rv_'.$Emisor->rut.'_'.$periodo.'_'.$tipo.'.csv');
        }
    }

}
