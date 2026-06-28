<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>

<?php
$user = $user ?? [];
$rol = (string)($rol ?? ($user['rol'] ?? 'usuario'));
$nombreCompleto = trim((string)(($user['nombre'] ?? '') ?: (($user['nombres'] ?? '') . ' ' . ($user['apellidos'] ?? ''))));
if ($nombreCompleto === '') {
    $nombreCompleto = 'Usuario';
}
 
$dni = (string)($dni ??($user['codigo'] ?? 'No registrado'));
$celular = (string)($celular ?? ($user['celular'] ?? 'No registrado'));
?>

<div class="content student-dashboard">
    <section class="student-hero">
        <div>
            <h2>Mis datos</h2>
          
        </div>
    </section>

    <section class="card student-panel">
        <div class="student-panel-header">
            <h3>Información del usuario</h3>
            <span class="student-panel-count"><?= htmlspecialchars(ucfirst($rol)) ?></span>
        </div>

        <div class="mis-detail-body">
            <?php if (!empty($ok)): ?>
                <div class="mis-alert-card is-info">Datos actualizados correctamente.</div>
            <?php endif; ?>

            <?php if (!empty($errores)): ?>
                <div class="mis-alert-card is-warning">
                    <ul style="margin: 0; padding-left: 18px;">
                        <?php foreach ($errores as $e): ?>
                            <li><?= htmlspecialchars((string)$e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:flex-end; margin-bottom:10px;">
                <button type="button" class="student-btn-secondary" id="openEditarPerfilModal">Actualizar mis datos</button>
            </div>

            <div class="mis-meta-grid">
                <div><span>Nombre completo</span><strong><?= htmlspecialchars($nombreCompleto) ?></strong></div>
                <div><span>Correo</span><strong><?= htmlspecialchars((string)($user['correo'] ?? $user['email'] ?? 'No registrado')) ?></strong></div>
                <div><span>Rol</span><strong><?= htmlspecialchars(ucfirst($rol)) ?></strong></div>
                <div><span>DNI</span><strong><?= htmlspecialchars($dni) ?></strong></div>
                <div><span>Celular</span><strong><?= htmlspecialchars($celular) ?></strong></div>
                
               
            </div>
        </div>
    </section>
</div>

<div id="editarPerfilModal" class="profile-modal-overlay" style="display:none;">
    <div class="profile-modal-card">
        <div class="profile-modal-head">
            <h3>Actualizar mis datos</h3>
           
        </div>

        <form method="post" action="index.php?route=perfil/mis-datos" class="profile-modal-form">
            <div class="profile-form-grid">
                <div>
                    <label>DNI</label>
                    <input type="text" name="dni" required value="<?= htmlspecialchars((string)($user['dni'] ?? '')) ?>">
                </div>
                <div>
                    <label>Nombres</label>
                    <input type="text" name="nombres" required value="<?= htmlspecialchars((string)($user['nombres'] ?? '')) ?>">
                </div>
                <div>
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" required value="<?= htmlspecialchars((string)($user['apellidos'] ?? '')) ?>">
                </div>
                <div>
                    <label>Correo</label>
                    <input type="email" name="correo" required value="<?= htmlspecialchars((string)($user['correo'] ?? $user['email'] ?? '')) ?>">
                </div>
                <div>
                    <label>Celular</label>
                    <input type="text" name="celular" value="<?= htmlspecialchars((string)($user['celular'] ?? '')) ?>">
                </div>
                <div>
                    <label>Nueva contraseña (opcional)</label>
                    <input type="password" name="password_nueva" autocomplete="new-password">
                </div>
                <div>
                    <label>Confirmar contraseña</label>
                    <input type="password" name="password_confirmar" autocomplete="new-password">
                </div>
            </div>

            <div class="profile-form-actions">
                <button type="button" class="student-btn-link" id="cancelEditarPerfilModal">Cancelar</button>
                <button type="submit" class="student-btn-secondary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var openBtn = document.getElementById('openEditarPerfilModal');
    var closeBtn = document.getElementById('closeEditarPerfilModal');
    var cancelBtn = document.getElementById('cancelEditarPerfilModal');
    var modal = document.getElementById('editarPerfilModal');

    if (!openBtn || !modal) return;

    function abrir() { modal.style.display = 'flex'; }
    function cerrar() { modal.style.display = 'none'; }

    openBtn.addEventListener('click', abrir);
    if (closeBtn) closeBtn.addEventListener('click', cerrar);
    if (cancelBtn) cancelBtn.addEventListener('click', cerrar);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) cerrar();
    });
})();
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
