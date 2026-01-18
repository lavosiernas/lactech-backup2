import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';

export default function OAuthCallback() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const userStr = searchParams.get('user');

  useEffect(() => {
    if (token && userStr) {
      try {
        const user = JSON.parse(decodeURIComponent(userStr));
        
        // Salvar token e usuário no store
        useAuthStore.setState({
          user,
          token,
          isAuthenticated: true,
          isLoading: false,
        });
        
        // Redirecionar para a home
        navigate('/');
      } catch (error) {
        console.error('Erro ao processar callback OAuth:', error);
        navigate('/login');
      }
    } else {
      navigate('/login');
    }
  }, [token, userStr, navigate]);

  return (
    <div className="min-h-screen flex items-center justify-center" style={{ backgroundColor: '#000000' }}>
      <div className="text-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
        <p style={{ color: '#94a3b8' }}>Finalizando autenticação...</p>
      </div>
    </div>
  );
}

