<?php
declare(strict_types=1);
require_once __DIR__ . '/../../app/helpers/Auth.php';

Auth::requireAuth();
if (Auth::role() !== 'administrador') {
    header('Location: index.php?route=login');
    exit;
}
?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<div class="content admin-dashboard">
    <section class="admin-hero">
        <div>
            <h2>Gestión de Usuarios</h2>
            <p>Administra tutores y estudiantes con acciones de editar, actualizar y eliminar.</p>
        </div>

        <?php if (($totalPagesEst ?? 1) > 1): ?>
        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
            <?php for ($i = 1; $i <= (int)$totalPagesEst; $i++): ?>
            <a class="student-btn-link"
                href="index.php?route=admin/usuarios&q=<?= urlencode((string)($q ?? '')) ?>&estado=<?= urlencode((string)($estadoRaw ?? '')) ?>&page_est=<?= $i ?>&page_tut=<?= (int)($pageTut ?? 1) ?>">
                <?= $i === (int)($pageEst ?? 1) ? '● ' : '' ?><?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>

    <?php if (!empty($ok)): ?>
    <section class="card">
        <div class="student-alert-success"><?= htmlspecialchars((string)$ok) ?></div>
    </section>
    <?php endif; ?>

    <?php if (!empty($errores)): ?>
    <section class="card">
        <div class="mis-alert-card is-warning">
            <ul style="margin:0; padding-left:18px;">
                <?php foreach ($errores as $e): ?>
                <li><?= htmlspecialchars((string)$e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

    </section>
    <?php endif; ?>

    <section class="card admin-panel">

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="button" class="student-btn-secondary" onclick="openModal('modalNuevoUsuario')">
                <i class="fas fa-plus me-1"></i> Nuevo usuario
            </button>
            <button type="button" class="student-btn-link" onclick="openModal('modalImportarUsuarios')">
                <i class="fas fa-file-import me-1"></i> Importar CSV/Excel
            </button>
        </div>

        <form method="get" action="index.php" class="mis-filters-form" style="margin-bottom:12px;">
            <input type="hidden" name="route" value="admin/usuarios">
            <div>
                <label for="q">Buscar (DNI / nombre / correo)</label>
                <input id="q" type="text" name="q" value="<?= htmlspecialchars((string)($q ?? '')) ?>">
            </div>
            <div>
                <label for="estado">Estado</label>
                <select id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="1" <?= (($estado ?? null) === 1) ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= (($estado ?? null) === 0) ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
            <div class="mis-filter-actions">
                <button type="submit" class="student-btn-secondary">Filtrar</button>
                <a href="index.php?route=admin/usuarios" class="student-btn-link">Limpiar</a>
            </div>
        </form>
    </section>
    <section class="card admin-panel">
        <div class="admin-table-wrap">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <h3 style="margin:0;">Estudiantes</h3>
                <span class="admin-pill"><?= (int)($totalEstudiantes ?? 0) ?> total(es)</span>
            </div>
            <table class="table table-hover align-middle text-center" id="tablaEstudiantes">
                <thead class="table-light">

                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($estudiantes ?? []) as $u): ?>
                    <tr>
                        <td><?= (int)$u['id_usuario'] ?></td>

                        <td><?= htmlspecialchars((string)$u['dni']) ?></td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars((string)$u['nombres']) ?>
                        </td>

                        <td><?= htmlspecialchars((string)$u['apellidos']) ?></td>

                        <td><?= htmlspecialchars((string)$u['correo']) ?></td>

                        <td><?= htmlspecialchars((string)($u['celular'] ?? '-')) ?></td>

                        <td>
                            <span class="badge <?= $u['estado'] == 1 ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $u['estado'] == 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>

                        <td style="display:flex; gap:8px;">

                            <button type="button" class="student-btn-secondary" onclick="abrirModalEditar(<?= (int)$u['id_usuario'] ?>,
                             '<?= htmlspecialchars($u['dni']) ?>',
                             '<?= htmlspecialchars($u['nombres']) ?>',
                             '<?= htmlspecialchars($u['apellidos']) ?>',
                             '<?= htmlspecialchars($u['correo']) ?>',
                             '<?= htmlspecialchars($u['celular']) ?>')">
                                Editar
                            </button>
                            <?php if ((int)$u['estado'] === 1): ?>

                            <!-- FORMULARIO PARA ELIMINAR (DESACTIVAR) -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;"
                                onsubmit="return confirm('¿Desactivar este usuario?');">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <button type="submit" class="student-btn-link">
                                    Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <!-- Si está INACTIVO, mostramos botón para ACTIVAR -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="activar">
                                <button type="submit" class="student-btn-link" style="color: green; font-weight: bold;">
                                    Activar
                                </button>
                            </form>
                            <?php endif; ?>

                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card admin-panel">
        <div class="admin-panel-header">
            <h3>Tutores</h3>
            <span class="admin-pill"><?= (int)($totalTutores ?? 0) ?> total(es)</span>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover align-middle text-center" id="tablaTutores">
                <thead class="table-light">

                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($tutores ?? []) as $u): ?>
                    <tr>

                        <td><?= (int)$u['id_usuario'] ?></td>

                        <td><?= htmlspecialchars((string)$u['dni']) ?></td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars((string)$u['nombres']) ?>
                        </td>

                        <td><?= htmlspecialchars((string)$u['apellidos']) ?></td>

                        <td><?= htmlspecialchars((string)$u['correo']) ?></td>

                        <td><?= htmlspecialchars((string)($u['celular'] ?? '-')) ?></td>

                        <td>
                            <span class="badge <?= (string)$u['estado'] === '1' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= (string)$u['estado'] === '1' ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td style="display:flex; gap:8px;">
                            <button type="button" class="student-btn-secondary" onclick="abrirModalEditar(<?= (int)$u['id_usuario'] ?>,
                             '<?= htmlspecialchars($u['dni']) ?>',
                             '<?= htmlspecialchars($u['nombres']) ?>',
                             '<?= htmlspecialchars($u['apellidos']) ?>',
                             '<?= htmlspecialchars($u['correo']) ?>',
                             '<?= htmlspecialchars($u['celular']) ?>')">
                                Editar
                            </button>
                            <?php if ((int)$u['estado'] === 1): ?>
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;"
                                onsubmit="return confirm('¿Desactivar este usuario?');">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <button type="submit" class="student-btn-link">
                                    Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <!-- Si está INACTIVO, mostramos botón para ACTIVAR -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="activar">
                                <button type="submit" class="student-btn-link" style="color: green; font-weight: bold;">
                                    Activar
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPagesTut ?? 1) > 1): ?>
        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
            <?php for ($i = 1; $i <= (int)$totalPagesTut; $i++): ?>
            <a class="student-btn-link"
                href="index.php?route=admin/usuarios&q=<?= urlencode((string)($q ?? '')) ?>&estado=<?= urlencode((string)($estadoRaw ?? '')) ?>&page_est=<?= (int)($pageEst ?? 1) ?>&page_tut=<?= $i ?>">
                <?= $i === (int)($pageTut ?? 1) ? '● ' : '' ?><?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>

    <section class="card admin-panel">
        <div class="admin-panel-header">
            <h3>Especialistas (Bienestar/Salud)</h3>
            <span class="admin-pill"><?= (int)($totalEspecialistas  ?? 0) ?> total(es)</span>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover align-middle text-center" id="tablaEspecialistas">
                <thead class="table-light">

                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($especialistas ?? []) as $u): ?>
                    <tr>

                        <td><?= (int)$u['id_usuario'] ?></td>

                        <td><?= htmlspecialchars((string)$u['dni']) ?></td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars((string)$u['nombres']) ?>
                        </td>

                        <td><?= htmlspecialchars((string)$u['apellidos']) ?></td>

                        <td><?= htmlspecialchars((string)$u['correo']) ?></td>

                        <td><?= htmlspecialchars((string)($u['celular'] ?? '-')) ?></td>

                        <td>
                            <span class="badge <?= (string)$u['estado'] === '1' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= (string)$u['estado'] === '1' ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td style="display:flex; gap:8px;">
                            <button type="button" class="student-btn-secondary" onclick="abrirModalEditar(<?= (int)$u['id_usuario'] ?>,
                             '<?= htmlspecialchars($u['dni']) ?>',
                             '<?= htmlspecialchars($u['nombres']) ?>',
                             '<?= htmlspecialchars($u['apellidos']) ?>',
                             '<?= htmlspecialchars($u['correo']) ?>',
                             '<?= htmlspecialchars($u['celular']) ?>')">
                                Editar
                            </button>
                            <?php if ((int)$u['estado'] === 1): ?>
                            <!-- FORMULARIO PARA ELIMINAR (DESACTIVAR) -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;"
                                onsubmit="return confirm('¿Desactivar este usuario?');">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <button type="submit" class="student-btn-link">
                                    Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <!-- Si está INACTIVO, mostramos botón para ACTIVAR -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="activar">
                                <button type="submit" class="student-btn-link" style="color: green; font-weight: bold;">
                                    Activar
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPagesEsp ?? 1) > 1): ?>
        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
            <?php for ($i = 1; $i <= (int)$totalPagesEsp; $i++): ?>
            <a class="student-btn-link"
                href="index.php?route=admin/usuarios&q=<?= urlencode((string)($q ?? '')) ?>&estado=<?= urlencode((string)($estadoRaw ?? '')) ?>&page_est=<?= (int)($pageEst ?? 1) ?>&page_tut=<?= $i ?>">
                <?= $i === (int)($pageTut ?? 1) ? '● ' : '' ?><?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>
    <section class="card admin-panel">
        <div class="admin-panel-header">
            <h3>Administrador</h3>
            <span class="admin-pill"><?= (int)($totalAdmin ?? 0) ?> total(es)</span>
        </div>
        <div class="admin-table-wrap">
            <table class="table table-hover align-middle text-center" id="tablaAdministrador">
                <thead class="table-light">

                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (($administrador ?? []) as $u): ?>
                    <tr>

                        <td><?= (int)$u['id_usuario'] ?></td>

                        <td><?= htmlspecialchars((string)$u['dni']) ?></td>

                        <td class="fw-semibold">
                            <?= htmlspecialchars((string)$u['nombres']) ?>
                        </td>

                        <td><?= htmlspecialchars((string)$u['apellidos']) ?></td>

                        <td><?= htmlspecialchars((string)$u['correo']) ?></td>

                        <td><?= htmlspecialchars((string)($u['celular'] ?? '-')) ?></td>

                        <td>
                            <span class="badge <?= (string)$u['estado'] === '1' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= (string)$u['estado'] === '1' ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td style="display:flex; gap:8px;">
                            <button type="button" class="student-btn-secondary" onclick="abrirModalEditar(<?= (int)$u['id_usuario'] ?>,
                             '<?= htmlspecialchars($u['dni']) ?>',
                             '<?= htmlspecialchars($u['nombres']) ?>',
                             '<?= htmlspecialchars($u['apellidos']) ?>',
                             '<?= htmlspecialchars($u['correo']) ?>',
                             '<?= htmlspecialchars($u['celular']) ?>')">
                                Editar
                            </button>
                            <?php if ((int)$u['estado'] === 1): ?>
                            <!-- FORMULARIO PARA ELIMINAR (DESACTIVAR) -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;"
                                onsubmit="return confirm('¿Desactivar este usuario?');">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="eliminar">
                                <button type="submit" class="student-btn-link">
                                    Eliminar
                                </button>
                            </form>
                            <?php else: ?>
                            <!-- Si está INACTIVO, mostramos botón para ACTIVAR -->
                            <form method="post" action="index.php?route=admin/usuarios" style="display:inline;">
                                <input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
                                <input type="hidden" name="accion" value="activar">
                                <button type="submit" class="student-btn-link" style="color: green; font-weight: bold;">
                                    Activar
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPagesAdm ?? 1) > 1): ?>
        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
            <?php for ($i = 1; $i <= (int)$totalPagesAdm; $i++): ?>
            <a class="student-btn-link"
                href="index.php?route=admin/usuarios&q=<?= urlencode((string)($q ?? '')) ?>&estado=<?= urlencode((string)($estadoRaw ?? '')) ?>&page_est=<?= (int)($pageEst ?? 1) ?>&page_tut=<?= $i ?>">
                <?= $i === (int)($pageTut ?? 1) ? '● ' : '' ?><?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>


</div>

<div id="modalNuevoUsuario" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999;">
    <div
        style="background:#fff; max-width:720px; margin:4% auto; padding:20px; border-radius:10px; width:92%; max-height:88vh; overflow:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Registrar nuevo usuario</h3>
            <button type="button" class="student-btn-link" onclick="closeModal('modalNuevoUsuario')">Cerrar</button>
        </div>
        <form method="post" action="index.php?route=admin/usuarios" style="margin-top:14px;">
            <input type="hidden" name="accion" value="crear_usuario">
            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px;">
                <div>
                    <label>Rol</label>
                    <select name="rol" required onchange="toggleCamposPorRol(this.value)">
                        <option value="">Seleccione</option>
                        <option value="estudiante">Estudiante</option>
                        <option value="tutor">Tutor</option>
                        <option value="especialista">Especialista (Psicopedagogía/Salud)</option>
                        <option value="administrador">Administrador</option>
                    </select>
                </div>
                <div>
                    <label>DNI</label>
                    <input type="text" name="dni" required minlength="8">
                </div>
                <div>
                    <label>Nombres</label>
                    <input type="text" name="nombres" required>
                </div>
                <div>
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" required>
                </div>
                <div>
                    <label>Correo</label>
                    <input type="email" name="correo" required>
                    <small style="display:block;color:#666;margin-top:2px;">
                        Estudiante: codigo@unamba.edu.pe Tutor: @gmail.com o @unamba.edu.pe
                    </small>
                </div>

                <div>
                    <label>Celular</label>
                    <input type="text" name="celular">
                </div>
                <div>
                    <label>Sexo</label>
                    <select name="sexo" class="form-control">
                        <option value="">Seleccione....</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>

                    </select>

                </div>

                <div style="grid-column: span 2;">
                    <label>Contraseña (opcional)</label>
                    <input type="text" name="password" style="width:100%; max-width:260px;"
                        placeholder="Si se deja vacío, se usará el DNI">
                </div>

                <div>
                    <label>Escuela</label>
                    <select name="id_escuela" class="form-control" required>
                        <option value="">Seleccione Escuela</option>
                        <?php foreach ($escuelas as $e):
                            ?>
                        <option value="<?= (int)$e['id_escuela'] ?>"><?= htmlspecialchars($e['nombre_escuela']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- CAMPOS ESTUDIANTE -->
                <div id="camposEstudiante"
                    style="display:none; grid-column:1/-1; margin-top:10px; border-top:1px solid #eee; padding-top:10px;">
                    <h5>Datos Académicos (Estudiante)</h5>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label>Código UNAMBA</label>
                            <input type="text" name="codigo_unamba" class="form-control data-estudiante">
                        </div>
                        <div>
                            <label>Ciclo Actual</label>
                            <input type="number" name="ciclo_actual" class="form-control data-estudiante">
                        </div>
                        <div>
                            <label>Fecha de Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control">
                        </div>
                        <div>
                            <label>Semestre de Ingreso</label>
                            <input type="text" name="semestre_ingreso" class="form-control">
                        </div>
                        <div>
                            <label>Situación</label>
                            <select name="situacion_academica" class="form-control">
                                <option value="Regular">Regular</option>
                                <option value="Repitente">Repitente</option>
                                <option value="Riesgo">Riesgo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- CAMPOS TUTOR -->
                <div id="camposTutor"
                    style="display:none; grid-column:1/-1; margin-top:10px; border-top:1px solid #eee; padding-top:10px;">
                    <h5>Datos Profesionales (Tutor)</h5>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label>Grado Académico</label>
                            <input type="text" name="grado_academico" class="form-control data-tutor">
                        </div>
                        <div>
                            <label>Especialidad</label>
                            <input type="text" name="especialidad" class="form-control data-tutor">
                        </div>
                        <div>
                            <label>Categoría</label>
                            <input type="text" name="categoria" class="form-control data-tutor">
                        </div>
                    </div>
                </div>

                <!-- CAMPOS ESPECIALISTA -->
                <div id="camposEspecialista"
                    style="display:none; grid-column:1/-1; margin-top:10px; border-top:1px solid #eee; padding-top:10px;">
                    <h5>Datos de Especialidad (Psicopedagogía / Salud)</h5>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label>Área de Especialidad</label>
                            <input type="text" name="area" class="form-control data-especialista" placeholder=" ">

                        </div>
                        <div>
                            <label>Cargo / Puesto</label>
                            <input type="text" name="cargo" class="form-control data-especialista"
                                placeholder="Ej. Psicólogo, Médico General">
                        </div>
                    </div>
                </div>

                <!-- CAMPOS ADMINISTRADOR -->
                <div id="camposAdministrador"
                    style="display:none; grid-column:1/-1; margin-top:10px; border-top:1px solid #eee; padding-top:10px;">

                    <h5>Datos del Administrador</h5>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">

                        <div>
                            <label>Cargo</label>
                            <input type="text" name="cargo" class="form-control data-admin"
                                placeholder="Ej. Administrador General">
                        </div>

                        <div>
                            <label>Dependencia</label>
                            <input type="text" name="dependencia" class="form-control data-admin"
                                placeholder="Ej. Dirección Académica">
                        </div>

                    </div>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="student-btn-secondary">Guardar Usuario</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="modalImportarUsuarios"
    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999;">
    <div style="background:#fff; max-width:680px; margin:6% auto; padding:20px; border-radius:10px; width:92%;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Importar usuarios</h3>
            <button type="button" class="student-btn-link" onclick="closeModal('modalImportarUsuarios')">Cerrar</button>
        </div>
        <form method="post" action="index.php?route=admin/usuarios" enctype="multipart/form-data"
            style="margin-top:14px;">
            <input type="hidden" name="accion" value="importar_usuarios">
            <div>
                <label>Archivo Excel</label>
                <input type="file" name="archivo_usuarios" accept=".csv,.xlsx,.xls" required>
            </div>

            <div style="margin-top:14px; display:flex; gap:8px;">
                <button type="submit" class="student-btn-secondary">Importar archivo</button>
                <button type="button" class="student-btn-link"
                    onclick="closeModal('modalImportarUsuarios')">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditarUsuario" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999;">

    <div style="background:#fff; max-width:600px; margin:5% auto; padding:20px; border-radius:10px;">

        <div style="display:flex; justify-content:space-between;">
            <h3>Editar Usuario</h3>
            <button onclick="closeModal('modalEditarUsuario')">Salir</button>
        </div>

        <form method="post" action="index.php?route=admin/usuarios">

            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id_usuario" id="edit_id">

            <div class="mb-2">
                <label>DNI</label>
                <input type="text" name="dni" id="edit_dni" class="form-control" required>
            </div>

            <div class="mb-2">
                <label>Nombres</label>
                <input type="text" name="nombres" id="edit_nombres" class="form-control" required>
            </div>

            <div class="mb-2">
                <label>Apellidos</label>
                <input type="text" name="apellidos" id="edit_apellidos" class="form-control" required>
            </div>

            <div class="mb-2">
                <label>Correo</label>
                <input type="email" name="correo" id="edit_correo" class="form-control" required>
            </div>

            <div class="mb-2">
                <label>Celular</label>
                <input type="text" name="celular" id="edit_celular" class="form-control">
            </div>

            <div style="margin-top:10px;">
                <button class="student-btn-secondary">Guardar cambios</button>
            </div>

        </form>
    </div>
</div>
<script>
// Previene el doble envío de formularios deshabilitando el botón
function preventDoubleSubmit(form) {
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.textContent;
        submitBtn.textContent = 'Guardando...';
    }
    return true;
}

// Controla qué campos se muestran u ocultan dependiendo del rol seleccionado
function toggleCamposPorRol(rol) {
    const divEst = document.getElementById('camposEstudiante');
    const divTut = document.getElementById('camposTutor');
    const divEsp = document.getElementById('camposEspecialista');
    const divAdmin = document.getElementById('camposAdministrador');

    // Ocultar todos los contenedores al inicio
    if (divEst) divEst.style.display = 'none';
    if (divTut) divTut.style.display = 'none';
    if (divEsp) divEsp.style.display = 'none';
    if (divAdmin) divAdmin.style.display = 'none';

    // Deshabilitar todos los inputs internos para evitar envíos de basura en el POST
    document.querySelectorAll('.data-estudiante, .data-tutor, .data-especialista, .data-admin').forEach(input => {
        input.disabled = true;
        input.required = false;
    });

    // Activar y mostrar según el rol correspondiente
    if (rol === 'estudiante' && divEst) {
        divEst.style.display = 'block';
        document.querySelectorAll('.data-estudiante').forEach(input => {
            input.disabled = false;
            if (input.name === 'codigo_unamba' || input.name === 'ciclo_actual') input.required = true;
        });
    } else if (rol === 'tutor' && divTut) {
        divTut.style.display = 'block';
        document.querySelectorAll('.data-tutor').forEach(input => {
            input.disabled = false;
        });
    } else if (rol === 'especialista' && divEsp) {
        divEsp.style.display = 'block';
        document.querySelectorAll('.data-especialista').forEach(input => {
            input.disabled = false;
            if (input.name === 'area') input.required = true;
        });
    } else if (rol === 'administrador' && divAdmin) {

        divAdmin.style.display = 'block';

        document.querySelectorAll('.data-admin').forEach(input => {

            input.disabled = false;

            if (
                input.name === 'cargo' ||
                input.name === 'dependencia'
            ) {
                input.required = true;
            }
        });
    }
}

// Funciones generales para abrir y cerrar ventanas modales
function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'block';
}

function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

// Cierra cualquiera de los modales automáticamente si haces clic fuera de su recuadro blanco
window.addEventListener('click', function(e) {
    var m1 = document.getElementById('modalNuevoUsuario');
    var m2 = document.getElementById('modalImportarUsuarios');
    var m3 = document.getElementById('modalEditarUsuario');

    if (e.target === m1) m1.style.display = 'none';
    if (e.target === m2) m2.style.display = 'none';
    if (e.target === m3) m3.style.display = 'none';
});

// Carga los datos del usuario seleccionado y levanta el modal de edición
function abrirModalEditar(id, dni, nombres, apellidos, correo, celular) {
    var modal = document.getElementById('modalEditarUsuario');
    if (modal) {
        modal.style.display = 'block';

        document.getElementById('edit_id').value = id;
        document.getElementById('edit_dni').value = dni;
        document.getElementById('edit_nombres').value = nombres;
        document.getElementById('edit_apellidos').value = apellidos;
        document.getElementById('edit_correo').value = correo;
        document.getElementById('edit_celular').value = celular;
    }
}
</script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Definimos una configuración estándar para ambas tablas
    const configuracionComun = {
        "pageLength": 5,
        "lengthMenu": [
            [5, 10, 25, 50],
            [5, 10, 25, 50]
        ],
        "dom": "rtip", // 'r' procesando, 't' tabla, 'i' información, 'p' paginación
        "pagingType": "simple_numbers", // Esto garantiza: Atrás, Números, Adelante
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json", // <-- Agregada la coma aquí
            "paginate": {
                "next": "Adelante",
                "previous": "Atrás"
            }
        }
    };

    // Inicializamos las tablas
    $('#tablaEstudiantes').DataTable(configuracionComun);
    $('#tablaTutores').DataTable(configuracionComun);
    $('#tablaEspecialistas').DataTable(configuracionComun);
    $('#tablaAdministrador').DataTable(configuracionComun);
});
</script>


<?php require_once __DIR__ . '/../layouts/footer.php'; ?>