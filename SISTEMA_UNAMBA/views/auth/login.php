<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tutorías - UNAMBA</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome (opcional) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body{
            margin:0;
            height:100vh;
            background:url('assets/img/unamba.jpg') no-repeat center center;
            background-size:cover;
        }

        .overlay{
            width:100%;
            height:100vh;
            background:rgba(0,0,0,0.35);
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .login-card{
            width:450px;
            background:rgba(255,255,255,0.95);
            border-radius:20px;
            padding:30px;
            box-shadow:0 10px 30px rgba(0,0,0,.3);
        }

        .titulo{
            color:#0b3b75;
            font-weight:bold;
        }

        .btn-login{
            background:#0b3b75;
            border:none;
        }

        .btn-login:hover{
            background:#072a54;
        }
    </style>
</head>
<body>

<div class="overlay">

    <div class="login-card">

        <div class="text-center">
            
            <h3 class="titulo">Sistema de Tutorías</h3>
            <p class="text-muted">Universidad Nacional Micaela Bastidas de Apurímac</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars((string)$error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="index.php?route=auth/process">

            <div class="mb-3">
                <label class="form-label">Correo Institucional</label>
                <input type="email"
                       class="form-control"
                       name="email"
                       placeholder="correo@unamba.edu.pe"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password"
                       class="form-control"
                       name="password"
                       required>
            </div>

            <button type="submit" class="btn btn-login text-white w-100">
                <i class="fas fa-sign-in-alt"></i> Ingresar
            </button>

        </form>

    </div>

</div>

</body>
</html>