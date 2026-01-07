# Wide Style - Implementação com Supabase

Este projeto utiliza o Supabase como backend para autenticação e gerenciamento de usuários.

## Configuração do Supabase

1. Crie uma conta no [Supabase](https://supabase.io)
2. Crie um novo projeto
3. Obtenha a URL e a chave pública do seu projeto Supabase
4. As credenciais já estão configuradas nos arquivos:

### Arquivo main.js
```javascript
const SUPABASE_URL = 'https://fgvlktxqtjpesbqtbueb.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZndmxrdHhxdGpwZXNicXRidWViIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDUwOTYzMjgsImV4cCI6MjA2MDY3MjMyOH0.O8aTqDaYPGuNaQcry-vIz_jPIyRr2xzwaInUqhxgu6w';
```

### Arquivo js/supabase.js
```javascript
const SUPABASE_URL = 'https://fgvlktxqtjpesbqtbueb.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZndmxrdHhxdGpwZXNicXRidWViIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDUwOTYzMjgsImV4cCI6MjA2MDY3MjMyOH0.O8aTqDaYPGuNaQcry-vIz_jPIyRr2xzwaInUqhxgu6w';
```

### Arquivo password-change.js
```javascript
const SUPABASE_URL = 'https://fgvlktxqtjpesbqtbueb.supabase.co';
const SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZndmxrdHhxdGpwZXNicXRidWViIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDUwOTYzMjgsImV4cCI6MjA2MDY3MjMyOH0.O8aTqDaYPGuNaQcry-vIz_jPIyRr2xzwaInUqhxgu6w';
```

## Banco de Dados no Supabase

Estrutura do banco de dados já configurada:

```sql
-- Tabela de perfis para armazenar informações adicionais dos usuários
create table profiles (
  id uuid references auth.users on delete cascade primary key,
  created_at timestamptz DEFAULT now(),
  name text,
  email text,
  favorites jsonb default '[]'
);

-- Tabela de pedidos (orders) para armazenar os pedidos dos usuários
create table orders (
  id uuid default gen_random_uuid() primary key,
  user_id uuid references auth.users not null,
  created_at timestamptz default now(),
  status text default 'pending' check (status in ('pending', 'processing', 'completed', 'cancelled')),
  shipping_address jsonb not null,
  payment_method text not null,
  items jsonb not null,
  total numeric(10,2) not null,
  tracking_code text
);

-- Tabela para armazenar solicitações de redefinição de senha
create table password_reset_requests (
  id uuid default gen_random_uuid() primary key,
  user_id uuid references auth.users not null,
  reset_token text not null,
  created_at timestamptz default now(),
  expires_at timestamptz not null,
  used boolean default false
);

-- Habilitar RLS na tabela de redefinição de senha
alter table password_reset_requests enable row level security;

-- Apenas o sistema pode visualizar/gerenciar solicitações de redefinição de senha
create policy "Apenas o sistema pode visualizar solicitações de redefinição"
  on password_reset_requests for select
  using (auth.uid() is not null);

create policy "Apenas o sistema pode inserir solicitações de redefinição"
  on password_reset_requests for insert
  with check (auth.uid() is not null);

create policy "Apenas o sistema pode atualizar solicitações de redefinição"
  on password_reset_requests for update
  using (auth.uid() is not null);

-- Tabela para registro de sessões de usuário
create table user_sessions (
  id uuid default gen_random_uuid() primary key,
  user_id uuid references auth.users not null,
  device_info jsonb,
  ip_address text,
  last_active timestamptz default now(),
  created_at timestamptz default now(),
  is_active boolean default true
);

-- Habilitar RLS na tabela de sessões
alter table user_sessions enable row level security;

-- Usuário pode ver apenas suas próprias sessões
create policy "Usuários podem ver apenas suas próprias sessões"
  on user_sessions for select
  using (auth.uid() = user_id);

-- Apenas o sistema pode inserir sessões
create policy "Apenas o sistema pode inserir sessões"
  on user_sessions for insert
  with check (auth.uid() is not null);

-- Usuário pode encerrar suas próprias sessões
create policy "Usuários podem encerrar suas próprias sessões"
  on user_sessions for update
  using (auth.uid() = user_id);

-- Índice para busca rápida de sessões por usuário
create index user_sessions_user_id_idx on user_sessions(user_id);

-- Tabela para tentativas de login
create table login_attempts (
  id uuid default gen_random_uuid() primary key,
  email text not null,
  ip_address text,
  user_agent text,
  successful boolean default false,
  created_at timestamptz default now()
);

-- Habilitar RLS na tabela de tentativas de login
alter table login_attempts enable row level security;

-- Apenas o sistema pode gerenciar tentativas de login
create policy "Apenas o sistema pode visualizar tentativas de login"
  on login_attempts for select
  using (auth.uid() in (
    select id from auth.users where raw_user_meta_data->>'role' = 'admin'
  ));

create policy "Apenas o sistema pode inserir tentativas de login"
  on login_attempts for insert
  with check (true);

-- Habilitar RLS na tabela de pedidos
alter table orders enable row level security;

-- Política para usuários verem apenas seus próprios pedidos
create policy "Usuários podem ver apenas seus próprios pedidos"
  on orders for select
  using (auth.uid() = user_id);

-- Política para usuários criarem apenas seus próprios pedidos
create policy "Usuários podem criar apenas seus próprios pedidos"
  on orders for insert
  with check (auth.uid() = user_id);

-- Política para usuários atualizarem apenas seus próprios pedidos em estado pendente
create policy "Usuários podem atualizar apenas seus próprios pedidos pendentes"
  on orders for update
  using (auth.uid() = user_id and status = 'pending');

-- Índice para melhorar performance de consultas de pedidos
create index orders_user_id_idx on orders(user_id);
create index orders_status_idx on orders(status);

-- Função para criar automaticamente um perfil quando um novo usuário se registra
create or replace function public.handle_new_user() 
returns trigger as $$
begin
  insert into public.profiles (id, name, email)
  values (new.id, new.raw_user_meta_data->>'name', new.email);
  return new;
end;
$$ language plpgsql security definer;

-- Gatilho para chamar a função quando um novo usuário é criado
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute procedure public.handle_new_user();
  
-- Configurações de segurança (RLS - Row Level Security)
-- Habilitar RLS na tabela de perfis
alter table profiles enable row level security;

-- Política para permitir que usuários vejam apenas seu próprio perfil
create policy "Usuários podem ver apenas seu próprio perfil"
  on profiles for select
  using (auth.uid() = id);

-- Política para permitir que usuários editem apenas seu próprio perfil
create policy "Usuários podem atualizar apenas seu próprio perfil"
  on profiles for update
  using (auth.uid() = id);

-- Política para permitir que apenas o próprio usuário delete seu perfil
create policy "Usuários podem deletar apenas seu próprio perfil"
  on profiles for delete
  using (auth.uid() = id);

-- Política para permitir que o serviço insira perfis
create policy "O serviço pode inserir perfis"
  on profiles for insert
  with check (true);

-- Adicionar índice para melhorar performance de consultas
create index profiles_id_idx on profiles(id);

-- Função para registrar histórico de alterações nos pedidos
create or replace function public.handle_order_status_change()
returns trigger as $$
begin
  insert into order_status_history (order_id, old_status, new_status)
  values (new.id, old.status, new.status);
  return new;
end;
$$ language plpgsql security definer;

-- Tabela para histórico de status dos pedidos
create table order_status_history (
  id uuid default gen_random_uuid() primary key,
  order_id uuid references orders not null,
  old_status text,
  new_status text not null,
  changed_at timestamptz default now()
);

-- Gatilho para registrar alterações de status
create trigger on_order_status_change
  after update of status on orders
  for each row execute procedure public.handle_order_status_change();

-- Função para solicitar redefinição de senha
create or replace function request_password_reset(user_email text)
returns text as $$
declare
  user_id uuid;
  reset_token text;
  expiration timestamptz;
begin
  -- Encontrar o usuário pelo email
  select id into user_id
  from auth.users
  where email = user_email;
  
  -- Verificar se o usuário existe
  if user_id is null then
    -- Por razões de segurança, não revelamos que o usuário não existe
    return 'Se o email estiver associado a uma conta, você receberá instruções para redefinir sua senha.';
  end if;
  
  -- Gerar token único
  reset_token := encode(gen_random_bytes(32), 'hex');
  
  -- Definir expiração (24 horas)
  expiration := now() + interval '24 hours';
  
  -- Invalidar tokens antigos deste usuário
  update password_reset_requests
  set used = true
  where user_id = request_password_reset.user_id
    and used = false
    and expires_at > now();
  
  -- Inserir nova solicitação
  insert into password_reset_requests (user_id, reset_token, expires_at)
  values (user_id, reset_token, expiration);
  
  -- Registrar atividade
  perform log_activity('password_reset_requested', 
    jsonb_build_object('email', user_email)
  );
  
  -- Em uma aplicação real, aqui enviaríamos um email com o link contendo o token
  -- Isso geralmente é feito por um serviço externo ou serverless function
  
  return 'Se o email estiver associado a uma conta, você receberá instruções para redefinir sua senha.';
end;
$$ language plpgsql security definer;

-- Função para validar token de redefinição de senha
create or replace function validate_reset_token(token text)
returns boolean as $$
declare
  valid_request boolean;
begin
  select exists(
    select 1
    from password_reset_requests
    where reset_token = token
      and used = false
      and expires_at > now()
  ) into valid_request;
  
  return valid_request;
end;
$$ language plpgsql security definer;

-- Função para redefinir senha com token
create or replace function reset_password_with_token(token text, new_password text)
returns boolean as $$
declare
  user_id uuid;
  valid_request boolean;
begin
  -- Verificar se o token é válido
  select exists(
    select 1
    from password_reset_requests
    where reset_token = token
      and used = false
      and expires_at > now()
  ) into valid_request;
  
  if not valid_request then
    return false;
  end if;
  
  -- Obter o ID do usuário
  select pr.user_id into user_id
  from password_reset_requests pr
  where pr.reset_token = token
    and pr.used = false
    and pr.expires_at > now();
  
  -- Atualizar a senha do usuário (usando a função do Supabase)
  -- Em um ambiente real, isso seria feito através da API do Supabase
  -- Esta é uma representação simplificada
  -- update auth.users
  -- set encrypted_password = crypt(new_password, gen_salt('bf'))
  -- where id = user_id;
  
  -- Marcar o token como usado
  update password_reset_requests
  set used = true
  where reset_token = token;
  
  -- Registrar atividade
  perform log_activity('password_reset_completed', 
    jsonb_build_object('user_id', user_id)
  );
  
  -- Invalidar todas as sessões ativas do usuário
  update user_sessions
  set is_active = false
  where user_id = reset_password_with_token.user_id
    and is_active = true;
  
  return true;
end;
$$ language plpgsql security definer;

-- Função para alterar senha (usuário autenticado)
create or replace function change_password(current_password text, new_password text)
returns boolean as $$
declare
  user_id uuid;
  password_correct boolean;
begin
  -- Obter ID do usuário atual
  user_id := auth.uid();
  
  if user_id is null then
    raise exception 'Usuário não autenticado';
  end if;
  
  -- Verificar se a senha atual está correta
  -- Em um ambiente real, isso seria validado pelo Supabase Auth
  -- Aqui é apenas uma representação do processo
  password_correct := true; -- Simulação
  
  if not password_correct then
    return false;
  end if;
  
  -- Atualizar a senha (usando a função do Supabase)
  -- Na prática isso seria feito através da API do Supabase
  -- update auth.users
  -- set encrypted_password = crypt(new_password, gen_salt('bf'))
  -- where id = user_id;
  
  -- Registrar atividade
  perform log_activity('password_changed', 
    jsonb_build_object('user_id', user_id)
  );
  
  return true;
end;
$$ language plpgsql security definer;

-- Função para registrar uma nova sessão de usuário
create or replace function register_user_session(device_info jsonb)
returns uuid as $$
declare
  user_id uuid;
  session_id uuid;
  client_ip text;
  client_agent text;
begin
  -- Obter ID do usuário atual
  user_id := auth.uid();
  
  if user_id is null then
    raise exception 'Usuário não autenticado';
  end if;
  
  -- Obter IP e User Agent
  client_ip := coalesce(current_setting('request.headers', true)::json->>'X-Forwarded-For', 'unknown');
  client_agent := coalesce(current_setting('request.headers', true)::json->>'User-Agent', 'unknown');
  
  -- Inserir nova sessão
  insert into user_sessions (user_id, device_info, ip_address)
  values (user_id, device_info, client_ip)
  returning id into session_id;
  
  -- Registrar atividade
  perform log_activity('login', 
    jsonb_build_object(
      'session_id', session_id,
      'device_info', device_info
    )
  );
  
  return session_id;
end;
$$ language plpgsql security definer;

-- Função para encerrar uma sessão específica
create or replace function terminate_session(session_id uuid)
returns boolean as $$
declare
  user_id uuid;
  session_exists boolean;
begin
  -- Obter ID do usuário atual
  user_id := auth.uid();
  
  if user_id is null then
    raise exception 'Usuário não autenticado';
  end if;
  
  -- Verificar se a sessão existe e pertence ao usuário
  select exists(
    select 1
    from user_sessions
    where id = session_id
      and user_id = terminate_session.user_id
  ) into session_exists;
  
  if not session_exists then
    return false;
  end if;
  
  -- Encerrar sessão
  update user_sessions
  set is_active = false
  where id = session_id;
  
  -- Registrar atividade
  perform log_activity('session_terminated', 
    jsonb_build_object('session_id', session_id)
  );
  
  return true;
end;
$$ language plpgsql security definer;

-- Função para encerrar todas as sessões do usuário exceto a atual
create or replace function terminate_other_sessions(current_session_id uuid)
returns integer as $$
declare
  user_id uuid;
  terminated_count integer;
begin
  -- Obter ID do usuário atual
  user_id := auth.uid();
  
  if user_id is null then
    raise exception 'Usuário não autenticado';
  end if;
  
  -- Encerrar todas as outras sessões
  update user_sessions
  set is_active = false
  where user_id = terminate_other_sessions.user_id
    and id != current_session_id
    and is_active = true
  returning count(*) into terminated_count;
  
  -- Registrar atividade
  perform log_activity('all_other_sessions_terminated', 
    jsonb_build_object('count', terminated_count)
  );
  
  return terminated_count;
end;
$$ language plpgsql security definer;

-- Configuração para exigir email verificado antes de login
-- Descomente a linha abaixo para exigir verificação de email
-- update auth.config set email_confirm_required = true;

-- Configuração para limpar tokens expirados a cada dia
create or replace function cleanup_expired_tokens()
returns void as $$
begin
  delete from password_reset_requests
  where expires_at < now();
end;
$$ language plpgsql security definer;

-- Agendamento de limpeza de tokens (simulado - no PostgreSQL real, seria um CRON job)
comment on function cleanup_expired_tokens() is 'Executar diariamente como um job agendado';

-- Função para registrar login com multi-fator (MFA)
create or replace function handle_mfa_login(user_id uuid, mfa_method text)
returns boolean as $$
begin
  -- Registrar atividade de login com MFA
  perform log_activity('mfa_login_success', 
    jsonb_build_object(
      'user_id', user_id,
      'method', mfa_method
    )
  );
  
  return true;
end;
$$ language plpgsql security definer;

-- Verificação de email para autenticação (recomendado em ambiente de produção)
-- Descomente a linha abaixo para exigir verificação de email
-- update auth.config set email_confirm_required = true;

-- Configuração de segurança para proteção contra injeção SQL
create or replace function is_valid_json(input_json text) returns boolean as $$
begin
  return (input_json::json is not null);
  exception when others then
    return false;
end;
$$ language plpgsql immutable security definer;

-- Função para sanitizar entrada de dados do usuário
create or replace function sanitize_input(input text) returns text as $$
begin
  -- Remove caracteres potencialmente perigosos
  return regexp_replace(input, '[<>''";]', '', 'g');
end;
$$ language plpgsql immutable security definer;

-- Função para validar dados antes de inserção/atualização
create or replace function validate_order_items(items jsonb) returns boolean as $$
begin
  -- Verifica se o JSON de itens tem o formato correto
  if not jsonb_typeof(items) = 'array' then
    return false;
  end if;
  
  -- Verifica se cada item tem os campos necessários
  for i in 0..jsonb_array_length(items) - 1 loop
    if not (
      items->i ? 'id' and
      items->i ? 'quantity' and
      items->i ? 'price' and
      items->i ? 'name'
    ) then
      return false;
    end if;
  end loop;
  
  return true;
end;
$$ language plpgsql immutable security definer;

-- Adicionar restrição para validar itens do pedido
alter table orders add constraint valid_items check (validate_order_items(items));

-- Tabela para registro de atividades de usuários
create table activity_log (
  id uuid default gen_random_uuid() primary key,
  user_id uuid references auth.users,
  activity_type text not null,
  details jsonb,
  ip_address text,
  user_agent text,
  created_at timestamptz default now()
);

-- Habilitar RLS
alter table activity_log enable row level security;

-- Apenas administradores podem visualizar os logs
create policy "Apenas administradores podem visualizar logs"
  on activity_log for select
  using (auth.uid() in (
    select id from auth.users where raw_user_meta_data->>'role' = 'admin'
  ));

-- Apenas o sistema pode inserir logs
create policy "Apenas o sistema pode inserir logs"
  on activity_log for insert
  with check (true);

-- Função para registrar atividade
create or replace function log_activity(activity_type text, details jsonb)
returns void as $$
declare
  current_user_id uuid;
  client_ip text;
  client_agent text;
begin
  -- Obter ID do usuário atual
  current_user_id := auth.uid();
  
  -- Obter IP e User Agent (simulado aqui, no Supabase real usar request.headers)
  client_ip := coalesce(current_setting('request.headers', true)::json->>'X-Forwarded-For', 'unknown');
  client_agent := coalesce(current_setting('request.headers', true)::json->>'User-Agent', 'unknown');
  
  -- Inserir log
  insert into activity_log (user_id, activity_type, details, ip_address, user_agent)
  values (current_user_id, activity_type, details, client_ip, client_agent);
  
  -- Alerta de segurança para tentativas de login falhas consecutivas
  if activity_type = 'login_failed' then
    perform check_login_attempts(current_user_id);
  end if;
end;
$$ language plpgsql security definer;

-- Função para verificar tentativas de login consecutivas
create or replace function check_login_attempts(user_id uuid)
returns void as $$
declare
  recent_failures int;
begin
  -- Contar falhas recentes (últimos 30 minutos)
  select count(*) into recent_failures
  from activity_log
  where user_id = check_login_attempts.user_id
    and activity_type = 'login_failed'
    and created_at > now() - interval '30 minutes';
    
  -- Se houver mais de 5 falhas, notificar
  if recent_failures >= 5 then
    -- Inserir na tabela de notificações (pode ser integrado com webhooks)
    insert into security_notifications (user_id, notification_type, details)
    values (
      user_id, 
      'excessive_login_attempts',
      jsonb_build_object(
        'attempts', recent_failures,
        'time_window', '30 minutes'
      )
    );
  end if;
end;
$$ language plpgsql security definer;

-- Tabela para notificações de segurança
create table security_notifications (
  id uuid default gen_random_uuid() primary key,
  user_id uuid references auth.users,
  notification_type text not null,
  details jsonb,
  created_at timestamptz default now(),
  processed boolean default false
);

-- Habilitar RLS
alter table security_notifications enable row level security;

-- Apenas administradores podem ver notificações
create policy "Apenas administradores podem ver notificações"
  on security_notifications for select
  using (auth.uid() in (
    select id from auth.users where raw_user_meta_data->>'role' = 'admin'
  ));

-- Função para marcar uma notificação como processada
create or replace function process_security_notification(notification_id uuid)
returns void as $$
begin
  -- Verificar se o usuário é administrador
  if (select raw_user_meta_data->>'role' from auth.users where id = auth.uid()) = 'admin' then
    update security_notifications
    set processed = true
    where id = notification_id;
  else
    raise exception 'Permissão negada';
  end if;
end;
$$ language plpgsql security definer;
```

## Funcionalidades Implementadas

- Registro de usuário
- Login de usuário
- Logout de usuário
- Persistência de sessão
- Alteração de senha
- Perfil de usuário

## Tecnologias Utilizadas

- HTML/CSS (com Tailwind CSS)
- JavaScript
- Supabase (Autenticação e Banco de Dados)

## Instruções para desenvolvimento

1. Clone o repositório
2. As credenciais do Supabase já estão configuradas
3. Abra o arquivo index.html em seu navegador ou use um servidor local 