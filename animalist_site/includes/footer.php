<?php
$anoAtual = date("Y"); // Para o ano dinâmico
?>

<footer class="rodape-principal">
    <div class="container-rodape">
        <div class="coluna-logo-rodape">
            <img src="img/logo_vertical.png" alt="Logo Animalist Rodapé" class="logo-rodape-img"> <!-- Ajuste o caminho/nome da sua imagem do logo -->
            <p class="copyright-rodape">© <?php echo $anoAtual; ?> AnimaList.</p>
            <p class="direitos-rodape">Todos os direitos reservados.</p>
        </div>

        <div class="coluna-links-rodape">
            <h4 class="titulo-coluna-rodape">Redes Sociais</h4>
            <ul class="lista-icones-sociais">
                <li><a href="#" aria-label="Twitter/X"><i class="fab fa-twitter"></i></a></li> <!-- Ícone do Twitter/X -->
                <li><a href="#" aria-label="Discord"><i class="fab fa-discord"></i></a></li> <!-- Ícone do Discord -->
                <li><a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a></li> <!-- Ícone do YouTube -->
            </ul>
        </div>

        <div class="coluna-links-rodape">
            <h4 class="titulo-coluna-rodape">Links</h4>
            <ul class="lista-links-uteis">
                <li><a href="sobre.php">Sobre</a></li>
                <li><a href="termos.php">Termos de Privacidade</a></li>
            </ul>
        </div>

        <div class="coluna-contato-rodape">
            <h4 class="titulo-coluna-rodape">Contato</h4>
            <p>Doe pelo nosso pix:<br>12.345.678/0001-95</p>
            <p>Telefone:<br>(51) 51403-5624</p>
            <p>Nosso suporte:<br>contact.animalist@gmail.com</p>
        </div>
    </div>
</footer>

<!-- Importante: Adicione o link para o Font Awesome no <head> da sua página principal se ainda não tiver -->
<!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> -->