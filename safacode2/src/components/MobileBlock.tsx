import { useEffect, useState } from 'react';

export const MobileBlock = () => {
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    const checkMobile = () => {
      const ua = navigator.userAgent;
      
      // Verificar APENAS se é claramente um smartphone pequeno pelo user agent
      // Padrões muito específicos para smartphones (não tablets, não desktops)
      const isSmallPhone = /iPhone|iPod|Android.*Mobile|Windows Phone|BlackBerry|BB10|Mobile.*Firefox|Opera Mini/i.test(ua);
      
      // Bloquear APENAS se for smartphone pequeno
      // Desktops, tablets e qualquer outro dispositivo NÃO será bloqueado
      setIsMobile(isSmallPhone);
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

