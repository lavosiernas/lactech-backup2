import { useState, useEffect } from 'react';
import { Monitor, Tablet, Smartphone, RefreshCw, RotateCcw, Lock, Power, Flashlight, Camera, Moon, Sun } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import type { PreviewMode } from '@/types/ide';

export const LivePreview: React.FC = () => {
  const { previewMode, setPreviewMode, tabs, activeTabId } = useIDEStore();
  const [darkMode, setDarkMode] = useState(false);
  const [rotated, setRotated] = useState(false);
  const [isLocked, setIsLocked] = useState(true);
  const [screenOff, setScreenOff] = useState(false);
  const [key, setKey] = useState(0);
  const [currentTime, setCurrentTime] = useState(new Date());
  const [currentDate, setCurrentDate] = useState(new Date());
  const [swipeStart, setSwipeStart] = useState<{ y: number; time: number } | null>(null);
  const [swipeOffset, setSwipeOffset] = useState(0);
  const [isUnlocking, setIsUnlocking] = useState(false);
  
  const activeTab = tabs.find(t => t.id === activeTabId);

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
        {isLocked && (
          <Lock className="w-3.5 h-3.5" style={{ color: '#fff', opacity: 0.85 }} />
        )}
        <div style={{
          width: '14px',
          height: '14px',
          background: 'radial-gradient(circle at 30% 30%, rgba(255, 255, 255, 0.1) 0%, #1a1a1a 40%, #000 100%)',
          borderRadius: '50%',
          border: '0.5px solid rgba(255, 255, 255, 0.15)',
          boxShadow: 'inset 0 0 6px rgba(0, 0, 0, 0.5), 0 0 2px rgba(255, 255, 255, 0.1)',
        }} />
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
            <div style={{ display: 'flex', alignItems: 'center', gap: '4px', opacity: 0.95 }}>
              {/* Signal Bars - iOS 18+ style */}
              <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                <path d="M1 11L1 9M3.5 11L3.5 6M6 11L6 3M8.5 11L8.5 1" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
              {/* Wi-Fi - iOS 18+ style */}
              <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                <path d="M8 1C10.5 1 12.5 2 13.5 3.5M8 4.5C9.5 4.5 10.5 5.2 11 6M8 7.5L8.5 8" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                <circle cx="8" cy="9.5" r="1" fill="white"/>
              </svg>
              {/* Battery - iOS 18+ style */}
              <svg width="20" height="12" viewBox="0 0 20 12" fill="none">
                <rect x="1" y="3" width="16" height="6" rx="1.5" stroke="white" strokeWidth="1.5" fill="none"/>
                <rect x="17.5" y="5" width="1.5" height="2" rx="0.5" fill="white"/>
                <rect x="2.5" y="4.5" width="13" height="5" rx="1" fill="white"/>
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

        {/* Content iframe */}
        {!isLocked && !screenOff && (
          <iframe
            key={key}
            srcDoc={getPreviewContent()}
            className="w-full h-full border-0"
            title="Preview"
            sandbox="allow-scripts"
            style={{
              width: '100%',
              height: 'calc(100% - 54px)',
              border: 'none',
              marginTop: '54px',
              animation: 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
            }}
          />
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
      {/* Android 14 - Centered Punch Hole */}
      <div style={{
        position: 'absolute',
        top: '12px',
        left: '50%',
        transform: 'translateX(-50%)',
        width: '32px',
        height: '20px',
        background: '#000',
        borderRadius: '0 0 16px 16px',
        zIndex: 100,
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
            <div style={{ display: 'flex', alignItems: 'center', gap: '4px', opacity: 0.95 }}>
              {/* Signal Bars - Android 14 style */}
              <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                <path d="M1 11L1 9M3.5 11L3.5 6M6 11L6 3M8.5 11L8.5 1" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
              </svg>
              {/* Wi-Fi - Android 14 style */}
              <svg width="16" height="12" viewBox="0 0 16 12" fill="none">
                <path d="M8 1C10.5 1 12.5 2 13.5 3.5M8 4.5C9.5 4.5 10.5 5.2 11 6M8 7.5L8.5 8" stroke="white" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
                <circle cx="8" cy="9.5" r="1" fill="white"/>
              </svg>
              {/* Battery - Android 14 style */}
              <svg width="20" height="12" viewBox="0 0 20 12" fill="none">
                <rect x="1" y="3" width="16" height="6" rx="1.5" stroke="white" strokeWidth="1.5" fill="none"/>
                <rect x="17.5" y="5" width="1.5" height="2" rx="0.5" fill="white"/>
                <rect x="2.5" y="4.5" width="13" height="5" rx="1" fill="white"/>
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

        {/* Content iframe */}
        {!isLocked && !screenOff && (
          <iframe
            key={key}
            srcDoc={getPreviewContent()}
            className="w-full h-full border-0"
            title="Preview"
            sandbox="allow-scripts"
            style={{
              width: '100%',
              height: 'calc(100% - 24px)',
              border: 'none',
              marginTop: '24px',
              animation: 'fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
            }}
          />
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
      <div className="flex items-center justify-between px-3 py-2 border-b border-panel-border h-9" style={{ backgroundColor: '#000000' }}>
        <div className="flex items-center gap-1">
          {previewModes.map(({ mode, icon, label }) => (
            <button
              key={mode}
              onClick={() => {
                setPreviewMode(mode);
                if (mode === 'desktop' || mode === 'tablet') {
                  setIsLocked(false);
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
        className="flex-1 flex flex-col items-center justify-center p-4 relative mobile-preview-container" 
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
              bottom: '20px',
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
