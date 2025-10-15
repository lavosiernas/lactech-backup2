<?php
/**
 * Instalador do FPDF
 * Este script baixa e instala a biblioteca FPDF necessária para gerar relatórios em PDF
 */

echo "Iniciando instalação do FPDF...\n";

// Criar diretório vendor se não existir
if (!is_dir('vendor')) {
    mkdir('vendor', 0755, true);
    echo "Diretório vendor criado.\n";
}

// Criar diretório vendor/fpdf se não existir
if (!is_dir('vendor/fpdf')) {
    mkdir('vendor/fpdf', 0755, true);
    echo "Diretório vendor/fpdf criado.\n";
}

// URL do FPDF
$fpdfUrl = 'http://www.fpdf.org/fpdf181.zip';

// Arquivo ZIP temporário
$zipFile = 'vendor/fpdf181.zip';

echo "Baixando FPDF...\n";

// Baixar o arquivo ZIP
$zipContent = file_get_contents($fpdfUrl);
if ($zipContent === false) {
    die("Erro ao baixar FPDF. Verifique sua conexão com a internet.\n");
}

// Salvar o arquivo ZIP
file_put_contents($zipFile, $zipContent);
echo "FPDF baixado com sucesso.\n";

// Verificar se a extensão ZIP está disponível
if (!extension_loaded('zip')) {
    echo "AVISO: Extensão ZIP não está disponível. Você precisará extrair manualmente o arquivo fpdf181.zip em vendor/fpdf/\n";
    echo "Arquivo salvo em: $zipFile\n";
    exit;
}

// Extrair o arquivo ZIP
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    // Extrair todos os arquivos
    $zip->extractTo('vendor/fpdf/');
    $zip->close();
    
    // Remover arquivo ZIP
    unlink($zipFile);
    
    echo "FPDF extraído com sucesso.\n";
    
    // Verificar se o arquivo principal existe
    if (file_exists('vendor/fpdf/fpdf.php')) {
        echo "Instalação do FPDF concluída com sucesso!\n";
        echo "Arquivo principal: vendor/fpdf/fpdf.php\n";
    } else {
        echo "AVISO: Arquivo fpdf.php não encontrado após extração.\n";
        echo "Verifique se o arquivo foi extraído corretamente.\n";
    }
} else {
    echo "Erro ao extrair o arquivo ZIP.\n";
    echo "Tente extrair manualmente o arquivo: $zipFile\n";
}

echo "\nInstalação concluída!\n";
echo "Agora você pode usar os relatórios em PDF.\n";
?>









