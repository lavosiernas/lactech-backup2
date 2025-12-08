import { NextRequest, NextResponse } from 'next/server'

// Proxy para APIs PHP
export async function GET(
  request: NextRequest,
  { params }: { params: { path: string[] } }
) {
  return handleProxy(request, params.path, 'GET')
}

export async function POST(
  request: NextRequest,
  { params }: { params: { path: string[] } }
) {
  return handleProxy(request, params.path, 'POST')
}

export async function PUT(
  request: NextRequest,
  { params }: { params: { path: string[] } }
) {
  return handleProxy(request, params.path, 'PUT')
}

export async function DELETE(
  request: NextRequest,
  { params }: { params: { path: string[] } }
) {
  return handleProxy(request, params.path, 'DELETE')
}

async function handleProxy(
  request: NextRequest,
  path: string[],
  method: string
) {
  try {
    // Construir URL do endpoint PHP
    const phpPath = path.join('/')
    
    // Determinar a URL base do PHP
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
    
    // Caminho do projeto no XAMPP
    const projectPath = process.env.PHP_PROJECT_PATH || '/GitHub/lactech-backup2/lactech/safenode2'
    
    // Determinar se o arquivo está na pasta api/ ou na raiz
    const isRootFile = ['login.php', 'register.php', 'logout.php', 'forgot-password.php', 'verify-otp.php'].includes(phpPath)
    const filePath = isRootFile ? `${projectPath}/${phpPath}` : `${projectPath}/api/${phpPath}`
    
    const phpUrl = `${baseUrl}${filePath}`
    
    // Obter query string
    const searchParams = request.nextUrl.searchParams.toString()
    const fullUrl = searchParams ? `${phpUrl}?${searchParams}` : phpUrl
    
    console.log('Proxy PHP:', method, fullUrl)

    // Obter headers relevantes
    const headers: HeadersInit = {
      'Content-Type': 'application/json',
      'User-Agent': request.headers.get('user-agent') || 'Next.js',
      'Accept': 'application/json, text/html, */*',
    }

    // Copiar cookies da requisição original (importante para manter sessão PHP)
    const cookies = request.headers.get('cookie')
    if (cookies) {
      headers['Cookie'] = cookies
    }
    
    // Copiar referer se existir
    const referer = request.headers.get('referer')
    if (referer) {
      headers['Referer'] = referer
    }

    // Preparar body para POST/PUT
    let body: string | undefined
    if (method === 'POST' || method === 'PUT') {
      try {
        const json = await request.json()
        body = JSON.stringify(json)
      } catch {
        // Se não for JSON, tentar como texto
        body = await request.text()
      }
    }

    // Fazer requisição para o endpoint PHP
    const response = await fetch(fullUrl, {
      method,
      headers,
      body,
    })

    // Obter resposta
    const data = await response.text()
    
    // Tentar parsear como JSON
    let jsonData
    try {
      jsonData = JSON.parse(data)
    } catch {
      jsonData = data
    }

    // Obter cookies da resposta PHP (importante para manter sessão)
    const setCookieHeader = response.headers.get('set-cookie')
    const responseHeaders: HeadersInit = {
      'Content-Type': 'application/json',
    }
    
    // Passar cookies da sessão PHP para o cliente
    if (setCookieHeader) {
      responseHeaders['Set-Cookie'] = setCookieHeader
    }

    // Retornar resposta
    return NextResponse.json(jsonData, {
      status: response.status,
      headers: responseHeaders,
    })
  } catch (error) {
    console.error('Erro no proxy PHP:', error)
    return NextResponse.json(
      { success: false, error: 'Erro ao conectar com a API' },
      { status: 500 }
    )
  }
}

