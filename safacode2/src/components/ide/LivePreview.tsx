import { useState, useEffect } from 'react';
import { Monitor, Tablet, Smartphone, RefreshCw, RotateCcw, Lock, Power, Flashlight, Camera, Moon, Sun, Globe, Code, Phone, MessageSquare, Music, Mail, Calendar, Image, Video, Settings, Map, Heart, Wallet, Cloud, Search, Clock, Home } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import type { PreviewMode } from '@/types/ide';

export const LivePreview: React.FC = () => {
  const { previewMode, setPreviewMode, tabs, activeTabId } = useIDEStore();
  const [darkMode, setDarkMode] = useState(false);
  const [rotated, setRotated] = useState(false);
  const [isLocked, setIsLocked] = useState(true);
  const [screenOff, setScreenOff] = useState(false);
  const [showHomeScreen, setShowHomeScreen] = useState(true);
  const [activeApp, setActiveApp] = useState<string | null>(null);
  const [browserUrl, setBrowserUrl] = useState('https://www.google.com');
  const [key, setKey] = useState(0);
  const [currentTime, setCurrentTime] = useState(new Date());
  const [currentDate, setCurrentDate] = useState(new Date());
  const [swipeStart, setSwipeStart] = useState<{ y: number; time: number } | null>(null);
  const [swipeOffset, setSwipeOffset] = useState(0);
  const [isUnlocking, setIsUnlocking] = useState(false);
  
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

  // Get preview content from active tab or default
  const getPreviewContent = () => {
    if (activeTab && activeTab.content) {
      if (activeTab.language === 'html') {
        return activeTab.content;
      }
      if (activeTab.language === 'typescript' || activeTab.language === 'javascript') {
        const bgColor = darkMode ? '#0f172a' : '#ffffff';
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
    
    return `
    <!DOCTYPE html>
    <html style="background: ${darkMode ? '#0f172a' : '#ffffff'}; color: ${darkMode ? '#f8fafc' : '#0f172a'};">
    <head>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          min-height: 100vh;
          display: flex;
          flex-direction: column;
        }
        .header {
          padding: 1rem 2rem;
          display: flex;
          justify-content: space-between;
          align-items: center;
          border-bottom: 1px solid ${darkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)'};
        }
        .header h1 { 
          font-size: 1.25rem; 
          font-weight: 600;
          background: linear-gradient(135deg, #3b82f6, #8b5cf6);
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
        }
        .btn {
          padding: 0.5rem 1rem;
          border-radius: 0.375rem;
          border: none;
          background: #3b82f6;
          color: white;
          cursor: pointer;
          font-weight: 500;
        }
        main {
          flex: 1;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 2rem;
          text-align: center;
        }
        main h2 {
          font-size: 2rem;
          font-weight: 700;
          margin-bottom: 1rem;
        }
        main p {
          color: ${darkMode ? '#94a3b8' : '#64748b'};
          max-width: 400px;
        }
      </style>
    </head>
    <body>
      <header class="header">
        <h1>SAFECODE</h1>
        <button class="btn">Settings</button>
      </header>
      <main>
        <h2>Welcome to SAFECODE</h2>
        <p>Your modern code editor. Start building amazing projects with real-time preview.</p>
      </main>
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
            style={{
              width: '100%',
              height: 'calc(100% - 54px)',
              border: 'none',
              marginTop: '54px',
              animation: 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
              scrollbarWidth: 'none',
              msOverflowStyle: 'none',
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
            background: '#fff',
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
              background: '#f5f5f5',
              borderBottom: '1px solid #e0e0e0',
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
                }}
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
              </button>
              <div style={{
                flex: 1,
                height: '32px',
                background: '#fff',
                borderRadius: '16px',
                display: 'flex',
                alignItems: 'center',
                padding: '0 12px',
                fontSize: '13px',
                color: '#333',
                border: '1px solid #e0e0e0',
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
                          finalUrl = 'https://www.google.com/search?q=' + encodeURIComponent(url);
                        }
                      }
                      setBrowserUrl(finalUrl);
                      setKey(k => k + 1);
                    }
                  }}
                  style={{
                    flex: 1,
                    border: 'none',
                    outline: 'none',
                    background: 'transparent',
                    fontSize: '13px',
                    color: '#333',
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
                      finalUrl = 'https://www.google.com/search?q=' + encodeURIComponent(browserUrl);
                    }
                  }
                  setBrowserUrl(finalUrl);
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
                }}
              >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
            <iframe
              key={`browser-${activeApp}-${key}`}
              src={browserUrl}
              className="w-full flex-1 border-0 hide-scrollbar"
              title={activeApp === 'safari' ? 'Safari Browser' : 'Chrome Browser'}
              style={{
                width: '100%',
                flex: 1,
                border: 'none',
                scrollbarWidth: 'none',
                msOverflowStyle: 'none',
              }}
              sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals"
            />
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
            style={{
              width: '100%',
              height: 'calc(100% - 28px)',
              border: 'none',
              marginTop: '28px',
              animation: 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
              scrollbarWidth: 'none',
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
            background: '#fff',
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
              background: '#f5f5f5',
              borderBottom: '1px solid #e0e0e0',
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
                }}
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
              </button>
              <div style={{
                flex: 1,
                height: '36px',
                background: '#fff',
                borderRadius: '18px',
                display: 'flex',
                alignItems: 'center',
                padding: '0 16px',
                fontSize: '13px',
                color: '#333',
                boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
                border: '1px solid #e0e0e0',
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
                          finalUrl = 'https://www.google.com/search?q=' + encodeURIComponent(url);
                        }
                      }
                      setBrowserUrl(finalUrl);
                      setKey(k => k + 1);
                    }
                  }}
                  style={{
                    flex: 1,
                    border: 'none',
                    outline: 'none',
                    background: 'transparent',
                    fontSize: '13px',
                    color: '#333',
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
                      finalUrl = 'https://www.google.com/search?q=' + encodeURIComponent(browserUrl);
                    }
                  }
                  setBrowserUrl(finalUrl);
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
                }}
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
            <iframe
              key={`browser-chrome-${key}`}
              src={browserUrl}
              className="w-full flex-1 border-0 hide-scrollbar"
              title="Chrome Browser"
              style={{
                width: '100%',
                flex: 1,
                border: 'none',
                scrollbarWidth: 'none',
                msOverflowStyle: 'none',
              }}
              sandbox="allow-same-origin allow-scripts allow-forms allow-popups allow-modals"
            />
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
        {isMobile && (
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
