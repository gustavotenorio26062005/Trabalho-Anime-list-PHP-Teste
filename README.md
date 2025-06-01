🎬 Animalist - Seu Site de Avaliações de Anime
👋 Bem-vindo ao Animalist! Uma plataforma para você descobrir, avaliar e discutir seus animes favoritos.
Este guia irá ajudá-lo a configurar e rodar o projeto em seu ambiente local.
🛠️ Pré-requisitos
Para rodar este projeto localmente, você precisará ter o seguinte instalado:
🖥️ XAMPP: Um ambiente de desenvolvimento PHP que inclui:
Apache (servidor web)
MySQL (banco de dados)
PHP
Você pode baixá-lo em: https://www.apachefriends.org/index.html
🌐 Navegador Web: Chrome, Firefox, Edge, etc.
📁 Os arquivos do projeto Animalist: Incluindo o arquivo .sql do banco de dados.
🚀 Configuração e Instalação
Siga os passos abaixo para colocar o Animalist no ar em sua máquina:
📥 Instale o XAMPP:
Baixe e instale o XAMPP. Durante a instalação, você pode manter as opções padrão.
📂 Copie os Arquivos do Projeto:
Pegue a pasta do projeto "Animalist" e copie-a para o diretório htdocs dentro da sua instalação do XAMPP.
Exemplo de caminho no Windows: C:\xampp\htdocs\animalist
Exemplo de caminho no macOS: /Applications/XAMPP/xamppfiles/htdocs/animalist
Exemplo de caminho no Linux: /opt/lampp/htdocs/animalist
(Certifique-se de que o nome da pasta seja animalist ou ajuste o URL no passo 6 de acordo)
▶️ Inicie os Serviços do XAMPP:
Abra o Painel de Controle do XAMPP.
Inicie os módulos Apache e MySQL. Espere até que fiquem verdes (ou indicando que estão rodando).
🗄️ Importe o Banco de Dados:
Abra seu navegador e acesse o phpMyAdmin: http://localhost/phpmyadmin
No menu à esquerda, clique em "Novo" para criar um novo banco de dados.
Dê um nome ao banco de dados (ex: animalist_db) e clique em "Criar".
Selecione o banco de dados que você acabou de criar na lista à esquerda.
Clique na aba "Importar" no menu superior.
Clique em "Escolher arquivo" e localize o arquivo .sql que veio com o projeto (ex: animalist_banco.sql).
Role para baixo e clique em "Executar". Aguarde a importação ser concluída. ✨
🔑 Verifique a Configuração de Conexão com o Banco (MUITO IMPORTANTE!):
Dentro da pasta do projeto (ex: htdocs/animalist), localize o arquivo PHP que faz a conexão com o banco de dados.
Ele pode se chamar conexao.php, db.php, config.php, ou estar dentro de uma pasta como includes/ ou config/.
Abra este arquivo em um editor de texto.
Verifique as seguintes configurações:
host: geralmente localhost
username: geralmente root
password: ❗ ATENÇÃO: Por padrão, este projeto está configurado para conectar ao MySQL com o usuário root SEM SENHA.
Se o seu MySQL (do XAMPP) tiver uma senha definida para o usuário root, você precisará:
✅ Opção A (Recomendado): Alterar a senha no arquivo de configuração do PHP para a senha que você usa no seu MySQL.
⚠️ Opção B: Remover a senha do usuário root no seu MySQL (menos seguro para outros projetos).
database_name ou dbname: Deve ser o mesmo nome que você deu ao banco de dados no passo 4 (ex: animalist_db).
📝 Exemplo de como pode estar no arquivo PHP:
<?php
$servidor = "localhost";
$usuario = "root";
$senha = ""; // <--- ❗ ATENÇÃO AQUI! Altere se seu root tiver senha.
$banco = "animalist_db"; // <--- Certifique-se que este é o nome do seu banco.

$conexao = mysqli_connect($servidor, $usuario, $senha, $banco);

if (!$conexao) {
    die("Falha na conexão: " . mysqli_connect_error());
}
// echo "Conectado com sucesso!"; // Descomente para testar a conexão
?>
Use code with caution.
PHP
💾 Salve as alterações, se houver alguma.
🌍 Acesse o Animalist:
Abra seu navegador web.
Digite o seguinte endereço: http://localhost/animalist
(Se você nomeou a pasta do projeto de forma diferente dentro de htdocs, substitua animalist pelo nome da sua pasta).
🎉 E pronto! Agora você deve conseguir navegar e usar o Animalist localmente.
🤔 Solução de Problemas Comuns
"Access denied for user 'root'@'localhost'":
Isso geralmente significa que a senha no arquivo de configuração do PHP não corresponde à senha do usuário root do seu MySQL. Verifique o Passo 5.
"Unknown database 'animalist_db'":
O nome do banco de dados no arquivo de configuração do PHP não corresponde ao nome do banco que você criou ou importou no phpMyAdmin. Verifique os Passos 4 e 5.
Página em branco ou erros de PHP:
Verifique se o Apache e o MySQL estão realmente rodando no XAMPP.
Certifique-se também de que você está usando uma versão do PHP compatível com o projeto (o XAMPP geralmente vem com uma versão recente e estável).
Erro 404 / Objeto não encontrado:
Verifique se o nome da pasta do projeto em htdocs está correto.
Verifique se você digitou o URL corretamente no navegador.
Divirta-se avaliando seus animes! 🌟
