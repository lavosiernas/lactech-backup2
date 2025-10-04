// Mock Data
const mockData = {
    volumeData: [0, 5, 12, 8, 15, 22, 18],
    productionData: [0, 0, 0, 0, 0, 0, 40],
    weekDays: ["sáb", "dom", "seg", "ter", "qua", "qui", "sex"],
  }
  
  // Application State
  const currentUser = "manager"
  
  // DOM Elements
  const navItems = document.querySelectorAll(".sidebar-item")
  const pageContents = document.querySelectorAll(".page-content")
  
  // Navigation
  function showPage(pageId) {
    // Hide all pages
    pageContents.forEach((page) => page.classList.add("hidden"))
  
    // Show selected page
    const targetPage = document.getElementById(`${pageId}-page`)
    if (targetPage) {
      targetPage.classList.remove("hidden")
    }
  
    // Update nav items - remove active class from all, add to current
    navItems.forEach((item) => {
      item.classList.remove("active")
    })
  
    const activeItem = document.querySelector(`[data-page="${pageId}"]`)
    if (activeItem) {
      activeItem.classList.add("active")
    }
  }
  
  // Volume Chart
  function drawVolumeChart() {
    const canvas = document.getElementById("volume-chart")
    if (!canvas) return
  
    const ctx = canvas.getContext("2d")
    const data = mockData.volumeData
    const labels = mockData.weekDays
  
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height)
  
    // Chart dimensions
    const padding = 40
    const chartWidth = canvas.width - 2 * padding
    const chartHeight = canvas.height - 2 * padding
  
    // Find max value for scaling
    const maxValue = Math.max(...data) || 50
  
    // Draw grid lines
    ctx.strokeStyle = "#f3f4f6"
    ctx.lineWidth = 1
  
    for (let i = 0; i <= 5; i++) {
      const y = padding + (i * chartHeight) / 5
      ctx.beginPath()
      ctx.moveTo(padding, y)
      ctx.lineTo(padding + chartWidth, y)
      ctx.stroke()
    }
  
    // Draw line
    ctx.strokeStyle = "#22c55e"
    ctx.lineWidth = 3
    ctx.beginPath()
  
    data.forEach((value, index) => {
      const x = padding + (index * chartWidth) / (data.length - 1)
      const y = padding + chartHeight - (value / maxValue) * chartHeight
  
      if (index === 0) {
        ctx.moveTo(x, y)
      } else {
        ctx.lineTo(x, y)
      }
    })
  
    ctx.stroke()
  
    // Fill area under curve
    ctx.fillStyle = "rgba(34, 197, 94, 0.1)"
    ctx.beginPath()
    data.forEach((value, index) => {
      const x = padding + (index * chartWidth) / (data.length - 1)
      const y = padding + chartHeight - (value / maxValue) * chartHeight
  
      if (index === 0) {
        ctx.moveTo(x, y)
      } else {
        ctx.lineTo(x, y)
      }
    })
    ctx.lineTo(padding + chartWidth, padding + chartHeight)
    ctx.lineTo(padding, padding + chartHeight)
    ctx.closePath()
    ctx.fill()
  
    // Draw points
    ctx.fillStyle = "#22c55e"
    data.forEach((value, index) => {
      const x = padding + (index * chartWidth) / (data.length - 1)
      const y = padding + chartHeight - (value / maxValue) * chartHeight
  
      ctx.beginPath()
      ctx.arc(x, y, 4, 0, 2 * Math.PI)
      ctx.fill()
    })
  
    // Draw labels
    ctx.fillStyle = "#6b7280"
    ctx.font = "12px sans-serif"
    ctx.textAlign = "center"
  
    labels.forEach((label, index) => {
      const x = padding + (index * chartWidth) / (data.length - 1)
      ctx.fillText(label, x, canvas.height - 10)
    })
  }
  
  // Production Chart
  function drawProductionChart() {
    const canvas = document.getElementById("production-chart")
    if (!canvas) return
  
    const ctx = canvas.getContext("2d")
    const data = mockData.productionData
    const labels = mockData.weekDays
  
    // Clear canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height)
  
    // Chart dimensions
    const padding = 40
    const chartWidth = canvas.width - 2 * padding
    const chartHeight = canvas.height - 2 * padding
  
    // Find max value for scaling
    const maxValue = Math.max(...data) || 50
  
    // Draw grid lines
    ctx.strokeStyle = "#f3f4f6"
    ctx.lineWidth = 1
  
    for (let i = 0; i <= 5; i++) {
      const y = padding + (i * chartHeight) / 5
      ctx.beginPath()
      ctx.moveTo(padding, y)
      ctx.lineTo(padding + chartWidth, y)
      ctx.stroke()
    }
  
    // Draw bars
    const barWidth = (chartWidth / data.length) * 0.6
    const barSpacing = chartWidth / data.length
  
    data.forEach((value, index) => {
      const x = padding + index * barSpacing + (barSpacing - barWidth) / 2
      const barHeight = (value / maxValue) * chartHeight
      const y = padding + chartHeight - barHeight
  
      ctx.fillStyle = "#3b82f6"
      ctx.fillRect(x, y, barWidth, barHeight)
    })
  
    // Draw labels
    ctx.fillStyle = "#6b7280"
    ctx.font = "12px sans-serif"
    ctx.textAlign = "center"
  
    labels.forEach((label, index) => {
      const x = padding + index * barSpacing + barSpacing / 2
      ctx.fillText(label, x, canvas.height - 10)
    })
  }
  
  // Event Listeners
  document.addEventListener("DOMContentLoaded", () => {
    // Navigation
    navItems.forEach((item) => {
      item.addEventListener("click", (e) => {
        e.preventDefault()
        const pageId = item.dataset.page
        showPage(pageId)
      })
    })
  
    // Initialize charts
    drawVolumeChart()
    drawProductionChart()
  
    // Show default page
    showPage("dashboard")
  })
  
  const style = document.createElement("style")
  style.textContent = `
      .sidebar-item {
          text-decoration: none;
          transition: all 0.2s;
      }
      
      .page-content {
          display: block;
      }
      
      .page-content.hidden {
          display: none;
      }
      
      @media (max-width: 768px) {
          .max-w-7xl {
              padding-left: 1rem;
              padding-right: 1rem;
          }
          
          .grid-cols-4 {
              grid-template-columns: repeat(2, minmax(0, 1fr));
          }
          
          .hidden.md\\:flex {
              display: none !important;
          }
      }
  `
  document.head.appendChild(style)
