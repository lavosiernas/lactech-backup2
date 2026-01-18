-- SafeCode IDE - Criar Usuário de Teste
-- Execute este script no MySQL para criar um usuário de teste

USE safecode;

-- Criar usuário de teste
-- Email: teste@safecode.test
-- Senha: teste123
-- Nome: Usuário Teste

INSERT INTO users (email, password_hash, name, provider, is_active)
VALUES (
    'teste@safecode.test',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- senha: "teste123"
    'Usuário Teste',
    'email',
    TRUE
)
ON DUPLICATE KEY UPDATE 
    email = email; -- Não atualizar se já existir

-- Verificar se o usuário foi criado
SELECT id, email, name, created_at, last_login 
FROM users 
WHERE email = 'teste@safecode.test';

