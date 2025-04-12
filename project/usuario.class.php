<?php
class usuario {
    public function Login($login, $senha) {
        global $pdo; // Use a variável global

        $sql = "SELECT * FROM usuario WHERE email = :email AND senha = :senha";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":email", $login);
        $stmt->bindValue(":senha", $senha); // Certifique-se de que o hash ou senha corresponde
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $dado = $stmt->fetch(PDO::FETCH_ASSOC);

            session_start(); // Inicia a sessão

            // Armazena dados na sessão
            $_SESSION["iduser"] = $dado['idusuario'];
            $_SESSION["nome"] = $dado['nome'];

            return true; // Login bem-sucedido
        } else {
            return false; // Login falhou
        }
    }
    public function logged($iduser) {
        global $pdo; // Use a variável global
       
        $array = array();

        $sql = "SELECT * FROM usuario WHERE idusuario = :iduser";
        $sql = $pdo->prepare($sql);
        $sql->bindValue(":iduser", $iduser);
        $sql->execute();

        if ($sql->rowCount() > 0) {
            $array = $sql->fetch(PDO::FETCH_ASSOC); // Fetch the user data
        }
        return $array; // Return the user data
    }
}
?>
