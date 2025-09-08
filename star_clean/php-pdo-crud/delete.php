<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_die();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('ID inválido.');

    try {
        // Descobre em qual tabela o usuário está
        $stmt = $pdo->prepare("
            SELECT 'cliente' AS tipo FROM clientes WHERE id = :id
            UNION
            SELECT 'prestador' AS tipo FROM prestadores WHERE id = :id
            UNION
            SELECT 'administrador' AS tipo FROM administradores WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        $tipo = $stmt->fetchColumn();

        if (!$tipo) exit('Usuário não encontrado.');

        $pdo->beginTransaction();

        switch ($tipo) {
            case 'cliente':
                // Deleta endereço do cliente
                $pdo->prepare("DELETE FROM enderecos_clientes WHERE cliente_id = :id")->execute([':id' => $id]);
                // Deleta cliente
                $pdo->prepare("DELETE FROM clientes WHERE id = :id")->execute([':id' => $id]);
                break;

            case 'prestador':
                // Deleta endereço do prestador
                $pdo->prepare("DELETE FROM enderecos WHERE id_usuario = :id")->execute([':id' => $id]);
                // Deleta serviços e disponibilidade, caso existam
                $pdo->prepare("DELETE FROM servicos WHERE prestador_id = :id")->execute([':id' => $id]);
                $pdo->prepare("DELETE FROM disponibilidade WHERE prestador_id = :id")->execute([':id' => $id]);
                // Deleta prestador
                $pdo->prepare("DELETE FROM prestadores WHERE id = :id")->execute([':id' => $id]);
                break;

            case 'administrador':
                // Administrador não tem endereço
                $pdo->prepare("DELETE FROM administradores WHERE id = :id")->execute([':id' => $id]);
                break;
        }

        $pdo->commit();
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        exit('Erro ao excluir usuário: ' . $e->getMessage());
    }
}
