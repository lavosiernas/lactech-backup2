import { useState, useEffect, useCallback, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Monitor, Tablet, Smartphone, RefreshCw, RotateCcw, Lock, Power, Flashlight, Camera, Moon, Sun, Globe, Code, Phone, MessageSquare, Music, Mail, Calendar, Image, Video, Settings, Map, Heart, Wallet, Cloud, Search, Clock, Home, Maximize2, X } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import type { PreviewMode } from '@/types/ide';
import { getLogoPath } from '@/lib/assets';

export const LivePreview: React.FC = () => {
  const { previewMode, setPreviewMode, tabs, activeTabId, setIsFloatingPreview } = useIDEStore();
  const [darkMode, setDarkMode] = useState(false);
  const [rotated, setRotated] = useState(false);
  const [isLocked, setIsLocked] = useState(true);
  const [screenOff, setScreenOff] = useState(false);
  const [showHomeScreen, setShowHomeScreen] = useState(true);
  const [activeApp, setActiveApp] = useState<string | null>(null);
  const [browserUrl, setBrowserUrl] = useState('about:blank');
  const [iframeError, setIframeError] = useState(false);
  const [key, setKey] = useState(0);
  const [currentTime, setCurrentTime] = useState(new Date());
  const [currentDate, setCurrentDate] = useState(new Date());
  const [swipeStart, setSwipeStart] = useState<{ y: number; time: number } | null>(null);
  const [swipeOffset, setSwipeOffset] = useState(0);
  const [isUnlocking, setIsUnlocking] = useState(false);
  const [isFloating, setIsFloating] = useState(false);
  const [floatingPosition, setFloatingPosition] = useState({ x: 100, y: 100 });
  const [floatingSize, setFloatingSize] = useState({ width: 323, height: 700 });
  const [isDragging, setIsDragging] = useState(false);
  const [isResizing, setIsResizing] = useState(false);
  const [resizeDirection, setResizeDirection] = useState<'both' | 'horizontal' | 'vertical'>('both');
  const [dragStart, setDragStart] = useState({ x: 0, y: 0 });
  const [resizeStart, setResizeStart] = useState({ x: 0, y: 0, width: 0, height: 0 });
  
  const activeTab = tabs.find(t => t.id === activeTabId);

  // App Icon Component
  const AppIcon: React.FC<{
    icon: React.ReactNode;
    label: string;
    bgColor: string;
    iconColor?: string;
    onClick: () => void;
  }> = ({ icon, label, bgColor, iconColor, onClick }) => (
    <button
      onClick={onClick}
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        gap: '6px',
        background: 'transparent',
        border: 'none',
        cursor: 'pointer',
        padding: '4px',
        transition: 'all 0.2s',
      }}
      onMouseEnter={(e) => {
        e.currentTarget.style.transform = 'scale(1.1)';
      }}
      onMouseLeave={(e) => {
        e.currentTarget.style.transform = 'scale(1)';
      }}
    >
      <div style={{
        width: '60px',
        height: '60px',
        borderRadius: '13px',
        background: bgColor.includes('gradient') ? bgColor : bgColor,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
      }}>
        <div style={{ color: iconColor || '#fff' }}>
          {icon}
        </div>
      </div>
      <span style={{ 
        fontSize: '10px', 
        color: '#fff', 
        fontFamily: 'SF Pro Display, -apple-system, sans-serif',
        textShadow: '0 1px 3px rgba(0,0,0,0.3)',
      }}>
        {label}
      </span>
    </button>
  );

  // Dock Icon Component
  const DockIcon: React.FC<{
    icon: React.ReactNode;
    bgColor: string;
    onClick?: () => void;
  }> = ({ icon, bgColor, onClick }) => (
    <button
      onClick={onClick}
      style={{
        width: '60px',
        height: '60px',
        borderRadius: '13px',
        background: bgColor,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        border: 'none',
        cursor: onClick ? 'pointer' : 'default',
        transition: 'all 0.2s',
        boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
      }}
      onMouseEnter={(e) => {
        if (onClick) {
          e.currentTarget.style.transform = 'scale(1.15)';
        }
      }}
      onMouseLeave={(e) => {
        if (onClick) {
          e.currentTarget.style.transform = 'scale(1)';
        }
      }}
    >
      {icon}
    </button>
  );

  // Handle swipe to unlock for iOS
  const handleSwipeStart = (e: React.TouchEvent | React.MouseEvent) => {
    if (isUnlocking) return;
    const y = 'touches' in e ? e.touches[0].clientY : e.clientY;
    setSwipeStart({ y, time: Date.now() });
    setSwipeOffset(0);
  };

  const handleSwipeMove = (e: React.TouchEvent | React.MouseEvent) => {
    if (!swipeStart || isUnlocking) return;
    const currentY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    const deltaY = swipeStart.y - currentY;
    const offset = Math.max(0, Math.min(deltaY, 100));
    setSwipeOffset(offset);
    
    // If swiped up more than 80px, unlock with animation
    if (deltaY > 80) {
      setIsUnlocking(true);
      setSwipeOffset(100);
      setTimeout(() => {
        setIsLocked(false);
        setShowHomeScreen(true);
        setIsUnlocking(false);
        setSwipeStart(null);
        setSwipeOffset(0);
      }, 300);
    }
  };

  const handleSwipeEnd = () => {
    if (isUnlocking) return;
    // Animate back if not unlocked
    if (swipeOffset > 0) {
      setSwipeOffset(0);
    }
    setSwipeStart(null);
  };

  // Atualizar relógio em tempo real
  useEffect(() => {
    const timer = setInterval(() => {
      const now = new Date();
      setCurrentTime(now);
      setCurrentDate(now);
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const formatTime = (date: Date): string => {
    return date.toLocaleTimeString('pt-BR', { 
      hour: '2-digit', 
      minute: '2-digit',
      hour12: false 
    });
  };

  const formatDate = (date: Date): string => {
    const days = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    const months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    const day = days[date.getDay()];
    const dayNum = date.getDate();
    const month = months[date.getMonth()];
    return `${day}, ${dayNum} ${month}`;
  };

  // Handlers para drag and drop do iframe flutuante
  const handleFloatingDragStart = (e: React.MouseEvent | React.TouchEvent) => {
    if (!isFloating || isResizing) return;
    
    // Não iniciar drag se o clique estiver na área de resize (últimos 20px da borda direita)
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const target = e.target as HTMLElement;
    const containerRect = target.closest('[style*="position: fixed"]')?.getBoundingClientRect();
    if (containerRect && clientX > containerRect.right - 20) {
      return; // Deixa o resize handle capturar o evento
    }
    
    setIsDragging(true);
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    setDragStart({
      x: clientX - floatingPosition.x,
      y: clientY - floatingPosition.y
    });
    e.preventDefault();
  };

  const handleFloatingDragMove = (e: MouseEvent | TouchEvent) => {
    if (!isDragging || !isFloating) return;
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    
    // Calcular posição relativa ao ponto de início do drag
    const newX = clientX - dragStart.x;
    const newY = clientY - dragStart.y;
    
    // Permitir movimento livre - sem limitações, pode sair completamente da página
    // Isso permite mover para outros monitores e qualquer lugar da tela
    setFloatingPosition({ 
      x: newX, 
      y: newY 
    });
  };

  const handleFloatingDragEnd = () => {
    setIsDragging(false);
  };

  // Handlers para redimensionar - VERSÃO SIMPLIFICADA
  const handleResizeStart = (e: React.MouseEvent | React.TouchEvent, direction: 'both' | 'horizontal' | 'vertical' = 'both') => {
    if (!isFloating) return;
    
    e.preventDefault();
    e.stopPropagation();
    
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    
    setResizeStart({
      x: clientX,
      y: clientY,
      width: floatingSize.width,
      height: floatingSize.height
    });
    setResizeDirection(direction);
    setIsResizing(true);
  };

  const handleResizeMove = (e: MouseEvent | TouchEvent) => {
    if (!isResizing || !isFloating) return;
    
    e.preventDefault();
    
    const clientX = 'touches' in e ? e.touches[0].clientX : e.clientX;
    const clientY = 'touches' in e ? e.touches[0].clientY : e.clientY;
    
    const deltaX = clientX - resizeStart.x;
    const deltaY = clientY - resizeStart.y;
    
    const minWidth = rotated ? 400 : 250;
    const minHeight = rotated ? 250 : 400;
    const maxWidth = window.innerWidth - floatingPosition.x;
    const maxHeight = window.innerHeight - floatingPosition.y;
    
    let newWidth = resizeStart.width;
    let newHeight = resizeStart.height;
    
    if (resizeDirection === 'both' || resizeDirection === 'horizontal') {
      newWidth = Math.max(minWidth, Math.min(resizeStart.width + deltaX, maxWidth));
    }
    
    if (resizeDirection === 'both' || resizeDirection === 'vertical') {
      newHeight = Math.max(minHeight, Math.min(resizeStart.height + deltaY, maxHeight));
    }
    
    setFloatingSize({
      width: newWidth,
      height: newHeight
    });
  };

  const handleResizeEnd = () => {
    setIsResizing(false);
  };

  useEffect(() => {
    if (isDragging) {
      const handleMouseMove = (e: MouseEvent) => handleFloatingDragMove(e);
      const handleTouchMove = (e: TouchEvent) => handleFloatingDragMove(e);
      const handleMouseUp = () => handleFloatingDragEnd();
      const handleTouchEnd = () => handleFloatingDragEnd();

      window.addEventListener('mousemove', handleMouseMove);
      window.addEventListener('touchmove', handleTouchMove);
      window.addEventListener('mouseup', handleMouseUp);
      window.addEventListener('touchend', handleTouchEnd);
      
      return () => {
        window.removeEventListener('mousemove', handleMouseMove);
        window.removeEventListener('touchmove', handleTouchMove);
        window.removeEventListener('mouseup', handleMouseUp);
        window.removeEventListener('touchend', handleTouchEnd);
      };
    }
  }, [isDragging, dragStart.x, dragStart.y, floatingSize.width, floatingSize.height]);

  const previewModes: { mode: PreviewMode; icon: React.ReactNode; label: string }[] = [
    { mode: 'desktop', icon: <Monitor className="w-4 h-4" />, label: 'Desktop' },
    { mode: 'tablet', icon: <Tablet className="w-4 h-4" />, label: 'Tablet' },
    { mode: 'ios', icon: <Smartphone className="w-4 h-4" />, label: 'iOS' },
    { mode: 'android', icon: <Smartphone className="w-4 h-4" />, label: 'Android' },
  ];

  const getPreviewSize = () => {
    const sizes = {
      desktop: { width: '100%', height: '100%' },
      tablet: rotated ? { width: '1024px', height: '768px' } : { width: '768px', height: '1024px' },
      ios: rotated ? { width: '844px', height: '390px' } : { width: '390px', height: '844px' },
      android: rotated ? { width: '844px', height: '390px' } : { width: '390px', height: '844px' },
    };
    return sizes[previewMode];
  };

  const size = getPreviewSize();
  const isMobile = previewMode === 'ios' || previewMode === 'android';

  // Detectar erros de X-Frame-Options
  useEffect(() => {
    if (!isFloating && !isMobile) return;
    
    const handleMessage = (event: MessageEvent) => {
      // Alguns sites podem enviar mensagens quando bloqueados
      if (event.data && typeof event.data === 'string' && event.data.includes('X-Frame-Options')) {
        setIframeError(true);
      }
    };
    
    window.addEventListener('message', handleMessage);
    return () => window.removeEventListener('message', handleMessage);
  }, [isFloating, isMobile]);

  useEffect(() => {
    if (!isResizing) return;
    
    const handleMouseMove = (e: MouseEvent) => {
      e.preventDefault();
      if (!isResizing || !isFloating) return;
      
      const deltaX = e.clientX - resizeStart.x;
      const deltaY = e.clientY - resizeStart.y;
      
      const minWidth = rotated ? 400 : 250;
      const minHeight = rotated ? 250 : 400;
      const maxWidth = window.innerWidth - floatingPosition.x;
      const maxHeight = window.innerHeight - floatingPosition.y;
      
      let newWidth = resizeStart.width;
      let newHeight = resizeStart.height;
      
      if (resizeDirection === 'both' || resizeDirection === 'horizontal') {
        newWidth = Math.max(minWidth, Math.min(resizeStart.width + deltaX, maxWidth));
      }
      
      if (resizeDirection === 'both' || resizeDirection === 'vertical') {
        newHeight = Math.max(minHeight, Math.min(resizeStart.height + deltaY, maxHeight));
      }
      
      setFloatingSize({ width: newWidth, height: newHeight });
    };
    
    const handleTouchMove = (e: TouchEvent) => {
      e.preventDefault();
      if (!isResizing || !isFloating || !e.touches[0]) return;
      
      const deltaX = e.touches[0].clientX - resizeStart.x;
      const deltaY = e.touches[0].clientY - resizeStart.y;
      
      const minWidth = rotated ? 400 : 250;
      const minHeight = rotated ? 250 : 400;
      const maxWidth = window.innerWidth - floatingPosition.x;
      const maxHeight = window.innerHeight - floatingPosition.y;
      
      let newWidth = resizeStart.width;
      let newHeight = resizeStart.height;
      
      if (resizeDirection === 'both' || resizeDirection === 'horizontal') {
        newWidth = Math.max(minWidth, Math.min(resizeStart.width + deltaX, maxWidth));
      }
      
      if (resizeDirection === 'both' || resizeDirection === 'vertical') {
        newHeight = Math.max(minHeight, Math.min(resizeStart.height + deltaY, maxHeight));
      }
      
      setFloatingSize({ width: newWidth, height: newHeight });
    };
    
    const handleMouseUp = () => handleResizeEnd();
    const handleTouchEnd = () => handleResizeEnd();

    window.addEventListener('mousemove', handleMouseMove, { passive: false });
    window.addEventListener('touchmove', handleTouchMove, { passive: false });
    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('touchend', handleTouchEnd);
    
    return () => {
      window.removeEventListener('mousemove', handleMouseMove);
      window.removeEventListener('touchmove', handleTouchMove);
      window.removeEventListener('mouseup', handleMouseUp);
      window.removeEventListener('touchend', handleTouchEnd);
    };
  }, [isResizing, resizeStart, resizeDirection, floatingPosition, rotated, isFloating]);

  // Função para abrir janela flutuante como overlay fullscreen
  const openFloatingWindow = () => {
    if (!isMobile) return;
    // Posição inicial centralizada
    const baseWidth = rotated ? 700 : 323;
    const baseHeight = rotated ? 323 : 700;
    // Atualizar todos os estados de uma vez
    setFloatingSize({ width: baseWidth, height: baseHeight });
    setFloatingPosition({ 
      x: (window.innerWidth - baseWidth) / 2, 
      y: (window.innerHeight - baseHeight) / 2 
    });
    // Atualizar estado local e do store simultaneamente
    setIsFloating(true);
    setIsFloatingPreview(true);
  };

  // Fechar janela flutuante
  const closeFloatingWindow = () => {
    setIsFloating(false);
    setIsFloatingPreview(false);
    // Forçar atualização do estado para garantir que a preview normal apareça
    setKey(prev => prev + 1);
  };

  // Get preview content from active tab or default
  const getPreviewContent = () => {
    if (activeTab && activeTab.content) {
      if (activeTab.language === 'html') {
        return activeTab.content;
      }
      if (activeTab.language === 'typescript' || activeTab.language === 'javascript') {
        const bgColor = darkMode ? '#000000' : '#ffffff';
        const textColor = darkMode ? '#f8fafc' : '#0f172a';
        const codePreview = activeTab.content.substring(0, 500).replace(/`/g, '\\`').replace(/\$/g, '\\$');
        return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Preview</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { 
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: ${bgColor};
      color: ${textColor};
      padding: 2rem;
    }
  </style>
</head>
<body>
  <div id="root"></div>
  <script>
    console.log('Preview mode - code:', \`${codePreview}\`);
  </script>
</body>
</html>`;
      }
      if (activeTab.language === 'css') {
        const cssContent = activeTab.content.replace(/`/g, '\\`').replace(/\${/g, '\\${').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return `<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
${cssContent}
  </style>
</head>
<body>
  <div class="demo">
    <h1>CSS Preview</h1>
    <p>This is a preview of your CSS styles.</p>
  </div>
</body>
</html>`;
      }
    }
    
    const bgColor = darkMode ? '#000000' : '#ffffff';
    const textColor = darkMode ? '#f8fafc' : '#0f172a';
    const borderColor = darkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
    const borderColorLight = darkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
    const borderColorDark = darkMode ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)';
    const inputBg = darkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.02)';
    const inputBorder = darkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
    const featureBg = darkMode ? 'rgba(255,255,255,0.03)' : 'rgba(0,0,0,0.02)';
    const featureBgHover = darkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.04)';
    const btnBg = darkMode ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.08)';
    const btnBgHover = darkMode ? 'rgba(59, 130, 246, 0.2)' : 'rgba(59, 130, 246, 0.15)';
    const btnColor = darkMode ? '#60a5fa' : '#3b82f6';
    const modalBg = darkMode ? '#000000' : '#ffffff';
    const modalText = darkMode ? '#cbd5e1' : '#475569';
    const modalTitle = darkMode ? '#f8fafc' : '#0f172a';
    const modalSubtext = darkMode ? '#64748b' : '#94a3b8';
    const sectionTitle = darkMode ? '#e2e8f0' : '#0f172a';
    const mutedColor = darkMode ? '#94a3b8' : '#64748b';
    
    return `
    <!DOCTYPE html>
    <html style="background: ${bgColor}; color: ${textColor};">
    <head>
      <meta charset="UTF-8">
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Inter', sans-serif;
          min-height: 100vh;
          display: flex;
          flex-direction: column;
          background: ${darkMode ? '#000000' : '#ffffff'};
          color: ${darkMode ? '#f8fafc' : '#0f172a'};
        }
        .header {
          padding: 1.5rem 2rem;
          display: flex;
          justify-content: space-between;
          align-items: center;
          border-bottom: 1px solid ${borderColor};
        }
        .header h1 { 
          font-size: 1rem; 
          font-weight: 500;
          color: ${textColor};
          letter-spacing: 0.5px;
        }
        .btn {
          padding: 0.5rem 1rem;
          border-radius: 0.25rem;
          border: none;
          background: transparent;
          color: ${mutedColor};
          cursor: pointer;
          font-weight: 400;
          font-size: 0.875rem;
          transition: color 0.2s ease;
        }
        .btn:hover {
          color: ${textColor};
        }
        main {
          flex: 1;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 2rem;
        }
        .welcome-content {
          text-align: center;
        }
        main h2 {
          font-size: 2rem;
          font-weight: 400;
          margin-bottom: 0.75rem;
          color: ${textColor};
          letter-spacing: -0.5px;
        }
        main p {
          color: ${mutedColor};
          font-size: 0.9375rem;
          line-height: 1.6;
          font-weight: 300;
        }
        .modal-overlay {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.6);
          backdrop-filter: blur(4px);
          z-index: 1000;
          align-items: center;
          justify-content: center;
        }
        .modal-overlay.active {
          display: flex;
          animation: fadeIn 0.2s ease;
        }
        .modal {
          background: ${modalBg};
          border-radius: 0.25rem;
          padding: 2rem;
          max-width: 420px;
          width: 90%;
          max-height: 80vh;
          overflow-y: auto;
          box-shadow: ${darkMode ? '0 20px 60px rgba(0, 0, 0, 0.5)' : '0 20px 60px rgba(0, 0, 0, 0.15)'};
          animation: slideUp 0.2s ease;
          border: 1px solid ${borderColor};
        }
        .modal-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 1.5rem;
        }
        .modal-title {
          font-size: 1rem;
          font-weight: 500;
          color: ${modalTitle};
        }
        .modal-close {
          background: none;
          border: none;
          font-size: 1.25rem;
          cursor: pointer;
          color: ${mutedColor};
          width: 28px;
          height: 28px;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: color 0.2s ease;
        }
        .modal-close:hover {
          color: ${textColor};
        }
        .modal-content {
          color: ${modalText};
          line-height: 1.6;
        }
        .settings-section {
          margin-bottom: 1.5rem;
          padding-bottom: 1.5rem;
          border-bottom: 1px solid ${borderColor};
        }
        .settings-section:last-child {
          border-bottom: none;
        }
        .settings-section h3 {
          font-size: 0.8125rem;
          font-weight: 500;
          margin-bottom: 1rem;
          color: ${sectionTitle};
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        .setting-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 0.875rem;
        }
        .setting-label {
          color: ${modalText};
          font-size: 0.875rem;
          font-weight: 300;
        }
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
        @keyframes slideUp {
          from {
            opacity: 0;
            transform: translateY(20px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
      </style>
    </head>
    <body>
      <header class="header">
        <h1>SAFECODE</h1>
        <button class="btn" onclick="openSettings()">Settings</button>
      </header>
      <main>
        <div class="welcome-content">
          <h2>Welcome to SAFECODE</h2>
          <p>Start building your next project</p>
        </div>
      </main>
      
      <div class="modal-overlay" id="settingsModal" onclick="closeSettingsOnOverlay(event)">
        <div class="modal" onclick="event.stopPropagation()">
          <div class="modal-header">
            <h3 class="modal-title">Settings</h3>
            <button class="modal-close" onclick="closeSettings()">×</button>
          </div>
          <div class="modal-content">
            <div class="settings-section">
              <h3>Editor</h3>
              <div class="setting-item">
                <span class="setting-label">Font Size</span>
                <input type="number" value="14" min="10" max="30" style="width: 80px; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid ${inputBorder}; background: ${inputBg}; color: ${textColor};">
              </div>
              <div class="setting-item">
                <span class="setting-label">Tab Size</span>
                <select style="width: 80px; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: 1px solid ${inputBorder}; background: ${inputBg}; color: ${textColor};">
                  <option value="2">2</option>
                  <option value="4">4</option>
                  <option value="8">8</option>
                </select>
              </div>
              <div class="setting-item">
                <span class="setting-label">Word Wrap</span>
                <label style="cursor: pointer;">
                  <input type="checkbox" checked style="margin-left: 0.5rem;">
                </label>
              </div>
            </div>
            <div class="settings-section">
              <h3>Files</h3>
              <div class="setting-item">
                <span class="setting-label">Auto Save</span>
                <label style="cursor: pointer;">
                  <input type="checkbox" style="margin-left: 0.5rem;">
                </label>
              </div>
            </div>
            <div class="settings-section">
              <h3>About</h3>
              <p style="font-size: 0.8125rem; color: ${modalSubtext}; font-weight: 300;">SAFECODE v1.0.0</p>
            </div>
          </div>
        </div>
      </div>
      
      <script>
        function openSettings() {
          document.getElementById('settingsModal').classList.add('active');
        }
        function closeSettings() {
          document.getElementById('settingsModal').classList.remove('active');
        }
        function closeSettingsOnOverlay(event) {
          if (event.target.id === 'settingsModal') {
            closeSettings();
          }
        }
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            closeSettings();
          }
        });
      </script>
    </body>
    </html>
  `;
  };

  const renderIOSFrame = () => (
    <div 
      className={`iphone-frame ${rotated ? 'landscape' : ''} ${screenOff ? 'screen-off' : ''}`}
      style={{
        width: rotated ? '932px' : '430px',
        height: rotated ? '430px' : '932px',
        borderRadius: '55px',
        background: '#000',
        border: '3px solid #1a1a1a',
        padding: '8px',
        position: 'relative',
        boxShadow: '0 40px 80px rgba(0, 0, 0, 0.8)',
        transform: 'scale(0.75)',
        transformOrigin: 'center center',
        transition: 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
      }}
    >
      {/* Dynamic Island - iOS 18+ Style */}
      <div 
        className="dynamic-island"
        style={{
          position: 'absolute',
          top: '16px',
          left: '50%',
          transform: 'translateX(-50%)',
          width: '126px',
          height: '40px',
          background: '#000',
          border: '0.5px solid rgba(255, 255, 255, 0.1)',
          borderRadius: '20px',
          zIndex: 100,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-between',
          padding: '0 14px',
          boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
        }}
      >
        {isLocked ? (
          <>
            <Lock className="w-3.5 h-3.5" style={{ color: '#fff', opacity: 0.85 }} />
            <div style={{
              width: '14px',
              height: '14px',
              background: 'radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1) 0%, #1a1a1a 40%, #000 100%)',
              borderRadius: '50%',
              border: '0.5px solid rgba(255, 255, 255, 0.15)',
              boxShadow: 'inset 0 0 6px rgba(0, 0, 0, 0.5), 0 0 2px rgba(255, 255, 255, 0.1)',
            }} />
          </>
        ) : (
          <>
            <div style={{ width: '14px' }} />
            <div style={{
              width: '14px',
              height: '14px',
              background: 'radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1) 0%, #1a1a1a 40%, #000 100%)',
              borderRadius: '50%',
              border: '0.5px solid rgba(255, 255, 255, 0.15)',
              boxShadow: 'inset 0 0 6px rgba(0, 0, 0, 0.5), 0 0 2px rgba(255, 255, 255, 0.1)',
            }} />
          </>
        )}
      </div>

      {/* iPhone Screen */}
      <div 
        className="iphone-screen"
        style={{
          width: '100%',
          height: '100%',
          background: '#000',
          borderRadius: '47px',
          overflow: 'hidden',
          position: 'relative',
        }}
      >
        {/* iOS 18+ Status Bar */}
        {!screenOff && (
          <div 
            className="ios-status-bar"
            style={{
              height: '54px',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              padding: '0 32px',
              fontSize: '11px',
              fontWeight: 600,
              color: '#fff',
              position: 'absolute',
              top: '0',
              left: 0,
              right: 0,
              zIndex: 50,
              pointerEvents: 'none',
              paddingTop: '12px',
              fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif',
            }}
          >
            <span style={{ 
              letterSpacing: '0.01em',
              fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif',
            }}>{formatTime(currentTime)}</span>
            <div style={{ display: 'flex', alignItems: 'center', gap: '5px', opacity: 0.95 }}>
              {/* Signal Bars - iOS 18+ style */}
              <svg width="18" height="12" viewBox="0 0 18 12" fill="none">
                <path d="M1 11V9" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
                <path d="M4 11V6" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
                <path d="M7 11V3" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
                <path d="M10 11V1" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
              </svg>
              {/* Wi-Fi - iOS 18+ style */}
              <svg width="18" height="14" viewBox="0 0 18 14" fill="none">
                <path d="M1.5 4.5A7.5 7.5 0 0 1 9 1A7.5 7.5 0 0 1 16.5 4.5" stroke="white" strokeWidth="1.2" strokeLinecap="round" fill="none"/>
                <path d="M4.5 6A4.5 4.5 0 0 1 9 4A4.5 4.5 0 0 1 13.5 6" stroke="white" strokeWidth="1.2" strokeLinecap="round" fill="none"/>
                <path d="M7.2 8A1.8 1.8 0 0 1 9 7A1.8 1.8 0 0 1 10.8 8" stroke="white" strokeWidth="1.2" strokeLinecap="round" fill="none"/>
                <circle cx="9" cy="11" r="1.2" fill="white"/>
              </svg>
              {/* Battery - iOS 18+ style */}
              <svg width="22" height="12" viewBox="0 0 22 12" fill="none">
                <rect x="1" y="2.5" width="17" height="7" rx="1.5" stroke="white" strokeWidth="1.5" fill="none"/>
                <rect x="18.5" y="4.5" width="1.5" height="3" rx="0.5" fill="white"/>
                <rect x="2.5" y="4" width="14" height="4" rx="0.8" fill="white"/>
              </svg>
            </div>
          </div>
        )}

        {/* iOS Lock Screen */}
        {isLocked && !screenOff && (
          <div 
            className="ios-lock-screen"
            onTouchStart={handleSwipeStart}
            onTouchMove={handleSwipeMove}
            onTouchEnd={handleSwipeEnd}
            onMouseDown={handleSwipeStart}
            onMouseMove={handleSwipeMove}
            onMouseUp={handleSwipeEnd}
            onMouseLeave={handleSwipeEnd}
            style={{
              position: 'absolute',
              top: 0,
              left: 0,
              width: '100%',
              height: '100%',
              backgroundImage: 'url(https://i.postimg.cc/zGQQWV9n/wwlp.jpg)',
              backgroundSize: 'cover',
              backgroundPosition: 'center',
              backgroundRepeat: 'no-repeat',
              zIndex: 40,
              display: 'flex',
              flexDirection: 'column',
              justifyContent: 'space-between',
              paddingTop: '60px',
              paddingBottom: '8px',
              color: '#fff',
              cursor: swipeStart ? 'grabbing' : 'grab',
              transform: `translateY(-${swipeOffset}px)`,
              opacity: 1 - (swipeOffset / 100) * 0.3,
              transition: isUnlocking ? 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1)' : 'none',
            }}
          >
            <div style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '6px',
              textShadow: '0 2px 12px rgba(0, 0, 0, 0.4)',
            }}>
              <div style={{ 
                fontSize: '15px', 
                fontWeight: 500, 
                opacity: 0.85, 
                textTransform: 'capitalize',
                fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif',
                letterSpacing: '0.01em',
              }}>
                {formatDate(currentDate)}
              </div>
              <div style={{ 
                fontSize: '72px', 
                fontWeight: 200, 
                letterSpacing: '-3px', 
                lineHeight: 1,
                fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif',
                fontFeatureSettings: '"tnum"',
              }}>
                {formatTime(currentTime)}
              </div>
            </div>
            <div style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '20px',
              width: '100%',
              padding: '0 32px',
            }}>
              <div style={{
                width: '100%',
                display: 'flex',
                justifyContent: 'space-between',
                padding: '0 20px',
              }}>
                <button style={{
                  width: '44px',
                  height: '44px',
                  borderRadius: '50%',
                  background: 'rgba(255, 255, 255, 0.15)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '1px solid rgba(255, 255, 255, 0.25)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#fff',
                  cursor: 'pointer',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.2)',
                  transition: 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)',
                  opacity: 0.7,
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background = 'rgba(255, 255, 255, 0.25)';
                  e.currentTarget.style.transform = 'scale(1.05)';
                  e.currentTarget.style.opacity = '0.9';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = 'rgba(255, 255, 255, 0.15)';
                  e.currentTarget.style.transform = 'scale(1)';
                  e.currentTarget.style.opacity = '0.7';
                }}
                >
                  <Flashlight className="w-5 h-5" strokeWidth="2.5" />
                </button>
                <button style={{
                  width: '44px',
                  height: '44px',
                  borderRadius: '50%',
                  background: 'rgba(255, 255, 255, 0.15)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '1px solid rgba(255, 255, 255, 0.25)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#fff',
                  cursor: 'pointer',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.2)',
                  transition: 'all 0.2s cubic-bezier(0.4, 0, 0.2, 1)',
                  opacity: 0.7,
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.background = 'rgba(255, 255, 255, 0.25)';
                  e.currentTarget.style.transform = 'scale(1.05)';
                  e.currentTarget.style.opacity = '0.9';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.background = 'rgba(255, 255, 255, 0.15)';
                  e.currentTarget.style.transform = 'scale(1)';
                  e.currentTarget.style.opacity = '0.7';
                }}
                >
                  <Camera className="w-5 h-5" strokeWidth="2.5" />
                </button>
              </div>
              <div style={{ 
                fontSize: '13px', 
                fontWeight: 500, 
                opacity: 0.75 - (swipeOffset / 100) * 0.3, 
                marginBottom: '4px',
                fontFamily: 'SF Pro Display, -apple-system, BlinkMacSystemFont, sans-serif',
                letterSpacing: '0.02em',
              }}>
                Passe o dedo para cima
              </div>
              <div style={{
                width: '134px',
                height: '5px',
                background: '#fff',
                borderRadius: '3px',
                opacity: 0.85 - (swipeOffset / 100) * 0.5,
                transform: `translateY(${swipeOffset * 0.5}px)`,
                transition: isUnlocking ? 'opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)' : 'none',
                boxShadow: '0 1px 3px rgba(0, 0, 0, 0.2)',
              }} />
            </div>
          </div>
        )}

        {/* iOS Home Screen */}
        {!isLocked && !screenOff && showHomeScreen && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            backgroundImage: 'url(https://i.postimg.cc/zGQQWV9n/wwlp.jpg)',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            zIndex: 30,
            paddingTop: '54px',
            paddingBottom: '120px',
            overflowY: 'auto',
            animation: 'fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
            scrollbarWidth: 'none',
            msOverflowStyle: 'none',
          }} className="hide-scrollbar">
            {/* Main App Grid - Only Safari, Chrome, SAFECODE */}
            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(4, 1fr)',
              gap: '20px',
              padding: '32px 24px 24px',
              maxWidth: '100%',
            }}>
              {/* Safari */}
              <button
                onClick={() => {
                  setActiveApp('safari');
                  setShowHomeScreen(false);
                }}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '6px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '4px',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'scale(1.1)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                <div style={{
                  width: '60px',
                  height: '60px',
                  borderRadius: '13px',
                  background: 'rgba(255, 255, 255, 0.25)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '0.5px solid rgba(255, 255, 255, 0.3)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
                  overflow: 'hidden',
                }}>
                  <img 
                    src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Safari_browser_logo.svg/500px-Safari_browser_logo.svg.png"
                    alt="Safari"
                    style={{ width: '50px', height: '50px', objectFit: 'contain' }}
                  />
                </div>
                <span style={{ fontSize: '10px', color: '#fff', fontFamily: 'SF Pro Display, -apple-system, sans-serif', textShadow: '0 1px 3px rgba(0,0,0,0.3)' }}>Safari</span>
              </button>

              {/* Chrome */}
              <button
                onClick={() => {
                  setActiveApp('chrome');
                  setShowHomeScreen(false);
                }}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '6px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '4px',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'scale(1.1)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                <div style={{
                  width: '60px',
                  height: '60px',
                  borderRadius: '13px',
                  background: 'rgba(255, 255, 255, 0.25)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '0.5px solid rgba(255, 255, 255, 0.3)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
                  overflow: 'hidden',
                }}>
                  <img 
                    src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/Google_Chrome_icon_%28February_2022%29.svg/2048px-Google_Chrome_icon_%28February_2022%29.svg.png"
                    alt="Chrome"
                    style={{ width: '50px', height: '50px', objectFit: 'contain' }}
                  />
                </div>
                <span style={{ fontSize: '10px', color: '#fff', fontFamily: 'SF Pro Display, -apple-system, sans-serif', textShadow: '0 1px 3px rgba(0,0,0,0.3)' }}>Chrome</span>
              </button>

              {/* SAFECODE */}
              <button
                onClick={() => {
                  setActiveApp('safecode');
                  setShowHomeScreen(false);
                }}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '6px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '4px',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'scale(1.1)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                <div style={{
                  width: '60px',
                  height: '60px',
                  borderRadius: '13px',
                  background: 'rgba(0, 0, 0, 0.6)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '0.5px solid rgba(255, 255, 255, 0.2)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
                  overflow: 'hidden',
                }}>
                  <img 
                    src={getLogoPath()}
                    alt="SAFECODE"
                    style={{ width: '50px', height: '50px', objectFit: 'contain' }}
                    onError={(e) => {
                      e.currentTarget.style.display = 'none';
                      const parent = e.currentTarget.parentElement;
                      if (parent) {
                        parent.innerHTML = '<div style="width: 50px; height: 50px; background: #000; display: flex; align-items: center; justify-content: center;"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M16 18h6M12 18h2M8 18h2M4 18h2M16 6h6M12 6h2M8 6h2M4 6h2"/></svg></div>';
                      }
                    }}
                  />
                </div>
                <span style={{ fontSize: '10px', color: '#fff', fontFamily: 'SF Pro Display, -apple-system, sans-serif', textShadow: '0 1px 3px rgba(0,0,0,0.3)' }}>SAFECODE</span>
              </button>
            </div>

            {/* Search Bar */}
            <div style={{
              display: 'flex',
              justifyContent: 'center',
              padding: '0 24px 16px',
            }}>
              <div style={{
                width: '100%',
                maxWidth: '340px',
                height: '36px',
                background: 'rgba(255, 255, 255, 0.15)',
                backdropFilter: 'blur(20px) saturate(180%)',
                borderRadius: '10px',
                display: 'flex',
                alignItems: 'center',
                padding: '0 12px',
                gap: '8px',
                border: '0.5px solid rgba(255, 255, 255, 0.2)',
              }}>
                <Search className="w-4 h-4" style={{ color: 'rgba(255, 255, 255, 0.6)' }} />
                <span style={{ fontSize: '14px', color: 'rgba(255, 255, 255, 0.6)', fontFamily: 'SF Pro Display, -apple-system, sans-serif' }}>Search</span>
              </div>
            </div>

            {/* Dock */}
            <div style={{
              position: 'absolute',
              bottom: '20px',
              left: '50%',
              transform: 'translateX(-50%)',
              width: 'calc(100% - 48px)',
              maxWidth: '340px',
              height: '80px',
              background: 'rgba(255, 255, 255, 0.15)',
              backdropFilter: 'blur(30px) saturate(180%)',
              borderRadius: '28px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              gap: '32px',
              padding: '0 20px',
              border: '0.5px solid rgba(255, 255, 255, 0.2)',
            }}>
              <DockIcon icon={<Phone className="w-6 h-6" style={{ color: '#fff' }} />} bgColor="#34C759" />
              <DockIcon 
                icon={<img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/52/Safari_browser_logo.svg/500px-Safari_browser_logo.svg.png" alt="Safari" style={{ width: '24px', height: '24px' }} />}
                bgColor="#fff"
                onClick={() => {
                  setActiveApp('safari');
                  setShowHomeScreen(false);
                }}
              />
              <DockIcon icon={<MessageSquare className="w-6 h-6" style={{ color: '#fff' }} />} bgColor="#34C759" />
              <DockIcon icon={<Music className="w-6 h-6" style={{ color: '#fff' }} />} bgColor="#FF3B30" />
            </div>
          </div>
        )}

        {/* Content iframe - Only show when SAFECODE app is active */}
        {!isLocked && !screenOff && !showHomeScreen && activeApp === 'safecode' && (
            <iframe
              key={key}
              srcDoc={getPreviewContent()}
              className="w-full h-full border-0 hide-scrollbar"
              title="Preview"
              sandbox="allow-scripts"
              scrolling="no"
              style={{
                width: '100%',
                height: 'calc(100% - 54px)',
                border: 'none',
                marginTop: '54px',
                animation: 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
                scrollbarWidth: 'none',
                msOverflowStyle: 'none',
                overflow: 'hidden',
              }}
            />
        )}

        {/* Browser for Safari/Chrome - Functional */}
        {!isLocked && !screenOff && !showHomeScreen && (activeApp === 'safari' || activeApp === 'chrome') && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            background: darkMode ? '#000000' : '#fff',
            zIndex: 30,
            paddingTop: '54px',
            display: 'flex',
            flexDirection: 'column',
            animation: 'fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
          }}>
            <div style={{
              height: '44px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              padding: '0 12px',
              background: darkMode ? '#1a1a1a' : '#f5f5f5',
              borderBottom: darkMode ? '1px solid rgba(255,255,255,0.1)' : '1px solid #e0e0e0',
              gap: '8px',
            }}>
              <button
                onClick={() => {
                  setShowHomeScreen(true);
                  setActiveApp(null);
                }}
                style={{
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '6px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: darkMode ? '#fff' : '#000',
                }}
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
              </button>
              <div style={{
                flex: 1,
                height: '32px',
                background: darkMode ? '#2a2a2a' : '#fff',
                borderRadius: '16px',
                display: 'flex',
                alignItems: 'center',
                padding: '0 12px',
                fontSize: '13px',
                color: darkMode ? '#fff' : '#333',
                border: darkMode ? '1px solid rgba(255,255,255,0.1)' : '1px solid #e0e0e0',
                gap: '8px',
              }}>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ opacity: 0.5 }}>
                  <circle cx="11" cy="11" r="8"/>
                  <path d="m21 21-4.35-4.35"/>
                </svg>
                <input
                  type="text"
                  value={browserUrl}
                  onChange={(e) => setBrowserUrl(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      const url = e.currentTarget.value;
                      let finalUrl = url;
                      if (!url.startsWith('http://') && !url.startsWith('https://')) {
                        if (url.includes('.') && !url.includes(' ')) {
                          finalUrl = 'https://' + url;
                        } else {
                          finalUrl = 'https://duckduckgo.com/?q=' + encodeURIComponent(url);
                        }
                      }
                      setBrowserUrl(finalUrl);
                      setIframeError(false);
                      setKey(k => k + 1);
                    }
                  }}
                  style={{
                    flex: 1,
                    border: 'none',
                    outline: 'none',
                    background: 'transparent',
                    fontSize: '13px',
                    color: darkMode ? '#fff' : '#333',
                  }}
                  placeholder="Search or enter website name"
                />
              </div>
              <button
                onClick={() => {
                  let finalUrl = browserUrl;
                  if (!browserUrl.startsWith('http://') && !browserUrl.startsWith('https://')) {
                    if (browserUrl.includes('.') && !browserUrl.includes(' ')) {
                      finalUrl = 'https://' + browserUrl;
                    } else {
                      finalUrl = 'https://duckduckgo.com/?q=' + encodeURIComponent(browserUrl);
                    }
                  }
                  setBrowserUrl(finalUrl);
                  setIframeError(false);
                  setKey(k => k + 1);
                }}
                style={{
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '6px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: darkMode ? '#fff' : '#000',
                }}
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
            {iframeError ? (
              <div style={{
                flex: 1,
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '2rem',
                textAlign: 'center',
                color: darkMode ? '#94a3b8' : '#64748b',
              }}>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke={darkMode ? '#64748b' : '#94a3b8'} strokeWidth="1.5" style={{ marginBottom: '1rem' }}>
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <h3 style={{ fontSize: '1.125rem', fontWeight: 500, marginBottom: '0.5rem', color: darkMode ? '#e2e8f0' : '#1e293b' }}>
                  Site não pode ser exibido em iframe
                </h3>
                <p style={{ fontSize: '0.875rem', marginBottom: '1rem' }}>
                  Este site bloqueia a exibição em frames por motivos de segurança.
                </p>
                <button
                  onClick={() => {
                    setBrowserUrl('about:blank');
                    setIframeError(false);
                    setKey(k => k + 1);
                  }}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '0.25rem',
                    border: `1px solid ${darkMode ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.2)'}`,
                    background: darkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.02)',
                    color: darkMode ? '#e2e8f0' : '#1e293b',
                    cursor: 'pointer',
                    fontSize: '0.875rem',
                  }}
                >
                  Voltar
                </button>
              </div>
            ) : (
              <div style={{
                flex: 1,
                position: 'relative',
                overflow: 'hidden',
                width: '100%',
              }}>
                <iframe
                  key={`browser-${activeApp}-${key}`}
                  src={browserUrl}
                  className="w-full h-full border-0 hide-scrollbar"
                  title={activeApp === 'safari' ? 'Safari Browser' : 'Chrome Browser'}
                  style={{
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    width: 'calc(100% + 17px)',
                    height: '100%',
                    border: 'none',
                    scrollbarWidth: 'none',
                    msOverflowStyle: 'none',
                  }}
                  sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-top-navigation-by-user-activation"
                onLoad={(e) => {
                  setIframeError(false);
                  try {
                    const iframe = e.target as HTMLIFrameElement;
                    // Tentar injetar CSS para esconder scrollbar dentro do iframe
                    try {
                      if (iframe.contentDocument) {
                        const style = iframe.contentDocument.createElement('style');
                        style.textContent = `
                          * { scrollbar-width: none !important; -ms-overflow-style: none !important; }
                          *::-webkit-scrollbar { display: none !important; width: 0 !important; height: 0 !important; }
                        `;
                        iframe.contentDocument.head.appendChild(style);
                      }
                    } catch (cssErr) {
                      // Não é possível injetar CSS em iframes cross-origin, ignorar
                    }
                    // Em desenvolvimento HTTP, alguns sites podem ter problemas
                    const isLocalhost = window.location.protocol === 'http:';
                    if (isLocalhost && iframe.contentDocument === null) {
                      // Pode ser problema de CORS ou política de segurança em HTTP
                      console.warn('Iframe pode ter problemas de segurança em HTTP/localhost');
                    }
                  } catch (err) {
                    // Erros de CORS são esperados em HTTP para alguns sites
                    const isLocalhost = window.location.protocol === 'http:';
                    if (!isLocalhost) {
                      setIframeError(true);
                    }
                  }
                }}
                onError={() => {
                  // Só mostrar erro se não for localhost (HTTP)
                  const isLocalhost = window.location.protocol === 'http:';
                  if (!isLocalhost) {
                    setIframeError(true);
                  }
                }}
              />
              </div>
            )}
          </div>
        )}
      </div>

      {/* Screen Off Overlay */}
      {screenOff && (
        <div style={{
          position: 'absolute',
          top: 0,
          left: 0,
          width: '100%',
          height: '100%',
          background: '#000',
          zIndex: 200,
          borderRadius: '47px',
        }} />
      )}
    </div>
  );

  const renderAndroidFrame = () => (
    <div 
      className={`android-frame ${rotated ? 'landscape' : ''} ${screenOff ? 'screen-off' : ''}`}
      style={{
        width: rotated ? '844px' : '393px',
        height: rotated ? '390px' : '852px',
        borderRadius: '28px',
        background: '#000',
        border: '3px solid #1a1a1a',
        padding: '6px',
        position: 'relative',
        boxShadow: '0 40px 80px rgba(0, 0, 0, 0.8)',
        transform: 'scale(0.75)',
        transformOrigin: 'center center',
        transition: 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
      }}
    >
      {/* Android 14 - Floating Camera (Round) */}
      <div style={{
        position: 'absolute',
        top: '16px',
        left: '50%',
        transform: 'translateX(-50%)',
        width: '24px',
        height: '24px',
        background: '#000',
        borderRadius: '50%',
        zIndex: 100,
        boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
      }} />

      {/* Android Screen */}
      <div style={{
        width: '100%',
        height: '100%',
        background: '#000',
        borderRadius: '22px',
        overflow: 'hidden',
        position: 'relative',
      }}>
        {/* Android 14 Status Bar */}
        {!screenOff && (
          <div style={{
            height: '28px',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            padding: '0 20px 0 24px',
            fontSize: '11px',
            fontWeight: 500,
            color: '#fff',
            position: 'absolute',
            top: '0',
            left: 0,
            right: 0,
            zIndex: 50,
            paddingTop: '8px',
          }}>
            <span style={{ 
              fontFamily: 'Roboto, system-ui, sans-serif',
              letterSpacing: '0.01em',
            }}>{formatTime(currentTime)}</span>
            <div style={{ display: 'flex', alignItems: 'center', gap: '5px', opacity: 0.95 }}>
              {/* Signal Bars - Android 14 style */}
              <svg width="18" height="12" viewBox="0 0 18 12" fill="none">
                <path d="M1 11V9" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
                <path d="M4 11V6" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
                <path d="M7 11V3" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
                <path d="M10 11V1" stroke="white" strokeWidth="1.8" strokeLinecap="round"/>
              </svg>
              {/* Wi-Fi - Android 14 style */}
              <svg width="18" height="14" viewBox="0 0 18 14" fill="none">
                <path d="M1.5 4.5A7.5 7.5 0 0 1 9 1A7.5 7.5 0 0 1 16.5 4.5" stroke="white" strokeWidth="1.2" strokeLinecap="round" fill="none"/>
                <path d="M4.5 6A4.5 4.5 0 0 1 9 4A4.5 4.5 0 0 1 13.5 6" stroke="white" strokeWidth="1.2" strokeLinecap="round" fill="none"/>
                <path d="M7.2 8A1.8 1.8 0 0 1 9 7A1.8 1.8 0 0 1 10.8 8" stroke="white" strokeWidth="1.2" strokeLinecap="round" fill="none"/>
                <circle cx="9" cy="11" r="1.2" fill="white"/>
              </svg>
              {/* Battery - Android 14 style */}
              <svg width="22" height="12" viewBox="0 0 22 12" fill="none">
                <rect x="1" y="2.5" width="17" height="7" rx="1.5" stroke="white" strokeWidth="1.5" fill="none"/>
                <rect x="18.5" y="4.5" width="1.5" height="3" rx="0.5" fill="white"/>
                <rect x="2.5" y="4" width="14" height="4" rx="0.8" fill="white"/>
              </svg>
            </div>
          </div>
        )}

        {/* Android 14 Lock Screen */}
        {isLocked && !screenOff && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            backgroundImage: 'url(https://i.postimg.cc/K82JP2XK/androidwlp.jpg)',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            zIndex: 40,
            display: 'flex',
            flexDirection: 'column',
            justifyContent: 'space-between',
            paddingTop: '100px',
            paddingBottom: '60px',
            color: '#fff',
          }}>
            <div style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '12px',
            }}>
              <div style={{ 
                fontSize: '64px', 
                fontWeight: 300, 
                letterSpacing: '-2px',
                fontFamily: 'Roboto, system-ui, sans-serif',
                lineHeight: 1,
              }}>
                {formatTime(currentTime)}
              </div>
              <div style={{ 
                fontSize: '16px', 
                opacity: 0.9,
                fontWeight: 400,
                fontFamily: 'Roboto, system-ui, sans-serif',
              }}>
                {formatDate(currentDate)}
              </div>
            </div>
            <div style={{
              display: 'flex',
              justifyContent: 'center',
              alignItems: 'center',
              gap: '8px',
            }}>
              {/* Android 14 Unlock Button - Material You style */}
              <button
                onClick={() => {
                  setIsUnlocking(true);
                  setTimeout(() => {
                    setIsLocked(false);
                    setShowHomeScreen(true);
                    setIsUnlocking(false);
                  }, 400);
                }}
                style={{
                  width: '64px',
                  height: '64px',
                  borderRadius: '50%',
                  border: 'none',
                  background: 'rgba(255, 255, 255, 0.15)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: '#fff',
                  cursor: 'pointer',
                  transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                  transform: isUnlocking ? 'scale(0.9)' : 'scale(1)',
                  boxShadow: '0 4px 16px rgba(0, 0, 0, 0.2)',
                }}
                onMouseEnter={(e) => {
                  if (!isUnlocking) {
                    e.currentTarget.style.background = 'rgba(255, 255, 255, 0.25)';
                    e.currentTarget.style.transform = 'scale(1.1)';
                    e.currentTarget.style.boxShadow = '0 6px 20px rgba(0, 0, 0, 0.3)';
                  }
                }}
                onMouseLeave={(e) => {
                  if (!isUnlocking) {
                    e.currentTarget.style.background = 'rgba(255, 255, 255, 0.15)';
                    e.currentTarget.style.transform = 'scale(1)';
                    e.currentTarget.style.boxShadow = '0 4px 16px rgba(0, 0, 0, 0.2)';
                  }
                }}
              >
                <svg 
                  width="28" 
                  height="28" 
                  viewBox="0 0 24 24" 
                  fill="none" 
                  stroke="currentColor" 
                  strokeWidth="2.5"
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  style={{
                    transform: isUnlocking ? 'translateX(6px)' : 'translateX(0)',
                    transition: 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
                  }}
                >
                  <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </div>
        )}

        {/* Android Home Screen */}
        {!isLocked && !screenOff && showHomeScreen && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            backgroundImage: 'url(https://i.postimg.cc/K82JP2XK/androidwlp.jpg)',
            backgroundSize: 'cover',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            zIndex: 30,
            paddingTop: '28px',
            paddingBottom: '80px',
            overflowY: 'auto',
            animation: 'fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
            scrollbarWidth: 'none',
            msOverflowStyle: 'none',
          }} className="hide-scrollbar">
            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(4, 1fr)',
              gap: '32px',
              padding: '40px 24px',
              maxWidth: '100%',
            }}>
              {/* Chrome */}
              <button
                onClick={() => {
                  setActiveApp('chrome');
                  setShowHomeScreen(false);
                }}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '8px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '8px',
                  borderRadius: '12px',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'scale(1.1)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                <div style={{
                  width: '56px',
                  height: '56px',
                  borderRadius: '12px',
                  background: 'rgba(255, 255, 255, 0.25)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '0.5px solid rgba(255, 255, 255, 0.3)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.15)',
                  overflow: 'hidden',
                }}>
                  <img 
                    src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e1/Google_Chrome_icon_%28February_2022%29.svg/2048px-Google_Chrome_icon_%28February_2022%29.svg.png"
                    alt="Chrome"
                    style={{ width: '46px', height: '46px', objectFit: 'contain' }}
                  />
                </div>
                <span style={{ fontSize: '12px', color: '#fff', fontFamily: 'Roboto, system-ui, sans-serif', textShadow: '0 1px 3px rgba(0,0,0,0.3)' }}>Chrome</span>
              </button>

              {/* SAFECODE IDE App */}
              <button
                onClick={() => {
                  setActiveApp('safecode');
                  setShowHomeScreen(false);
                }}
                style={{
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  gap: '8px',
                  background: 'transparent',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '8px',
                  borderRadius: '12px',
                  transition: 'all 0.2s',
                }}
                onMouseEnter={(e) => {
                  e.currentTarget.style.transform = 'scale(1.1)';
                }}
                onMouseLeave={(e) => {
                  e.currentTarget.style.transform = 'scale(1)';
                }}
              >
                <div style={{
                  width: '56px',
                  height: '56px',
                  borderRadius: '12px',
                  background: 'rgba(0, 0, 0, 0.6)',
                  backdropFilter: 'blur(20px) saturate(180%)',
                  border: '0.5px solid rgba(255, 255, 255, 0.2)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  boxShadow: '0 2px 8px rgba(0, 0, 0, 0.3)',
                  overflow: 'hidden',
                }}>
                  <img 
                    src={getLogoPath()}
                    alt="SAFECODE"
                    style={{ width: '46px', height: '46px', objectFit: 'contain' }}
                    onError={(e) => {
                      e.currentTarget.style.display = 'none';
                      const parent = e.currentTarget.parentElement;
                      if (parent) {
                        parent.innerHTML = '<div style="width: 46px; height: 46px; background: #000; display: flex; align-items: center; justify-content: center;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M16 18h6M12 18h2M8 18h2M4 18h2M16 6h6M12 6h2M8 6h2M4 6h2"/></svg></div>';
                      }
                    }}
                  />
                </div>
                <span style={{ fontSize: '12px', color: '#fff', fontFamily: 'Roboto, system-ui, sans-serif', textShadow: '0 1px 3px rgba(0,0,0,0.3)' }}>SAFECODE</span>
              </button>
            </div>
          </div>
        )}

        {/* Content iframe - Only show when SAFECODE app is active */}
        {!isLocked && !screenOff && !showHomeScreen && activeApp === 'safecode' && (
          <iframe
            key={key}
            srcDoc={getPreviewContent()}
            className="w-full h-full border-0 hide-scrollbar"
            title="Preview"
            sandbox="allow-scripts"
            scrolling="no"
            style={{
              width: '100%',
              height: 'calc(100% - 28px)',
              border: 'none',
              marginTop: '28px',
              animation: 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
              scrollbarWidth: 'none',
              overflow: 'hidden',
              msOverflowStyle: 'none',
            }}
          />
        )}

        {/* Browser for Chrome - Functional */}
        {!isLocked && !screenOff && !showHomeScreen && activeApp === 'chrome' && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            background: darkMode ? '#000000' : '#fff',
            zIndex: 30,
            paddingTop: '28px',
            display: 'flex',
            flexDirection: 'column',
            animation: 'fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
          }}>
            <div style={{
              height: '48px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              padding: '0 12px',
              background: darkMode ? '#1a1a1a' : '#f5f5f5',
              borderBottom: darkMode ? '1px solid rgba(255,255,255,0.1)' : '1px solid #e0e0e0',
              gap: '8px',
            }}>
              <button
                onClick={() => {
                  setShowHomeScreen(true);
                  setActiveApp(null);
                }}
                style={{
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '6px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: darkMode ? '#fff' : '#000',
                }}
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
              </button>
              <div style={{
                flex: 1,
                height: '36px',
                background: darkMode ? '#2a2a2a' : '#fff',
                borderRadius: '18px',
                display: 'flex',
                alignItems: 'center',
                padding: '0 16px',
                fontSize: '13px',
                color: darkMode ? '#fff' : '#333',
                boxShadow: darkMode ? '0 1px 3px rgba(0,0,0,0.3)' : '0 1px 3px rgba(0,0,0,0.1)',
                border: darkMode ? '1px solid rgba(255,255,255,0.1)' : '1px solid #e0e0e0',
                gap: '8px',
              }}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ opacity: 0.5 }}>
                  <circle cx="11" cy="11" r="8"/>
                  <path d="m21 21-4.35-4.35"/>
                </svg>
                <input
                  type="text"
                  value={browserUrl}
                  onChange={(e) => setBrowserUrl(e.target.value)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                      const url = e.currentTarget.value;
                      let finalUrl = url;
                      if (!url.startsWith('http://') && !url.startsWith('https://')) {
                        if (url.includes('.') && !url.includes(' ')) {
                          finalUrl = 'https://' + url;
                        } else {
                          finalUrl = 'https://duckduckgo.com/?q=' + encodeURIComponent(url);
                        }
                      }
                      setBrowserUrl(finalUrl);
                      setIframeError(false);
                      setKey(k => k + 1);
                    }
                  }}
                  style={{
                    flex: 1,
                    border: 'none',
                    outline: 'none',
                    background: 'transparent',
                    fontSize: '13px',
                    color: darkMode ? '#fff' : '#333',
                  }}
                  placeholder="Search Google or type URL"
                />
              </div>
              <button
                onClick={() => {
                  let finalUrl = browserUrl;
                  if (!browserUrl.startsWith('http://') && !browserUrl.startsWith('https://')) {
                    if (browserUrl.includes('.') && !browserUrl.includes(' ')) {
                      finalUrl = 'https://' + browserUrl;
                    } else {
                      finalUrl = 'https://duckduckgo.com/?q=' + encodeURIComponent(browserUrl);
                    }
                  }
                  setBrowserUrl(finalUrl);
                  setIframeError(false);
                  setKey(k => k + 1);
                }}
                style={{
                  background: 'none',
                  border: 'none',
                  cursor: 'pointer',
                  padding: '6px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  color: darkMode ? '#fff' : '#000',
                }}
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
            {iframeError ? (
              <div style={{
                flex: 1,
                display: 'flex',
                flexDirection: 'column',
                alignItems: 'center',
                justifyContent: 'center',
                padding: '2rem',
                textAlign: 'center',
                color: darkMode ? '#94a3b8' : '#64748b',
              }}>
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke={darkMode ? '#64748b' : '#94a3b8'} strokeWidth="1.5" style={{ marginBottom: '1rem' }}>
                  <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                  <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
                <h3 style={{ fontSize: '1.125rem', fontWeight: 500, marginBottom: '0.5rem', color: darkMode ? '#e2e8f0' : '#1e293b' }}>
                  Site não pode ser exibido em iframe
                </h3>
                <p style={{ fontSize: '0.875rem', marginBottom: '1rem' }}>
                  Este site bloqueia a exibição em frames por motivos de segurança.
                </p>
                <button
                  onClick={() => {
                    setBrowserUrl('about:blank');
                    setIframeError(false);
                    setKey(k => k + 1);
                  }}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '0.25rem',
                    border: `1px solid ${darkMode ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.2)'}`,
                    background: darkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.02)',
                    color: darkMode ? '#e2e8f0' : '#1e293b',
                    cursor: 'pointer',
                    fontSize: '0.875rem',
                  }}
                >
                  Voltar
                </button>
              </div>
            ) : (
              <div style={{
                flex: 1,
                position: 'relative',
                overflow: 'hidden',
                width: '100%',
              }}>
                <iframe
                  key={`browser-chrome-${key}`}
                  src={browserUrl}
                  className="w-full h-full border-0 hide-scrollbar"
                  title="Chrome Browser"
                  style={{
                    position: 'absolute',
                    top: 0,
                    left: 0,
                    width: 'calc(100% + 17px)',
                    height: '100%',
                    border: 'none',
                    scrollbarWidth: 'none',
                    msOverflowStyle: 'none',
                  }}
                  sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-top-navigation-by-user-activation"
                onLoad={(e) => {
                  try {
                    const iframe = e.target as HTMLIFrameElement;
                    if (iframe.contentWindow?.location.href === 'about:blank' || !iframe.contentDocument) {
                      setTimeout(() => {
                        try {
                          iframe.contentWindow?.location.href;
                        } catch (err) {
                          setIframeError(true);
                        }
                      }, 1000);
                    } else {
                      setIframeError(false);
                    }
                  } catch (err) {
                    setIframeError(true);
                  }
                }}
                onError={() => setIframeError(true)}
              />
              </div>
            )}
          </div>
        )}

        {/* Screen Off Overlay */}
        {screenOff && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            background: '#000',
            zIndex: 200,
            borderRadius: '22px',
          }} />
        )}
      </div>
    </div>
  );

  // Se a janela flutuante estiver aberta, renderizar apenas ela via Portal e retornar null
  if (isFloating && isMobile) {
    return createPortal(
      <div
        style={{
          position: 'fixed',
          left: `${floatingPosition.x}px`,
          top: `${floatingPosition.y}px`,
          width: `${floatingSize.width}px`,
          height: `${floatingSize.height}px`,
          zIndex: 999999,
          borderRadius: '12px',
          overflow: 'hidden',
          boxShadow: '0 20px 60px rgba(0, 0, 0, 0.9)',
          border: '2px solid rgba(255, 255, 255, 0.1)',
          background: '#000',
          cursor: isDragging ? 'grabbing' : (isResizing ? 'nwse-resize' : 'default'),
          display: 'flex',
          flexDirection: 'column',
          pointerEvents: 'auto',
        }}
      >
        {/* Floating Window Header */}
        <div
          style={{
            height: '32px',
            background: 'rgba(20, 20, 20, 0.95)',
            backdropFilter: 'blur(12px)',
            borderBottom: '1px solid rgba(255, 255, 255, 0.1)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            padding: '0 12px',
            cursor: isDragging ? 'grabbing' : 'grab',
            flexShrink: 0,
            userSelect: 'none',
            WebkitUserSelect: 'none',
          }}
          onMouseDown={(e) => handleFloatingDragStart(e)}
          onTouchStart={(e) => handleFloatingDragStart(e)}
        >
          <div style={{ 
            fontSize: '11px', 
            color: '#999',
            fontFamily: 'system-ui, sans-serif',
            userSelect: 'none',
          }}>
            {previewMode === 'ios' ? 'iPhone Preview' : 'Android Preview'}
          </div>
          <button
            onClick={(e) => {
              e.stopPropagation();
              closeFloatingWindow();
            }}
            style={{
              background: 'transparent',
              border: 'none',
              color: '#999',
              cursor: 'pointer',
              padding: '4px',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              borderRadius: '4px',
              transition: 'all 0.2s',
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.background = 'rgba(255, 255, 255, 0.1)';
              e.currentTarget.style.color = '#fff';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.background = 'transparent';
              e.currentTarget.style.color = '#999';
            }}
            title="Voltar ao Preview Normal"
          >
            <X className="w-3.5 h-3.5" />
          </button>
        </div>

        {/* Floating Preview Content */}
        <div style={{ 
          width: '100%', 
          height: 'calc(100% - 32px - 60px)',
          overflow: 'hidden',
          position: 'relative',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          background: '#000',
          flex: 1,
        }}>
          {previewMode === 'ios' && (
            <div style={{ 
              transform: `scale(${Math.min(floatingSize.width / 430, floatingSize.height / 932)})`,
              transformOrigin: 'center center',
            }}>
              {renderIOSFrame()}
            </div>
          )}
          {previewMode === 'android' && (
            <div style={{ 
              transform: `scale(${Math.min(floatingSize.width / 393, floatingSize.height / 852)})`,
              transformOrigin: 'center center',
            }}>
              {renderAndroidFrame()}
            </div>
          )}
        </div>

        {/* Device Controls Menu - Inside floating window */}
        <div 
          className="device-controls-menu"
          style={{
            height: '60px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '12px',
            padding: '0 16px',
            background: 'rgba(20, 20, 20, 0.9)',
            backdropFilter: 'blur(12px)',
            borderTop: '1px solid rgba(255, 255, 255, 0.1)',
            flexShrink: 0,
          }}
        >
          <button
            onClick={() => {
              setShowHomeScreen(true);
              setActiveApp(null);
            }}
            className="control-btn"
            title="Home"
            style={{
              width: '36px',
              height: '36px',
              borderRadius: '50%',
              border: 'none',
              background: showHomeScreen ? 'rgba(59, 130, 246, 0.2)' : 'rgba(255, 255, 255, 0.05)',
              color: showHomeScreen ? '#3b82f6' : '#ccc',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.2s',
            }}
          >
            <Home className="w-4 h-4" />
          </button>
          <button
            onClick={() => setRotated(!rotated)}
            className="control-btn"
            title="Rotate"
            style={{
              width: '36px',
              height: '36px',
              borderRadius: '50%',
              border: 'none',
              background: 'rgba(255, 255, 255, 0.05)',
              color: '#ccc',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.2s',
            }}
          >
            <RotateCcw className="w-4 h-4" />
          </button>
          <button
            onClick={() => setIsLocked(!isLocked)}
            className="control-btn"
            title={isLocked ? 'Unlock' : 'Lock'}
            style={{
              width: '36px',
              height: '36px',
              borderRadius: '50%',
              border: 'none',
              background: isLocked ? 'rgba(59, 130, 246, 0.2)' : 'rgba(255, 255, 255, 0.05)',
              color: isLocked ? '#3b82f6' : '#ccc',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.2s',
            }}
          >
            <Lock className="w-4 h-4" />
          </button>
          <button
            onClick={() => {
              setScreenOff(!screenOff);
              if (screenOff) setIsLocked(true);
            }}
            className="control-btn"
            title={screenOff ? 'Turn On' : 'Turn Off'}
            style={{
              width: '36px',
              height: '36px',
              borderRadius: '50%',
              border: 'none',
              background: screenOff ? 'rgba(239, 68, 68, 0.2)' : 'rgba(255, 255, 255, 0.05)',
              color: screenOff ? '#ef4444' : '#ccc',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              transition: 'all 0.2s',
            }}
          >
            <Power className="w-4 h-4" />
          </button>
        </div>

        {/* Resize Handle - Canto inferior direito */}
        <div
          onMouseDown={(e) => {
            e.preventDefault();
            e.stopPropagation();
            handleResizeStart(e, 'both');
          }}
          onTouchStart={(e) => {
            e.preventDefault();
            e.stopPropagation();
            handleResizeStart(e, 'both');
          }}
          style={{
            position: 'absolute',
            bottom: 0,
            right: 0,
            width: '24px',
            height: '24px',
            cursor: 'nwse-resize',
            background: 'linear-gradient(135deg, transparent 0%, transparent 40%, rgba(255, 255, 255, 0.4) 40%, rgba(255, 255, 255, 0.4) 45%, transparent 45%, transparent 60%, rgba(255, 255, 255, 0.4) 60%, rgba(255, 255, 255, 0.4) 65%, transparent 65%)',
            zIndex: 10001,
            userSelect: 'none',
            WebkitUserSelect: 'none',
            pointerEvents: 'auto',
          }}
          title="Redimensionar"
        />
        {/* Resize Handle - Borda direita (para redimensionar largura) */}
        <div
          onMouseDown={(e) => {
            e.preventDefault();
            e.stopPropagation();
            handleResizeStart(e, 'horizontal');
          }}
          onTouchStart={(e) => {
            e.preventDefault();
            e.stopPropagation();
            handleResizeStart(e, 'horizontal');
          }}
          style={{
            position: 'absolute',
            top: 0,
            right: 0,
            bottom: 0,
            width: '20px',
            cursor: 'ew-resize',
            zIndex: 10001,
            userSelect: 'none',
            WebkitUserSelect: 'none',
            pointerEvents: 'auto',
            background: 'transparent',
          }}
          title="Ajustar largura"
        />
        {/* Resize Handle - Borda inferior (para redimensionar altura) */}
        <div
          onMouseDown={(e) => {
            e.preventDefault();
            e.stopPropagation();
            handleResizeStart(e, 'vertical');
          }}
          onTouchStart={(e) => {
            e.preventDefault();
            e.stopPropagation();
            handleResizeStart(e, 'vertical');
          }}
          style={{
            position: 'absolute',
            left: 0,
            right: 0,
            bottom: 0,
            height: '12px',
            cursor: 'ns-resize',
            zIndex: 10000,
            userSelect: 'none',
            WebkitUserSelect: 'none',
            pointerEvents: 'auto',
          }}
          title="Ajustar altura"
        />
      </div>,
      document.body
    );
  }

  return (
    <div className="h-full flex flex-col" style={{ backgroundColor: '#000000' }}>
      {/* Preview toolbar */}
      <div className="flex items-center justify-between px-3 py-2 h-9" style={{ backgroundColor: '#000000' }}>
        <div className="flex items-center gap-1">
          {previewModes.map(({ mode, icon, label }) => (
            <button
              key={mode}
              onClick={() => {
                setPreviewMode(mode);
                if (mode === 'desktop' || mode === 'tablet') {
                  setIsLocked(false);
                  setShowHomeScreen(false);
                  setActiveApp('safecode');
                } else {
                  setShowHomeScreen(true);
                  setActiveApp(null);
                }
              }}
              className={`p-1.5 rounded transition-colors ${
                previewMode === mode 
                  ? 'bg-primary text-primary-foreground' 
                  : 'text-muted-foreground hover:bg-muted'
              }`}
              title={label}
            >
              {icon}
            </button>
          ))}
        </div>

        <div className="flex items-center gap-1">
          {isMobile && (
            <button
              onClick={() => {
                if (isFloating) {
                  closeFloatingWindow();
                } else {
                  openFloatingWindow();
                }
              }}
              className={`p-1.5 rounded transition-colors ${
                isFloating 
                  ? 'bg-primary text-primary-foreground' 
                  : 'text-muted-foreground hover:bg-muted'
              }`}
              title={isFloating ? 'Fechar Janela Flutuante' : 'Abrir em Nova Janela'}
            >
              <Maximize2 className="w-4 h-4" />
            </button>
          )}
          {previewMode !== 'desktop' && (
            <button
              onClick={() => setRotated(!rotated)}
              className="p-1.5 rounded text-muted-foreground hover:bg-muted transition-colors"
              title="Rotate"
            >
              <RotateCcw className="w-4 h-4" />
            </button>
          )}
          <button
            onClick={() => setDarkMode(!darkMode)}
            className="p-1.5 rounded text-muted-foreground hover:bg-muted transition-colors"
            title="Toggle Dark Mode"
          >
            {darkMode ? <Moon className="w-4 h-4" /> : <Sun className="w-4 h-4" />}
          </button>
          <button
            onClick={() => setKey(k => k + 1)}
            className="p-1.5 rounded text-muted-foreground hover:bg-muted transition-colors"
            title="Refresh"
          >
            <RefreshCw className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Preview frame */}
      {!(isFloating && isMobile) && (
      <div 
        className="flex-1 flex items-center justify-center p-4 relative mobile-preview-container" 
        style={{ 
          backgroundColor: '#000000',
          overflow: 'auto',
        }}
      >
        {previewMode === 'desktop' && (
          <div
            className="bg-background rounded-lg shadow-2xl overflow-hidden transition-all duration-300"
            style={{
              width: '100%',
              height: '100%',
            }}
          >
            <iframe
              key={key}
              srcDoc={getPreviewContent()}
              className="w-full h-full border-0"
              title="Preview"
              sandbox="allow-scripts"
            />
          </div>
        )}

        {previewMode === 'tablet' && (
          <div
            className="bg-background rounded-lg shadow-2xl overflow-hidden transition-all duration-300 border-8 border-gray-800 rounded-3xl"
            style={{
              width: size.width,
              height: size.height,
              maxWidth: '100%',
              maxHeight: '100%',
            }}
          >
            <iframe
              key={key}
              srcDoc={getPreviewContent()}
              className="w-full h-full border-0"
              title="Preview"
              sandbox="allow-scripts"
            />
          </div>
        )}

        {previewMode === 'ios' && renderIOSFrame()}
        {previewMode === 'android' && renderAndroidFrame()}

          {/* Device Controls Menu - Only for mobile */}
        {isMobile && !isFloating && (
          <div
            className="device-controls-menu"
            style={{
              position: 'absolute',
              bottom: 'clamp(-20px, -2%, -14px)',
              left: '50%',
              transform: 'translateX(-50%)',
              display: 'flex',
              gap: '12px',
              padding: '10px 16px',
              background: 'rgba(20, 20, 20, 0.9)',
              backdropFilter: 'blur(12px)',
              border: '1px solid rgba(255, 255, 255, 0.1)',
              borderRadius: '24px',
              zIndex: 1000,
              boxShadow: '0 10px 25px rgba(0, 0, 0, 0.5)',
            }}
          >
            <button
              onClick={() => {
                setShowHomeScreen(true);
                setActiveApp(null);
              }}
              className="control-btn"
              title="Home"
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '50%',
                border: 'none',
                background: showHomeScreen ? 'rgba(59, 130, 246, 0.2)' : 'rgba(255, 255, 255, 0.05)',
                color: showHomeScreen ? '#3b82f6' : '#ccc',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
              }}
            >
              <Home className="w-4 h-4" />
            </button>
            <button
              onClick={() => setRotated(!rotated)}
              className="control-btn"
              title="Rotate"
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '50%',
                border: 'none',
                background: 'rgba(255, 255, 255, 0.05)',
                color: '#ccc',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
              }}
            >
              <RotateCcw className="w-4 h-4" />
            </button>
            <button
              onClick={() => setIsLocked(!isLocked)}
              className="control-btn"
              title={isLocked ? 'Unlock' : 'Lock'}
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '50%',
                border: 'none',
                background: isLocked ? 'rgba(59, 130, 246, 0.2)' : 'rgba(255, 255, 255, 0.05)',
                color: isLocked ? '#3b82f6' : '#ccc',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
              }}
            >
              <Lock className="w-4 h-4" />
            </button>
            <button
              onClick={() => {
                setScreenOff(!screenOff);
                if (screenOff) setIsLocked(true);
              }}
              className="control-btn"
              title={screenOff ? 'Turn On' : 'Turn Off'}
              style={{
                width: '36px',
                height: '36px',
                borderRadius: '50%',
                border: 'none',
                background: screenOff ? 'rgba(239, 68, 68, 0.2)' : 'rgba(255, 255, 255, 0.05)',
                color: screenOff ? '#ef4444' : '#ccc',
                cursor: 'pointer',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                transition: 'all 0.2s',
              }}
            >
              <Power className="w-4 h-4" />
            </button>
          </div>
        )}
      </div>
      )}

      {/* Preview status */}
      {previewMode !== 'desktop' && (
        <div className="px-3 py-1 text-xs text-muted-foreground text-center border-t border-panel-border">
          {previewMode === 'tablet' 
            ? (rotated ? '1024 × 768' : '768 × 1024')
            : previewMode === 'ios'
            ? (rotated ? '844 × 390' : '390 × 844')
            : (rotated ? '844 × 390' : '360 × 800')
          }
          {rotated && ' (horizontal)'}
        </div>
      )}

    </div>
  );
};
