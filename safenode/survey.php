<?php
/**
 * SafeNode - Formulário de Pesquisa/Validação
 * Descobrir dores, confirmar foco, priorizar roadmap
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();
$message = '';
$messageType = '';

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $uses_hosting = $_POST['uses_hosting'] ?? '';
    $hosting_type = trim($_POST['hosting_type'] ?? '');
    $biggest_pain = trim($_POST['biggest_pain'] ?? '');
    $pays_for_email = $_POST['pays_for_email'] ?? '';
    $would_pay_integration = $_POST['would_pay_integration'] ?? '';
    $wants_beta = isset($_POST['wants_beta']) ? 1 : 0;
    $additional_info = trim($_POST['additional_info'] ?? '');
    
    // Validações básicas
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Por favor, informe um e-mail válido';
        $messageType = 'error';
    } else if (empty($uses_hosting) || empty($biggest_pain)) {
        $message = 'Por favor, responda todas as perguntas obrigatórias';
        $messageType = 'error';
    } else {
        // Salvar no banco de dados
        try {
            // Criar tabela se não existir
            $db->exec("
                CREATE TABLE IF NOT EXISTS safenode_survey_responses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) NOT NULL,
                    uses_hosting VARCHAR(50) NOT NULL,
                    hosting_type VARCHAR(255) DEFAULT NULL,
                    biggest_pain TEXT NOT NULL,
                    pays_for_email VARCHAR(50) NOT NULL,
                    would_pay_integration VARCHAR(50) NOT NULL,
                    wants_beta TINYINT(1) DEFAULT 0,
                    additional_info TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_email (email),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $stmt = $db->prepare("
                INSERT INTO safenode_survey_responses 
                (email, uses_hosting, hosting_type, biggest_pain, pays_for_email, would_pay_integration, wants_beta, additional_info)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $email,
                $uses_hosting,
                $hosting_type ?: null,
                $biggest_pain,
                $pays_for_email,
                $would_pay_integration,
                $wants_beta,
                $additional_info ?: null
            ]);
            
            $message = 'Obrigado pela sua resposta! Seus insights nos ajudam a construir um produto melhor.';
            $messageType = 'success';
            
            // Limpar campos após sucesso
            $_POST = [];
            
        } catch (PDOException $e) {
            error_log("SafeNode Survey Error: " . $e->getMessage());
            $message = 'Erro ao salvar resposta. Por favor, tente novamente.';
            $messageType = 'error';
        }
    }
}

$pageTitle = 'Pesquisa SafeNode';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-black text-white min-h-screen">
    
    <!-- Header -->
    <nav class="border-b border-zinc-900 bg-black/50 backdrop-blur-xl sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center gap-2">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="h-8 w-auto">
                    <span class="font-bold text-xl">SafeNode</span>
                </a>
                <div class="flex items-center gap-4">
                    <a href="index.php" class="text-sm text-zinc-400 hover:text-white transition-colors">Voltar ao site</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-900 border border-zinc-800 mb-6">
                <i data-lucide="message-square" class="w-4 h-4 text-white"></i>
                <span class="text-xs font-medium text-zinc-300">Pesquisa de Validação</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Ajude-nos a construir o futuro da SafeNode</h1>
            <p class="text-xl text-zinc-400 max-w-2xl mx-auto">
                Suas respostas nos ajudam a priorizar o que realmente importa e construir um produto que resolve problemas reais.
            </p>
        </div>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-green-500/20 border border-green-500/50 text-green-400' : 'bg-red-500/20 border border-red-500/50 text-red-400'; ?>">
            <div class="flex items-center gap-2">
                <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="space-y-8 bg-zinc-900/50 border border-zinc-800 rounded-2xl p-8">
            
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    Seu e-mail <span class="text-red-400">*</span>
                </label>
                <input 
                    type="email" 
                    name="email" 
                    required
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                    placeholder="seu@email.com"
                >
                <p class="mt-1 text-xs text-zinc-500">Nunca compartilharemos seu e-mail. Usado apenas para contato sobre beta.</p>
            </div>

            <!-- Usa hospedagem -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    Você usa alguma hospedagem atualmente? <span class="text-red-400">*</span>
                </label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="uses_hosting" value="yes" required <?php echo ($_POST['uses_hosting'] ?? '') === 'yes' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Sim, uso hospedagem</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="uses_hosting" value="vps" required <?php echo ($_POST['uses_hosting'] ?? '') === 'vps' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Sim, uso VPS (DigitalOcean, AWS, etc)</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="uses_hosting" value="no" required <?php echo ($_POST['uses_hosting'] ?? '') === 'no' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Não, mas pretendo usar</span>
                    </label>
                </div>
            </div>

            <!-- Tipo de hospedagem (condicional) -->
            <div id="hosting-type-field" style="display: none;">
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    Qual hospedagem você usa?
                </label>
                <input 
                    type="text" 
                    name="hosting_type"
                    value="<?php echo htmlspecialchars($_POST['hosting_type'] ?? ''); ?>"
                    class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                    placeholder="Ex: Hostinger, DigitalOcean, AWS, cPanel, Plesk..."
                >
            </div>

            <!-- Maior dor -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    O que mais dói no seu fluxo de envio de e-mails? <span class="text-red-400">*</span>
                </label>
                <textarea 
                    name="biggest_pain" 
                    required
                    rows="4"
                    class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors resize-none"
                    placeholder="Ex: Configurar SMTP é muito complicado, e-mails caem na caixa de spam, não consigo rastrear entregas..."
                ><?php echo htmlspecialchars($_POST['biggest_pain'] ?? ''); ?></textarea>
            </div>

            <!-- Paga por e-mail -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    Você já paga por algum serviço de e-mail? <span class="text-red-400">*</span>
                </label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="pays_for_email" value="yes" required <?php echo ($_POST['pays_for_email'] ?? '') === 'yes' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Sim, já pago (SendGrid, Mailgun, etc)</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="pays_for_email" value="no" required <?php echo ($_POST['pays_for_email'] ?? '') === 'no' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Não, uso serviço gratuito ou SMTP próprio</span>
                    </label>
                </div>
            </div>

            <!-- Pagaria por integração -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    Você pagaria por uma integração pronta que funcionasse direto na sua hospedagem? <span class="text-red-400">*</span>
                </label>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="would_pay_integration" value="yes" required <?php echo ($_POST['would_pay_integration'] ?? '') === 'yes' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Sim, definitivamente</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="would_pay_integration" value="maybe" required <?php echo ($_POST['would_pay_integration'] ?? '') === 'maybe' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Talvez, depende do preço</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                        <input type="radio" name="would_pay_integration" value="no" required <?php echo ($_POST['would_pay_integration'] ?? '') === 'no' ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                        <span class="text-zinc-300">Não</span>
                    </label>
                </div>
            </div>

            <!-- Quer testar beta -->
            <div>
                <label class="flex items-center gap-3 p-4 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                    <input 
                        type="checkbox" 
                        name="wants_beta"
                        <?php echo isset($_POST['wants_beta']) ? 'checked' : ''; ?>
                        class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white rounded"
                    >
                    <div>
                        <span class="text-zinc-300 font-medium">Quero testar a versão beta da integração</span>
                        <p class="text-xs text-zinc-500 mt-1">Vamos entrar em contato quando estiver pronto!</p>
                    </div>
                </label>
            </div>

            <!-- Informações adicionais -->
            <div>
                <label class="block text-sm font-medium text-zinc-300 mb-2">
                    Algo mais que gostaria de compartilhar?
                </label>
                <textarea 
                    name="additional_info" 
                    rows="3"
                    class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors resize-none"
                    placeholder="Sugestões, feedback, casos de uso..."
                ><?php echo htmlspecialchars($_POST['additional_info'] ?? ''); ?></textarea>
            </div>

            <!-- Submit -->
            <button 
                type="submit"
                class="w-full px-6 py-4 bg-white text-black rounded-lg font-semibold hover:bg-zinc-100 transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2"
            >
                <span>Enviar Respostas</span>
                <i data-lucide="send" class="w-4 h-4"></i>
            </button>

        </form>

        <!-- Footer Note -->
        <div class="mt-8 text-center text-sm text-zinc-500">
            <p>Suas respostas são confidenciais e nos ajudam a priorizar o roadmap.</p>
        </div>

    </main>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
        
        // Mostrar/ocultar campo de tipo de hospedagem
        document.querySelectorAll('input[name="uses_hosting"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const hostingTypeField = document.getElementById('hosting-type-field');
                if (this.value === 'yes' || this.value === 'vps') {
                    hostingTypeField.style.display = 'block';
                } else {
                    hostingTypeField.style.display = 'none';
                }
            });
        });
        
        // Trigger on load se já estiver selecionado
        const selectedHosting = document.querySelector('input[name="uses_hosting"]:checked');
        if (selectedHosting && (selectedHosting.value === 'yes' || selectedHosting.value === 'vps')) {
            document.getElementById('hosting-type-field').style.display = 'block';
        }
    </script>

</body>
</html>

