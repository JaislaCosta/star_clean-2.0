<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

$pdo = getPDO();
$errors = [];
$success = '';
$name = $sobrenome = $email = $telefone = $data_nascimento = '';
$cep = $logradouro = $bairro = $cidade = $uf = $numero = $complemento = '';
$cpf_cnpj = '';
$tipo_usuario = 'cliente';
$especialidade = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_die();

  // Dados do usuário
  $name            = trim($_POST['name'] ?? '');
  $sobrenome       = trim($_POST['sobrenome'] ?? '');
  $email           = trim($_POST['email'] ?? '');
  $telefone        = trim($_POST['telefone'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $cpf_cnpj        = trim($_POST['cpf_cnpj'] ?? '');
  $tipo_usuario    = $_POST['tipo_usuario'] ?? 'cliente';
  $especialidade   = trim($_POST['especialidade'] ?? '');
  $pass            = $_POST['password'] ?? '';
  $confirmPass     = $_POST['confirm_password'] ?? '';

  // Dados do endereço
  $cep        = trim($_POST['cep'] ?? '');
  $logradouro = trim($_POST['logradouro'] ?? '');
  $bairro     = trim($_POST['bairro'] ?? '');
  $cidade     = trim($_POST['cidade'] ?? '');
  $uf         = trim($_POST['uf'] ?? '');
  $numero     = trim($_POST['numero'] ?? '');
  $complemento = trim($_POST['complemento'] ?? '');

  // Validações
  if ($name === '') $errors[] = 'Nome é obrigatório.';
  if ($sobrenome === '') $errors[] = 'Sobrenome é obrigatório.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
  if ($telefone === '') $errors[] = 'Telefone é obrigatório.';
  if ($data_nascimento === '') $errors[] = 'Data de nascimento é obrigatória.';
  if ($cpf_cnpj === '') $errors[] = 'CPF/CNPJ é obrigatório.';

  if ($tipo_usuario === 'cliente' && !preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf_cnpj)) {
    $errors[] = 'CPF inválido. Use o formato 000.000.000-00';
  } elseif ($tipo_usuario === 'prestador' && !preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', $cpf_cnpj)) {
    $errors[] = 'CNPJ inválido. Use o formato 00.000.000/0000-00';
  }

  if ($tipo_usuario === 'prestador' && $especialidade === '') {
    $errors[] = 'Especialidade é obrigatória para prestadores.';
  }

  // Validação senha forte
  $senhaForte = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
  if (!preg_match($senhaForte, $pass)) {
    $errors[] = 'A senha deve ter pelo menos 8 caracteres, incluindo letra maiúscula, minúscula, número e caractere especial.';
  }
  if ($pass !== $confirmPass) $errors[] = 'As senhas não conferem.';

  // Validação endereço
  if ($cep === '' || $logradouro === '' || $bairro === '' || $cidade === '' || $uf === '') {
    $errors[] = 'Todos os campos obrigatórios do endereço devem ser preenchidos.';
  }

  if (!$errors) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);

    try {
      $pdo->beginTransaction();

      // Insere usuário
      $stmtUser = $pdo->prepare("
                INSERT INTO usuarios 
                (name, sobrenome, email, telefone, data_nascimento, cpf_cnpj, especialidade, password_hash, tipo_usuario) 
                VALUES (:name, :sobrenome, :email, :telefone, :data_nasc, :cpf_cnpj, :especialidade, :pass, :tipo)
            ");
      $stmtUser->execute([
        ':name' => $name,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':telefone' => $telefone,
        ':data_nasc' => $data_nascimento,
        ':cpf_cnpj' => $cpf_cnpj,
        ':especialidade' => $especialidade ?: null,
        ':pass' => $hash,
        ':tipo' => $tipo_usuario
      ]);

      $userId = $pdo->lastInsertId();

      // Insere endereço único
      $stmtEnd = $pdo->prepare("
                INSERT INTO enderecos 
                (id_usuario, cep, logradouro, bairro, cidade, uf, numero, complemento) 
                VALUES (:u, :c, :l, :b, :ci, :uf, :n, :co)
            ");
      $stmtEnd->execute([
        ':u' => $userId,
        ':c' => $cep,
        ':l' => $logradouro,
        ':b' => $bairro,
        ':ci' => $cidade,
        ':uf' => $uf,
        ':n' => $numero,
        ':co' => $complemento
      ]);

      $pdo->commit();
      $success = 'Usuário cadastrado com sucesso!';

      // Limpar campos
      $name = $sobrenome = $email = $telefone = $data_nascimento = '';
      $cep = $logradouro = $bairro = $cidade = $uf = $numero = $complemento = '';
      $cpf_cnpj = '';
      $tipo_usuario = 'cliente';
      $especialidade = '';
    } catch (PDOException $e) {
      $pdo->rollBack();
      if ($e->getCode() === '23000') $errors[] = 'Email ou CPF/CNPJ já cadastrado.';
      else $errors[] = 'Erro ao inserir: ' . $e->getMessage();
    }
  }
}

require_once __DIR__ . '/header.php';
$token = csrf_token();
?>

<div class="row">
  <div class="col-lg-6">
    <h2>Novo Usuário</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?= $token ?>">

      <!-- Campos do usuário -->
      <div class="mb-3">
        <label>Nome</label>
        <input type="text" name="name" class="form-control" placeholder="Nome" value="<?= htmlspecialchars($name) ?>" required>
      </div>
      <div class="mb-3">
        <label>Sobrenome</label>
        <input type="text" name="sobrenome" class="form-control" placeholder="Sobrenome" value="<?= htmlspecialchars($sobrenome) ?>" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" placeholder="email@exemplo.com" value="<?= htmlspecialchars($email) ?>" required>
      </div>
      <div class="mb-3">
        <label>CPF/CNPJ</label>
        <input type="text" name="cpf_cnpj" class="form-control" placeholder="000.000.000-00 ou 00.000.000/0000-00" value="<?= htmlspecialchars($cpf_cnpj) ?>" required>
      </div>
      <div class="mb-3">
        <label>Tipo de Usuário</label>
        <select name="tipo_usuario" class="form-select" required>
          <option value="cliente" <?= $tipo_usuario == 'cliente' ? 'selected' : '' ?>>Cliente</option>
          <option value="prestador" <?= $tipo_usuario == 'prestador' ? 'selected' : '' ?>>Prestador</option>
          <option value="administrador" <?= $tipo_usuario == 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>
      <div class="mb-3">
        <label>Especialidade</label>
        <input type="text" name="especialidade" class="form-control" value="<?= htmlspecialchars($especialidade) ?>">
      </div>
      <div class="mb-3">
        <label>Data de Nascimento</label>
        <input type="date" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($data_nascimento) ?>" required>
      </div>
      <div class="mb-3">
        <label>Telefone</label>
        <input type="tel" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= htmlspecialchars($telefone) ?>" required>
      </div>

      <!-- Endereço único -->
      <h4>Endereço</h4>
      <div class="mb-3">
        <label>CEP</label>
        <input type="text" name="cep" id="cep" class="form-control" placeholder="00000-000" value="<?= htmlspecialchars($cep) ?>">
      </div>
      <div class="mb-3"><label>Logradouro</label><input type="text" name="logradouro" class="form-control" value="<?= htmlspecialchars($logradouro) ?>"></div>
      <div class="mb-3"><label>Bairro</label><input type="text" name="bairro" class="form-control" value="<?= htmlspecialchars($bairro) ?>"></div>
      <div class="mb-3"><label>Cidade</label><input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($cidade) ?>"></div>
      <div class="mb-3"><label>UF</label><input type="text" name="uf" class="form-control" maxlength="2" value="<?= htmlspecialchars($uf) ?>"></div>
      <div class="mb-3"><label>Número</label><input type="text" name="numero" class="form-control" value="<?= htmlspecialchars($numero) ?>"></div>
      <div class="mb-3"><label>Complemento</label><input type="text" name="complemento" class="form-control" value="<?= htmlspecialchars($complemento) ?>"></div>

      <!-- Senha -->
      <div class="mb-3"><label>Senha</label><input type="password" name="password" class="form-control" required></div>
      <div class="mb-3"><label>Confirmar Senha</label><input type="password" name="confirm_password" class="form-control" required></div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary">Salvar</button>
        <a class="btn btn-secondary" href="index.php">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>