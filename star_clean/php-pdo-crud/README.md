# Esqueleto básico para alunos ETC
# PHP CRUD (Usuários)

Projeto  básico e introdutório em **PHP** utilizando **PDO** para demonstrar um CRUD básico de **Usuários** (Criar, Listar, Editar e Deletar).

## Requisitos
- PHP 7.4+ (ou superior)
- MySQL/MariaDB
- Servidor local (XAMPP/Laragon/WAMP) ou PHP embutido
- Extensão PDO habilitada

## Instalação
1. Crie um banco de dados (ex.: `crud_php`).
2. Importe o arquivo **init.sql** na sua base.
3. Ajuste as credenciais em **config.php** "Se necessário" (host, dbname, usuário, senha).
4. Coloque a pasta do projeto no seu servidor (ex.: `C:/xampp/htdocs/php-pdo-crud`).
5. Acesse `http://localhost/php-pdo-crud/index.php`.

## Rotas
- `index.php` – lista de usuários + ações
- `create.php` – criar usuário
- `edit.php?id=...` – editar um usuário
- `delete.php` – endpoint POST para excluir

## Observações de Segurança
- Uso de **prepared statements** (PDO) contra SQL Injection
- **CSRF token** nos formulários que alteram dados
- **password_hash()** para senha (exemplo didático)
