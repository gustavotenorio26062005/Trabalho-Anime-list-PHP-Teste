<?php
// Modelo de configuração do banco de dados.
// Copie este arquivo para includes/config.php e ajuste os valores para o seu ambiente.
// O arquivo config.php está no .gitignore e NÃO deve ser enviado ao repositório.
return [
    'host'     => 'localhost',     // Geralmente 'localhost' no XAMPP/WAMP
    'user'     => 'root',          // Usuário do MySQL
    'password' => '',              // Senha do MySQL (vazia por padrão no XAMPP)
    'database' => 'animalist_db',  // Nome do banco criado a partir de config_SQL/animalist_db.sql
];
