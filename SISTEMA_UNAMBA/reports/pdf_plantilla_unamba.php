<?php
declare(strict_types=1);

/**
 * Plantilla base para exportación PDF institucional UNAMBA.
 * Integrable con DomPDF.
 */
function generarHtmlReporteUNAMBA(array $data): string
{
    $titulo = htmlspecialchars($data['titulo'] ?? 'Reporte Institucional');
    $contenido = htmlspecialchars($data['contenido'] ?? 'Sin contenido');

    return "
    <h1 style='text-align:center;'>UNAMBA</h1>
    <h2 style='text-align:center;'>{$titulo}</h2>
    <p>{$contenido}</p>
    <hr>
    <small>Generado: " . date('Y-m-d H:i:s') . "</small>
    ";
}
