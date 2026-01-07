<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Caderno CS50</title>
  <style>
    :root {
      --bg-main: #0b0b0d;
      --bg-side: #050507;
      --bg-card: #0f0f13;
      --text-main: #e6e6eb;
      --text-muted: #8b8b94;
      --accent: #3b82f6;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: var(--bg-main);
      color: var(--text-main);
      display: flex;
      height: 100vh;
    }

    aside {
      width: 180px;
      background: var(--bg-side);
      padding: 12px 10px;
    }

    .logo {
      display: flex;
      justify-content: center;
      margin-bottom: 14px;
    }

    .logo img {
      max-width: 120px;
      opacity: 0.9;
    }

    aside h2 {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 1px;
      color: var(--text-muted);
      margin-bottom: 14px;
      text-transform: uppercase;
    }

    aside button {
      width: 100%;
      background: transparent;
      color: var(--text-muted);
      border: none;
      padding: 8px 6px;
      margin-bottom: 6px;
      text-align: left;
      border-radius: 6px;
      font-size: 13px;
    }

    aside button.active {
      background: var(--bg-card);
      color: var(--text-main);
    }

    main {
      flex: 1;
      padding: 14px;
      display: flex;
      flex-direction: column;
    }

    header {
      font-size: 13px;
      margin-bottom: 8px;
      color: var(--text-muted);
    }

    textarea {
      flex: 1;
      resize: none;
      border: none;
      border-radius: 12px;
      padding: 14px;
      font-size: 14px;
      background: var(--bg-card);
      color: var(--text-main);
      outline: none;
      line-height: 1.6;
    }

    textarea::placeholder {
      color: #5a5a66;
    }

    footer {
      display: flex;
      justify-content: flex-end;
      margin-top: 8px;
    }

    footer button {
      border: none;
      padding: 8px 14px;
      border-radius: 999px;
      font-size: 13px;
      background: var(--accent);
      color: #020617;
      font-weight: 600;
    }
  </style>
</head>
<body>

  <aside>
    <div class="logo">
      <img src="safenote.png" alt="SafeNote">
    </div>
    <h2>Matérias</h2>
    <button onclick="loadNote('cs50')" id="btn-cs50">CS50</button>
    <button onclick="loadNote('algoritmos')" id="btn-algoritmos">Algoritmos</button>
    <button onclick="loadNote('estruturas')" id="btn-estruturas">Estruturas de Dados</button>
    <button onclick="loadNote('math')" id="btn-math">Matemática</button>
  </aside>

  <main>
    <header id="title">CS50</header>
    <textarea id="editor" placeholder="Anote aqui suas ideias, códigos e explicações..."></textarea>
    <footer>
      <button onclick="saveNote()">Salvar</button>
    </footer>
  </main>

<script>
  var current = 'cs50';
  var editor = document.getElementById('editor');
  var title = document.getElementById('title');

  function loadNote(key) {
    current = key;
    editor.value = localStorage.getItem('note_' + key) || '';
    title.innerText = key.toUpperCase();

    var buttons = document.querySelectorAll('aside button');
    for (var i = 0; i < buttons.length; i++) {
      buttons[i].classList.remove('active');
    }
    document.getElementById('btn-' + key).classList.add('active');
  }

  function saveNote() {
    localStorage.setItem('note_' + current, editor.value);
    alert('Anotação salva ✔');
  }

  loadNote('cs50');
</script>

</body>
</html>
