document.addEventListener('DOMContentLoaded', () => {
    // Seleciona os elementos relevantes
    const profileImageContainer = document.querySelector('.profile-pic-container');
    const addContentBtn = document.getElementById('addContentBtn');
    const deleteProfileBtn = document.getElementById('deleteProfileBtn');
    const animePosters = document.querySelectorAll('.anime-poster');
    const sidebarDetailsContainer = document.querySelector('.sidebar-details');
    const userNameInput = document.getElementById('userName');
    const userDescriptionTextarea = document.getElementById('userDescription');

    // Configuração para "Ver Tudo" / "Ver Menos"
    const initialItemsToShow = 5; // Deve ser o mesmo valor que no PHP

    document.querySelectorAll('.view-all').forEach(viewAllLink => {
        const targetGridId = viewAllLink.dataset.targetGrid;
        const grid = document.getElementById(targetGridId);

        if (!grid) return;

        const postersInGrid = Array.from(grid.querySelectorAll('.anime-poster'));
        
        // Verifica se há itens suficientes para justificar o botão "Ver Tudo"
        if (postersInGrid.length <= initialItemsToShow) {
            viewAllLink.classList.add('hidden-link'); // Esconde o link "Ver Tudo"
            // Garante que todos os itens estejam visíveis se forem menos que o limite
            postersInGrid.forEach(poster => poster.classList.remove('initially-hidden'));
            return; // Não adiciona o event listener se não for necessário
        }

        // Inicialmente, garante que apenas o número correto de itens esteja visível
        // O PHP já adiciona 'initially-hidden', mas podemos reforçar ou ajustar aqui se necessário.
        // postersInGrid.forEach((poster, index) => {
        //     if (index >= initialItemsToShow) {
        //         poster.classList.add('initially-hidden');
        //     } else {
        //         poster.classList.remove('initially-hidden');
        //     }
        // });


        viewAllLink.addEventListener('click', (event) => {
            event.preventDefault();
            const isExpanded = grid.classList.contains('expanded');

            if (isExpanded) {
                // Recolher - esconder itens extras
                postersInGrid.forEach((poster, index) => {
                    if (index >= initialItemsToShow) {
                        poster.classList.add('initially-hidden');
                    }
                });
                grid.classList.remove('expanded');
                viewAllLink.textContent = 'Ver Tudo';
            } else {
                // Expandir - mostrar todos os itens
                postersInGrid.forEach(poster => {
                    poster.classList.remove('initially-hidden');
                });
                grid.classList.add('expanded');
                viewAllLink.textContent = 'Ver Menos';
            }
        });
    });


    // Exemplo: Ação ao clicar no container da imagem de perfil
    if (profileImageContainer) {
        profileImageContainer.addEventListener('click', () => {
            console.log('Clicou para mudar a foto de perfil.');
            // Adicionar lógica para upload de imagem aqui
        });
    }

    // Exemplo: Ação do botão Adicionar (+)
    if (addContentBtn) {
        addContentBtn.addEventListener('click', () => {
            console.log('Botão Adicionar (+) clicado.');
            alert('Funcionalidade "Adicionar Novo Item" a ser implementada!');
        });
    }

    // Exemplo: Ação do botão Deletar Perfil (lixeira)
    if (deleteProfileBtn) {
        deleteProfileBtn.addEventListener('click', () => {
            console.log('Botão Deletar Perfil clicado.');
            if (confirm('Tem certeza que deseja deletar o perfil? Esta ação não pode ser desfeita.')) {
                alert('Perfil "deletado" (simulação).');
                if(userNameInput) userNameInput.value = 'Usuário Deletado';
                if(userDescriptionTextarea) userDescriptionTextarea.value = '';
            }
        });
    }

    // Exemplo: Ação ao clicar em um pôster de anime
    animePosters.forEach(poster => {
        poster.addEventListener('click', () => {
            const animeName = poster.dataset.animeName || 'Anime Desconhecido';
            console.log(`Clicou no pôster do anime: ${animeName}`);

            sidebarDetailsContainer.innerHTML = `
                <div class="anime-detail-card">
                    <h3>${animeName} (Detalhes)</h3>
                    <p>Esta é uma descrição detalhada do anime ${animeName}. Aqui viriam informações como sinopse, episódios, estúdio, etc.</p>
                </div>
                <div class="anime-detail-card">
                    <h3>Opinião sobre ${animeName}</h3>
                    <p>Minha avaliação pessoal ou notas sobre este anime.</p>
                </div>
            `;
            sidebarDetailsContainer.scrollTop = 0;
        });
    });

    // Exemplo: Salvar alterações no nome e descrição
    if(userNameInput) {
        userNameInput.addEventListener('blur', () => {
            console.log(`Nome do usuário alterado para: ${userNameInput.value}`);
        });
    }

    if(userDescriptionTextarea) {
        userDescriptionTextarea.addEventListener('blur', () => {
            console.log(`Descrição do usuário alterada para: ${userDescriptionTextarea.value}`);
        });
    }

    console.log('Página carregada e scripts JS prontos.');
});

//Verificar avaliações

document.addEventListener('DOMContentLoaded', () => {
    const viewAllReviewsLink = document.getElementById('viewAllReviewsSidebar');
    if (viewAllReviewsLink) {
        const sidebar = viewAllReviewsLink.closest('.sidebar-details');
        const hiddenReviews = sidebar.querySelectorAll('.initially-hidden-review');

        if (hiddenReviews.length === 0) {
            viewAllReviewsLink.style.display = 'none'; // Esconde o link se não há o que expandir
        } else {
            viewAllReviewsLink.addEventListener('click', (event) => {
                event.preventDefault();
                const isExpanded = sidebar.classList.contains('reviews-expanded');
                if (isExpanded) {
                    hiddenReviews.forEach(review => review.style.display = 'none');
                    sidebar.classList.remove('reviews-expanded');
                    viewAllReviewsLink.textContent = 'Ver Todas Avaliações';
                } else {
                    hiddenReviews.forEach(review => review.style.display = 'block'); // Ou 'flex' se for o caso
                    sidebar.classList.add('reviews-expanded');
                    viewAllReviewsLink.textContent = 'Ver Menos Avaliações';
                }
            });
            // Inicialmente esconde as avaliações extras
            hiddenReviews.forEach(review => review.style.display = 'none');
        }
    }
});