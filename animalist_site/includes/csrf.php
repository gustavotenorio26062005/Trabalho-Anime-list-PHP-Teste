<?php
// Proteção contra CSRF (Cross-Site Request Forgery).
// O token é gerado uma vez por sessão e deve acompanhar toda requisição POST que altera dados.

function csrf_token(): string {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Campo oculto para incluir dentro de formulários HTML.
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

// Valida o token enviado via POST (comparação em tempo constante).
function csrf_validate(): bool {
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}
