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

        $tokenSession = $_SESSION['safenode_hv_token'] ?? null;
        $timeSession  = $_SESSION['safenode_hv_time'] ?? 0;
        $ipSession    = $_SESSION['safenode_hv_ip'] ?? '';

        // Regras básicas de segurança
        $now = time();
        $minElapsed = 1;             // mínimo de 1s entre carregar página e enviar login
        $maxElapsed = 24 * 60 * 60;  // desafio expira em 24 horas (muito tolerante)

        if (!$tokenSession || !$timeSession) {
            // Tenta inicializar automaticamente se não existir
            self::initChallenge();
            $tokenSession = $_SESSION['safenode_hv_token'] ?? null;
            $timeSession  = $_SESSION['safenode_hv_time'] ?? 0;
            
            // Se ainda não existir, retorna erro
            if (!$tokenSession || !$timeSession) {
                $error = 'Falha na verificação de segurança. Recarregue a página.';
                return false;
            }
        }

        if (!hash_equals($tokenSession, (string)$tokenPost)) {
            $error = 'Verificação de segurança inválida. Recarregue a página.';
            return false;
        }

        if ($jsFlag !== '1') {
            // Se o JavaScript não marcou o campo, provavelmente é um bot ou requisição forjada
            $error = 'É necessário habilitar JavaScript para fazer login com segurança.';
            return false;
        }

        $elapsed = $now - (int)$timeSession;
        if ($elapsed < $minElapsed) {
            $error = 'Verificação muito rápida. Aguarde alguns segundos e tente novamente.';
            return false;
        }

        if ($elapsed > $maxElapsed) {
            $error = 'Sessão de verificação expirada. Recarregue a página.';
            return false;
        }

        // Verificação leve de IP (apenas para evitar reutilização simples de sessão)
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($ipSession && $currentIp && $ipSession !== $currentIp) {
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


