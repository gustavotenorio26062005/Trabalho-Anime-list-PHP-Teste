![Novo Projeto (39)](https://github.com/user-attachments/assets/977bc3af-5a76-4565-be6c-584790534ee3)


# 🎬 Animalist - Seu Site de Avaliações de Anime

<p align="center">
  <strong>Uma plataforma para você descobrir, avaliar e discutir seus animes favoritos.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Linguagem-PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="Linguagem PHP">
  <img src="https://img.shields.io/badge/Banco%20de%20Dados-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="Banco de Dados MySQL">
  <img src="https://img.shields.io/badge/Servidor-Apache-D22128?style=for-the-badge&logo=Apache&logoColor=white" alt="Servidor Apache">
  <img src="https://img.shields.io/badge/Status-Completo-green?style=for-the-badge" alt="Status: Completo.">
</p>


<p align="center">
  <a href="https://www.youtube.com/watch?v=l2qvem76MwY" title="Clique para assistir à demonstração">
    <img src="https://img.shields.io/badge/Assista%20à%20Demonstração-FF0000?style=for-the-badge&logo=youtube&logoColor=white" alt="Assista à Demonstração">
  </a>
</p>


-----

## 📖 Sobre o Projeto

O **Animalist** é um site para avaliação de animes desenvolvido como um projeto acadêmico por alunos do curso de Tecnologia em Análise e Desenvolvimento de Sistemas do Instituto Federal de São Paulo (IFSP), Câmpus Bragança Paulista.

A plataforma permite que os usuários criem um perfil, pesquisem animes, mantenham listas personalizadas e compartilhem suas opiniões com a comunidade.

-----

## ✨ Funcionalidades

O sistema foi projetado com funcionalidades distintas para usuários e administradores.

### Para Usuários:

  - 👤 **Gerenciamento de Conta:** Cadastro, login, alteração de senha e exclusão de perfil.
  - 🖼️ **Customização de Perfil:** Alteração de nome, foto, banner e descrição.
  - 🔍 **Pesquisa Avançada:** Busca de animes por nome, gênero e ano.
  - 📝 **Listas Pessoais:** Organize animes com status ("Assistindo", "Completado", "Planejando Assistir", etc.).
  - 👍 **Avaliações e Comentários:** Recomende ou não um anime e deixe sua opinião.

### Para Administradores:

  - ➕ **Gerenciamento de Conteúdo:** Adicionar, editar e excluir animes do catálogo.
  - 🛡️ **Moderação:** Excluir avaliações e comentários de usuários.

-----

## 🛠️ Tecnologias Utilizadas

  - **Backend:** PHP (extensão `mysqli` com *prepared statements*)
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
    git clone https://github.com/gustavotenorio26062005/trabalho-anime-list-php-teste.git
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

    **Alternativamente:**
    
    a. Copie o código do arquivo `animalist_site/config_SQL/animalist_db.sql`
    
    b. Abra a página admin do MySQL no Xampp
    
    c. Cole o código que copiou dentro da aba "SQL"
    
    d. Clique no botão executar

5.  **Configure a Conexão com o Banco**

    As credenciais ficam fora do código versionado. Copie o modelo e ajuste:

    ```sh
    cp animalist_site/includes/config.example.php animalist_site/includes/config.php
    ```

    ```php
    return [
        'host'     => 'localhost',
        'user'     => 'root',
        'password' => '',             // ajuste se o seu root tiver senha
        'database' => 'animalist_db',
    ];
    ```

    O `config.php` está no `.gitignore`. Se ele não existir, o `includes/db_connect.php` usa os valores padrão do `config.example.php` (padrão do XAMPP).

6.  **Acesse o Animalist**
    Abra seu navegador e digite o endereço: **[http://localhost/animalist_site](http://localhost/animalist_site)**.

🎉 E pronto\! Agora você pode navegar e usar o Animalist localmente.

-----

## 🤔 Solução de Problemas Comuns

  - **`Access denied for user 'root'@'localhost'`**: A senha em `includes/config.php` não corresponde à senha do seu MySQL. Verifique o **Passo 5**.
  - **`Unknown database 'animalist_db'`**: O nome do banco em `includes/config.php` está incorreto ou não foi criado corretamente. Verifique os **Passos 4 e 5**.
  - **Página em branco ou erros de PHP**: Verifique se os serviços Apache e MySQL estão realmente rodando no XAMPP.
  - **Erro 404 (Objeto não encontrado)**: Verifique se o nome da pasta em `htdocs` está correto (`animalist_site`) e se a URL no navegador está correta.

-----

## 🗂️ Estrutura do Projeto

```
animalist_site/
├── index.php             # Página inicial com seções de animes por gênero
├── pesquisar.php         # Busca com filtros (nome, gênero, ano) e ordenação
├── anime_detalhes.php    # Detalhes do anime, lista pessoal e avaliações
├── perfil.php            # Perfil do usuário, listas e avaliações
├── editar_perfil.php     # Edição de dados, foto, banner e senha
├── deletar_conta.php     # Endpoint (JSON) de exclusão de conta
├── admin.php             # Painel do administrador (CRUD de animes)
├── cadastro.php / login.php / logout.php
├── includes/             # Conexão com o banco, configuração, header e footer
├── config_SQL/           # Script de criação e dados de exemplo do banco
├── css/  js/  img/       # Estilos, scripts e imagens
```

A documentação completa do trabalho está em `ANIMALIST_DOCUMENTAÇÃO.pdf`.

-----

## 🔐 Segurança

  - Todas as consultas com dados do usuário usam *prepared statements* (`mysqli`), evitando SQL Injection.
  - Saídas em HTML passam por `htmlspecialchars`, reduzindo o risco de XSS.
  - Senhas são armazenadas com `password_hash` e verificadas com `password_verify`.
  - Os usuários de exemplo do `animalist_db.sql` usam a senha `senha123` em texto puro; ela é convertida automaticamente para hash no primeiro login.

-----

## 📚 Aprendizados

  - Modelagem de banco relacional (usuários, animes, gêneros, listas e avaliações) em MySQL.
  - Autenticação com sessões PHP e controle de acesso por tipo de usuário (comum/administrador).
  - Montagem de consultas dinâmicas com filtros e ordenação de forma segura.
  - Uso de transações para operações que alteram várias tabelas (ex.: exclusão de conta).
  - Trabalho em equipe no desenvolvimento de um projeto acadêmico completo.

-----

## 👥 Contribuidores

Este projeto foi idealizado e desenvolvido por:

  - [Gabriel Dias Ribeiro](https://github.com/Tsarco)
  - [Gustavo Barros Tenório](https://github.com/gustavotenorio26062005)
  - [Luiz Henrique Gonçalvez](https://github.com/LuizHenriqueGon)
  - [Maycon Cabral da Silva](https://github.com/Mayconcabral1196)
  - [Renan Valença Bueno Rodrigues](https://github.com/RenanVKoashi)

-----

<p align="center"\>
Divirta-se avaliando seus animes! 🌟 </p>
