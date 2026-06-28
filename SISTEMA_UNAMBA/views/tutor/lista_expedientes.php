<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="container-fluid py-4">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">📑 Expedientes de Estudiantes</h5>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Apellidos</th>
                            <th>Nombres</th>
                            <th>Ciclo</th>
                            <th>Situación</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if(!empty($estudiantes)): ?>
                        <?php foreach($estudiantes as $e): ?>

                        <tr>
                            <td><?= htmlspecialchars($e['codigo_unamba']) ?></td>
                            <td><?= htmlspecialchars($e['apellidos']) ?></td>
                            <td><?= htmlspecialchars($e['nombres']) ?></td>
                            <td><?= htmlspecialchars($e['ciclo_actual']) ?></td>
                            <td><?= htmlspecialchars($e['situacion_academica']) ?></td>

                            <td class="text-end">

                                <a class="btn btn-sm btn-primary"
                                   href="index.php?route=tutor/ver-expediente&id=<?= (int)$e['id_usuario'] ?>">

                                   📑 Ver expediente
                                </a>

                            </td>
                        </tr>

                        <?php endforeach; ?>
                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No existen estudiantes asignados
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>