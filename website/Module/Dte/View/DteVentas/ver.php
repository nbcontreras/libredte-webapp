<ul class="nav nav-pills float-right">
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_ventas/rcv_resumen/<?=$Libro->periodo?>" class="nav-link" onclick="return Form.loading('Consultando datos al SII...')">
            <span class="fas fa-university"></span> Ver resumen RV
        </a>
    </li>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <span class="fa fa-download"></span> Descargar
        </a>
        <div class="dropdown-menu">
            <a href="<?=$_base?>/dte/dte_ventas/descargar_registro_venta/<?=$Libro->periodo?>" title="Descargar CSV con los documentos emitidos que forman el registro de ventas" class="dropdown-item">
                    Registro de ventas
            </a>
            <a href="<?=$_base?>/dte/dte_ventas/descargar_resumenes/<?=$Libro->periodo?>" title="Descargar CSV con los resúmenes de boletas y pagos electrónicos" class="dropdown-item">
                Resúmenes (ej: boletas)
            </a>
        </div>
    </li>
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_ventas" title="Ir al libro de ventas (IEV)" class="nav-link">
            <i class="fa fa-book"></i>
            Libro de ventas
        </a>
    </li>
</ul>

<div class="page-header"><h1>Libro de ventas período <?=$Libro->periodo?></h1></div>
<p>Esta es la página del libro de ventas del período <?=$Libro->periodo?> de la empresa <?=$Emisor->razon_social?>.</p>

<script type="text/javascript">
$(function() {
    var url = document.location.toString();
    if (url.match('#')) {
        $('#'+url.split('#')[1]+'-tab').tab('show');
        $('html,body').scrollTop(0);
    }
});
function get_codigo_reemplazo() {
    $.get(_base+'/api/dte/dte_ventas/codigo_reemplazo/<?=$Libro->periodo?>/<?=$Emisor->rut?>', function(codigo) {
        document.getElementById('CodAutRecField').value = codigo;
    }).fail(function(error){Form.alert(error.responseJSON, document.getElementById('CodAutRecField'))});
}
</script>

<div role="tabpanel">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><a href="#datos" aria-controls="datos" role="tab" data-toggle="tab" id="datos-tab" class="nav-link active" aria-selected="true">Datos básicos</a></li>
        <li class="nav-item"><a href="#resumen" aria-controls="resumen" role="tab" data-toggle="tab" id="resumen-tab" class="nav-link">Resumen</a></li>
<?php if ($n_detalles) : ?>
<?php if (isset($detalle)) : ?>
        <li class="nav-item"><a href="#detalle" aria-controls="detalle" role="tab" data-toggle="tab" id="detalle-tab" class="nav-link">Detalle</a></li>
<?php endif; ?>
        <li class="nav-item"><a href="#estadisticas" aria-controls="estadisticas" role="tab" data-toggle="tab" id="estadisticas-tab" class="nav-link">Estadísticas</a></li>
<?php endif; ?>
    </ul>
    <div class="tab-content pt-4">

<!-- INICIO DATOS BÁSICOS -->
<div role="tabpanel" class="tab-pane active" id="datos" aria-labelledby="datos-tab">
    <div class="row">
        <div class="col-md-9">
<?php
new \sowerphp\general\View_Helper_Table([
    ['Período', 'Emitidos', 'Envíados'],
    [$Libro->periodo, num($n_detalles), num($Libro->documentos)],
]);
?>
            <div class="row">
                <div class="col-md-4">
                    <a class="btn btn-primary btn-lg btn-block<?=!$n_detalles?' disabled':''?>" href="<?=$_base?>/dte/dte_ventas/csv/<?=$Libro->periodo?>" role="button">
                        <span class="far fa-file-excel"></span>
                        Descargar CSV
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="btn btn-primary btn-lg btn-block<?=!$Libro->xml?' disabled':''?>" href="<?=$_base?>/dte/dte_ventas/pdf/<?=$Libro->periodo?>" role="button">
                        <span class="far fa-file-pdf"></span>
                        Descargar PDF
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="btn btn-primary btn-lg btn-block<?=!$Libro->xml?' disabled':''?>" href="<?=$_base?>/dte/dte_ventas/xml/<?=$Libro->periodo?>" role="button">
                        <span class="far fa-file-code"></span>
                        Descargar XML
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light">
                <div class="card-header lead text-center">Track ID SII: <?=$Libro->track_id?></div>
                <div class="card-body text-center">
                    <p><strong><?=$Libro->revision_estado?></strong></p>
                    <p><?=str_replace("\n", '<br/>', $Libro->revision_detalle)?></p>
<?php if ($Libro->track_id and $Libro->getEstado()!='LRH') : ?>
                    <p>
<?php if ($Libro->track_id!=-1) : ?>
                        <a class="btn btn-primary" href="<?=$_base?>/dte/dte_ventas/actualizar_estado/<?=$Libro->periodo?>" role="button" onclick="return Form.loading('Actualizando estado del envío...')">Actualizar estado</a><br/>
                        <span class="small">
                            <a href="<?=$_base?>/dte/dte_ventas/solicitar_revision/<?=$Libro->periodo?>" title="Solicitar revisión del libro al SII" onclick="return Form.loading('Solicitando revisión del envío al SII...')">solicitar revisión del envío</a><br/>
                            <a href="#" onclick="__.popup('<?=$_base?>/dte/sii/estado_envio/<?=$Libro->track_id?>', 750, 550)" title="Ver el estado del envío en la web del SII">ver estado envío en SII</a><br/>
                            <a href="<?=$_base?>/dte/dte_ventas/enviar_rectificacion/<?=$Libro->periodo?>" title="Enviar rectificación del libro al SII">enviar rectificación</a>
                        </span>
<?php else : ?>
                        <span class="small">
                            <a href="<?=$_base?>/dte/dte_ventas/enviar_sii/<?=$Libro->periodo?>" onclick="return Form.confirm(this, '¿Confirmar la generación del libro?', 'Generando libro...')">Generar nuevo libro</a>
                        </span>
<?php endif; ?>
                    </p>
<?php else: ?>
                    <p>
                        <a class="btn btn-primary" href="<?=$_base?>/dte/dte_ventas/enviar_sii/<?=$Libro->periodo?>" role="button" onclick="return Form.confirm(this, '¿Confirmar la generación del libro?', 'Generando libro...')">
                            <?=$Libro->periodo<201708?'Enviar libro al SII':'Generar libro'?>
                        </a>
                    </p>
<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- FIN DATOS BÁSICOS -->

<!-- INICIO RESUMEN -->
<div role="tabpanel" class="tab-pane" id="resumen" aria-labelledby="resumen-tab">
<?php
$total = [
    'TpoDoc' => '<strong>Total</strong>',
    'TotDoc' => 0,
    'TotAnulado' => 0,
    'TotOpExe' => 0,
    'TotMntExe' => 0,
    'TotMntNeto' => 0,
    'TotMntIVA' => 0,
    'TotIVAPropio' => 0,
    'TotIVATerceros' => 0,
    'TotLey18211' => 0,
    'TotMntTotal' => 0,
    'TotMntNoFact' => 0,
    'TotMntPeriodo' => 0,
];
foreach ($resumen as &$r) {
    // sumar campos que se suman directamente
    foreach (['TotDoc', 'TotAnulado', 'TotOpExe'] as $c) {
        $total[$c] += $r[$c];
    }
    // sumar o restar campos segun operación
    foreach (['TotMntExe', 'TotMntNeto', 'TotMntIVA', 'TotIVAPropio', 'TotIVATerceros', 'TotLey18211', 'TotMntTotal', 'TotMntNoFact', 'TotMntPeriodo'] as $c) {
        if ($operaciones[$r['TpoDoc']]=='S') {
            $total[$c] += $r[$c];
        } else if ($operaciones[$r['TpoDoc']]=='R') {
            $total[$c] -= $r[$c];
        }
    }
    // verificar si IVA boleta cuadra para mostrar alerta con explicación si no lo hace
    if ($r['TpoDoc']==39) {
        $iva_boleta = $r['TotMntIVA'];
        $iva_boleta_segun_neto = round($r['TotMntNeto'] * 0.19);
        $iva_boleta_segun_total = round(round($r['TotMntTotal'] / 1.19) * 0.19);
        if ($iva_boleta != $iva_boleta_segun_neto or $iva_boleta != $iva_boleta_segun_total) {
            $alerta_iva_boleta = '<sup><i class="fa fa-exclamation-triangle fa-fw text-warning"></i></sup>';
        } else {
            $alerta_iva_boleta = '';
        }
    }
    // dar formato de número
    foreach ($r as &$v) {
        if ($v) {
            $v = num($v);
        }
    }
    // agregar alerta IVA boleta
    if ($r['TpoDoc']==39 and $alerta_iva_boleta) {
        $r['TotMntIVA'] .= ' '.$alerta_iva_boleta;
    }
}
?>
    <div class="card-deck">
        <div class="card mb-4">
            <div class="card-body text-center">
                <small>ventas (exento + neto)</small><br/>
                <span class="text-info lead"><?=num((int)$total['TotMntExe']+(int)$total['TotMntNeto'])?></span>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body text-center">
                <small>base imponible</small><br/>
                <span class="text-info lead"><?=num((int)$total['TotMntExe']+(int)round($total['TotMntIVA']/(\sasco\LibreDTE\Sii::getIVA()/100),0))?></span>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-body text-center">
                <small>total</small><br/>
                <span class="text-info lead"><?=num((int)$total['TotMntTotal'])?></span>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            Documentos con detalle registrado
        </div>
        <div class="card-body">
<?php
foreach ($total as &$tot) {
    if (is_numeric($tot)) {
        $tot = $tot>0 ? num($tot) : null;
    }
}
$titulos = ['Tipo Doc.', '# docs', 'Anulados', 'Op. exen.', 'Exento', 'Neto', 'IVA', 'IVA propio', 'IVA terc.', 'Ley 18211', 'Monto total', 'No fact.', 'Total periodo'];
array_unshift($resumen, $titulos);
$resumen[] = $total;
$t = new \sowerphp\general\View_Helper_Table();
$t->setShowEmptyCols(false);
echo $t->generate($resumen);
if (!empty($alerta_iva_boleta)) :
?>
            <p class="small text-muted">
                <i class="fa fa-exclamation-triangle fa-fw text-warning"></i>
                El IVA de boletas no cuadra, esto podría ser "normal" dependiendo de los valores.
                El IVA calculado a partir del neto es <?=num($iva_boleta_segun_neto)?> (<?=num($iva_boleta-$iva_boleta_segun_neto)?>) y el IVA calculado a partir del total es <?=num($iva_boleta_segun_total)?> (<?=num($iva_boleta-$iva_boleta_segun_total)?>).
            </p>
            <p class="small text-muted">
                Para el cálculo del IVA total de las boletas se obtiene el IVA de cada boleta por separado y se aproxima a su valor entero. Luego se suman todos los IVAs obteniendo el valor final. Al haber una aproximación en el cálculo individual de cada IVA es que se produce la diferencia aquí indicada en el total.
            </p>
<?php endif; ?>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            Enviar libro agregando resúmenes manuales
        </div>
        <div class="card-body">
<?php
$f = new \sowerphp\general\View_Helper_Form(false);
echo $f->begin([
    'id'=>'enviar_sii',
    'action'=>$_base.'/dte/dte_ventas/enviar_sii/'.$Libro->periodo,
    'onsubmit'=>'Form.check(\'enviar_sii\') && Form.confirm(this)'
]);
echo $f->input([
    'type' => 'js',
    'id' => 'resumenes',
    'titles' => $titulos,
    'inputs' => [
        ['type'=>'select', 'name'=>'TpoDoc', 'options'=>[35=>'Boleta', 38=>'Boleta exenta', 48=>'Pago electrónico'], 'attr'=>'style="width:10em"'],
        ['name'=>'TotDoc', 'check'=>'notempty integer'],
        ['name'=>'TotAnulado', 'check'=>'integer'],
        ['name'=>'TotOpExe', 'check'=>'integer'],
        ['name'=>'TotMntExe', 'check'=>'integer'],
        ['name'=>'TotMntNeto', 'check'=>'integer'],
        ['name'=>'TotMntIVA', 'check'=>'integer'],
        ['name'=>'TotIVAPropio', 'check'=>'integer'],
        ['name'=>'TotIVATerceros', 'check'=>'integer'],
        ['name'=>'TotLey18211', 'check'=>'integer'],
        ['name'=>'TotMntTotal', 'check'=>'notempty integer'],
        ['name'=>'TotMntNoFact', 'check'=>'integer'],
        ['name'=>'TotMntPeriodo', 'check'=>'integer'],
    ],
]);
$f->setStyle('horizontal');
echo $f->input([
    'name' => 'CodAutRec',
    'label'=>'Autorización rectificación',
    'help' => 'Código de autorización de rectificación obtenido desde el SII (sólo si es rectificación). <a href="#" onclick="get_codigo_reemplazo()">Solicitar código aquí</a>',
    'check' => ($Libro->track_id and $Libro->getEstado()!='LRH' and $Libro->track_id!=-1)?'notempty':'',
]);
?>
            <div class="row">
                <div class="form-group offset-md-3 col-md-6">
                    <button type="submit" name="submit" class="btn btn-primary btn-block">
                        Enviar libro al SII incorporando los resúmenes manuales
                    </button>
                </div>
            </div>
<?php
echo $f->end(false);
?>
        </div>
    </div>
</div>
<!-- FIN RESUMEN -->

<?php if ($n_detalles) : ?>

<?php if (isset($detalle)) : ?>
<!-- INICIO DETALLES -->
<div role="tabpanel" class="tab-pane" id="detalle" aria-labelledby="detalle-tab">
<?php
array_unshift($detalle, $libro_cols);
new \sowerphp\general\View_Helper_Table($detalle);
?>
</div>
<!-- FIN DETALLES -->
<?php endif; ?>

<!-- INICIO ESTADÍSTICAS -->
<div role="tabpanel" class="tab-pane" id="estadisticas" aria-labelledby="estadisticas-tab">
<div class="card mb-4">
    <div class="card-header">
        <i class="far fa-chart-bar fa-fw"></i> Documentos emitidos por día
    </div>
    <div class="card-body">
        <div id="grafico-documentos_por_dia"></div>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="far fa-chart-bar fa-fw"></i> Documentos emitidos por tipo
    </div>
    <div class="card-body">
        <div id="grafico-documentos_por_tipo"></div>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">
        <i class="far fa-chart-bar fa-fw"></i> Documentos emitidos según el estado que asignó el receptor
    </div>
    <div class="card-body">
<?php
$documentos_por_estado_receptor = $Libro->getDocumentosPorEventoReceptor();
$tabla = [['Evento', 'Documentos', 'Ver']];
foreach ($documentos_por_estado_receptor as $evento) {
    $tabla[] = [
        $evento['glosa'],
        num($evento['documentos']),
        '<a href="'.$_base.'/dte/dte_ventas/eventos_receptor/'.$Libro->periodo.'/'.$evento['codigo'].'" class="btn btn-primary" title="Ver documentos con estado '.$evento['glosa'].'"><span class="fa fa-search"></span></a>'
    ];
}
new \sowerphp\general\View_Helper_Table($tabla, 'eventos_receptor', false, false);
?>
        <div id="grafico-documentos_por_estado_receptor"></div>
    </div>
</div>
<script>
var documentos_por_dia = Morris.Line({
    element: 'grafico-documentos_por_dia',
    data: <?=json_encode($Libro->getDocumentosPorDia())?>,
    xkey: 'dia',
    ykeys: ['documentos'],
    labels: ['Documentos'],
    resize: true,
    parseTime: false
});
var documentos_por_tipo = Morris.Bar({
    element: 'grafico-documentos_por_tipo',
    data: <?=json_encode($Libro->getDocumentosPorTipo())?>,
    xkey: 'tipo',
    ykeys: ['documentos'],
    labels: ['Documentos'],
    resize: true
});
var documentos_por_estado_receptor = Morris.Bar({
    element: 'grafico-documentos_por_estado_receptor',
    data: <?=json_encode($documentos_por_estado_receptor)?>,
    xkey: 'glosa',
    ykeys: ['documentos'],
    labels: ['Documentos'],
    resize: true
});
$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
    var target = $(e.target).attr("href");
    if (target=='#estadisticas') {
        documentos_por_dia.redraw();
        documentos_por_tipo.redraw();
        documentos_por_estado_receptor.redraw();
        $(window).trigger('resize');
    }
});
</script>
</div>
<!-- FIN ESTADÍSTICAS -->

<?php endif; ?>

    </div>
</div>
