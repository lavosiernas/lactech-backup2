<!DOCTYPE html>
<html>
<head>
  <title>Teste Login</title>
  <script src="https://unpkg.com/@supabase/supabase-js@2"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('loginForm');
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        
        const supabaseClient = supabase.createClient(
          'https://fgvlktxqtjpesbqtbueb.supabase.co',
          'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZndmxrdHhxdGpwZXNicXRidWViIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDUwOTYzMjgsImV4cCI6MjA2MDY3MjMyOH0.O8aTqDaYPGuNaQcry-vIz_jPIyRr2xzwaInUqhxgu6w'
        );
        
        const result = await supabaseClient.auth.signInWithPassword({
          email,
          password
        });
        
        console.log('Resultado:', result);
        document.getElementById('result').textContent = 
          JSON.stringify(result, null, 2);
      });
    });
  </script>
</head>
<body>
  <form id="loginForm">
    <input id="email" type="email" placeholder="Email">
    <input id="password" type="password" placeholder="Senha">
    <button type="submit">Login</button>
  </form>
  <pre id="result"></pre>
</body>
</html>