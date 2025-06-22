document.addEventListener('DOMContentLoaded', () => {
    // Seleciona os elementos relevantes
    const profileImageContainer = document.querySelector('.profile-pic-container');
    const addContentBtn = document.getElementById('addContentBtn');
    // const deleteProfileBtn = document.getElementById('deleteProfileBtn'); 
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
            viewAllLink.classList.add('hidden-link'); 
            postersInGrid.forEach(poster => poster.classList.remove('initially-hidden'));
            return; 
        }

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

    /* 
    // Comentário original: Exemplo: Ação do botão Deletar Perfil (lixeira)
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
    */

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

    console.log('Página carregada e scripts JS (bloco 1) prontos.');
});

//Verificar avaliações
document.addEventListener('DOMContentLoaded', () => {
    const viewAllReviewsLink = document.getElementById('viewAllReviewsSidebar');
    if (viewAllReviewsLink) {
        const sidebar = viewAllReviewsLink.closest('.sidebar-details'); 
        const hiddenReviews = sidebar.querySelectorAll('.initially-hidden-review'); 

        if (hiddenReviews.length === 0) {
            viewAllReviewsLink.style.display = 'none'; 
        } else {
            viewAllReviewsLink.addEventListener('click', (event) => {
                event.preventDefault(); 
                const isExpanded = sidebar.classList.contains('reviews-expanded');
                if (isExpanded) {
                    hiddenReviews.forEach(review => review.style.display = 'none');
                    sidebar.classList.remove('reviews-expanded');
                    viewAllReviewsLink.textContent = 'Ver Todas Avaliações';
                } else {
                    hiddenReviews.forEach(review => review.style.display = 'block'); 
                    sidebar.classList.add('reviews-expanded');
                    viewAllReviewsLink.textContent = 'Ver Menos Avaliações';
                }
            });
            hiddenReviews.forEach(review => review.style.display = 'none');
        }
    }
});

// Deletar conta logica
document.addEventListener('DOMContentLoaded', function() {
    // Seu código existente para '.view-all'
    document.querySelectorAll('.view-all').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const targetGridId = this.dataset.targetGrid;
            const grid = document.getElementById(targetGridId);
            const isViewAllButton = this.id !== 'viewAllReviewsSidebar';
            const itemsToToggleSelector = isViewAllButton ? '.initially-hidden' : '.initially-hidden-review';
            
            const itemsToToggle = grid ? grid.querySelectorAll(itemsToToggleSelector) : document.querySelectorAll(itemsToToggleSelector);

            if (this.textContent.includes('Ver Tudo') || this.textContent.includes('Ver Todas')) {
                itemsToToggle.forEach(item => {
                    item.style.display = 'block'; 
                    item.classList.remove(isViewAllButton ? 'initially-hidden' : 'initially-hidden-review');
                });
                this.textContent = isViewAllButton ? 'Ver Menos' : 'Ver Menos Avaliações';
            } else {
                const initialShowCount = typeof initial_items_to_show !== 'undefined' ? parseInt('<?php echo $initial_items_to_show; ?>') : 5; 
                const initialReviewShowCount = 5; 
                
                const parentContainer = grid || document.querySelector('.reviews-list-sidebar'); 
                if (parentContainer) { 
                    const allItems = Array.from(parentContainer.children).filter(child => child.matches(isViewAllButton ? '.anime-link-perfil' : '.review-item-sidebar'));
                    const limit = isViewAllButton ? initialShowCount : initialReviewShowCount;

                    allItems.forEach((item, index) => {
                        if (index >= limit) {
                            item.style.display = 'none';
                            item.classList.add(isViewAllButton ? 'initially-hidden' : 'initially-hidden-review');
                        }
                    });
                }
                this.textContent = isViewAllButton ? 'Ver Tudo' : 'Ver Todas Avaliações';
            }
        });
    });

    // --- Lógica para Deletar Conta ---
    const deleteProfileBtn = document.getElementById('deleteProfileBtn'); 
    const deleteAccountModal = document.getElementById('deleteAccountModal'); 
    
    if (deleteProfileBtn && deleteAccountModal) {
        const closeButton = deleteAccountModal.querySelector('.close-button'); 
        const confirmDeleteAccountBtn = document.getElementById('confirmDeleteAccountBtn'); 
        const deleteConfirmPasswordInput = document.getElementById('deleteConfirmPassword'); 
        const deleteAccountErrorDiv = document.getElementById('deleteAccountError'); 

        deleteProfileBtn.addEventListener('click', function() {
            deleteAccountModal.style.display = 'flex'; 
            deleteConfirmPasswordInput.value = ''; 
            deleteAccountErrorDiv.textContent = ''; 
            deleteConfirmPasswordInput.focus(); 
        });

        if (closeButton) {
            closeButton.addEventListener('click', function() {
                deleteAccountModal.style.display = 'none'; 
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target == deleteAccountModal) { 
                deleteAccountModal.style.display = 'none'; 
            }
        });

        if (confirmDeleteAccountBtn) {
            confirmDeleteAccountBtn.addEventListener('click', function() {
                const password = deleteConfirmPasswordInput.value; 

                if (!password) {
                    deleteAccountErrorDiv.textContent = 'Por favor, insira sua senha.'; 
                    deleteConfirmPasswordInput.focus(); 
                    return; 
                }
                deleteAccountErrorDiv.textContent = ''; 

                confirmDeleteAccountBtn.disabled = true;
                confirmDeleteAccountBtn.textContent = 'Excluindo...';

                // ATENÇÃO: O nome do arquivo PHP foi alterado para refletir a verificação de texto puro
                fetch('deletar_conta.php', { 
                    method: 'POST', 
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'password=' + encodeURIComponent(password)
                })
                .then(response => {
                    if (!response.ok) { 
                        throw new Error('A resposta da rede não foi ok: ' + response.statusText);
                    }
                    return response.json(); 
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Conta excluída com sucesso!'); 
                        window.location.href = 'login.php?message=account_deleted_success'; 
                    } else {
                        deleteAccountErrorDiv.textContent = data.message || 'Ocorreu um erro ao tentar excluir a conta.'; 
                        deleteConfirmPasswordInput.focus(); 
                    }
                })
                .catch(error => {
                    console.error('Erro na requisição de exclusão:', error);
                    deleteAccountErrorDiv.textContent = 'Erro de comunicação com o servidor. Tente novamente.'; 
                })
                .finally(() => {
                    confirmDeleteAccountBtn.disabled = false;
                    confirmDeleteAccountBtn.textContent = 'Excluir Minha Conta Permanentemente';
                });
            });
        }
    } else {
        if (!deleteProfileBtn) console.warn("Botão 'deleteProfileBtn' não encontrado no DOM. O modal de exclusão pode não ser acionado.");
        if (!deleteAccountModal) console.warn("Modal 'deleteAccountModal' não encontrado no DOM. A funcionalidade de exclusão de conta está comprometida.");
    }
    console.log('Página carregada e scripts JS (bloco "Deletar conta logica") prontos.');
});