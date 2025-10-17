// Sistema Veterinário - Gestão de Animais, Tratamentos e Inseminação Artificial

// Configuração do Supabase
const SUPABASE_CONFIG = {
  url: "https://tmaamwuyucaspqcrhuck.supabase.co",
  key: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InRtYWFtd3V5dWNhc3BxY3JodWNrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTY2OTY1MzMsImV4cCI6MjA3MjI3MjUzM30.AdDXp0xrX_xKutFHQrJ47LhFdLTtanTSku7fcK1eTB0",
}

// Aplicação Principal do Veterinário
const VetApp = {
  // Estado da aplicação
  state: {
    supabaseClient: null,
    currentUser: null,
    currentFarm: null,
    isLoading: false,
  },

     // Inicialização da aplicação
   async init() {
     try {
       console.log("🔧 Iniciando aplicação veterinária...")
       await this.waitForSupabase()
       this.state.supabaseClient = window.supabase.createClient(SUPABASE_CONFIG.url, SUPABASE_CONFIG.key)
       console.log("✅ Supabase conectado")

       await this.loadUserData()
       await this.loadDashboardData()
       this.setupEventListeners()
       this.setCurrentDate()
       console.log("✅ Aplicação inicializada com sucesso")
     } catch (error) {
       console.error("❌ Erro na inicialização:", error)
       this.showNotification("Erro ao inicializar aplicação: " + error.message, "error")
     }
   },

  // Aguardar Supabase estar disponível
  async waitForSupabase() {
    let attempts = 0
    while (!window.supabase && attempts < 20) {
      await new Promise((resolve) => setTimeout(resolve, 500))
      attempts++
    }
    if (!window.supabase) {
      throw new Error("Supabase não disponível")
    }
  },

     // Carregar dados do usuário
   async loadUserData() {
     try {
       console.log("👤 Carregando dados do usuário...")
       const {
         data: { user },
         error,
       } = await this.state.supabaseClient.auth.getUser()
       if (error || !user) {
         throw new Error("Usuário não autenticado")
       }

       this.state.currentUser = user
       console.log("✅ Usuário autenticado:", user.email)

       // Buscar dados do usuário e fazenda
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id, name, whatsapp, role")
         .eq("id", user.id)
         .single()

       if (userError) throw userError
       console.log("✅ Dados do usuário carregados:", userData)

       // Buscar nome da fazenda
       if (userData.farm_id) {
         const { data: farmData, error: farmError } = await this.state.supabaseClient
           .from("farms")
           .select("name")
           .eq("id", userData.farm_id)
           .single()

         if (!farmError && farmData) {
           this.state.currentFarm = farmData
           console.log("✅ Dados da fazenda carregados:", farmData)
         }
       }

       // Atualizar interface
       this.updateUserInterface(userData)
     } catch (error) {
       console.error("❌ Erro ao carregar dados do usuário:", error)
       this.showNotification("Erro ao carregar dados do usuário: " + error.message, "error")
     }
   },

  // Atualizar interface com dados do usuário
  updateUserInterface(userData) {
    const farmName = this.state.currentFarm?.name || "Lagoa do Mato"
    const vetName = userData?.name || this.state.currentUser?.email?.split("@")[0] || "Veterinário"
    const formalName = this.extractFormalName(vetName)

         // Atualizar elementos da interface
     const elements = {
       farmNameHeader: farmName,
       vetName: formalName,
       vetWelcome: formalName,
       profileName: vetName,
       profileFullName: vetName,
       profileFarmName: farmName,
       profileEmail2: this.state.currentUser?.email || "",
       profilePhone: userData?.whatsapp || "Não informado",
       profileSpecialty: userData?.role === 'veterinario' ? "Medicina Veterinária" : userData?.role || "Funcionário",
     }

    Object.entries(elements).forEach(([id, value]) => {
      const element = document.getElementById(id)
      if (element) {
        element.textContent = value
      }
    })
  },

  // Extrair nome formal (segundo nome)
  extractFormalName(fullName) {
    if (!fullName || typeof fullName !== "string") {
      return "Veterinário"
    }

    const names = fullName.trim().split(/\s+/)

    if (names.length === 1) {
      return names[0]
    }

    if (names.length === 2) {
      return names[1]
    }

    const skipWords = ["da", "de", "do", "das", "dos", "di", "del", "della", "delle", "delli"]
    let formalName = ""
    let nameCount = 0

    for (let i = 0; i < names.length; i++) {
      const name = names[i].toLowerCase()

      if (skipWords.includes(name)) {
        continue
      }

      nameCount++

      if (nameCount === 2) {
        formalName = names[i]
        break
      }
    }

    if (!formalName && names.length >= 2) {
      formalName = names[1]
    }

    if (!formalName) {
      formalName = names[0]
    }

    return formalName.charAt(0).toUpperCase() + formalName.slice(1).toLowerCase()
  },

  // Carregar dados do dashboard
  async loadDashboardData() {
    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) return

             // Carregar estatísticas de saúde (query simplificada)
       try {
         console.log("🔍 Tentando carregar estatísticas de saúde...")
         
         const { data: healthStats, error: healthError } = await this.state.supabaseClient
           .from("animal_health_records")
           .select("id, health_status")
           .eq("farm_id", userData.farm_id)

         if (healthError) {
           console.error("❌ Erro ao carregar estatísticas de saúde:", healthError)
           // Não vamos retornar aqui, vamos continuar para carregar outros dados
         }

         console.log("✅ Estatísticas de saúde carregadas:", healthStats)

         // Sempre atualizar os elementos, mesmo se não houver dados
         const healthyCount = healthStats ? healthStats.filter(record => record.health_status === "Saudável").length : 0
         const warningCount = healthStats ? healthStats.filter(record => record.health_status === "Em Tratamento").length : 0
         const criticalCount = healthStats ? healthStats.filter(record => record.health_status === "Doente").length : 0

         console.log("📊 Contadores de saúde:", { healthyCount, warningCount, criticalCount })

         // Atualizar elementos se existirem (usando IDs corretos do HTML)
         const healthyElement = document.getElementById("healthyAnimals")
         const warningElement = document.getElementById("warningAnimals")
         const criticalElement = document.getElementById("criticalAnimals")

         console.log("🔍 Elementos encontrados:", {
           healthy: !!healthyElement,
           warning: !!warningElement,
           critical: !!criticalElement
         })

         if (healthyElement) healthyElement.textContent = healthyCount
         if (warningElement) warningElement.textContent = warningCount
         if (criticalElement) criticalElement.textContent = criticalCount

         console.log("✅ Indicadores de saúde atualizados no DOM")
       } catch (error) {
         console.error("❌ Erro ao carregar estatísticas de saúde:", error)
       }

       // Carregar tratamentos ativos
       try {
         console.log("🔍 Carregando tratamentos para farm_id:", userData.farm_id)
         const { data: tableInfo, error: tableError } = await this.state.supabaseClient
           .from("treatments")
           .select("*")
           .limit(1)

         if (tableError) {
           console.error("❌ Erro ao acessar tabela treatments:", tableError)
           console.log("🔍 Verificando se a tabela existe...")
           
           // Se a tabela não existir, vamos pular esta parte
           const treatmentsContainer = document.getElementById("activeTreatments")
           if (treatmentsContainer) {
             treatmentsContainer.innerHTML = '<p class="text-gray-500 text-sm">Tabela de tratamentos não encontrada</p>'
           }
           return
         }

         console.log("✅ Estrutura da tabela treatments:", tableInfo)

         // Agora vamos tentar a query real com colunas que existem
         const { data: activeTreatments, error: treatmentError } = await this.state.supabaseClient
           .from("treatments")
           .select("id, treatment_date, description, observations")
           .eq("farm_id", userData.farm_id)
           .limit(5)

         if (treatmentError) {
           console.error("❌ Erro ao carregar tratamentos:", treatmentError)
           return
         }

         console.log("✅ Tratamentos carregados:", activeTreatments)

         const treatmentsContainer = document.getElementById("activeTreatments")
         if (treatmentsContainer) {
           if (!activeTreatments || activeTreatments.length === 0) {
             treatmentsContainer.innerHTML = '<p class="text-gray-500 text-sm">Nenhum tratamento encontrado</p>'
           } else {
             treatmentsContainer.innerHTML = activeTreatments
               .map(treatment => `
                 <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                   <div>
                     <p class="font-medium text-gray-900">Tratamento ${treatment.id}</p>
                     <p class="text-sm text-gray-600">${treatment.description || "Sem descrição"}</p>
                     <p class="text-xs text-gray-500">${treatment.observations || "Sem observações"}</p>
                     <p class="text-xs text-gray-400">${treatment.treatment_date ? new Date(treatment.treatment_date).toLocaleDateString('pt-BR') : "Data não informada"}</p>
                   </div>
                   <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Tratamento</span>
                 </div>
               `)
               .join("")
           }
         }
       } catch (error) {
         console.error("❌ Erro ao carregar tratamentos:", error)
       }

      // Preencher lista de animais para registro rápido
      await this.loadQuickHealthAnimals(userData.farm_id)
      
      // Carregar estatísticas de inseminação
      await this.loadInseminationStats()
    } catch (error) {
      console.error("Erro ao carregar dados do dashboard:", error)
    }
  },

  // Carregar estatísticas de inseminação
  async loadInseminationStats() {
    try {
      console.log("🔍 Carregando estatísticas de inseminação...")
      
      const { data: userData, error: userError } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (userError || !userData?.farm_id) {
        console.log("❌ Erro ao carregar dados do usuário para estatísticas:", userError)
        return
      }

      console.log("✅ Farm ID para estatísticas:", userData.farm_id)

      const { data: inseminations, error } = await this.state.supabaseClient
        .from("artificial_inseminations")
        .select("pregnancy_confirmed")
        .eq("farm_id", userData.farm_id)

      if (error) {
        console.error("❌ Erro ao carregar inseminações:", error)
        return
      }

      console.log("✅ Inseminações carregadas:", inseminations)

      if (inseminations) {
        const total = inseminations.length
        const confirmed = inseminations.filter((i) => i.pregnancy_confirmed === true).length
        const pending = inseminations.filter((i) => i.pregnancy_confirmed === null).length
        const successRate = total > 0 ? Math.round((confirmed / total) * 100) : 0

        console.log("📊 Estatísticas calculadas:", { total, confirmed, pending, successRate })

        this.updateElement("totalInseminations", total)
        this.updateElement("confirmedPregnancies", confirmed)
        this.updateElement("pendingConfirmations", pending)
        this.updateElement("successRate", successRate + "%")

        console.log("✅ Elementos atualizados no DOM")
      }
    } catch (error) {
      console.error("❌ Erro ao carregar estatísticas de inseminação:", error)
    }
  },

  // Atualizar elemento do DOM
  updateElement(id, value) {
    const element = document.getElementById(id)
    if (element) {
      element.textContent = value
    }
  },

     // Configurar event listeners
   setupEventListeners() {
     console.log("🔧 Configurando event listeners...")
     
     // Navegação por abas
     const navItems = document.querySelectorAll(".nav-item, .mobile-nav-item")
     console.log("📱 Nav items encontrados:", navItems.length)
     navItems.forEach((item) => {
       item.addEventListener("click", (e) => {
         const targetTab = e.target.getAttribute("data-tab")
         console.log("🔄 Mudando para aba:", targetTab)
         this.showTab(targetTab)
       })
     })

     // Formulários
     this.setupFormListeners()

     // Filtros
     this.setupFilterListeners()

     // Cálculo automático de data de parto
     const inseminationDateInput = document.getElementById("inseminationDate")
     if (inseminationDateInput) {
       inseminationDateInput.addEventListener("change", (e) => {
         const expectedDate = this.calculateExpectedBirthDate(e.target.value)
         this.updateElement("expectedBirthDate", expectedDate)
       })
     }
     
     console.log("✅ Event listeners configurados")
   },

        // Configurar listeners dos formulários
   setupFormListeners() {
     console.log("📝 Configurando listeners dos formulários...")
     const forms = [
       { id: "quickHealthForm", handler: this.handleQuickHealth.bind(this) },
       { id: "treatmentForm", handler: this.handleTreatmentRegister.bind(this) },
       { id: "inseminationForm", handler: this.handleInseminationRegister.bind(this) },
       { id: "pregnancyConfirmationForm", handler: this.handlePregnancyConfirmation.bind(this) },
       { id: "changePasswordForm", handler: this.handlePasswordChange.bind(this) },
       { id: "addAnimalForm", handler: this.handleAddAnimal.bind(this) },
       { id: "addTreatmentForm", handler: this.handleAddTreatment.bind(this) },
       { id: "addInseminationForm", handler: this.handleAddInsemination.bind(this) },
       { id: "healthStatusForm", handler: this.handleHealthStatusUpdate.bind(this) },
     ]

     let formsFound = 0
     forms.forEach(({ id, handler }) => {
       const form = document.getElementById(id)
       if (form) {
         form.addEventListener("submit", handler)
         formsFound++
         console.log("✅ Formulário configurado:", id)
       } else {
         console.log("❌ Formulário não encontrado:", id)
       }
     })
     
     console.log(`📊 Total de formulários configurados: ${formsFound}/${forms.length}`)
   },

  // Configurar listeners dos filtros
  setupFilterListeners() {
    const filters = ["animalFilter", "treatmentFilter", "inseminationFilter"]
    filters.forEach((filterId) => {
      const filter = document.getElementById(filterId)
      if (filter) {
        filter.addEventListener("change", () => {
          // Implementar lógica de filtro conforme necessário
        })
      }
    })
  },

  // Mostrar aba específica
  showTab(tabName) {
    const navItems = document.querySelectorAll(".nav-item, .mobile-nav-item")
    const tabContents = document.querySelectorAll(".tab-content")

    navItems.forEach((nav) => nav.classList.remove("active"))
    tabContents.forEach((content) => content.classList.add("hidden"))

    const targetNav = document.querySelector(`[data-tab="${tabName}"]`)
    if (targetNav) {
      targetNav.classList.add("active")
    }

    const targetTab = document.getElementById(tabName + "-tab")
    if (targetTab) {
      targetTab.classList.remove("hidden")
    }

         // Carregar dados específicos da aba
     setTimeout(() => {
       if (tabName === "animals") {
         this.loadAnimalsList()
       } else if (tabName === "treatments") {
         this.loadTreatmentsList()
       } else if (tabName === "insemination") {
         this.loadInseminationsList()
         this.loadPendingInseminations()
       }
     }, 100)
  },

     // Manipular registro rápido de saúde
   async handleQuickHealth(e) {
     e.preventDefault()
     console.log("🏥 Registro rápido de saúde...")
     const formData = new FormData(e.target)

     try {
       const { data: userData } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (!userData?.farm_id) {
         throw new Error("Fazenda não encontrada")
       }

       const { error } = await this.state.supabaseClient.from("animal_health_records").insert([
         {
           animal_id: formData.get("animal_id"),
           health_status: formData.get("health_status"),
           user_id: this.state.currentUser.id,
           farm_id: userData.farm_id,
           assessment_date: new Date().toISOString().split("T")[0],
           symptoms: null,
           diagnosis: null,
           recommendations: null,
           veterinarian: this.state.currentUser?.email || "Veterinário",
           created_at: new Date().toISOString(),
           updated_at: new Date().toISOString()
         },
       ])

       if (error) throw error

       console.log("✅ Status de saúde registrado com sucesso!")
       this.showNotification("Status de saúde registrado com sucesso!", "success")
       e.target.reset()
       
       // Recarregar dados do dashboard
       await this.loadDashboardData()
     } catch (error) {
       console.error("❌ Erro no registro rápido:", error)
       this.showNotification("Erro ao registrar status de saúde: " + error.message, "error")
     }
   },

  // Manipular registro de tratamento
  async handleTreatmentRegister(e) {
    e.preventDefault()
    const formData = new FormData(e.target)

    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) {
        throw new Error("Fazenda não encontrada")
      }

      const treatmentData = {
        animal_id: formData.get("animal_id"),
        farm_id: userData.farm_id,
        user_id: this.state.currentUser.id,
        treatment_date: formData.get("treatment_date"),
        description: formData.get("treatment_type"),
        medication: formData.get("medication"),
        dosage: formData.get("dosage"),
        observations: formData.get("observations"),
        next_treatment_date: formData.get("next_treatment_date") || null,
      }

      const { error } = await this.state.supabaseClient.from("treatments").insert([treatmentData])

      if (error) throw error

      this.showNotification("Tratamento registrado com sucesso!", "success")
      e.target.reset()
      this.setCurrentDate()
      await this.loadDashboardData()
    } catch (error) {
      this.showNotification("Erro ao registrar tratamento: " + error.message, "error")
    }
  },

  // Manipular registro de inseminação
  async handleInseminationRegister(e) {
    e.preventDefault()
    const formData = new FormData(e.target)

    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) {
        throw new Error("Fazenda não encontrada")
      }

      const inseminationData = {
        animal_id: formData.get("animal_id"),
        farm_id: userData.farm_id,
        user_id: this.state.currentUser.id,
        insemination_date: formData.get("insemination_date"),
        semen_batch: formData.get("semen_batch"),
        semen_origin: formData.get("semen_origin") || null,
        bull_identification: formData.get("bull_identification") || null,
        technician_name: formData.get("technician_name") || null,
        technique_used: formData.get("technique_used") || null,
        body_condition_score: formData.get("body_condition_score")
          ? Number.parseInt(formData.get("body_condition_score"))
          : null,
        expected_calving_date: formData.get("expected_calving_date") || null,
        observations: formData.get("observations") || null,
      }

      const { error } = await this.state.supabaseClient.from("artificial_inseminations").insert([inseminationData])

      if (error) throw error

      this.showNotification("Inseminação registrada com sucesso!", "success")
      e.target.reset()
      await this.loadInseminationStats()
      await this.loadInseminationsList()
      await this.loadPendingInseminations()
    } catch (error) {
      this.showNotification("Erro ao registrar inseminação: " + error.message, "error")
    }
  },

  // Manipular confirmação de gravidez
  async handlePregnancyConfirmation(e) {
    e.preventDefault()
    const formData = new FormData(e.target)

    try {
      const { error } = await this.state.supabaseClient
        .from("artificial_inseminations")
        .update({
          pregnancy_confirmed: formData.get("pregnancy_confirmed") === "true",
          pregnancy_confirmation_date: formData.get("pregnancy_confirmation_date"),
        })
        .eq("id", formData.get("insemination_id"))

      if (error) throw error

      this.showNotification("Status de gravidez atualizado com sucesso!", "success")
      e.target.reset()
      await this.loadInseminationStats()
      await this.loadInseminationsList()
      await this.loadPendingInseminations()
    } catch (error) {
      this.showNotification("Erro ao confirmar gravidez: " + error.message, "error")
    }
  },

  // Manipular alteração de senha
  async handlePasswordChange(e) {
    e.preventDefault()
    const formData = new FormData(e.target)
    const newPassword = formData.get("new_password")
    const confirmPassword = formData.get("confirm_password")

    if (newPassword !== confirmPassword) {
      this.showNotification("As senhas não coincidem", "error")
      return
    }

    try {
      const { error } = await this.state.supabaseClient.auth.updateUser({
        password: newPassword,
      })

      if (error) throw error

      this.showNotification("Senha alterada com sucesso!", "success")
      e.target.reset()
    } catch (error) {
      this.showNotification("Erro ao alterar senha: " + error.message, "error")
    }
  },

  // Carregar lista de inseminações
  async loadInseminationsList() {
    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) return

      const { data: inseminations, error } = await this.state.supabaseClient
        .from("artificial_inseminations")
        .select("*")
        .eq("farm_id", userData.farm_id)
        .order("insemination_date", { ascending: false })

      if (error) throw error

      const container = document.getElementById("inseminationsList")
      if (!container) return

      if (!inseminations || inseminations.length === 0) {
        container.innerHTML = this.getEmptyStateHTML("insemination")
        return
      }

      container.innerHTML = inseminations
        .map((insemination) => {
          return this.createInseminationCard(insemination)
        })
        .join("")
    } catch (error) {
      this.showNotification("Erro ao carregar inseminações: " + error.message, "error")
    }
  },

  // Carregar inseminações pendentes para o select
  async loadPendingInseminations() {
    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) return

      const { data: inseminations, error } = await this.state.supabaseClient
        .from("artificial_inseminations")
        .select("id, animal_id, insemination_date, semen_batch")
        .eq("farm_id", userData.farm_id)
        .is("pregnancy_confirmed", null)
        .order("insemination_date", { ascending: false })

      if (error) throw error

      const select = document.getElementById("inseminationSelect")
      if (!select) return

      select.innerHTML = '<option value="">Selecione uma inseminação...</option>'

      if (inseminations && inseminations.length > 0) {
        inseminations.forEach((insemination) => {
          const date = new Date(insemination.insemination_date).toLocaleDateString("pt-BR")
          const option = document.createElement("option")
          option.value = insemination.id
          option.textContent = `${insemination.animal_id} - ${date} - Lote: ${insemination.semen_batch}`
          select.appendChild(option)
        })
      }
    } catch (error) {
      // Silenciar erro
    }
  },

  // Criar card de inseminação
  createInseminationCard(insemination) {
    const date = new Date(insemination.insemination_date).toLocaleDateString("pt-BR")
    const expectedBirth = insemination.expected_calving_date
      ? new Date(insemination.expected_calving_date).toLocaleDateString("pt-BR")
      : "N/A"

    const statusBadge =
      insemination.pregnancy_confirmed === true
        ? '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">Confirmada</span>'
        : insemination.pregnancy_confirmed === false
          ? '<span class="px-2 py-1 bg-red-100 text-red-800 text-xs font-medium rounded-full">Não Confirmada</span>'
          : '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">Pendente</span>'

    return `
            <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h4 class="font-semibold text-slate-900">Animal: ${insemination.animal_id}</h4>
                            ${statusBadge}
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-slate-600">
                            <div><strong>Data IA:</strong> ${date}</div>
                            <div><strong>Lote:</strong> ${insemination.semen_batch}</div>
                            <div><strong>Touro:</strong> ${insemination.bull_identification || "N/A"}</div>
                            <div><strong>Parto Prev.:</strong> ${expectedBirth}</div>
                        </div>
                        ${insemination.technician_name ? `<div class="text-sm text-slate-600 mt-1"><strong>Técnico:</strong> ${insemination.technician_name}</div>` : ""}
                        ${insemination.observations ? `<div class="text-sm text-slate-600 mt-1"><strong>Obs:</strong> ${insemination.observations}</div>` : ""}
                    </div>
                    <div class="flex gap-2">
                        ${
                          insemination.pregnancy_confirmed === null
                            ? `
                            <button onclick="VetApp.quickConfirmPregnancy('${insemination.id}', true)" class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700 transition-all">
                                Confirmar
                            </button>
                            <button onclick="VetApp.quickConfirmPregnancy('${insemination.id}', false)" class="px-3 py-1 bg-red-600 text-white text-xs rounded-lg hover:bg-red-700 transition-all">
                                Negar
                            </button>
                        `
                            : ""
                        }
                    </div>
                </div>
            </div>
        `
  },

  // Confirmação rápida de gravidez
  async quickConfirmPregnancy(inseminationId, isConfirmed) {
    try {
      const today = new Date().toISOString().split("T")[0]

      const { error } = await this.state.supabaseClient
        .from("artificial_inseminations")
        .update({
          pregnancy_confirmed: isConfirmed,
          pregnancy_confirmation_date: today,
        })
        .eq("id", inseminationId)

      if (error) throw error

      this.showNotification("Status de gravidez atualizado com sucesso!", "success")
      await this.loadInseminationStats()
      await this.loadInseminationsList()
      await this.loadPendingInseminations()
    } catch (error) {
      this.showNotification("Erro ao confirmar gravidez: " + error.message, "error")
    }
  },

  // Calcular data prevista de parto (280 dias após inseminação)
  calculateExpectedBirthDate(inseminationDate) {
    if (!inseminationDate) return ""
    const date = new Date(inseminationDate)
    date.setDate(date.getDate() + 280)
    return date.toISOString().split("T")[0]
  },

  // Definir data atual nos formulários
  setCurrentDate() {
    const now = new Date()
    const dateString = now.toISOString().split("T")[0]

    const dateInputs = ["startDate", "confirmationDate"]
    dateInputs.forEach((id) => {
      const input = document.getElementById(id)
      if (input) {
        input.value = dateString
      }
    })
  },

  // Resetar formulário de tratamento
  resetTreatmentForm() {
    const form = document.getElementById("treatmentForm")
    if (form) {
      form.reset()
      this.setCurrentDate()
    }
  },

  // Resetar formulário de inseminação
  resetInseminationForm() {
    const form = document.getElementById("inseminationForm")
    if (form) {
      form.reset()
      const expectedBirthInput = document.getElementById("expectedBirthDate")
      if (expectedBirthInput) {
        expectedBirthInput.value = ""
      }
    }
  },

  // Atualizar listas de inseminação
  async refreshInseminationsList() {
    await this.loadInseminationsList()
    await this.loadInseminationStats()
    await this.loadPendingInseminations()
  },

  // Obter HTML de estado vazio
  getEmptyStateHTML(type) {
    const configs = {
      insemination: {
        icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z",
        title: "Nenhuma Inseminação Registrada",
        description: "Registre inseminações para controlar o programa reprodutivo",
        action: "Use o formulário acima para adicionar uma nova inseminação",
      },
    }

    const config = configs[type]
    if (!config) return ""

    return `
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="${config.icon}"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">${config.title}</h3>
                <p class="text-gray-600 mb-4">${config.description}</p>
                <p class="text-gray-500 text-sm">${config.action}</p>
            </div>
        `
  },

     // Modais
   openAddAnimalModal() {
     console.log("🐄 Abrindo modal de adicionar animal...")
     const modal = document.getElementById("addAnimalModal")
     if (modal) {
       modal.classList.remove("hidden")
       console.log("✅ Modal de animal aberto")
       // Limpar formulário
       const form = modal.querySelector("form")
       if (form) form.reset()
     } else {
       console.log("❌ Modal de animal não encontrado")
     }
   },

   closeModal(modalId) {
     console.log("🔒 Fechando modal:", modalId)
     const modal = document.getElementById(modalId)
     if (modal) {
       console.log("🔍 Modal encontrado, classes antes:", modal.className)
       modal.classList.add("hidden")
       console.log("🔍 Modal encontrado, classes depois:", modal.className)
       
       
       console.log("✅ Modal fechado:", modalId)
     } else {
       console.log("❌ Modal não encontrado para fechar:", modalId)
     }
   },

   openAddTreatmentModal() {
     console.log("💊 Abrindo modal de adicionar tratamento...")
     const modal = document.getElementById("addTreatmentModal")
     if (modal) {
       modal.classList.remove("hidden")
       console.log("✅ Modal de tratamento aberto")
       // Limpar formulário
       const form = modal.querySelector("form")
       if (form) form.reset()
       // Definir data atual
       this.setCurrentDate()
     } else {
       console.log("❌ Modal de tratamento não encontrado")
     }
   },

   openAddInseminationModal() {
     console.log("🐄 Abrindo modal de adicionar inseminação...")
     const modal = document.getElementById("addInseminationModal")
     if (modal) {
       modal.classList.remove("hidden")
       console.log("✅ Modal de inseminação aberto")
       // Limpar formulário
       const form = modal.querySelector("form")
       if (form) form.reset()
       // Definir data atual
       this.setCurrentDate()
       // Carregar lista de animais para o dropdown
       this.loadAnimalsForModal("addInseminationModal")
     } else {
       console.log("❌ Modal de inseminação não encontrado")
     }
   },

  // Modal de perfil
  openProfileModal() {
    const modal = document.getElementById("profileModal")
    if (modal) {
      modal.classList.remove("hidden")
    }
  },

        closeProfileModal() {
     const modal = document.getElementById("profileModal")
     if (modal) {
       modal.classList.add("hidden")
     }
   },

         closeModal(modalId) {
     const modal = document.getElementById(modalId)
     if (modal) {
       modal.classList.add("hidden")
     }
   },

   // Funções de gerenciamento de animais
   async handleAddAnimal(e) {
     e.preventDefault()
     console.log("🐄 Tentando adicionar animal...")
     
     try {
       const formData = new FormData(e.target)
       console.log("📝 Dados do formulário:", {
         identification: formData.get("identification"),
         name: formData.get("name"),
         breed: formData.get("breed"),
         gender: formData.get("gender")
       })
       
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (userError || !userData?.farm_id) {
         throw new Error("Fazenda não encontrada")
       }

       // Vamos usar apenas as colunas que existem na tabela animals
       const animalData = {
         farm_id: userData.farm_id,
         identification: formData.get("identification"),
         name: formData.get("name") || null,
         breed: formData.get("breed") || null,
         birth_date: formData.get("birth_date") || null,
         created_at: new Date().toISOString(),
         updated_at: new Date().toISOString()
       }

       console.log("💾 Dados do animal para inserir:", animalData)

       const { error } = await this.state.supabaseClient
         .from("animals")
         .insert([animalData])

       if (error) throw error

       console.log("✅ Animal adicionado com sucesso!")
       this.showNotification("Animal adicionado com sucesso!", "success")
       this.closeModal("addAnimalModal")
       
       // Recarregar dados do dashboard
       await this.loadDashboardData()
       
       // Se estiver na aba de animais, recarregar lista
       const activeTab = document.querySelector(".nav-item.active")
       if (activeTab && activeTab.dataset.tab === "animals") {
         await this.loadAnimalsList()
       }
     } catch (error) {
       console.error("❌ Erro ao adicionar animal:", error)
       this.showNotification("Erro ao adicionar animal: " + error.message, "error")
     }
   },

   // Funções de gerenciamento de tratamentos
   async handleAddTreatment(e) {
     e.preventDefault()
     console.log("💊 Tentando adicionar tratamento...")
     
     try {
       const formData = new FormData(e.target)
       console.log("📝 Dados do formulário de tratamento:", {
         animal_id: formData.get("animal_id"),
         treatment_type: formData.get("treatment_type"),
         start_date: formData.get("start_date")
       })
       
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (userError || !userData?.farm_id) {
         throw new Error("Fazenda não encontrada")
       }

       // Vamos usar apenas as colunas que existem na tabela treatments
       const treatmentData = {
         farm_id: userData.farm_id,
         animal_id: formData.get("animal_id"),
         description: formData.get("treatment_type") || "Tratamento",
         medication: formData.get("medication") || null,
         dosage: formData.get("dosage") || null,
         treatment_date: formData.get("start_date"),
         next_treatment_date: formData.get("end_date") || null,
         observations: formData.get("observations") || null,
         created_at: new Date().toISOString(),
         updated_at: new Date().toISOString()
       }

       console.log("💾 Dados do tratamento para inserir:", treatmentData)

       const { error } = await this.state.supabaseClient
         .from("treatments")
         .insert([treatmentData])

       if (error) throw error

       console.log("✅ Tratamento adicionado com sucesso!")
       this.showNotification("Tratamento adicionado com sucesso!", "success")
       this.closeModal("addTreatmentModal")
       
       // Recarregar dados do dashboard
       await this.loadDashboardData()
       
       // Se estiver na aba de tratamentos, recarregar lista
       const activeTab = document.querySelector(".nav-item.active")
       if (activeTab && activeTab.dataset.tab === "treatments") {
         await this.loadTreatmentsList()
       }
     } catch (error) {
       console.error("❌ Erro ao adicionar tratamento:", error)
       this.showNotification("Erro ao adicionar tratamento: " + error.message, "error")
     }
   },

   // Funções de gerenciamento de inseminação
   async handleAddInsemination(e) {
     e.preventDefault()
     console.log("🐄 Tentando adicionar inseminação...")
     
     try {
       const formData = new FormData(e.target)
       console.log("📝 Dados do formulário de inseminação:", {
         animal_id: formData.get("animal_id"),
         insemination_date: formData.get("insemination_date"),
         semen_type: formData.get("semen_type")
       })
       
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (userError || !userData?.farm_id) {
         throw new Error("Fazenda não encontrada")
       }

       // Vamos usar apenas as colunas que existem na tabela artificial_inseminations
       const inseminationData = {
         farm_id: userData.farm_id,
         animal_id: formData.get("animal_id"),
         insemination_date: formData.get("insemination_date"),
         semen_batch: formData.get("semen_type") || null,
         semen_origin: formData.get("bull_breed") || null,
         bull_identification: formData.get("bull_breed") || null,
         technician_name: this.state.currentUser?.name || "Veterinário",
         technique_used: "Inseminação Artificial",
         estrus_detection_method: "Observação",
         body_condition_score: 3.0,
         expected_calving_date: this.calculateExpectedBirthDate(formData.get("insemination_date")),
         pregnancy_confirmed: false,
         pregnancy_confirmation_date: null,
         pregnancy_confirmation_result: null,
         observations: formData.get("observations") || null,
         success_rate_notes: null,
         created_at: new Date().toISOString(),
         updated_at: new Date().toISOString()
       }

       console.log("💾 Dados da inseminação para inserir:", inseminationData)

       const { error } = await this.state.supabaseClient
         .from("artificial_inseminations")
         .insert([inseminationData])

       if (error) throw error

       console.log("✅ Inseminação registrada com sucesso!")
       this.showNotification("Inseminação registrada com sucesso!", "success")
       this.closeModal("addInseminationModal")
       
       // Recarregar dados de inseminação
       await this.loadInseminationStats()
       await this.loadInseminationsList()
       await this.loadPendingInseminations()
     } catch (error) {
       console.error("❌ Erro ao registrar inseminação:", error)
       this.showNotification("Erro ao registrar inseminação: " + error.message, "error")
     }
   },

   // Função para confirmar gravidez
   async handlePregnancyConfirmation(e) {
     e.preventDefault()
     const formData = new FormData(e.target)
   
     try {
       const { error } = await this.state.supabaseClient
         .from("artificial_inseminations")
         .update({
           pregnancy_confirmed: formData.get("pregnancy_confirmed") === "true",
           pregnancy_confirmation_date: formData.get("pregnancy_confirmation_date"),
           updated_at: new Date().toISOString()
         })
         .eq("id", formData.get("insemination_id"))
   
       if (error) throw error
   
       this.showNotification("Status de gravidez atualizado com sucesso!", "success")
       e.target.reset()
       
       // Recarregar dados de inseminação
       await this.loadInseminationStats()
       await this.loadInseminationsList()
       await this.loadPendingInseminations()
     } catch (error) {
       this.showNotification("Erro ao confirmar gravidez: " + error.message, "error")
     }
   },

   // Função para registrar status de saúde
   async handleHealthStatusUpdate(e) {
     e.preventDefault()
     console.log("🏥 Tentando registrar status de saúde...")
     
     try {
       const formData = new FormData(e.target)
       console.log("📝 Dados do formulário de status de saúde:", {
         animal_id: formData.get("animal_id"),
         assessment_date: formData.get("assessment_date"),
         health_status: formData.get("health_status")
       })
       
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (userError || !userData?.farm_id) {
         throw new Error("Fazenda não encontrada")
       }

       // Vamos usar apenas as colunas que existem na tabela animal_health_records
       const healthData = {
         farm_id: userData.farm_id,
         animal_id: formData.get("animal_id"),
         record_date: formData.get("assessment_date"),
         health_status: formData.get("health_status"),
         weight: null,
         temperature: null,
         observations: formData.get("symptoms") || formData.get("diagnosis") || formData.get("recommendations") || null,
         created_at: new Date().toISOString(),
         updated_at: new Date().toISOString()
       }

       console.log("💾 Dados de saúde para inserir:", healthData)

       const { error } = await this.state.supabaseClient
         .from("animal_health_records")
         .insert([healthData])

       if (error) throw error

       // Não vamos atualizar a tabela animals pois ela não tem health_status
       console.log("ℹ️ Status de saúde registrado na tabela animal_health_records")

       console.log("✅ Status de saúde registrado com sucesso!")
       this.showNotification("Status de saúde registrado com sucesso!", "success")
       this.closeModal("healthStatusModal")
       
       // Recarregar dados do dashboard
       await this.loadDashboardData()
     } catch (error) {
       console.error("❌ Erro ao registrar status de saúde:", error)
       this.showNotification("Erro ao registrar status de saúde: " + error.message, "error")
     }
   },

   // Carregar lista de animais
   async loadAnimalsList() {
     try {
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (userError || !userData?.farm_id) return

       const { data: animals, error } = await this.state.supabaseClient
         .from("animals")
         .select("*")
         .eq("farm_id", userData.farm_id)
         .order("created_at", { ascending: false })

       if (error) throw error

       const container = document.getElementById("animalsList")
       if (!container) return

       if (!animals || animals.length === 0) {
         container.innerHTML = this.getEmptyAnimalsStateHTML()
         return
       }

       container.innerHTML = animals
         .map((animal) => this.createAnimalCard(animal))
         .join("")
     } catch (error) {
       this.showNotification("Erro ao carregar lista de animais: " + error.message, "error")
     }
   },

   // Carregar lista de tratamentos
   async loadTreatmentsList() {
     try {
       const { data: userData, error: userError } = await this.state.supabaseClient
         .from("users")
         .select("farm_id")
         .eq("id", this.state.currentUser.id)
         .single()

       if (userError || !userData?.farm_id) return

       const { data: treatments, error } = await this.state.supabaseClient
         .from("treatments")
         .select("*")
         .eq("farm_id", userData.farm_id)
         .order("treatment_date", { ascending: false })

       if (error) throw error

       const container = document.getElementById("treatmentsList")
       if (!container) return

       if (!treatments || treatments.length === 0) {
         container.innerHTML = this.getEmptyTreatmentsStateHTML()
         return
       }

       container.innerHTML = treatments
         .map((treatment) => this.createTreatmentCard(treatment))
         .join("")
     } catch (error) {
       this.showNotification("Erro ao carregar lista de tratamentos: " + error.message, "error")
     }
   },

   // Criar card de animal
   createAnimalCard(animal) {
     const birthDate = animal.birth_date ? new Date(animal.birth_date).toLocaleDateString("pt-BR") : "N/A"
     const weight = animal.weight ? `${animal.weight} kg` : "N/A"
     
     const statusBadge = this.getHealthStatusBadge(animal.health_status)

     return `
       <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all">
         <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
           <div class="flex-1">
             <div class="flex items-center gap-3 mb-2">
               <h4 class="font-semibold text-slate-900">${animal.identification}</h4>
               ${statusBadge}
             </div>
             <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-slate-600">
               <div><strong>Nome:</strong> ${animal.name || "N/A"}</div>
               <div><strong>Raça:</strong> ${animal.breed || "N/A"}</div>
               <div><strong>Nascimento:</strong> ${birthDate}</div>
               <div><strong>Peso:</strong> ${weight}</div>
             </div>
             ${animal.observations ? `<div class="text-sm text-slate-600 mt-1"><strong>Obs:</strong> ${animal.observations}</div>` : ""}
           </div>
           <div class="flex gap-2">
             <button onclick="VetApp.openHealthStatusModal('${animal.id}')" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-all">
               Status
             </button>
             <button onclick="VetApp.openEditAnimalModal('${animal.id}')" class="px-3 py-1 bg-forest-600 text-white text-xs rounded-lg hover:bg-forest-700 transition-all">
               Editar
             </button>
           </div>
         </div>
       </div>
     `
   },

   // Criar card de tratamento
   createTreatmentCard(treatment) {
     const treatmentDate = new Date(treatment.treatment_date).toLocaleDateString("pt-BR")
     const nextDate = treatment.next_treatment_date ? new Date(treatment.next_treatment_date).toLocaleDateString("pt-BR") : "N/A"
     
     const statusBadge = this.getTreatmentStatusBadge(treatment.status)

     return `
       <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all">
         <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
           <div class="flex-1">
             <div class="flex items-center gap-3 mb-2">
               <h4 class="font-semibold text-slate-900">Animal: ${treatment.animal_id}</h4>
               ${statusBadge}
             </div>
             <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-slate-600">
               <div><strong>Tipo:</strong> ${treatment.treatment_type}</div>
               <div><strong>Data:</strong> ${treatmentDate}</div>
               <div><strong>Medicação:</strong> ${treatment.medication || "N/A"}</div>
               <div><strong>Próximo:</strong> ${nextDate}</div>
             </div>
             ${treatment.observations ? `<div class="text-sm text-slate-600 mt-1"><strong>Obs:</strong> ${treatment.observations}</div>` : ""}
           </div>
           <div class="flex gap-2">
             <button onclick="VetApp.openEditTreatmentModal('${treatment.id}')" class="px-3 py-1 bg-forest-600 text-white text-xs rounded-lg hover:bg-forest-700 transition-all">
               Editar
             </button>
           </div>
         </div>
       </div>
     `
   },

   // Obter badge de status de saúde
   getHealthStatusBadge(status) {
     const statusConfig = {
       "Saudável": "bg-green-100 text-green-800",
       "Em Tratamento": "bg-yellow-100 text-yellow-800",
       "Doente": "bg-red-100 text-red-800",
       "Quarentena": "bg-orange-100 text-orange-800"
     }
     
     const badgeClass = statusConfig[status] || "bg-gray-100 text-gray-800"
     return `<span class="px-2 py-1 ${badgeClass} text-xs font-medium rounded-full">${status}</span>`
   },

   // Obter badge de status de tratamento
   getTreatmentStatusBadge(status) {
     const statusConfig = {
       "Ativo": "bg-blue-100 text-blue-800",
       "Concluído": "bg-green-100 text-green-800",
       "Suspenso": "bg-yellow-100 text-yellow-800"
     }
     
     const badgeClass = statusConfig[status] || "bg-gray-100 text-gray-800"
     return `<span class="px-2 py-1 ${badgeClass} text-xs font-medium rounded-full">${status}</span>`
   },

   // Abrir modal de status de saúde
   openHealthStatusModal(animalId = null) {
     console.log("🏥 Abrindo modal de status de saúde...", animalId ? `para animal ${animalId}` : "")
     const modal = document.getElementById("healthStatusModal")
     if (modal) {
       modal.classList.remove("hidden")
       console.log("✅ Modal de status de saúde aberto")
       
       // Se um animal foi especificado, preencher o campo
       if (animalId) {
         const animalIdInput = modal.querySelector('select[name="animal_id"]')
         if (animalIdInput) {
           animalIdInput.value = animalId
         }
       }
       
       // Definir data atual
       const assessmentDateInput = modal.querySelector('input[name="assessment_date"]')
       if (assessmentDateInput) {
         assessmentDateInput.value = new Date().toISOString().split("T")[0]
       }
     } else {
       console.log("❌ Modal de status de saúde não encontrado")
     }
   },

   // Abrir modal de edição de animal
   openEditAnimalModal(animalId) {
     // Implementar edição de animal
     this.showNotification("Funcionalidade de edição será implementada em breve", "info")
   },

   // Abrir modal de edição de tratamento
   openEditTreatmentModal(treatmentId) {
     // Implementar edição de tratamento
     this.showNotification("Funcionalidade de edição será implementada em breve", "info")
   },

   // Obter HTML de estado vazio para animais
   getEmptyAnimalsStateHTML() {
     return `
       <div class="text-center py-12">
         <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
           <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
             <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
           </svg>
         </div>
         <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum Animal Cadastrado</h3>
         <p class="text-gray-600 mb-4">Comece adicionando animais ao sistema</p>
         <button onclick="VetApp.openAddAnimalModal()" class="px-6 py-3 gradient-forest text-white font-semibold rounded-xl hover:shadow-lg transition-all">
           <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
             <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
           </svg>
           Adicionar Primeiro Animal
         </button>
       </div>
     `
   },

   // Obter HTML de estado vazio para tratamentos
   getEmptyTreatmentsStateHTML() {
     return `
       <div class="text-center py-12">
         <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
           <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
             <path stroke-linecap="round" stroke-linejoin="round" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
           </svg>
         </div>
         <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum Tratamento Ativo</h3>
         <p class="text-gray-600 mb-4">Registre tratamentos para acompanhar a saúde dos animais</p>
         <p class="text-gray-500 text-sm">Use o formulário acima para adicionar um novo tratamento</p>
       </div>
     `
   },

   // Relatórios
  async generateHealthReport() {
    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) {
        throw new Error("Fazenda não encontrada")
      }

      const { data: healthData, error } = await this.state.supabaseClient
        .from("animal_health_records")
        .select("*")
        .eq("farm_id", userData.farm_id)
        .order("record_date", { ascending: false })

      if (error) throw error

      await this.generateHealthPDF(healthData)
      this.showNotification("Relatório de Saúde gerado com sucesso!", "success")
    } catch (error) {
      this.showNotification("Erro ao gerar relatório de saúde: " + error.message, "error")
    }
  },

  async generateTreatmentReport() {
    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) {
        throw new Error("Fazenda não encontrada")
      }

      const { data: treatmentData, error } = await this.state.supabaseClient
        .from("treatments")
        .select("*")
        .eq("farm_id", userData.farm_id)
        .order("treatment_date", { ascending: false })

      if (error) throw error

      await this.generateTreatmentPDF(treatmentData)
      this.showNotification("Relatório de Tratamentos gerado com sucesso!", "success")
    } catch (error) {
      this.showNotification("Erro ao gerar relatório de tratamentos: " + error.message, "error")
    }
  },

  async generateVaccinationReport() {
    try {
      const { data: userData } = await this.state.supabaseClient
        .from("users")
        .select("farm_id")
        .eq("id", this.state.currentUser.id)
        .single()

      if (!userData?.farm_id) {
        throw new Error("Fazenda não encontrada")
      }

      const { data: vaccinationData, error } = await this.state.supabaseClient
        .from("treatments")
        .select("*")
        .eq("farm_id", userData.farm_id)
        .ilike("description", "%vacin%")
        .order("treatment_date", { ascending: false })

      if (error) throw error

      await this.generateVaccinationPDF(vaccinationData)
      this.showNotification("Relatório de Vacinação gerado com sucesso!", "success")
    } catch (error) {
      this.showNotification("Erro ao gerar relatório de vacinação: " + error.message, "error")
    }
  },

  // Geração de PDFs
  async generateHealthPDF(data) {
    try {
      const { jsPDF } = window.jspdf
      const doc = new jsPDF()

      const pageWidth = doc.internal.pageSize.getWidth()
      const pageHeight = doc.internal.pageSize.getHeight()
      const margin = 20
      let yPosition = margin

      // Título
      doc.setFontSize(18)
      doc.setFont("helvetica", "bold")
      const titleText = `RELATÓRIO DE SAÚDE ANIMAL - ${this.state.currentFarm?.name || "Fazenda"}`
      doc.text(titleText, margin, yPosition)
      yPosition += 20

      // Data do relatório
      doc.setFontSize(12)
      doc.setFont("helvetica", "normal")
      const today = new Date().toLocaleDateString("pt-BR")
      doc.text(`Relatório gerado em: ${today}`, margin, yPosition)
      yPosition += 20

      // Cabeçalho da tabela
      doc.setFontSize(10)
      doc.setFont("helvetica", "bold")
      const headers = ["Data", "Animal", "Status", "Peso", "Observações"]
      const colWidths = [25, 35, 30, 25, 75]
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
      data.forEach((record) => {
        if (yPosition > pageHeight - 30) {
          doc.addPage()
          yPosition = margin
        }

        xPosition = margin
        const rowData = [
          new Date(record.record_date).toLocaleDateString("pt-BR"),
          record.animal_id || "N/A",
          record.health_status || "N/A",
          record.weight ? record.weight + "kg" : "N/A",
          record.observations || "N/A",
        ]

        rowData.forEach((cell, cellIndex) => {
          const cellText = String(cell).substring(0, 20)
          doc.text(cellText, xPosition, yPosition)
          xPosition += colWidths[cellIndex]
        })
        yPosition += 6
      })

      doc.save(`relatorio_saude_animais_${new Date().toISOString().split("T")[0]}.pdf`)
    } catch (error) {
      throw error
    }
  },

  async generateTreatmentPDF(data) {
    try {
      const { jsPDF } = window.jspdf
      const doc = new jsPDF()

      const pageWidth = doc.internal.pageSize.getWidth()
      const pageHeight = doc.internal.pageSize.getHeight()
      const margin = 20
      let yPosition = margin

      // Título
      doc.setFontSize(18)
      doc.setFont("helvetica", "bold")
      const titleText = `RELATÓRIO DE TRATAMENTOS - ${this.state.currentFarm?.name || "Fazenda"}`
      doc.text(titleText, margin, yPosition)
      yPosition += 20

      // Data do relatório
      doc.setFontSize(12)
      doc.setFont("helvetica", "normal")
      const today = new Date().toLocaleDateString("pt-BR")
      doc.text(`Relatório gerado em: ${today}`, margin, yPosition)
      yPosition += 20

      // Cabeçalho da tabela
      doc.setFontSize(10)
      doc.setFont("helvetica", "bold")
      const headers = ["Data", "Animal", "Descrição", "Medicação", "Dosagem"]
      const colWidths = [25, 25, 40, 35, 25]
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
      data.forEach((treatment) => {
        if (yPosition > pageHeight - 30) {
          doc.addPage()
          yPosition = margin
        }

        xPosition = margin
        const rowData = [
          new Date(treatment.treatment_date).toLocaleDateString("pt-BR"),
          treatment.animal_id || "N/A",
          treatment.description || "N/A",
          treatment.medication || "N/A",
          treatment.dosage || "N/A",
        ]

        rowData.forEach((cell, cellIndex) => {
          const cellText = String(cell).substring(0, 15)
          doc.text(cellText, xPosition, yPosition)
          xPosition += colWidths[cellIndex]
        })
        yPosition += 6
      })

      doc.save(`relatorio_tratamentos_${new Date().toISOString().split("T")[0]}.pdf`)
    } catch (error) {
      throw error
    }
  },

  async generateVaccinationPDF(data) {
    try {
      const { jsPDF } = window.jspdf
      const doc = new jsPDF()

      const pageWidth = doc.internal.pageSize.getWidth()
      const pageHeight = doc.internal.pageSize.getHeight()
      const margin = 20
      let yPosition = margin

      // Título
      doc.setFontSize(18)
      doc.setFont("helvetica", "bold")
      const titleText = `RELATÓRIO DE VACINAÇÃO - ${this.state.currentFarm?.name || "Fazenda"}`
      doc.text(titleText, margin, yPosition)
      yPosition += 20

      // Data do relatório
      doc.setFontSize(12)
      doc.setFont("helvetica", "normal")
      const today = new Date().toLocaleDateString("pt-BR")
      doc.text(`Relatório gerado em: ${today}`, margin, yPosition)
      yPosition += 20

      // Cabeçalho da tabela
      doc.setFontSize(10)
      doc.setFont("helvetica", "bold")
      const headers = ["Data", "Animal", "Vacina", "Dosagem", "Próxima"]
      const colWidths = [25, 25, 40, 25, 35]
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
      data.forEach((vaccination) => {
        if (yPosition > pageHeight - 30) {
          doc.addPage()
          yPosition = margin
        }

        xPosition = margin
        const rowData = [
          new Date(vaccination.treatment_date).toLocaleDateString("pt-BR"),
          vaccination.animal_id || "N/A",
          vaccination.medication || "N/A",
          vaccination.dosage || "N/A",
          vaccination.next_treatment_date
            ? new Date(vaccination.next_treatment_date).toLocaleDateString("pt-BR")
            : "N/A",
        ]

        rowData.forEach((cell, cellIndex) => {
          const cellText = String(cell).substring(0, 18)
          doc.text(cellText, xPosition, yPosition)
          xPosition += colWidths[cellIndex]
        })
        yPosition += 6
      })

      doc.save(`relatorio_vacinacao_${new Date().toISOString().split("T")[0]}.pdf`)
    } catch (error) {
      throw error
    }
  },

  // Gerenciamento de conta
  async returnToManagerAccount() {
    const confirmed = await window.showConfirm(
      "Deseja retornar à sua conta de gerente?\n\nVocê será redirecionado para o painel do gerente.",
      {
        title: 'Retornar ao Gerente',
        type: 'question',
        confirmText: 'Sim, Retornar',
        cancelText: 'Cancelar'
      }
    )

    if (confirmed) {
      sessionStorage.removeItem("currentSecondaryAccount")
      window.location.href = "gerente.php"
    }
  },

  switchToPrimaryAccount() {
    this.returnToManagerAccount()
  },

  async signOut() {
    const confirmed = await window.showConfirm("Tem certeza que deseja sair?", {
      title: 'Confirmar Saída',
      type: 'question',
      confirmText: 'Sim, Sair',
      cancelText: 'Cancelar'
    });
    
    if (confirmed) {
      try {
        await this.state.supabaseClient.auth.signOut()
        window.location.href = "login.php"
      } catch (error) {
        window.location.href = "login.php"
      }
    }
  },

  // Sistema de notificações
  showNotification(message, type = "success") {
    const toast = document.getElementById("notificationToast")
    const messageElement = document.getElementById("toastMessage")
    const iconElement = toast.querySelector("svg")

    if (!toast || !messageElement || !iconElement) return

    messageElement.textContent = message

    // Atualizar ícone e cores baseado no tipo
    if (type === "error") {
      iconElement.classList.remove("text-green-400")
      iconElement.classList.add("text-red-400")
      iconElement.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>'
    } else if (type === "info") {
      iconElement.classList.remove("text-green-400", "text-red-400")
      iconElement.classList.add("text-blue-400")
      iconElement.innerHTML =
        '<path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
    } else {
      iconElement.classList.remove("text-red-400", "text-blue-400")
      iconElement.classList.add("text-green-400")
      iconElement.innerHTML =
        '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
    }

    toast.classList.add("show")

    setTimeout(() => {
      toast.classList.remove("show")
    }, 5000)
  },

  hideNotification() {
    const toast = document.getElementById("notificationToast")
    if (toast) {
      toast.classList.remove("show")
    }
  },

  // Verificar se é conta secundária
  async checkSecondaryAccount() {
    try {
      const secondaryAccount = sessionStorage.getItem("currentSecondaryAccount")
      const returnBtn = document.getElementById("returnToManagerBtn")

      if (secondaryAccount && returnBtn) {
        const accountData = JSON.parse(secondaryAccount)
        if (accountData.isSecondary) {
          returnBtn.classList.remove("hidden")
        }
      }
    } catch (error) {
      // Silenciar erro
    }
  },

  // Carregar animais para o dropdown de registro rápido
  async loadQuickHealthAnimals(farmId) {
    try {
      const { data: animals, error } = await this.state.supabaseClient
        .from("animals")
        .select("id, identification, name")
        .eq("farm_id", farmId)
        .order("identification")

      if (error) throw error

      const select = document.getElementById("quickHealthAnimalSelect")
      if (!select) return

      select.innerHTML = '<option value="">Selecione um animal...</option>'

      if (animals && animals.length > 0) {
        animals.forEach((animal) => {
          const option = document.createElement("option")
          option.value = animal.id
          option.textContent = `${animal.identification} - ${animal.name || "N/A"}`
          select.appendChild(option)
        })
      }
    } catch (error) {
      console.error("Erro ao carregar animais para registro rápido:", error)
    }
  },
}

   // Inicialização quando o DOM estiver carregado
   document.addEventListener("DOMContentLoaded", () => {
     console.log("🚀 DOM carregado, iniciando aplicação...")
     VetApp.init()

     // Verificar conta secundária após um tempo
     setTimeout(() => {
       VetApp.checkSecondaryAccount()
     }, 1000)

     // Adicionar versão do app
     setTimeout(() => {
       const appVersion = "1.0.0"
       const profileModal = document.getElementById("profileModal")
       if (profileModal && !profileModal.querySelector(".app-version")) {
         const versionDiv = document.createElement("div")
         versionDiv.className = "app-version text-xs text-gray-500 text-center mt-4 p-4 border-t border-gray-200"
         versionDiv.innerHTML = `LacTech v${appVersion}`
         
         // Verificar se o modal-content existe antes de usar appendChild
         const modalContent = profileModal.querySelector(".modal-content")
         if (modalContent) {
           modalContent.appendChild(versionDiv)
         }
       }
     }, 2000)
   })

// Expor VetApp globalmente para uso nos event handlers do HTML
window.VetApp = VetApp
