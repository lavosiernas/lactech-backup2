import { useEffect, useState } from 'react';

export const MobileBlock = () => {
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const checkMobile = () => {
      // Verificar user agent primeiro (mais confiável)
      const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i;
      const isMobileUA = mobileRegex.test(navigator.userAgent);
      
      // Se não é mobile user agent, não bloquear (mesmo que tenha touch ou tela pequena)
      if (!isMobileUA) {
        setIsMobile(false);
        return;
      }
      
      // Se é mobile user agent, verificar largura da tela
      if (isMobileUA && window.innerWidth < 768) {
        setIsMobile(true);
        return;
      }
      
      // Se é mobile user agent mas tela grande, ainda bloquear (tablets grandes podem tentar usar)
      if (isMobileUA) {
        setIsMobile(true);
        return;
      }

      setIsMobile(false);
    };

    checkMobile();
    window.addEventListener('resize', checkMobile);
    
    return () => window.removeEventListener('resize', checkMobile);
  }, []);

  if (!isMobile) return null;

  return (
    <div
      style={{
        position: 'fixed',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        background: '#000',
        color: '#fff',
        zIndex: 99999,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
      }}
    >
      <div style={{ textAlign: 'center', padding: '2rem', maxWidth: '500px' }}>
        <h1 style={{ fontSize: '1.5rem', marginBottom: '1rem', fontWeight: 600 }}>
          SafeCode IDE
        </h1>
        <p style={{ color: '#999', lineHeight: 1.6, marginBottom: '1.5rem' }}>
          A SafeCode IDE não está disponível para dispositivos móveis.
        </p>
        <p style={{ color: '#666', fontSize: '0.9rem' }}>
          Por favor, acesse de um desktop ou tablet com tela maior.
        </p>
      </div>
    </div>
  );
};

