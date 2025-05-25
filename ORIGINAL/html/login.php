<?php
require '../switch.php'; // Inclui a conexão com o banco de dados
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <title>Login</title>
</head>
<body>
    <form action="logar.php" method="post" class="form-login">
        <div class="container mt-6">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card p-4 shadow">
                        <h2 class="text-center mb-4">Login</h2>
                        <div class="mb-4">
                            <label for="E-mail" class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control form-control-lg rounded-pill" id="E-mail" placeholder="Entre com seu E-mail">
                        </div>
                        <div class="mb-4">
                            <label for="Senha" class="form-label">Senha</label>
                            <input type="password" name="senha" class="form-control form-control-lg rounded-pill" id="password" placeholder="Entre com sua senha">
                        </div>
                        <button type="submit" class="btn btn-verde w-100 rounded-pill" style="background-color: green; color: white;">Login</button>
    
                        <div class="row justify-content-center mb-4 mt-4">
                            <div class="col-md-12 text-center">
                                <a href="?page=esqueci-senha" class="text-decoration-none me-4">Esqueci minha senha</a>
                                <a href="?page=cadastrar" class="text-decoration-none">Não tem uma conta? Cadastre-se</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
</body>
</html>
