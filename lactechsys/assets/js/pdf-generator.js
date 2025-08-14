// Gerador de PDF usando jsPDF

// Logo do sistema (global) - será convertida para base64
const SYSTEM_LOGO_URL = "https://i.postimg.cc/vmrkgDcB/lactech.png"
let systemLogoBase64 = null
let farmLogoBase64 = null

// Função para converter URL de imagem para base64
async function urlToBase64(url) {
  try {
    const response = await fetch(url)
    const blob = await response.blob()
    return new Promise((resolve, reject) => {
      const reader = new FileReader()
      reader.onload = () => resolve(reader.result)
      reader.onerror = reject
      reader.readAsDataURL(blob)
    })
  } catch (error) {
    console.error("Erro ao converter URL para base64:", error)
    return null
  }
}

// Carregar logo do sistema
async function loadSystemLogo() {
  if (!systemLogoBase64) {
    systemLogoBase64 = await urlToBase64(SYSTEM_LOGO_URL)
  }
  return systemLogoBase64
}

// Carregar logo da fazenda do banco de dados (busca configurações do gerente)
async function loadFarmLogo() {
  try {
    // Primeiro, obter o farm_id do usuário atual
    const { data: { user: currentUser } } = await supabase.auth.getUser()
    if (!currentUser) {
      console.log("Usuário não autenticado")
      return null
    }

    // Primeiro, verificar se o usuário atual tem logo configurada
    const { data: currentUserData, error: currentUserError } = await supabase
      .from('users')
      .select('report_farm_logo_base64, report_farm_name, farm_id')
      .eq('id', currentUser.id)
      .single()

    if (currentUserError) {
      console.error("Erro ao obter dados do usuário atual:", currentUserError)
      return null
    }

    // Se o usuário atual tem logo, usar ela
    if (currentUserData?.report_farm_logo_base64) {
      console.log("Logo da fazenda carregada do usuário atual com sucesso")
      console.log("Tamanho da logo:", currentUserData.report_farm_logo_base64.length)
      
      // Atualizar o nome da fazenda se disponível
      if (currentUserData.report_farm_name && window.reportSettings) {
        window.reportSettings.farmName = currentUserData.report_farm_name
      }
      
      return currentUserData.report_farm_logo_base64
    }

    // Se não tem farm_id, não pode buscar configurações do gerente
    if (!currentUserData?.farm_id) {
      console.log("Usuário não tem farm_id associado")
      return null
    }

    // Buscar configurações do gerente da fazenda como fallback
    const { data: managerData, error: managerError } = await supabase
      .from('users')
      .select('report_farm_logo_base64, report_farm_name')
      .eq('farm_id', currentUserData.farm_id)
      .eq('role', 'gerente')
      .not('report_farm_logo_base64', 'is', null)
      .maybeSingle()

    if (managerError) {
      console.log("Erro ao buscar configurações do gerente:", managerError)
      return null
    }

    if (managerData?.report_farm_logo_base64) {
      console.log("Logo da fazenda carregada do gerente com sucesso")
      console.log("Tamanho da logo:", managerData.report_farm_logo_base64.length)
      
      // Também atualizar o nome da fazenda se disponível
      if (managerData.report_farm_name && window.reportSettings) {
        window.reportSettings.farmName = managerData.report_farm_name
      }
      
      return managerData.report_farm_logo_base64
    }

    console.log("Nenhuma logo da fazenda encontrada nas configurações do usuário ou do gerente")
    return null
  } catch (error) {
    console.error("Erro ao carregar logo da fazenda:", error)
    return null
  }
}

/**
 * Gera um relatório de volume e faz o download
 * @param {Array} data - Dados do relatório
 * @param {boolean} isPreview - Se é uma prévia
 */
async function generateVolumePDF(data, isPreview = false) {
  try {
    // Carregar logos
    const systemLogo = await loadSystemLogo()
    const farmLogo = await loadFarmLogo()
    
    console.log("Status das logos:")
    console.log("- Logo do sistema:", systemLogo ? "Carregada" : "Não carregada")
    console.log("- Logo da fazenda:", farmLogo ? "Carregada" : "Não carregada")
    console.log("- window.reportSettings:", window.reportSettings)

    const today = new Date().toLocaleDateString("pt-BR")
    const totalVolume = data.reduce((sum, record) => sum + (parseFloat(record.volume_liters) || 0), 0)
    const avgVolume = data.length > 0 ? totalVolume / data.length : 0

    // Criar novo documento PDF usando a sintaxe correta do jsPDF 2.x
    const { jsPDF } = window.jspdf
    const doc = new jsPDF()

    // Configurações
    const pageWidth = doc.internal.pageSize.getWidth()
    const pageHeight = doc.internal.pageSize.getHeight()
    const margin = 20
    let yPosition = margin

    // Adicionar logo da fazenda (canto superior direito) - tamanho otimizado
    if (farmLogo) {
      doc.addImage(farmLogo, "PNG", pageWidth - 50, 20, 30, 30)
    }

    // Adicionar marca d'água (logo da fazenda transparente no centro) - versão original
    if (farmLogo) {
      doc.saveGraphicsState()
      doc.setGState(new doc.GState({ opacity: 0.1 })) // Volta para valor original
      const watermarkSize = 150 // Volta para tamanho original
      const watermarkX = (pageWidth - watermarkSize) / 2
      const watermarkY = (pageHeight - watermarkSize) / 2
      doc.addImage(farmLogo, "PNG", watermarkX, watermarkY, watermarkSize, watermarkSize)
      doc.restoreGraphicsState()
    }

    yPosition = 30 // Posição fixa para não ser afetada pelo logo

    // Título com nome da fazenda
    doc.setFontSize(18)
    doc.setFont("helvetica", "bold")
    doc.setTextColor(0, 0, 0) // Texto preto
    const titleText = window.reportSettings?.farmName
      ? `RELATÓRIO DE VOLUME - ${window.reportSettings.farmName}`
      : "RELATÓRIO DE VOLUME"
    doc.text(titleText, margin, yPosition)
    yPosition += 20

    // Data do relatório
    doc.setFontSize(12)
    doc.setFont("helvetica", "normal")
    doc.text(`Relatório gerado em: ${today}`, margin, yPosition)
    yPosition += 20

    // Resumo
    doc.setFontSize(14)
    doc.setFont("helvetica", "bold")
    doc.text("RESUMO", margin, yPosition)
    yPosition += 10

    doc.setFontSize(12)
    doc.setFont("helvetica", "normal")
    doc.text(`Volume Total: ${totalVolume.toFixed(2)} L`, margin, yPosition)
    yPosition += 8
    doc.text(`Média por Registro: ${avgVolume.toFixed(2)} L`, margin, yPosition)
    yPosition += 20

    // Detalhamento
    doc.setFontSize(14)
    doc.setFont("helvetica", "bold")
    doc.setTextColor(0, 0, 0)
    doc.text("DETALHAMENTO DOS REGISTROS", margin, yPosition)
    yPosition += 15

    // Cabeçalho da tabela
    doc.setFontSize(10)
    doc.setFont("helvetica", "bold")
    const headers = ["Data", "Hora", "Volume (L)", "Turno", "Observações"]
    const colWidths = [30, 25, 25, 25, 65]
    let xPosition = margin

    headers.forEach((header, index) => {
      doc.text(header, xPosition, yPosition)
      xPosition += colWidths[index]
    })
    yPosition += 8

    // Linha separadora
    doc.line(margin, yPosition, pageWidth - margin, yPosition)
    yPosition += 5

    // Dados da tabela
    doc.setFont("helvetica", "normal")
    doc.setTextColor(0, 0, 0)
    data.forEach((record, index) => {
      if (yPosition > pageHeight - 30) {
        doc.addPage()
        yPosition = margin
      }

      xPosition = margin
      const rowData = [
        new Date(record.production_date).toLocaleDateString("pt-BR"),
        record.created_at ? new Date(record.created_at).toLocaleTimeString("pt-BR", { hour: '2-digit', minute: '2-digit' }) : "",
        (parseFloat(record.volume_liters) || 0).toFixed(2),
        record.shift || "",
        record.observations || "",
      ]

      rowData.forEach((cell, cellIndex) => {
        doc.text(String(cell), xPosition, yPosition)
        xPosition += colWidths[cellIndex]
      })
      yPosition += 6
    })

    // Rodapé elegante
    const footerY = pageHeight - 25
    
    // Linha decorativa
    doc.setDrawColor(203, 213, 225)
    doc.line(margin, footerY - 5, pageWidth - margin, footerY - 5)
    
    // Logo menor do sistema
    if (systemLogo) {
      doc.addImage(systemLogo, "PNG", margin, footerY, 8, 8)
    }
    
    doc.setTextColor(107, 114, 128)
    doc.setFontSize(8)
    doc.setFont("helvetica", "normal")
    doc.text("Sistema de Gestão Leiteira", margin + 12, footerY + 4)
    doc.setFont("helvetica", "bold")
    doc.text("LacTech", margin + 12, footerY + 8)
    
    // Data/hora da geração no canto direito
    doc.setFont("helvetica", "italic")
    doc.setFontSize(7)
    const timestamp = new Date().toLocaleString('pt-BR')
    doc.text(`Gerado em: ${timestamp}`, pageWidth - margin, footerY + 6, { align: "right" })

    // Marca d'água se for prévia
    if (isPreview) {
      doc.setFontSize(50)
      doc.setTextColor(255, 0, 0, 0.3)
      doc.text("PRÉVIA", pageWidth / 2, pageHeight / 2, {
        align: "center",
        angle: 45,
      })
    }

    // Download do PDF
    doc.save(`relatorio_volume_${new Date().toISOString().split("T")[0]}.pdf`)

    if (!isPreview) {
      window.showNotification("Relatório de volume gerado com sucesso!", "success")
    }
  } catch (error) {
    console.error("Erro ao gerar PDF:", error)
    window.showNotification("Erro ao gerar PDF: " + error.message, "error")
  }
}

/**
 * Gera um relatório de qualidade e faz o download
 * @param {Array} data - Dados do relatório
 * @param {boolean} isPreview - Se é uma prévia
 */
async function generateQualityPDF(data, isPreview = false) {
  try {
    // Carregar logos
    const systemLogo = await loadSystemLogo()
    const farmLogo = await loadFarmLogo()
    
    console.log("Status das logos (Quality PDF):")
    console.log("- Logo do sistema:", systemLogo ? "Carregada" : "Não carregada")
    console.log("- Logo da fazenda:", farmLogo ? "Carregada" : "Não carregada")
    console.log("- window.reportSettings:", window.reportSettings)

    const today = new Date().toLocaleDateString("pt-BR")
    const avgFat =
      data.reduce((sum, record) => sum + (Number.parseFloat(record.fat_percentage) || 0), 0) / data.length || 0
    const avgProtein =
      data.reduce((sum, record) => sum + (Number.parseFloat(record.protein_percentage) || 0), 0) / data.length || 0
    const avgSCC =
      data.reduce((sum, record) => sum + (Number.parseFloat(record.somatic_cell_count) || 0), 0) / data.length || 0
    const avgCBT =
      data.reduce((sum, record) => sum + (Number.parseFloat(record.total_bacterial_count) || 0), 0) / data.length || 0

    // Criar novo documento PDF usando a sintaxe correta do jsPDF 2.x
    const { jsPDF } = window.jspdf
    const doc = new jsPDF()

    // Configurações
    const pageWidth = doc.internal.pageSize.getWidth()
    const pageHeight = doc.internal.pageSize.getHeight()
    const margin = 20
    let yPosition = margin

    // Adicionar logo da fazenda (canto superior direito) - posição fixa sem empurrar conteúdo
    if (farmLogo) {
      doc.addImage(farmLogo, "PNG", pageWidth - 60, 30, 40, 40)
    }

    // Adicionar marca d'água (logo da fazenda transparente no centro) - mais transparente
    if (farmLogo) {
      doc.saveGraphicsState()
      doc.setGState(new doc.GState({ opacity: 0.05 })) // Mudado de 0.1 para 0.05
      const watermarkSize = 150
      const watermarkX = (pageWidth - watermarkSize) / 2
      const watermarkY = (pageHeight - watermarkSize) / 2
      doc.addImage(farmLogo, "PNG", watermarkX, watermarkY, watermarkSize, watermarkSize)
      doc.restoreGraphicsState()
    }

    yPosition = 30 // Posição fixa para não ser afetada pelo logo

    // Título com nome da fazenda
    doc.setFontSize(18)
    doc.setFont("helvetica", "bold")
    const titleText = window.reportSettings?.farmName
      ? `RELATÓRIO DE QUALIDADE - ${window.reportSettings.farmName}`
      : "RELATÓRIO DE QUALIDADE"
    doc.text(titleText, margin, yPosition)
    yPosition += 20

    // Data do relatório
    doc.setFontSize(12)
    doc.setFont("helvetica", "normal")
    doc.text(`Relatório gerado em: ${today}`, margin, yPosition)
    yPosition += 20

    // Resumo
    doc.setFontSize(14)
    doc.setFont("helvetica", "bold")
    doc.text("RESUMO", margin, yPosition)
    yPosition += 10

    doc.setFontSize(12)
    doc.setFont("helvetica", "normal")
    doc.text(`Média de Gordura: ${avgFat.toFixed(2)}%`, margin, yPosition)
    yPosition += 8
    doc.text(`Média de Proteína: ${avgProtein.toFixed(2)}%`, margin, yPosition)
    yPosition += 8
    doc.text(`Média de CCS: ${avgSCC.toFixed(0)} cél/mL`, margin, yPosition)
    yPosition += 8
    doc.text(`Média de CBT: ${avgCBT.toFixed(0)} UFC/mL`, margin, yPosition)
    yPosition += 20

    // Detalhamento
    doc.setFontSize(14)
    doc.setFont("helvetica", "bold")
    doc.text("DETALHAMENTO DOS REGISTROS", margin, yPosition)
    yPosition += 15

    // Cabeçalho da tabela
    doc.setFontSize(10)
    doc.setFont("helvetica", "bold")
    const headers = ["Data", "Gordura (%)", "Proteína (%)", "CCS", "CBT"]
    const colWidths = [35, 30, 30, 30, 30]
    let xPosition = margin

    headers.forEach((header, index) => {
      doc.text(header, xPosition, yPosition)
      xPosition += colWidths[index]
    })
    yPosition += 8

    // Linha separadora
    doc.line(margin, yPosition, pageWidth - margin, yPosition)
    yPosition += 5

    // Dados da tabela
    doc.setFont("helvetica", "normal")
    data.forEach((record, index) => {
      if (yPosition > pageHeight - 30) {
        doc.addPage()
        yPosition = margin
      }

      xPosition = margin
      const rowData = [
        new Date(record.test_date).toLocaleDateString("pt-BR"),
        (Number.parseFloat(record.fat_percentage) || 0).toFixed(2),
        (Number.parseFloat(record.protein_percentage) || 0).toFixed(2),
        (Number.parseFloat(record.somatic_cell_count) || 0).toFixed(0),
        (Number.parseFloat(record.total_bacterial_count) || 0).toFixed(0),
      ]

      rowData.forEach((cell, cellIndex) => {
        doc.text(String(cell), xPosition, yPosition)
        xPosition += colWidths[cellIndex]
      })
      yPosition += 6
    })

    // Rodapé com logo menor do sistema
    const footerY = pageHeight - 30
    if (systemLogo) {
      doc.addImage(systemLogo, "PNG", pageWidth / 2 - 5, footerY - 10, 10, 10) // Reduzido para 10x10
    }
    doc.setFontSize(8)
    doc.setFont("helvetica", "italic")
    doc.text("Sistema de Gestão Leiteira - LacTech", pageWidth / 2, footerY + 10, { align: "center" })

    // Marca d'água se for prévia
    if (isPreview) {
      doc.setFontSize(50)
      doc.setTextColor(255, 0, 0, 0.3)
      doc.text("PRÉVIA", pageWidth / 2, pageHeight / 2, {
        align: "center",
        angle: 45,
      })
    }

    // Download do PDF
    doc.save(`relatorio_qualidade_${new Date().toISOString().split("T")[0]}.pdf`)

    if (!isPreview) {
      window.showNotification("Relatório de qualidade gerado com sucesso!", "success")
    }
  } catch (error) {
    console.error("Erro ao gerar PDF:", error)
    window.showNotification("Erro ao gerar PDF: " + error.message, "error")
  }
}

/**
 * Gera um relatório financeiro e faz o download
 * @param {Array} data - Dados do relatório
 * @param {boolean} isPreview - Se é uma prévia
 */
async function generatePaymentsPDF(data, isPreview = false) {
  try {
    // Carregar logos
    const systemLogo = await loadSystemLogo()
    const farmLogo = await loadFarmLogo()
    
    console.log("Status das logos (Payments PDF):")
    console.log("- Logo do sistema:", systemLogo ? "Carregada" : "Não carregada")
    console.log("- Logo da fazenda:", farmLogo ? "Carregada" : "Não carregada")
    console.log("- window.reportSettings:", window.reportSettings)

    const today = new Date().toLocaleDateString("pt-BR")
    const totalGross = data.reduce((sum, sale) => sum + (Number.parseFloat(sale.amount) || 0), 0)
    const totalNet = data.reduce((sum, sale) => sum + (Number.parseFloat(sale.amount) || 0), 0)
    const totalVolume = 0 // Volume não está disponível em financial_records

    // Criar novo documento PDF usando a sintaxe correta do jsPDF 2.x
    const { jsPDF } = window.jspdf
    const doc = new jsPDF()

    // Configurações
    const pageWidth = doc.internal.pageSize.getWidth()
    const pageHeight = doc.internal.pageSize.getHeight()
    const margin = 20
    let yPosition = margin

    // Adicionar logo da fazenda (canto superior direito) - posição fixa sem empurrar conteúdo
    if (farmLogo) {
      doc.addImage(farmLogo, "PNG", pageWidth - 60, 30, 40, 40)
    }

    // Adicionar marca d'água (logo da fazenda transparente no centro) - mais transparente
    if (farmLogo) {
      doc.saveGraphicsState()
      doc.setGState(new doc.GState({ opacity: 0.05 })) // Mudado de 0.1 para 0.05
      const watermarkSize = 150
      const watermarkX = (pageWidth - watermarkSize) / 2
      const watermarkY = (pageHeight - watermarkSize) / 2
      doc.addImage(farmLogo, "PNG", watermarkX, watermarkY, watermarkSize, watermarkSize)
      doc.restoreGraphicsState()
    }

    yPosition = 30 // Posição fixa para não ser afetada pelo logo

    // Título com nome da fazenda
    doc.setFontSize(18)
    doc.setFont("helvetica", "bold")
    const titleText = window.reportSettings?.farmName
      ? `RELATÓRIO DE PAGAMENTOS - ${window.reportSettings.farmName}`
      : "RELATÓRIO DE PAGAMENTOS"
    doc.text(titleText, margin, yPosition)
    yPosition += 20

    // Data do relatório
    doc.setFontSize(12)
    doc.setFont("helvetica", "normal")
    doc.text(`Relatório gerado em: ${today}`, margin, yPosition)
    yPosition += 20

    // Resumo
    doc.setFontSize(14)
    doc.setFont("helvetica", "bold")
    doc.text("RESUMO", margin, yPosition)
    yPosition += 10

    doc.setFontSize(12)
    doc.setFont("helvetica", "normal")
    doc.text(`Volume Total: ${totalVolume.toFixed(2)} L`, margin, yPosition)
    yPosition += 8
    doc.text(`Valor Bruto Total: R$ ${totalGross.toFixed(2)}`, margin, yPosition)
    yPosition += 8
    doc.text(`Valor Líquido Total: R$ ${totalNet.toFixed(2)}`, margin, yPosition)
    yPosition += 20

    // Detalhamento
    doc.setFontSize(14)
    doc.setFont("helvetica", "bold")
    doc.text("DETALHAMENTO DOS REGISTROS", margin, yPosition)
    yPosition += 15

    // Cabeçalho da tabela
    doc.setFontSize(10)
    doc.setFont("helvetica", "bold")
    const headers = ["Data", "Descrição", "Categoria", "Valor", "Status"]
    const colWidths = [30, 40, 30, 30, 25]
    let xPosition = margin

    headers.forEach((header, index) => {
      doc.text(header, xPosition, yPosition)
      xPosition += colWidths[index]
    })
    yPosition += 8

    // Linha separadora
    doc.line(margin, yPosition, pageWidth - margin, yPosition)
    yPosition += 5

    // Dados da tabela
    doc.setFont("helvetica", "normal")
    data.forEach((record, index) => {
      if (yPosition > pageHeight - 30) {
        doc.addPage()
        yPosition = margin
      }

      xPosition = margin
      const rowData = [
        new Date(record.record_date || record.created_at).toLocaleDateString("pt-BR"),
        (record.description || 'Receita').substring(0, 20),
        record.category || 'venda_leite',
        `R$ ${(Number.parseFloat(record.amount) || 0).toFixed(2)}`,
        "Realizado",
      ]

      rowData.forEach((cell, cellIndex) => {
        doc.text(String(cell), xPosition, yPosition)
        xPosition += colWidths[cellIndex]
      })
      yPosition += 6
    })

    // Rodapé com logo menor do sistema
    const footerY = pageHeight - 30
    if (systemLogo) {
      doc.addImage(systemLogo, "PNG", pageWidth / 2 - 5, footerY - 10, 10, 10) // Reduzido para 10x10
    }
    doc.setFontSize(8)
    doc.setFont("helvetica", "italic")
    doc.text("Sistema de Gestão Leiteira - LacTech", pageWidth / 2, footerY + 10, { align: "center" })

    // Marca d'água se for prévia
    if (isPreview) {
      doc.setFontSize(50)
      doc.setTextColor(255, 0, 0, 0.3)
      doc.text("PRÉVIA", pageWidth / 2, pageHeight / 2, {
        align: "center",
        angle: 45,
      })
    }

    // Download do PDF
    doc.save(`relatorio_financeiro_${new Date().toISOString().split("T")[0]}.pdf`)

    if (!isPreview) {
      window.showNotification("Relatório financeiro gerado com sucesso!", "success")
    }
  } catch (error) {
    console.error("Erro ao gerar PDF:", error)
    window.showNotification("Erro ao gerar PDF: " + error.message, "error")
  }
}
