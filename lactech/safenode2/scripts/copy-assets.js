const fs = require('fs')
const path = require('path')

// Criar estrutura de diretórios
const publicAssetsDir = path.join(__dirname, '../public/assets/img')
const sourceAssetsDir = path.join(__dirname, '../assets/img')

if (!fs.existsSync(publicAssetsDir)) {
  fs.mkdirSync(publicAssetsDir, { recursive: true })
}

// Copiar arquivos
if (fs.existsSync(sourceAssetsDir)) {
  const files = fs.readdirSync(sourceAssetsDir)
  
  files.forEach(file => {
    const sourcePath = path.join(sourceAssetsDir, file)
    const destPath = path.join(publicAssetsDir, file)
    
    if (fs.statSync(sourcePath).isFile()) {
      fs.copyFileSync(sourcePath, destPath)
      console.log(`Copied: ${file}`)
    }
  })
  
  console.log('Assets copied successfully!')
} else {
  console.log('Source assets directory not found. Creating symlink...')
  // Tentar criar link simbólico (pode não funcionar no Windows sem privilégios)
  try {
    if (process.platform !== 'win32') {
      fs.symlinkSync(sourceAssetsDir, publicAssetsDir, 'dir')
      console.log('Symlink created!')
    } else {
      console.log('Windows detected. Please copy assets manually or run as administrator for symlink.')
    }
  } catch (error) {
    console.error('Error:', error.message)
  }
}






