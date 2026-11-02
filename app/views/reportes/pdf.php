<?php
/**
 * Un reporte de "Ver reportes" como documento para Dompdf (ReporteController::pdf).
 * Dompdf entiende CSS sencillo (sin flex ni Bootstrap): por eso los estilos van aquí.
 * Las tablas son las mismas de la pantalla (reportes/_secciones.php). Ninguna fila se
 * parte entre dos páginas y el encabezado de cada tabla se repite en cada página.
 *
 * @var string $tipo, $desde, $hasta
 * @var array  $reporte   Reporte::generar(): ['titulo', 'secciones']
 */
// Logo de EquipRent: el cono de Bootstrap Icons 1.11.3 sobre el cuadro naranja (SVG, sin imágenes).
$logo = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><rect width="24" height="24" rx="5" fill="#F9A825"/>'
    . '<path transform="translate(4 4)" fill="#212529" d="m9.97 4.88.953 3.811C10.159 8.878 9.14 9 8 9s-2.158-.122-2.923-.309L6.03 4.88C6.635 4.957 7.3 5 8 5s1.365-.043 1.97-.12m-.245-.978L8.97.88C8.718-.13 7.282-.13 7.03.88L6.275 3.9C6.8 3.965 7.382 4 8 4s1.2-.036 1.725-.098m4.396 8.613a.5.5 0 0 1 .037.96l-6 2a.5.5 0 0 1-.316 0l-6-2a.5.5 0 0 1 .037-.96l2.391-.598.565-2.257c.862.212 1.964.339 3.165.339s2.303-.127 3.165-.339l.565 2.257z"/></svg>';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title><?= e($reporte['titulo']) ?> · EquipRent</title>
<style>
    @page { margin: 16mm 14mm 16mm; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; color: #1f2328; }
    .marca td { vertical-align: middle; padding: 0; border: 0; }
    .marca img { width: 30px; height: 30px; margin-right: 8px; }
    .marca-texto { font-size: 15pt; font-weight: bold; }
    .marca-texto span { color: #E89400; }
    .marca-subtitulo { font-size: 7.5pt; color: #5b6470; text-transform: uppercase; letter-spacing: 1px; }
    .emision { text-align: right; font-size: 8pt; color: #5b6470; }
    .franja { height: 4px; background: #F9A825; margin: 8px 0 12px; }
    h1 { font-size: 15pt; margin: 0; }
    .periodo { margin: 2px 0 10px; color: #5b6470; }
    .panel { margin-top: 12px; }
    .panel-titulo { font-size: 9.5pt; text-transform: uppercase; letter-spacing: .5px; margin: 0 0 6px; padding-bottom: 4px; border-bottom: 2px solid #F9A825; }
    table.tabla-er { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    th { background: #212529; color: #fff; font-size: 8pt; text-align: left; padding: 5px 6px; }
    td { padding: 5px 6px; border-bottom: 1px solid #e3e6ea; vertical-align: top; }
    td:first-child { white-space: nowrap; } /* código, mes o fecha: no se parte en dos líneas */
    .text-end { text-align: right; }
    .tabla-importe-total td { font-weight: bold; background: #fff7e6; border-top: 2px solid #212529; }
    .texto-suave { color: #6b7280; }
    .estado { padding: 1px 6px; border-radius: 8px; font-size: 7.5pt; }
    .estado-verde   { color: #15803d; background: #dcfce7; }
    .estado-naranja { color: #b45309; background: #fff3db; }
    .estado-azul    { color: #1d4ed8; background: #dbeafe; }
    .estado-rojo    { color: #b91c1c; background: #fee2e2; }
    .estado-gris    { color: #4b5563; background: #eef0f2; }
</style>
</head>
<body>
<table class="marca" style="width: 100%">
    <tr>
        <td style="width: 40px"><img src="data:image/svg+xml;base64,<?= base64_encode($logo) ?>" alt="EquipRent"></td>
        <td>
            <div class="marca-texto">EQUIP<span>RENT</span></div>
            <div class="marca-subtitulo">Sistema de Alquiler de Equipos</div>
        </td>
        <td class="emision">Emitido el <?= e(date('d/m/Y H:i')) ?><br><?= e(Auth::usuario()['nombres'] . ' ' . Auth::usuario()['apellidos']) ?></td>
    </tr>
</table>
<div class="franja"></div>

<h1><?= e($reporte['titulo']) ?></h1>
<p class="periodo"><?= e($tipo === 'inventario' ? 'Corte al ' . fechaCorta($desde) : 'Período: ' . periodo($desde, $hasta)) ?></p>

<?php require RAIZ . '/app/views/reportes/_secciones.php'; ?>
</body>
</html>
