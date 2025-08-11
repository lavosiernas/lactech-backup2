// Serviço de geração de PDF com Puppeteer
const express = require('express');
const puppeteer = require('puppeteer');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const port = 3000;

// Configurar CORS para permitir requisições do servidor local
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));

// Pasta para armazenar PDFs temporários
const tempDir = path.join(__dirname, 'temp');
if (!fs.existsSync(tempDir)) {
  fs.mkdirSync(tempDir);
}

// Rota para gerar PDF a partir de HTML
app.post('/generate-pdf', async (req, res) => {
  try {
    const { html, filename = 'relatorio.pdf', options = {} } = req.body;
    
    if (!html) {
      return res.status(400).json({ error: 'HTML content is required' });
    }
    
    // Iniciar o navegador
    const browser = await puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    
    // Criar uma nova página
    const page = await browser.newPage();
    
    // Configurar o conteúdo HTML
    await page.setContent(html, { waitUntil: 'networkidle0' });
    
    // Usar CSS para tela em vez de impressão
    await page.emulateMediaType('screen');
    
    // Gerar o PDF
    const pdfOptions = {
      format: 'A4',
      printBackground: true,
      margin: { top: '1cm', right: '1cm', bottom: '1cm', left: '1cm' },
      ...options
    };
    
    const pdfBuffer = await page.pdf(pdfOptions);
    
    // Fechar o navegador
    await browser.close();
    
    // Salvar o PDF temporariamente
    const pdfPath = path.join(tempDir, filename);
    fs.writeFileSync(pdfPath, pdfBuffer);
    
    // Enviar o PDF como resposta
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', `attachment; filename="${filename}"`);
    res.send(pdfBuffer);
    
    // Remover o arquivo após envio
    setTimeout(() => {
      if (fs.existsSync(pdfPath)) {
        fs.unlinkSync(pdfPath);
      }
    }, 5000);
    
  } catch (error) {
    console.error('Erro ao gerar PDF:', error);
    res.status(500).json({ error: 'Falha ao gerar PDF', details: error.message });
  }
});

// Rota para verificar se o serviço está funcionando
app.get('/health', (req, res) => {
  res.json({ status: 'ok', message: 'Serviço de PDF está funcionando' });
});

// Iniciar o servidor
app.listen(port, () => {
  console.log(`Serviço de PDF rodando em http://localhost:${port}`);
});