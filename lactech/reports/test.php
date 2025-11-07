<?php
// Arquivo de teste para diagnosticar problemas
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>Teste de Relatórios</h1>";

// Testar includes
echo "<h2>1. Testando includes...</h2>";
try {
    require_once '../includes/config_mysql.php';
    echo "✓ config_mysql.php carregado<br>";
    
    require_once '../includes/functions.php';
    echo "✓ functions.php carregado<br>";
    
    require_once '../includes/PDFGenerator.php';
    echo "✓ PDFGenerator.php carregado<br>";
    
    require_once '../includes/ExcelGenerator.php';
    echo "✓ ExcelGenerator.php carregado<br>";
} catch (Exception $e) {
    echo "✗ Erro ao carregar: " . $e->getMessage() . "<br>";
    exit;
}

// Testar FPDF
echo "<h2>2. Testando FPDF...</h2>";
if (class_exists('FPDF')) {
    echo "✓ Classe FPDF encontrada<br>";
} else {
    echo "✗ Classe FPDF NÃO encontrada<br>";
}

if (class_exists('FPDFConfig')) {
    echo "✓ Classe FPDFConfig encontrada<br>";
} else {
    echo "✗ Classe FPDFConfig NÃO encontrada<br>";
}

// Testar conexão
echo "<h2>3. Testando conexão com banco...</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "✓ Conexão com banco OK<br>";
} catch (PDOException $e) {
    echo "✗ Erro de conexão: " . $e->getMessage() . "<br>";
}

// Testar PDFGenerator
echo "<h2>4. Testando PDFGenerator...</h2>";
try {
    $pdfGen = new PDFGenerator();
    echo "✓ PDFGenerator criado com sucesso<br>";
} catch (Exception $e) {
    echo "✗ Erro ao criar PDFGenerator: " . $e->getMessage() . "<br>";
}

// Testar ExcelGenerator
echo "<h2>5. Testando ExcelGenerator...</h2>";
try {
    $excelGen = new ExcelGenerator();
    echo "✓ ExcelGenerator criado com sucesso<br>";
} catch (Exception $e) {
    echo "✗ Erro ao criar ExcelGenerator: " . $e->getMessage() . "<br>";
}

echo "<h2>Teste concluído!</h2>";
?>

