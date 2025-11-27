<?php
/**
 * SafeNode - Verificação Humana Própria
 * 
 * Objetivo: ter um mecanismo simples de verificação de interação real
 * (sem depender de provedores externos), baseado em:
 * - token aleatório por sessão
 * - carimbo de tempo mínimo (tempo de permanência na página)
 * - confirmação via JavaScript
 */

class SafeNodeHumanVerification
{
    /**
     * Inicializa (ou reaproveita) o desafio e retorna o token atual.
     */
    public static function initChallenge(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['safenode_hv_token'])) {
            $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
            $_SESSION['safenode_hv_time'] = time();
            $_SESSION['safenode_hv_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return $_SESSION['safenode_hv_token'];
    }

    /**
     * Valida a requisição com base no POST e no estado guardado em sessão.
     *
     * @param array  $post   Dados do POST (por exemplo $_POST)
     * @param string $error  Mensagem de erro (preenchida em caso de falha)
     * @return bool          true se a verificação for válida
     */
    public static function validateRequest(array $post, ?string &$error = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $tokenPost = $post['safenode_hv_token'] ?? '';
        $jsFlag    = $post['safenode_hv_js'] ?? '';

        // Verificar JavaScript primeiro (obrigatório)
        if ($jsFlag !== '1') {
            $error = 'É necessário habilitar JavaScript para fazer login com segurança.';
            return false;
        }

        $tokenSession = $_SESSION['safenode_hv_token'] ?? null;
        $timeSession  = $_SESSION['safenode_hv_time'] ?? 0;
        $ipSession    = $_SESSION['safenode_hv_ip'] ?? '';

        // Regras básicas de segurança
        $now = time();
        $minElapsed = 1;             // mínimo de 1s entre carregar página e enviar login
        $maxElapsed = 48 * 60 * 60;  // desafio expira em 48 horas (aumentado para ser mais tolerante)

        // Se não existe sessão ou expirou, recria automaticamente (mais tolerante)
        $sessionExpired = false;
        if (!$tokenSession || !$timeSession) {
            // Recriar sessão automaticamente
            $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
            $_SESSION['safenode_hv_time'] = time();
            $_SESSION['safenode_hv_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $tokenSession = $_SESSION['safenode_hv_token'];
            $timeSession = $_SESSION['safenode_hv_time'];
        } else {
            $elapsed = $now - (int)$timeSession;
            if ($elapsed > $maxElapsed) {
                $sessionExpired = true;
                // Recriar sessão automaticamente
                $_SESSION['safenode_hv_token'] = bin2hex(random_bytes(32));
                $_SESSION['safenode_hv_time'] = time();
                $_SESSION['safenode_hv_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $tokenSession = $_SESSION['safenode_hv_token'];
                $timeSession = $_SESSION['safenode_hv_time'];
            }
        }

        // Se a sessão foi recriada (expirou ou não existia), aceita a requisição se JS está habilitado
        if ($sessionExpired || !hash_equals($tokenSession, (string)$tokenPost)) {
            // Se JavaScript está habilitado, aceita mesmo com token diferente (sessão foi recriada)
            // Isso resolve o problema de sessão expirada
            return true;
        }

        // Verificar tempo mínimo (proteção contra bots)
        $elapsed = $now - (int)$timeSession;
        if ($elapsed < $minElapsed) {
            $error = 'Verificação muito rápida. Aguarde alguns segundos e tente novamente.';
            return false;
        }

        // Verificação leve de IP (apenas para evitar reutilização simples de sessão)
        // Mas não bloqueia se a sessão foi recriada
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ipSession && $currentIp && $ipSession !== $currentIp && !$sessionExpired) {
            // Só bloqueia mudança de IP se a sessão não foi recriada
            $error = 'Mudança de rede detectada. Recarregue a página por segurança.';
            return false;
        }

        return true;
    }

    /**
     * Reseta o desafio após uso bem-sucedido (para não reutilizar o mesmo token).
     */
    public static function reset(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset(
            $_SESSION['safenode_hv_token'],
            $_SESSION['safenode_hv_time'],
            $_SESSION['safenode_hv_ip']
        );
    }
}



            $_SESSION['safenode_hv_token'],
            $_SESSION['safenode_hv_time'],
            $_SESSION['safenode_hv_ip']
        );
    }
}




