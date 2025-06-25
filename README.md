![Novo Projeto (39)](https://github.com/user-attachments/assets/99d3056b-b3b9-4260-859a-9e5c8394fd0f)
# 🎬 Animalist - Seu Site de Avaliações de Anime

<p align="center">
  <strong>Uma plataforma para você descobrir, avaliar e discutir seus animes favoritos.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Linguagem-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="Linguagem PHP">
  <img src="https://img.shields.io/badge/Banco%20de%20Dados-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="Banco de Dados MySQL">
  <img src="https://img.shields.io/badge/Servidor-Apache-D22128?style=for-the-badge&logo=Apache&logoColor=white" alt="Servidor Apache">
  <img src="https://img.shields.io/badge/Status-Em%20Desenvolvimento-yellow?style=for-the-badge" alt="Status: Em Desenvolvimento">
</p>

-----

## 📖 Sobre o Projeto

O **Animalist** é um site para avaliação de animes desenvolvido como um projeto acadêmico por alunos do curso de Tecnologia em Análise e Desenvolvimento de Sistemas do Instituto Federal de São Paulo (IFSP), Câmpus Bragança Paulista.

A plataforma permite que os usuários criem um perfil, pesquisem animes, mantenham listas personalizadas e compartilhem suas opiniões com a comunidade.

-----

## ✨ Funcionalidades

O sistema foi projetado com funcionalidades distintas para usuários e administradores.

### Para Usuários:

  - 👤 **Gerenciamento de Conta:** Cadastro, login, recuperação de senha e exclusão de perfil.
  - 🖼️ **Customização de Perfil:** Alteração de nome, foto, banner e descrição.
  - 🔍 **Pesquisa Avançada:** Busca de animes por nome, gênero e ano.
  - 📝 **Listas Pessoais:** Organize animes com status ("Assistindo", "Completado", "Planejando Assistir", etc.).
  - 👍 **Avaliações e Comentários:** Recomende ou não um anime e deixe sua opinião.

### Para Administradores:

  - ➕ **Gerenciamento de Conteúdo:** Adicionar, editar e excluir animes do catálogo.
  - 🛡️ **Moderação:** Excluir avaliações e comentários de usuários.

-----

## 🛠️ Tecnologias Utilizadas

  - **Backend:** PHP
  - **Banco de Dados:** MySQL
  - **Servidor Web:** Apache (configurado via XAMPP)
  - **Frontend:** HTML, CSS, JavaScript

-----

## 🚀 Começando

Siga os passos abaixo para configurar e rodar o projeto em seu ambiente local.

### Pré-requisitos

  - **XAMPP:** Um ambiente de desenvolvimento PHP completo.
      - Pode ser baixado em: **[https://www.apachefriends.org/index.html](https://www.apachefriends.org/index.html)**
  - **Navegador Web:** Google Chrome, Mozilla Firefox, etc.

### Instalação

1.  **Clone o Repositório**

    ```sh
    git clone https://github.com/SEU-USUARIO/Projeto-ANIMALIST-PHP.git
    ```

    *Como alternativa, você pode baixar o arquivo `.zip` e extraí-lo.*

2.  **Mova os Arquivos**
    Copie a pasta `animalist_site` para o diretório `htdocs` da sua instalação do XAMPP.

    ```
    # Exemplo no Windows
    C:\xampp\htdocs\animalist_site

    # Exemplo no macOS
    /Applications/XAMPP/xamppfiles/htdocs/animalist_site
    ```

3.  **Inicie os Serviços**
    Abra o Painel de Controle do XAMPP e inicie os módulos **Apache** e **MySQL**.

4.  **Importe o Banco de Dados**
   
    a. Abra o phpMyAdmin em `http://localhost/phpmyadmin`.
    
    b. Clique em **"Novo"** para criar um banco de dados.
    
    c. Nomeie o banco como `animalist_db` e clique em "Criar".
    
    d. Selecione o banco `animalist_db` e vá para a aba **"Importar"**.
    
    e. Clique em **"Escolher arquivo"** e localize o arquivo `animalist_site/config_SQL/animalist_db.sql`.
    
    f. Clique em **"Executar"** no final da página para iniciar a importação.

    Alternativamente:
    
    a. Copie o código dentro do arquivo `animalist_db`
    
    b. abra a página admin do MySQL no Xampp
    
    c. Cole o código que copiou dentro da aba "SQL"
    
    d. Clique no botão executar

6.  **Configure a Conexão com o Banco**

    > **⚠️ MUITO IMPORTANTE:** Este passo garante que o site se conecte ao banco de dados.

    a. Abra o arquivo de conexão em um editor de código: `animalist_site/includes/db_connect.php`.
    b. Verifique se as variáveis de conexão correspondem à sua configuração do MySQL.

    | Variável | Valor Padrão | Descrição |
    | :--- | :--- | :--- |
    | `$host` | `localhost` | Endereço do servidor. |
    | `$user` | `root` | Usuário do MySQL. |
    | `$password` | `''` (vazio) | **Ajuste aqui se o seu `root` tiver senha.** |
    | `$dbh` | `animalist_db`| Nome do banco de dados criado no passo 4. |

    **Exemplo no arquivo `db_connect.php`:**

    ```php
    <?php
    $host = 'localhost';
    $user = 'root';
    $password = ''; // <-- ❗ ATENÇÃO AQUI! Altere se seu root tiver senha.
    $dbh = 'animalist_db'; // <-- Certifique-se que este é o nome do seu banco.

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbh", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("ERROR: Could not connect. " . $e->getMessage());
    }
    ?>
    ```

7.  **Acesse o Animalist**
    Abra seu navegador e digite o endereço: **[http://localhost/animalist\_site](https://www.google.com/search?q=http://localhost/animalist_site)**.

🎉 E pronto\! Agora você pode navegar e usar o Animalist localmente.

-----

## 🤔 Solução de Problemas Comuns

  - **`Access denied for user 'root'@'localhost'`**: A senha no arquivo `db_connect.php` não corresponde à senha do seu MySQL. Verifique o **Passo 5**.
  - **`Unknown database 'animalist_db'`**: O nome do banco no arquivo de conexão está incorreto ou não foi criado corretamente. Verifique os **Passos 4 e 5**.
  - **Página em branco ou erros de PHP**: Verifique se os serviços Apache e MySQL estão realmente rodando no XAMPP.
  - **Erro 404 (Objeto não encontrado)**: Verifique se o nome da pasta em `htdocs` está correto (`animalist_site`) e se a URL no navegador está correta.

-----

## 👥 Contribuidores

Este projeto foi idealizado e desenvolvido por:

  - [Gabriel Dias Ribeiro](https://github.com/Tsarco)
  - [Gustavo Barros Tenório](https://github.com/gustavotenorio26062005)
  - [Luiz Henrique Gonçalvez](https://github.com/LuizHenriqueGon)
  - [Maycon Cabral da Silva](https://github.com/Mayconcabral1196)
  - [Renan Valença Bueno Rodrigues](https://github.com/RenanVKoashi)

-----

\<p align="center"\>
Divirta-se avaliando seus animes\! 🌟
\</p\>
