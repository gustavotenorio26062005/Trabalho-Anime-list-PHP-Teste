<nav class="barra-navegacao">
    <div class="container-navegacao">
        <a href="/" class="logo-link-navegacao">
            <!-- O logo pode ser uma imagem ou SVG. Aqui, uma simulação com texto e ícone -->
            <div class="logo-navegacao">
                <i class="fas fa-eye logo-olho-nav"></i>
                <i class="fas fa-check-circle logo-check-nav"></i>
                <span class="texto-logo-nav">
                    <span class="texto-logo-anima">ANIMA</span>
                    <span class="texto-logo-list">LIST</span>
                </span>
            </div>
        </a>
        <ul class="lista-links-navegacao">
            <li><a href="#" class="link-nav">Home</a></li>
            <li><a href="#pesquisar" class="link-nav">Pesquisar</a></li>
            <li><a href="#" class="link-nav">Perfil</a></li>
            <li><a href="#" class="link-nav">Sua Lista</a></li>
        </ul>
        <div class="acoes-usuario-navegacao">
            <span class="separador-vertical-nav">|</span>
            <a href="#" class="link-nav link-entrar-nav">Entrar</a>
            <a href="#" class="botao-cadastrar-nav">Se Cadastrar</a>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const smartLinks = document.querySelectorAll('a.smart-link'); // Or target all 'nav a'

    smartLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const href = this.getAttribute('href');

            // Check if it's an on-page anchor link (starts with #)
            if (href.startsWith('#')) {
                event.preventDefault(); // Prevent default anchor jump

                const targetId = href.substring(1); // Get ID without '#'
                const targetElement = document.getElementById(targetId);

                if (targetElement) {
                    targetElement.scrollIntoView({
                        behavior: 'smooth'
                    });

                    // Optional: Update URL hash without causing another jump
                    // This helps with back button behavior and bookmarking
                    if (history.pushState) {
                        history.pushState(null, null, href);
                    } else {
                        // Fallback for older browsers (will cause a jump, but better than nothing)
                        window.location.hash = href;
                    }
                } else {
                    console.warn(`Element with ID '${targetId}' not found on this page.`);
                    // If the on-page anchor doesn't exist, you could optionally
                    // redirect to a default page or a specific part of another page.
                    // For example:
                    // window.location.href = `default-page.html${href}`;
                    // But for this example, we'll just log a warning.
                }
            }
            // For links not starting with '#', the default browser action will occur,
            // which is to navigate to the new page.
            // The `scroll-behavior: smooth;` in CSS (if present on the target page)
            // will handle the smooth scroll to an anchor on the new page upon loading.
        });
    });
});
</script>