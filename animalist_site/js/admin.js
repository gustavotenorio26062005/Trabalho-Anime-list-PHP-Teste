function addGenre() {
  const input = document.getElementById('genreInput');
  const genre = input.value.trim();

  if (genre === '') return;

  const genreList = document.getElementById('genreList');

  const genreDiv = document.createElement('div');
  genreDiv.className = 'genre-item';
  genreDiv.innerHTML = `
    ${genre}
    <button onclick="this.parentElement.remove()" type="button">🗑️</button>
  `;

  genreList.appendChild(genreDiv);
  input.value = '';
}

function enviarDados() {
  const lista = document.querySelectorAll('.genre-item');
  const generos = [];

  lista.forEach(item => {
    generos.push(item.firstChild.textContent.trim());
  });

  if (generos.length === 0) {
    alert("Por favor, adicione pelo menos um gênero.");
    return false; // cancela o envio do formulário
  }

  document.getElementById('generosInput').value = generos.join(',');
  return true;
}