<?php
include '../includes/header.php';
include '../includes/navbar.php';
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card p-4 shadow-sm" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Login</h3>

        <form action="#" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail:</label>
                <input type="email" class="form-control" name="email" id="email" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha:</label>
                <input type="password" class="form-control" name="senha" id="senha" required>
            </div>

            <div class="mb-3">
                <label for="tipo" class="form-label">Tipo de usuário:</label>
                <select name="tipo" id="tipo" class="form-select" required>
                    <option value="cliente">Cliente</option>
                    <option value="prestador">Prestador</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

        <!-- Links adicionais -->
        <div class="text-center mt-3">
            <a href="esqueci-senha.php" class="d-block">Esqueci minha senha</a>
            <span class="text-muted">Ainda não tem conta?</span>
            <a href="cadastro.php">Cadastre-se</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>