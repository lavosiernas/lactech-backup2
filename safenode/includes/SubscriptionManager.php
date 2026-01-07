<?php
/**
 * SafeNode - Subscription Manager
 * Gerencia subscriptions, limites de eventos e cobrança
 */

class SubscriptionManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Obtém subscription do usuário
     */
    public function getUserSubscription($userId) {
        if (!$this->db || !$userId) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM safenode_subscriptions 
                WHERE user_id = ? 
                ORDER BY id DESC 
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Se não existe, criar free trial
            if (!$subscription) {
                return $this->createFreeTrial($userId);
            }
            
            // Verificar se precisa resetar ciclo de cobrança
            $this->checkBillingCycle($subscription);
            
            return $subscription;
        } catch (PDOException $e) {
            error_log("SafeNode Subscription Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cria free trial de 14 dias
     */
    public function createFreeTrial($userId) {
        if (!$this->db || !$userId) {
            return null;
        }
        
        try {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+14 days'));
            
            $stmt = $this->db->prepare("
                INSERT INTO safenode_subscriptions 
                (user_id, plan_type, events_limit, events_used, billing_cycle_start, billing_cycle_end, status)
                VALUES (?, 'free_trial', 10000, 0, ?, ?, 'active')
            ");
            $stmt->execute([$userId, $startDate, $endDate]);
            
            return $this->getUserSubscription($userId);
        } catch (PDOException $e) {
            error_log("SafeNode Create Trial Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verifica e reseta ciclo de cobrança se necessário
     */
    private function checkBillingCycle($subscription) {
        if (!$subscription || !$this->db) {
            return;
        }
        
        $today = date('Y-m-d');
        $cycleEnd = $subscription['billing_cycle_end'];
        
        // Se passou do fim do ciclo, resetar
        if ($today > $cycleEnd) {
            // Se é free trial e expirou, marcar como expirado
            if ($subscription['plan_type'] === 'free_trial' && $subscription['status'] === 'active') {
                $this->db->prepare("
                    UPDATE safenode_subscriptions 
                    SET status = 'trial_expired', events_used = 0
                    WHERE id = ?
                ")->execute([$subscription['id']]);
            } else {
                // Se é pago, resetar contador e novo ciclo
                $newStart = $today;
                $newEnd = date('Y-m-d', strtotime('+1 month'));
                
                $this->db->prepare("
                    UPDATE safenode_subscriptions 
                    SET events_used = 0, 
                        billing_cycle_start = ?, 
                        billing_cycle_end = ?
                    WHERE id = ?
                ")->execute([$newStart, $newEnd, $subscription['id']]);
            }
        }
    }
    
    /**
     * Verifica se pode usar evento (dentro do limite)
     */
    public function canUseEvent($userId) {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            return false;
        }
        
        // Se está expirado ou cancelado
        if (in_array($subscription['status'], ['expired', 'trial_expired', 'cancelled'])) {
            return false;
        }
        
        // Se ultrapassou limite
        if ($subscription['events_used'] >= $subscription['events_limit']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Incrementa contador de eventos
     */
    public function incrementEvent($userId) {
        if (!$this->canUseEvent($userId)) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("
                UPDATE safenode_subscriptions 
                SET events_used = events_used + 1 
                WHERE user_id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode Increment Event Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém estatísticas de uso
     */
    public function getUsageStats($userId) {
        $subscription = $this->getUserSubscription($userId);
        
        if (!$subscription) {
            return [
                'events_used' => 0,
                'events_limit' => 10000,
                'events_remaining' => 10000,
                'percentage_used' => 0,
                'status' => 'inactive',
                'plan_type' => 'free_trial',
                'days_remaining' => 0
            ];
        }
        
        $used = (int)$subscription['events_used'];
        $limit = (int)$subscription['events_limit'];
        $remaining = max(0, $limit - $used);
        $percentage = $limit > 0 ? round(($used / $limit) * 100, 1) : 0;
        
        $today = new DateTime();
        $endDate = new DateTime($subscription['billing_cycle_end']);
        $daysRemaining = max(0, $today->diff($endDate)->days);
        
        return [
            'events_used' => $used,
            'events_limit' => $limit,
            'events_remaining' => $remaining,
            'percentage_used' => $percentage,
            'status' => $subscription['status'],
            'plan_type' => $subscription['plan_type'],
            'days_remaining' => $daysRemaining,
            'billing_cycle_end' => $subscription['billing_cycle_end']
        ];
    }
    
    /**
     * Atualiza para plano pago
     */
    public function upgradeToPaid($userId, $stripeCustomerId, $stripeSubscriptionId) {
        if (!$this->db || !$userId) {
            return false;
        }
        
        try {
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+1 month'));
            
            $stmt = $this->db->prepare("
                UPDATE safenode_subscriptions 
                SET plan_type = 'paid',
                    status = 'active',
                    events_used = 0,
                    billing_cycle_start = ?,
                    billing_cycle_end = ?,
                    stripe_customer_id = ?,
                    stripe_subscription_id = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$startDate, $endDate, $stripeCustomerId, $stripeSubscriptionId, $userId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("SafeNode Upgrade Error: " . $e->getMessage());
            return false;
        }
    }
}



