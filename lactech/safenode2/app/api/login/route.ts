import { NextRequest, NextResponse } from 'next/server'

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const { email, password, hv_token, safenode_hv_js } = body

    if (!email || !password) {
      return NextResponse.json({
        success: false,
        error: 'Email e senha são obrigatórios',
      }, { status: 400 })
    }

    // Construir URL do login.php
    const host = request.headers.get('host') || 'localhost'
    const protocol = request.headers.get('x-forwarded-proto') || 
                     (host.includes('localhost') ? 'http' : 'https')
    
    let baseUrl = process.env.PHP_API_BASE_URL
    
    if (!baseUrl) {
      if (host.includes('localhost') || host.includes('127.0.0.1')) {
        const port = host.includes(':') ? host.split(':')[1] : '3000'
        baseUrl = port === '3000' ? 'http://localhost' : `${protocol}://${host.split(':')[0]}`
      } else {
        baseUrl = `${protocol}://${host}`
      }
    }
    
    baseUrl = baseUrl.replace(':3000', '').replace(':8080', '')
    
    // Adicionar caminho do projeto se necessário
    // No XAMPP, o caminho é relativo ao htdocs
    const projectPath = process.env.PHP_PROJECT_PATH || '/GitHub/lactech-backup2/lactech/safenode2'
    
    // Obter cookies da requisição original para manter sessão
    const cookies = request.headers.get('cookie') || ''

    // Tentar diferentes caminhos possíveis
    const possiblePaths = [
      `${projectPath}/login.php`,  // Caminho completo: /GitHub/lactech-backup2/lactech/safenode2/login.php
      '/lactech/safenode2/login.php',  // Caminho alternativo
      '/safenode2/login.php',      // Apenas safenode2
      '/login.php',                // Raiz do htdocs
    ]
    
    let loginUrl = ''
    let getResponse: Response | null = null
    let htmlContent = ''
    let workingPath = ''
    
    // Tentar cada caminho até encontrar um que funcione
    for (const path of possiblePaths) {
      loginUrl = `${baseUrl}${path}`
      console.log('Tentando acessar:', loginUrl)
      
      try {
        getResponse = await fetch(loginUrl, {
          method: 'GET',
          headers: {
            'Cookie': cookies,
            'User-Agent': request.headers.get('user-agent') || 'Next.js',
          },
        })
        
        if (getResponse.ok || getResponse.status === 200) {
          htmlContent = await getResponse.text()
          // Verificar se é realmente a página de login
          if (htmlContent.includes('Bem-vindo de volta') || htmlContent.includes('loginForm') || htmlContent.includes('safenode_csrf_token')) {
            console.log('✅ Página de login encontrada em:', loginUrl)
            workingPath = path
            break
          }
        }
      } catch (error) {
        console.log('❌ Erro ao acessar', loginUrl, ':', error)
        continue
      }
    }
    
    if (!getResponse || !htmlContent || !workingPath) {
      return NextResponse.json({
        success: false,
        error: `Não foi possível acessar o login.php. Verifique se o PHP está rodando e o caminho está correto. Tentado: ${possiblePaths.map(p => `${baseUrl}${p}`).join(', ')}`,
      }, { status: 500 })
    }
    
    // Extrair CSRF token do HTML
    // Tentar diferentes padrões de busca
    let csrfToken = null
    
    // Padrão 1: name="safenode_csrf_token" value="..."
    const csrfMatch1 = htmlContent.match(/name=["']safenode_csrf_token["']\s+value=["']([^"']+)["']/i)
    if (csrfMatch1) {
      csrfToken = csrfMatch1[1]
    }
    
    // Padrão 2: input hidden com safenode_csrf_token
    if (!csrfToken) {
      const csrfMatch2 = htmlContent.match(/<input[^>]*name=["']safenode_csrf_token["'][^>]*value=["']([^"']+)["']/i)
      if (csrfMatch2) {
        csrfToken = csrfMatch2[1]
      }
    }
    
    if (!csrfToken) {
      console.error('Não foi possível obter CSRF token')
      console.error('Status GET:', getResponse.status)
      console.error('HTML preview:', htmlContent.substring(0, 1000))
      return NextResponse.json({
        success: false,
        error: 'Erro ao obter token de segurança. Verifique se o PHP está rodando e acessível.',
      }, { status: 500 })
    }
    
    console.log('CSRF Token obtido:', csrfToken.substring(0, 10) + '...')
    
    // Obter cookies da resposta GET (PHPSESSID)
    const getCookies = getResponse?.headers.get('set-cookie') || cookies
    
    // SEGUNDO: Fazer POST com o CSRF token
    const formData = new URLSearchParams()
    formData.append('login', '1')
    formData.append('email', email)
    formData.append('password', password)
    formData.append('safenode_csrf_token', csrfToken)
    formData.append('safenode_hv_token', hv_token || '')
    formData.append('safenode_hv_js', safenode_hv_js || '1')
    
    // Fazer requisição POST para o login.php com CSRF token (usar o caminho que funcionou)
    const postUrl = `${baseUrl}${workingPath}`
    console.log('Fazendo POST para:', postUrl)
    
    const response = await fetch(postUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Cookie': getCookies, // Usar cookies da sessão GET
        'User-Agent': request.headers.get('user-agent') || 'Next.js',
        'Referer': `${protocol}://${host}/login`,
        'Accept': 'text/html,application/json',
      },
      body: formData.toString(),
      redirect: 'manual', // Não seguir redirects automaticamente
    })

    // Se redirecionou para dashboard, login foi bem-sucedido
    if (response.status === 302 || response.status === 301) {
      const location = response.headers.get('location') || ''
      
      if (location.includes('dashboard') || location.includes('dashboard.php')) {
        // Extrair cookies da resposta (PHPSESSID)
        const setCookieHeader = response.headers.get('set-cookie')
        
        // Combinar cookies da sessão
        const allCookies = setCookieHeader || getCookies
        
        return NextResponse.json({
          success: true,
          data: {
            token: 'php_session', // Sessão PHP
            user: {
              email: email,
              name: email.split('@')[0],
            },
            redirect: '/dashboard',
          },
        }, {
          headers: allCookies ? {
            'Set-Cookie': allCookies,
          } : {},
        })
      }
    }

    // Se não foi redirect, pode ser erro
    const responseText = await response.text()
    console.log('Status da resposta:', response.status)
    console.log('Tamanho da resposta:', responseText.length)
    console.log('Primeiros 500 caracteres:', responseText.substring(0, 500))
    
    // Tentar extrair mensagem de erro do HTML
    let errorMessage = 'Credenciais inválidas ou erro no servidor'
    
    // Procurar por mensagens de erro comuns no HTML
    const errorPatterns = [
      /Email não encontrado/i,
      /Senha incorreta/i,
      /Token de segurança inválido/i,
      /Muitas tentativas/i,
      /verifique seu email/i,
      /conta está inativa/i,
      /Por favor, preencha todos os campos/i,
      /Por favor, insira um email válido/i,
    ]
    
    for (const pattern of errorPatterns) {
      const match = responseText.match(pattern)
      if (match) {
        errorMessage = match[0]
        break
      }
    }
    
    // Se a resposta contém a página de login completa, significa que não houve redirect
    // Isso geralmente indica erro de validação
    if (responseText.includes('Bem-vindo de volta') || responseText.includes('loginForm')) {
      // Procurar por div de erro específica
      const errorDivMatch = responseText.match(/<div[^>]*class="[^"]*bg-red-500[^"]*"[^>]*>([^<]+)</i)
      if (errorDivMatch) {
        errorMessage = errorDivMatch[1].trim()
      }
    }

    return NextResponse.json({
      success: false,
      error: errorMessage,
    }, { status: 401 })
    
  } catch (error) {
    console.error('Erro no login:', error)
    return NextResponse.json(
      { 
        success: false, 
        error: error instanceof Error ? error.message : 'Erro ao conectar com o servidor' 
      },
      { status: 500 }
    )
  }
}

