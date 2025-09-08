<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);

// Busca usuário
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  http_response_code(404);
  exit('Usuário não encontrado.');
}

// Busca endereço do usuário
$stmtEnd = $pdo->prepare("SELECT * FROM enderecos WHERE id_usuario = :id_usuario");
$stmtEnd->execute([':id_usuario' => $id]);
$endereco = $stmtEnd->fetch(PDO::FETCH_ASSOC) ?: [
  'cep' => '',
  'logradouro' => '',
  'bairro' => '',
  'cidade' => '',
  'uf' => '',
  'numero' => '',
  'complemento' => ''
];

$errors = [];
$name = $user['name'];
$sobrenome = $user['sobrenome'];
$email = $user['email'];
$telefone = $user['telefone'];
$data_nascimento = $user['data_nascimento'];
$cpf_cnpj = $user['cpf_cnpj'];
$tipo_usuario = $user['tipo_usuario'];
$especialidade = $user['especialidade'] ?? '';
$pass = $confirmPass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf_or_die();

  $name = trim($_POST['name'] ?? '');
  $sobrenome = trim($_POST['sobrenome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $telefone = trim($_POST['telefone'] ?? '');
  $data_nascimento = trim($_POST['data_nascimento'] ?? '');
  $cpf_cnpj = trim($_POST['cpf_cnpj'] ?? '');
  $tipo_usuario = $_POST['tipo_usuario'] ?? '';
  $especialidade = trim($_POST['especialidade'] ?? '');
  $pass = $_POST['password'] ?? '';
  $confirmPass = $_POST['confirm_password'] ?? '';

  // Endereço
  $cep = trim($_POST['cep'] ?? '');
  $logradouro = trim($_POST['logradouro'] ?? '');
  $bairro = trim($_POST['bairro'] ?? '');
  $cidade = trim($_POST['cidade'] ?? '');
  $uf = trim($_POST['uf'] ?? '');
  $numero = trim($_POST['numero'] ?? '');
  $complemento = trim($_POST['complemento'] ?? '');

  // Validações
  if ($name === '') $errors[] = 'Nome é obrigatório.';
  if ($sobrenome === '') $errors[] = 'Sobrenome é obrigatório.';
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
  if ($telefone === '') $errors[] = 'Telefone é obrigatório.';
  if ($data_nascimento === '') $errors[] = 'Data de nascimento é obrigatória.';
  if ($cpf_cnpj === '') $errors[] = 'CPF/CNPJ é obrigatório.';
  if ($tipo_usuario === '') $errors[] = 'Tipo de usuário é obrigatório.';

  if ($tipo_usuario === 'cliente' && !preg_match('/^\d{3}\.\d{3}\.\d{3}-\d{2}$/', $cpf_cnpj)) {
    $errors[] = 'CPF inválido. Use o formato 000.000.000-00';
  } elseif ($tipo_usuario === 'prestador' && !preg_match('/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/', $cpf_cnpj)) {
    $errors[] = 'CNPJ inválido. Use o formato 00.000.000/0000-00';
  }

  if ($tipo_usuario === 'prestador' && $especialidade === '') {
    $errors[] = 'Especialidade é obrigatória para prestadores.';
  }

  $senhaForte = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
  if ($pass !== '' && !preg_match($senhaForte, $pass)) {
    $errors[] = 'A senha deve ter pelo menos 8 caracteres, incluindo letra maiúscula, minúscula, número e caractere especial.';
  }
  if ($pass !== $confirmPass) $errors[] = 'As senhas não conferem.';

  // Validação endereço
  if ($tipo_usuario === 'cliente' || $tipo_usuario === 'prestador') {
    if ($cep === '' || $logradouro === '' || $bairro === '' || $cidade === '' || $uf === '') {
      $errors[] = 'Todos os campos obrigatórios do endereço devem ser preenchidos.';
    }
  }

  if (!$errors) {
    try {
      $pdo->beginTransaction();

      // Atualiza usuário
      $params = [
        ':name' => $name,
        ':sobrenome' => $sobrenome,
        ':email' => $email,
        ':telefone' => $telefone,
        ':data_nascimento' => $data_nascimento,
        ':cpf_cnpj' => $cpf_cnpj,
        ':especialidade' => $especialidade ?: null,
        ':tipo_usuario' => $tipo_usuario,
        ':id' => $id
      ];

      if ($pass !== '') {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $params[':password_hash'] = $hash;
        $stmtUpdate = $pdo->prepare("
                    UPDATE usuarios
                    SET name=:name, sobrenome=:sobrenome, email=:email, telefone=:telefone,
                        data_nascimento=:data_nascimento, cpf_cnpj=:cpf_cnpj, especialidade=:especialidade,
                        password_hash=:password_hash, tipo_usuario=:tipo_usuario
                    WHERE id=:id
                ");
      } else {
        $stmtUpdate = $pdo->prepare("
                    UPDATE usuarios
                    SET name=:name, sobrenome=:sobrenome, email=:email, telefone=:telefone,
                        data_nascimento=:data_nascimento, cpf_cnpj=:cpf_cnpj, especialidade=:especialidade,
                        tipo_usuario=:tipo_usuario
                    WHERE id=:id
                ");
      }
      $stmtUpdate->execute($params);

      // Atualiza endereço único
      $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM enderecos WHERE id_usuario=:id");
      $stmtCheck->execute([':id' => $id]);
      if ($stmtCheck->fetchColumn() > 0) {
        $stmtEndUpd = $pdo->prepare("
                    UPDATE enderecos
                    SET cep=:cep, logradouro=:logradouro, bairro=:bairro, cidade=:cidade, uf=:uf, numero=:numero, complemento=:complemento
                    WHERE id_usuario=:id_usuario
                ");
        $stmtEndUpd->execute([
          ':cep' => $cep,
          ':logradouro' => $logradouro,
          ':bairro' => $bairro,
          ':cidade' => $cidade,
          ':uf' => $uf,
          ':numero' => $numero,
          ':complemento' => $complemento,
          ':id_usuario' => $id
        ]);
      } else {
        $stmtEndIns = $pdo->prepare("
                    INSERT INTO enderecos (id_usuario, cep, logradouro, bairro, cidade, uf, numero, complemento)
                    VALUES (:id_usuario, :cep, :logradouro, :bairro, :cidade, :uf, :numero, :complemento)
                ");
        $stmtEndIns->execute([
          ':id_usuario' => $id,
          ':cep' => $cep,
          ':logradouro' => $logradouro,
          ':bairro' => $bairro,
          ':cidade' => $cidade,
          ':uf' => $uf,
          ':numero' => $numero,
          ':complemento' => $complemento
        ]);
      }

      $pdo->commit();
      header('Location: index.php');
      exit;
    } catch (PDOException $e) {
      $pdo->rollBack();
      if ($e->getCode() === '23000') $errors[] = 'Email ou CPF/CNPJ já cadastrado.';
      else $errors[] = 'Erro ao atualizar: ' . $e->getMessage();
    }
  }
}

require_once __DIR__ . '/header.php';
$token = csrf_token();
?>

<div class="row">
  <div class="col-lg-6">
    <h2>Editar Usuário #<?= (int)$user['id'] ?></h2>

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

      <!-- Dados do usuário -->
      <div class="mb-3">
        <label>Nome</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($name) ?>" required>
      </div>
      <div class="mb-3">
        <label>Sobrenome</label>
        <input type="text" name="sobrenome" class="form-control" value="<?= htmlspecialchars($sobrenome) ?>" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
      </div>
      <div class="mb-3">
        <label>CPF/CNPJ</label>
        <input type="text" name="cpf_cnpj" class="form-control" value="<?= htmlspecialchars($cpf_cnpj) ?>" required>
      </div>

      <div class="mb-3">
        <label>Tipo de Usuário</label>
        <select name="tipo_usuario" class="form-select" required onchange="document.getElementById('especialidadeDiv').style.display=this.value=='prestador'?'block':'none';">
          <option value="cliente" <?= $tipo_usuario == 'cliente' ? 'selected' : '' ?>>Cliente</option>
          <option value="prestador" <?= $tipo_usuario == 'prestador' ? 'selected' : '' ?>>Prestador</option>
          <option value="administrador" <?= $tipo_usuario == 'administrador' ? 'selected' : '' ?>>Administrador</option>
        </select>
      </div>

      <div class="mb-3" id="especialidadeDiv" style="display:<?= $tipo_usuario == 'prestador' ? 'block' : 'none' ?>">
        <label>Especialidade</label>
        <input type="text" name="especialidade" class="form-control" value="<?= htmlspecialchars($especialidade) ?>">
      </div>

      <div class="mb-3">
        <label>Data de Nascimento</label>
        <input type="date" name="data_nascimento" class="form-control" value="<?= htmlspecialchars($data_nascimento) ?>" required>
      </div>
      <div class="mb-3">
        <label>Telefone</label>
        <input type="tel" name="telefone" class="form-control" value="<?= htmlspecialchars($telefone) ?>" required>
      </div>

      <!-- Endereço único -->
      <h4>Endereço</h4>
      <div class="mb-3">
        <label>CEP</label>
        <input type="text" name="cep" id="cep" class="form-control" value="<?= htmlspecialchars($endereco['cep']) ?>" onblur="pesquisacep(this.value);">
      </div>
      <div class="mb-3"><label>Logradouro</label><input type="text" name="logradouro" class="form-control" value="<?= htmlspecialchars($endereco['logradouro']) ?>"></div>
      <div class="mb-3"><label>Bairro</label><input type="text" name="bairro" class="form-control" value="<?= htmlspecialchars($endereco['bairro']) ?>"></div>
      <div class="mb-3"><label>Cidade</label><input type="text" name="cidade" class="form-control" value="<?= htmlspecialchars($endereco['cidade']) ?>"></div>
      <div class="mb-3"><label>UF</label><input type="text" name="uf" class="form-control" maxlength="2" value="<?= htmlspecialchars($endereco['uf']) ?>"></div>
      <div class="mb-3"><label>Número</label><input type="text" name="numero" class="form-control" value="<?= htmlspecialchars($endereco['numero']) ?>"></div>
      <div class="mb-3"><label>Complemento</label><input type="text" name="complemento" class="form-control" value="<?= htmlspecialchars($endereco['complemento']) ?>"></div>

      <!-- Senha -->
      <h4>Senha</h4>
      <div class="mb-3">
        <label>Senha (deixe em branco para manter)</label>
        <input type="password" name="password" class="form-control">
      </div>
      <div class="mb-3">
        <label>Confirmar Senha</label>
        <input type="password" name="confirm_password" class="form-control">
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-primary">Salvar</button>
        <a class="btn btn-secondary" href="index.php">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>