<ul class="nav nav-pills float-right">
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_intercambios/probar_xml" title="Probar manualmente si un XML podría ingresar por email a la bandeja de intercambio" class="nav-link">
            <i class="fa fa-upload"></i>
            Probar XML
        </a>
    </li>
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_compras/registro_compras" title="Ir al Registro de Compras del SII" class="nav-link">
            <i class="fa fa-university"></i>
            RC SII
        </a>
    </li>
<?php if ($soloPendientes) : ?>
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_intercambios/listar" title="Listar todos los intercambios paginados" class="nav-link">
            <i class="fa fa-list-alt"></i>
            Listar todo
        </a>
    </li>
<?php else : ?>
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_intercambios/listar/0/1" title="Ver todos los documentos pendientes de procesar" class="nav-link">
            <i class="fa fa-list-alt"></i>
            Pendientes
        </a>
    </li>
<?php endif; ?>
    <li class="nav-item">
        <a href="<?=$_base?>/dte/dte_intercambios/buscar" title="Búsqueda avanzada de los documentos de intercambio" class="nav-link">
            <i class="fa fa-search"></i>
            Buscar
        </a>
    </li>
    <li class="nav-item" class="dropdown">
        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-sync"></i> Actualizar
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <a href="<?=$_base?>/dte/dte_intercambios/actualizar/3" class="dropdown-item" onclick="return Form.loading('Actualizando últimos 3 días...')">Últimos 3 días</a>
            <a href="<?=$_base?>/dte/dte_intercambios/actualizar/7" class="dropdown-item" onclick="return Form.loading('Actualizando última semana...')">Última semana</a>
            <a href="<?=$_base?>/dte/dte_intercambios/actualizar/14" class="dropdown-item" onclick="return Form.loading('Actualizando últimas 2 semanas...')">Últimas 2 semanas</a>
            <a href="<?=$_base?>/dte/dte_intercambios/actualizar/30" class="dropdown-item" onclick="return Form.loading('Actualizando último mes...')">Último mes</a>
            <a href="<?=$_base?>/dte/dte_intercambios/actualizar/90" class="dropdown-item" onclick="return Form.loading('Actualizando últimos 3 meses...')">Últimos 3 meses</a>
        </div>
    </li>
</ul>

<div class="page-header"><h1>Bandeja de intercambio</h1></div>
<p>Aquí podrá revisar los documentos que ha recibido en su correo de intercambio <span class="lead text-center text-monospace"><?=$Emisor->config_email_intercambio_user?></span>, tanto los procesados como los pendientes. Para estos últimos podrá aceptar o reclamar según corresponda.</p>

<?php
foreach ($documentos as &$i) {
    $acciones = '<a href="'.$_base.'/dte/dte_intercambios/ver/'.$i['codigo'].'" title="Ver detalles del intercambio" class="btn btn-primary mb-2"><i class="fa fa-search fa-fw"></i></a>';
    $acciones .= ' <a href="'.$_base.'/dte/dte_intercambios/pdf/'.$i['codigo'].'" title="Descargar PDF del intercambio" class="btn btn-primary mb-2"><i class="far fa-file-pdf fa-fw"></i></a>';
    $i[] = $acciones;
    if (is_numeric($i['emisor'])) {
        $i['emisor'] = \sowerphp\app\Utility_Rut::addDV($i['emisor']);
    }
    $i['fecha_hora_email'] = \sowerphp\general\Utility_Date::format($i['fecha_hora_email']);
    $i['documentos'] = is_array($i['documentos']) ? implode('<br/>', $i['documentos']) : num($i['documentos']);
    $i['totales'] = implode('<br/>', array_map('num', $i['totales']));
    if ($i['estado'] === null) {
        $i['estado'] = '<i class="fas fa-question-circle fa-fw text-warning"></i>';
    } else if ($i['estado'] === true) {
        $i['estado'] = '<i class="fas fa-check-circle fa-fw text-success"></i>';
    } else {
        $i['estado'] = '<i class="fas fa-times-circle fa-fw text-danger"></i>';
    }
    unset($i['usuario']);
}
$f = new \sowerphp\general\View_Helper_Form(false);
array_unshift($documentos, [
    '',
    $f->input(['name'=>'emisor', 'value'=>(isset($search['emisor'])?$search['emisor']:'')]),
    $f->input(['name'=>'folio', 'value'=>(isset($search['folio'])?$search['folio']:''), 'check'=>'integer']),
    '',
    '',
    '',
    '<button type="submit" class="btn btn-primary" onclick="return Form.check()"><i class="fa fa-search fa-fw" aria-hidden="true"></i></button>',
]);
array_unshift($documentos, ['Código', 'Emisor', 'Documento', 'Recibido', 'Total', 'Estado', 'Acciones']);
$paginator = new \sowerphp\app\View_Helper_Paginator([
    'link' => $_base.'/dte/dte_intercambios/listar',
]);
$paginator->setColsWidth([null, null, null, null, null, null, 110]);
echo $paginator->generate($documentos, $paginas, $pagina);
?>

<div class="card-deck mt-4">
    <div class="card">
        <div class="card-body text-center">
            <i class="fas fa-question-circle fa-fw fa-3x text-warning mb-4"></i>
            <h5 class="card-title">
                <a href="https://soporte.sasco.cl/kb/faq.php?id=196">¿Cómo procesar los documentos?</a>
            </h5>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <i class="fas fa-question-circle fa-fw fa-3x text-warning mb-4"></i>
            <h5 class="card-title">
                <a href="https://soporte.sasco.cl/kb/faq.php?id=31">¿Por qué faltan documentos?</a>
            </h5>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <i class="fas fa-question-circle fa-fw fa-3x text-warning mb-4"></i>
            <h5 class="card-title">
                <a href="https://soporte.sasco.cl/kb/faq.php?id=40">¿Puedo sincronizar con SII?</a>
            </h5>
        </div>
    </div>
</div>
