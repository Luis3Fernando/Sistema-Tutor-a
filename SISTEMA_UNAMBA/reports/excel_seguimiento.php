<?php
declare(strict_types=1);

/**
 * Exportación CSV simple compatible con Excel.
 */
function exportarSeguimientoExcel(array $rows, string $filename = 'seguimiento_tutorias.csv'): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Tutor', 'Alumno', 'Fecha', 'Tema', 'Estado']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id'] ?? '',
            $r['tutor_id'] ?? '',
            $r['alumno_id'] ?? '',
            $r['fecha'] ?? '',
            $r['tema'] ?? '',
            $r['estado'] ?? '',
        ]);
    }

    fclose($output);
}
