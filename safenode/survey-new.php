<?php
/**
 * SafeNode - Formulário de Pesquisa/Validação
 * Nova versão com perguntas detalhadas para validação de produto
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();
$message = '';
$messageType = '';

// Processar envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados do formulário
    $email = trim($_POST['email'] ?? '');
    $dev_level = $_POST['dev_level'] ?? '';
    $work_type = $_POST['work_type'] ?? '';
    $main_stack = $_POST['main_stack'] ?? '';
    $main_stack_other = trim($_POST['main_stack_other'] ?? '');
    $pain_points = isset($_POST['pain_points']) ? json_encode($_POST['pain_points']) : null;
    $time_wasted_per_week = $_POST['time_wasted_per_week'] ?? '';
    $platform_help = $_POST['platform_help'] ?? '';
    $first_feature = $_POST['first_feature'] ?? '';
    $use_ai_analysis = $_POST['use_ai_analysis'] ?? '';
    $price_willing = $_POST['price_willing'] ?? '';
    $use_in_production = $_POST['use_in_production'] ?? '';
    $recommend_to_team = $_POST['recommend_to_team'] ?? '';
    $decision_maker = $_POST['decision_maker'] ?? '';
    $switch_reasons = trim($_POST['switch_reasons'] ?? '');
    $must_have_features = trim($_POST['must_have_features'] ?? '');
    
    // Validações básicas
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Por favor, informe um e-mail válido';
        $messageType = 'error';
    } else if (empty($dev_level) || empty($work_type) || empty($main_stack) || 
               empty($time_wasted_per_week) || empty($platform_help) || 
               empty($first_feature) || empty($use_ai_analysis) || empty($price_willing) ||
               empty($use_in_production) || empty($recommend_to_team) || empty($decision_maker)) {
        $message = 'Por favor, responda todas as perguntas obrigatórias';
        $messageType = 'error';
    } else if (!isset($_POST['pain_points']) || count($_POST['pain_points']) === 0) {
        $message = 'Por favor, selecione pelo menos 1 ponto de dor';
        $messageType = 'error';
    } else if ($main_stack === 'outra' && empty($main_stack_other)) {
        $message = 'Por favor, especifique qual stack você usa';
        $messageType = 'error';
    } else {
        // Salvar no banco de dados
        try {
            $stmt = $db->prepare("
                INSERT INTO safenode_survey_responses 
                (email, dev_level, work_type, main_stack, main_stack_other, pain_points, time_wasted_per_week,
                 platform_help, first_feature, use_ai_analysis, price_willing, use_in_production,
                 recommend_to_team, decision_maker, switch_reasons, must_have_features, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $email,
                $dev_level,
                $work_type,
                $main_stack,
                $main_stack_other ?: null,
                $pain_points,
                $time_wasted_per_week,
                $platform_help,
                $first_feature,
                $use_ai_analysis,
                $price_willing,
                $use_in_production,
                $recommend_to_team,
                $decision_maker,
                $switch_reasons ?: null,
                $must_have_features ?: null
            ]);
            
            $message = 'Obrigado pela sua resposta! Seus insights são extremamente valiosos para nós.';
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

$pageTitle = 'Pesquisa SafeNode - Validação de Produto';
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
        .section-divider {
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            margin: 3rem 0;
        }
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
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        
        <!-- Header Section -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-zinc-900 border border-zinc-800 mb-6">
                <i data-lucide="message-square" class="w-4 h-4 text-white"></i>
                <span class="text-xs font-medium text-zinc-300">Validação de Produto</span>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Ajude-nos a construir a SafeNode</h1>
            <p class="text-xl text-zinc-400 max-w-2xl mx-auto">
                Sua opinião é fundamental para criarmos uma plataforma que realmente resolve suas dores.
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
        <form method="POST" id="surveyForm" class="space-y-12 bg-zinc-900/50 border border-zinc-800 rounded-2xl p-8">
            
            <!-- Seção 1: PERFIL DO DEV -->
            <section>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">👤 PERFIL DO DEV (segmentação)</h2>
                    <p class="text-zinc-400 text-sm">Nos ajude a entender quem você é</p>
                </div>

                <!-- Email -->
                <div class="mb-6">
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
                    <p class="mt-1 text-xs text-zinc-500">Se quiser testar a SafeNode quando estiver pronta, deixe seu e-mail</p>
                </div>

                <!-- Nível como dev -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Qual seu nível como dev? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $devLevels = ['Estudante', 'Júnior', 'Pleno', 'Sênior', 'Tech Lead / Arquiteto', 'Fundador'];
                        foreach ($devLevels as $level): 
                            $value = strtolower(str_replace([' / ', ' '], ['-', '_'], $level));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="dev_level" value="<?php echo $value; ?>" required <?php echo ($_POST['dev_level'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $level; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tipo de trabalho -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Hoje você trabalha mais como: <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $workTypes = ['Dev solo / freelancer', 'Startup', 'Empresa média', 'Empresa grande'];
                        foreach ($workTypes as $type): 
                            $value = strtolower(str_replace([' / ', ' '], ['-', '_'], $type));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="work_type" value="<?php echo $value; ?>" required <?php echo ($_POST['work_type'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $type; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Stack principal -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Qual stack você mais usa hoje? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $stacks = ['JavaScript / TypeScript', 'Node.js', 'PHP', 'Python', 'Java', 'Go', 'Outra'];
                        foreach ($stacks as $stack): 
                            $value = strtolower(str_replace([' / ', ' '], ['-', '_'], $stack));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="main_stack" value="<?php echo $value; ?>" required <?php echo ($_POST['main_stack'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white" onchange="toggleStackOther(this.value)">
                            <span class="text-zinc-300"><?php echo $stack; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div id="stack-other-field" style="display: none;" class="mt-3">
                        <input 
                            type="text" 
                            name="main_stack_other"
                            value="<?php echo htmlspecialchars($_POST['main_stack_other'] ?? ''); ?>"
                            class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors"
                            placeholder="Qual stack você usa?"
                        >
                    </div>
                </div>
            </section>

            <div class="section-divider"></div>

            <!-- Seção 2: DOR REAL -->
            <section>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">🧱 DOR REAL (ESSA É OURO)</h2>
                    <p class="text-zinc-400 text-sm">O que mais te incomoda no dia a dia?</p>
                </div>

                <!-- Pontos de dor -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        O que mais te dá dor de cabeça hoje? <span class="text-zinc-500 text-xs">(marque até 3)</span> <span class="text-red-400">*</span>
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php 
                        $painPoints = [
                            'Infraestrutura', 'Deploy', 'Configuração de ambientes', 'E-mails transacionais',
                            'Monitoramento', 'Custos cloud', 'Documentação ruim', 'Debug em produção',
                            'Organização do projeto', 'Comunicação entre serviços'
                        ];
                        foreach ($painPoints as $point): 
                            $value = strtolower(str_replace(' ', '_', $point));
                            $checked = isset($_POST['pain_points']) && in_array($value, $_POST['pain_points']);
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="checkbox" name="pain_points[]" value="<?php echo $value; ?>" <?php echo $checked ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white rounded pain-point-checkbox" onchange="limitPainPoints()">
                            <span class="text-zinc-300"><?php echo $point; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-2 text-xs text-zinc-500" id="pain-points-count">0 de até 3 selecionados</p>
                </div>

                <!-- Tempo perdido -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Quanto tempo por semana você perde com infra/configuração? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $timeOptions = ['Menos de 1h', '1–3h', '3–5h', 'Mais de 5h'];
                        foreach ($timeOptions as $time): 
                            $value = strtolower(str_replace(['–', ' '], ['-', '_'], $time));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="time_wasted_per_week" value="<?php echo $value; ?>" required <?php echo ($_POST['time_wasted_per_week'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $time; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="section-divider"></div>

            <!-- Seção 3: SAFE NODE (VALIDAÇÃO) -->
            <section>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">🧠 SAFE NODE (VALIDAÇÃO DE IDEIA)</h2>
                    <p class="text-zinc-400 text-sm">O que você acha da nossa ideia?</p>
                </div>

                <!-- Plataforma ajudaria -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Uma plataforma visual para modelar infraestrutura e fluxos te ajudaria? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $helpOptions = ['Muito', 'Um pouco', 'Não vejo valor'];
                        foreach ($helpOptions as $help): 
                            $value = strtolower(str_replace(' ', '_', $help));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="platform_help" value="<?php echo $value; ?>" required <?php echo ($_POST['platform_help'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $help; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Primeira feature -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Qual dessas features você usaria primeiro? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $features = [
                            'IDE com IA focada em backend/infra',
                            'Infra visual (estilo Figma)',
                            'Automação de fluxos',
                            'E-mail transacional integrado',
                            'Monitoramento unificado',
                            'Templates prontos'
                        ];
                        foreach ($features as $feature): 
                            $value = strtolower(str_replace(['/', ' '], ['-', '_'], $feature));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="first_feature" value="<?php echo $value; ?>" required <?php echo ($_POST['first_feature'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $feature; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- IA de análise -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Você usaria uma IA que analisasse sua arquitetura e sugerisse melhorias? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $aiOptions = ['Sim, com certeza', 'Talvez', 'Não confio nisso'];
                        foreach ($aiOptions as $ai): 
                            $value = strtolower(str_replace([' ', ','], ['_', ''], $ai));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="use_ai_analysis" value="<?php echo $value; ?>" required <?php echo ($_POST['use_ai_analysis'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $ai; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="section-divider"></div>

            <!-- Seção 4: PREÇO -->
            <section>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">💸 PREÇO (SEM MEDO)</h2>
                    <p class="text-zinc-400 text-sm">Quanto você pagaria por uma plataforma assim?</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Quanto você estaria disposto a pagar por mês por uma plataforma assim? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $prices = ['US$ 10–20', 'US$ 20–50', 'US$ 50–100', 'US$ 100+', 'Só usaria se fosse free'];
                        foreach ($prices as $price): 
                            $value = strtolower(str_replace(['US$ ', '–', ' ', '+'], ['', '-', '_', 'plus'], $price));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="price_willing" value="<?php echo $value; ?>" required <?php echo ($_POST['price_willing'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $price; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="section-divider"></div>

            <!-- Seção 5: USO PROFISSIONAL -->
            <section>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">🏢 USO PROFISSIONAL</h2>
                    <p class="text-zinc-400 text-sm">Como você usaria isso no seu trabalho?</p>
                </div>

                <!-- Usaria em produção -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Você usaria isso em produção real? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $productionOptions = ['Sim', 'Talvez', 'Não'];
                        foreach ($productionOptions as $opt): 
                            $value = strtolower($opt);
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="use_in_production" value="<?php echo $value; ?>" required <?php echo ($_POST['use_in_production'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $opt; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Indicaria para time -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Você indicaria essa ferramenta para seu time/empresa? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $recommendOptions = ['Sim', 'Não'];
                        foreach ($recommendOptions as $opt): 
                            $value = strtolower($opt);
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="recommend_to_team" value="<?php echo $value; ?>" required <?php echo ($_POST['recommend_to_team'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $opt; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quem decide -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-3">
                        Na sua empresa, quem decide usar uma ferramenta dessas? <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2">
                        <?php 
                        $deciders = ['Eu', 'Meu time', 'A empresa', 'Diretoria / Arquitetura'];
                        foreach ($deciders as $decider): 
                            $value = strtolower(str_replace([' / ', ' '], ['-', '_'], $decider));
                        ?>
                        <label class="flex items-center gap-3 p-3 bg-zinc-950 border border-zinc-800 rounded-lg cursor-pointer hover:border-zinc-700 transition-colors">
                            <input type="radio" name="decision_maker" value="<?php echo $value; ?>" required <?php echo ($_POST['decision_maker'] ?? '') === $value ? 'checked' : ''; ?> class="w-4 h-4 text-white bg-zinc-800 border-zinc-700 focus:ring-2 focus:ring-white">
                            <span class="text-zinc-300"><?php echo $decider; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="section-divider"></div>

            <!-- Seção 6: FECHAMENTO -->
            <section>
                <div class="mb-6">
                    <h2 class="text-2xl font-bold mb-2">🚀 FECHAMENTO (INSIGHT BRUTO)</h2>
                    <p class="text-zinc-400 text-sm">O que realmente faria diferença?</p>
                </div>

                <!-- Motivos para trocar -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-2">
                        O que faria você trocar sua stack atual por algo como a SafeNode?
                    </label>
                    <textarea 
                        name="switch_reasons" 
                        rows="4"
                        class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors resize-none"
                        placeholder="O que seria necessário para você considerar uma mudança?"
                    ><?php echo htmlspecialchars($_POST['switch_reasons'] ?? ''); ?></textarea>
                </div>

                <!-- O que não pode faltar -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-zinc-300 mb-2">
                        O que você acha que NÃO pode faltar numa plataforma dessas?
                    </label>
                    <textarea 
                        name="must_have_features" 
                        rows="4"
                        class="w-full px-4 py-3 bg-zinc-950 border border-zinc-800 rounded-lg text-white placeholder:text-zinc-600 focus:outline-none focus:border-zinc-600 transition-colors resize-none"
                        placeholder="Quais funcionalidades são essenciais?"
                    ><?php echo htmlspecialchars($_POST['must_have_features'] ?? ''); ?></textarea>
                </div>
            </section>

            <!-- Submit -->
            <div class="pt-6">
                <button 
                    type="submit"
                    class="w-full px-6 py-4 bg-white text-black rounded-lg font-semibold hover:bg-zinc-100 transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2"
                >
                    <span>Enviar Respostas</span>
                    <i data-lucide="send" class="w-4 h-4"></i>
                </button>
            </div>

        </form>

        <!-- Footer Note -->
        <div class="mt-8 text-center text-sm text-zinc-500">
            <p>Suas respostas são confidenciais e nos ajudam a priorizar o roadmap.</p>
        </div>

    </main>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
        
        // Mostrar/ocultar campo "Outra" stack
        function toggleStackOther(value) {
            const otherField = document.getElementById('stack-other-field');
            if (value === 'outra') {
                otherField.style.display = 'block';
                otherField.querySelector('input').required = true;
            } else {
                otherField.style.display = 'none';
                otherField.querySelector('input').required = false;
                otherField.querySelector('input').value = '';
            }
        }
        
        // Limitar seleção de pontos de dor a 3
        function limitPainPoints() {
            const checkboxes = document.querySelectorAll('.pain-point-checkbox:checked');
            const countElement = document.getElementById('pain-points-count');
            
            if (checkboxes.length > 3) {
                // Desmarcar o último
                checkboxes[checkboxes.length - 1].checked = false;
                alert('Você pode selecionar no máximo 3 pontos de dor');
            }
            
            const currentCount = document.querySelectorAll('.pain-point-checkbox:checked').length;
            countElement.textContent = `${currentCount} de até 3 selecionados`;
        }
        
        // Atualizar contador inicial
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.pain-point-checkbox:checked');
            const countElement = document.getElementById('pain-points-count');
            countElement.textContent = `${checkboxes.length} de até 3 selecionados`;
            
            // Verificar stack other no load
            const selectedStack = document.querySelector('input[name="main_stack"]:checked');
            if (selectedStack && selectedStack.value === 'outra') {
                toggleStackOther('outra');
            }
        });
        
        // Validação antes de enviar
        document.getElementById('surveyForm').addEventListener('submit', function(e) {
            const painPoints = document.querySelectorAll('.pain-point-checkbox:checked');
            if (painPoints.length === 0) {
                e.preventDefault();
                alert('Por favor, selecione pelo menos 1 ponto de dor');
                return false;
            }
        });
    </script>

</body>
</html>

