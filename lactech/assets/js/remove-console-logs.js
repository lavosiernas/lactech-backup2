// Script temporário para remover console.log
const fs = require('fs');
const path = require('path');

const filePath = path.join(__dirname, 'gerente-completo.js');
let content = fs.readFileSync(filePath, 'utf8');

// Remover console.log, console.warn, console.info (mantendo console.error)
content = content.replace(/^\s*console\.(log|warn|info)\([^)]*\);?\s*$/gm, '');
content = content.replace(/console\.(log|warn|info)\([^)]*\);?\s*/g, '');

// Remover linhas vazias múltiplas
content = content.replace(/\n\s*\n\s*\n/g, '\n\n');

fs.writeFileSync(filePath, content, 'utf8');
console.log('Console.log removidos com sucesso!');

