<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Esqueci a Senha</title>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 shadow">
                    <h2 class="text-center mb-4">Esqueci a Senha</h2>
                    <form action="processa_esqueci_senha.php" method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Digite seu E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="Digite seu e-mail" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Enviar Link de Redefinição</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
