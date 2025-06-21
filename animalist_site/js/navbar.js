document.addEventListener('DOMContentLoaded', function () {
    const navLinksFromDOM = document.querySelectorAll('.barra-navegacao .link-nav');
    const sectionsToSpy = [];
    const navbar = document.querySelector('.barra-navegacao');

    if (!navbar || navLinksFromDOM.length === 0) {
        return;
    }
    const offset = navbar.offsetHeight + 10;
    let currentPagePath = window.location.pathname;

    // Normaliza para caminhos que terminam com '/' (ex: servidor pode servir / como /index.php)
    if (currentPagePath.endsWith('/')) {
        currentPagePath += 'index.php';
    }

    navLinksFromDOM.forEach(linkElement => {
        const linkHref = linkElement.getAttribute('href');
        if (!linkHref) return;

        const linkUrl = new URL(linkHref, document.baseURI);
        let targetId = null;
        let linkPath = linkUrl.pathname;
        if (linkPath.endsWith('/')) {
            linkPath += 'index.php';
        }

        // Verifica se o link é para a página atual
        if (linkPath === currentPagePath) {
            if (linkUrl.hash) {
                targetId = linkUrl.hash.substring(1);
            } else if (linkElement.dataset.scrollTarget) {
                targetId = linkElement.dataset.scrollTarget;
            }
        }

        if (targetId) {
            const sectionElement = document.getElementById(targetId);
            if (sectionElement) {
                sectionsToSpy.push({
                    link: linkElement,
                    section: sectionElement,
                    id: targetId
                });
            }
        }
    });

    if (sectionsToSpy.length === 0) {
        return; // Nenhuma seção para espionar nesta página
    }

    sectionsToSpy.sort((a, b) => a.section.offsetTop - b.section.offsetTop);

    function updateActiveLinkOnScroll() {
        const scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        let newActiveSectionId = null;

        for (let i = sectionsToSpy.length - 1; i >= 0; i--) {
            const item = sectionsToSpy[i];
            const sectionTop = item.section.offsetTop - offset;
            if (scrollPosition >= sectionTop) {
                newActiveSectionId = item.id;
                break;
            }
        }

        // Se rolou acima de todas as seções, ativa a primeira da lista de espionagem
        if (newActiveSectionId === null && sectionsToSpy.length > 0) {
             if (scrollPosition < (sectionsToSpy[0].section.offsetTop - offset + sectionsToSpy[0].section.offsetHeight)) {
                newActiveSectionId = sectionsToSpy[0].id;
             }
        }

        // Remove 'ativo' de todos os links que estão sendo espionados
        sectionsToSpy.forEach(item => {
            item.link.classList.remove('ativo');
        });

        // Adiciona 'ativo' apenas ao link da seção correta
        if (newActiveSectionId !== null) {
            const activeItem = sectionsToSpy.find(item => item.id === newActiveSectionId);
            if (activeItem) {
                activeItem.link.classList.add('ativo');
            }
        }
    }

    window.addEventListener('scroll', updateActiveLinkOnScroll, { passive: true });
    setTimeout(updateActiveLinkOnScroll, 100); // Chamada inicial após um breve delay
});