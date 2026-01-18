import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/authStore';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2, Mail, Lock, User, Eye, EyeOff } from 'lucide-react';
import { getLogoPath } from '@/lib/assets';

export default function LoginPage() {
  const navigate = useNavigate();
  const { login, register, isLoading, isAuthenticated } = useAuthStore();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [error, setError] = useState('');
  const [isRegisterMode, setIsRegisterMode] = useState(false);
  const [name, setName] = useState('');
  const [lastName, setLastName] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  // Redirecionar se já estiver autenticado
  useEffect(() => {
    if (isAuthenticated) {
      navigate('/');
    }
  }, [isAuthenticated, navigate]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (isRegisterMode) {
      if (password !== confirmPassword) {
        setError('As senhas não coincidem');
        return;
      }
      const fullName = lastName ? `${name} ${lastName}` : name;
      const result = await register(email, password, fullName);
      if (result.success) {
        navigate('/');
      } else {
        setError(result.error || 'Erro ao registrar');
      }
    } else {
      const result = await login(email, password);
      if (result.success) {
        navigate('/');
      } else {
        setError(result.error || 'Erro ao fazer login');
      }
    }
  };

  return (
    <div 
      className="fixed inset-0 flex"
      style={{ backgroundColor: '#000000' }}
    >
      {/* Painel Esquerdo - Branding */}
      <div 
        className="relative flex flex-col justify-between p-10"
        style={{
          width: '35%',
          background: 'linear-gradient(180deg, #1e3a8a 0%, #000000 100%)'
        }}
      >
        {/* Vídeo de fundo */}
        <video
          autoPlay
          loop
          muted
          playsInline
          className="absolute inset-0 w-full h-full object-cover opacity-30"
          style={{ mixBlendMode: 'overlay' }}
        >
          <source src="/cristao.mp4" type="video/mp4" />
        </video>

        {/* Logo e texto no canto inferior */}
        <div className="relative z-10 mt-auto space-y-6">
          <div className="flex items-center gap-2">
            <img 
              src={getLogoPath()}
              alt="SAFECODE" 
              className="w-8 h-8 object-contain"
            />
            <span className="text-xl font-semibold" style={{ color: '#ffffff' }}>SafeCode IDE</span>
          </div>
          
          <div className="space-y-3">
            <h2 className="text-3xl font-bold" style={{ color: '#ffffff' }}>
              {isRegisterMode ? 'Build. Collaborate. Innovate.' : 'Back to your space.'}
            </h2>
            <p className="text-base leading-relaxed" style={{ color: 'rgba(255,255,255,0.9)' }}>
              {isRegisterMode 
                ? 'Empower your team with tools that make collaboration seamless, creative, and inspiring.'
                : 'Access your projects, manage your workspace, and keep building together with your team.'}
            </p>
          </div>
        </div>
      </div>

      {/* Painel Direito - Formulário */}
      <div 
        className="flex-1 flex items-center justify-center p-10 overflow-y-auto"
        style={{ backgroundColor: '#000000' }}
      >
        <div className="max-w-md w-full space-y-6">
          {/* Header */}
          <div className="space-y-2">
            <h1 className="text-3xl font-bold" style={{ color: '#ffffff' }}>
              {isRegisterMode ? "Let's Get You Onboard!" : 'Welcome Back!'}
            </h1>
            <p className="text-sm leading-relaxed" style={{ color: '#94a3b8' }}>
              {isRegisterMode
                ? 'Join SafeCode IDE and start collaborating — create smarter, work faster, and grow together.'
                : 'Continue your journey — sign in to reconnect, collaborate, and pick up where you left off.'}
            </p>
          </div>

          {/* Botões de Login Social */}
          <div className="grid grid-cols-2 gap-3">
            <Button
              type="button"
              variant="outline"
              className="w-full h-11 rounded-lg transition-all hover:bg-[#111111] flex items-center justify-center gap-2"
              style={{
                backgroundColor: '#000000',
                borderColor: 'rgba(255,255,255,0.15)',
                color: '#ffffff'
              }}
            >
              <svg className="w-5 h-5" viewBox="0 0 24 24">
                <path
                  fill="currentColor"
                  d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                />
                <path
                  fill="currentColor"
                  d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                />
                <path
                  fill="currentColor"
                  d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                />
                <path
                  fill="currentColor"
                  d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                />
              </svg>
              <span className="text-sm font-medium">Google</span>
            </Button>
            <Button
              type="button"
              variant="outline"
              className="w-full h-11 rounded-lg transition-all hover:bg-[#111111] flex items-center justify-center gap-2"
              style={{
                backgroundColor: '#000000',
                borderColor: 'rgba(255,255,255,0.15)',
                color: '#ffffff'
              }}
            >
              <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path
                  fillRule="evenodd"
                  d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                  clipRule="evenodd"
                />
              </svg>
              <span className="text-sm font-medium">GitHub</span>
            </Button>
          </div>

          {/* Separador */}
          <div className="flex items-center gap-3">
            <div className="flex-1 h-px" style={{ backgroundColor: 'rgba(255,255,255,0.1)' }}></div>
            <span className="text-xs" style={{ color: '#94a3b8' }}>Or</span>
            <div className="flex-1 h-px" style={{ backgroundColor: 'rgba(255,255,255,0.1)' }}></div>
          </div>

          {/* Formulário */}
          <form onSubmit={handleSubmit} className="space-y-5">
            {isRegisterMode && (
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="firstName" className="text-xs font-medium" style={{ color: '#cbd5e1' }}>
                    First Name
                  </Label>
                  <Input
                    id="firstName"
                    type="text"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                    placeholder="e.g. Michelle"
                    className="rounded-lg h-11 border-0 focus-visible:ring-2 focus-visible:ring-blue-500 transition-all"
                    style={{ 
                      backgroundColor: '#111111', 
                      borderColor: 'rgba(255,255,255,0.1)',
                      color: '#ffffff'
                    }}
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="lastName" className="text-xs font-medium" style={{ color: '#cbd5e1' }}>
                    Last Name
                  </Label>
                  <Input
                    id="lastName"
                    type="text"
                    value={lastName}
                    onChange={(e) => setLastName(e.target.value)}
                    placeholder="e.g. Smith"
                    className="rounded-lg h-11 border-0 focus-visible:ring-2 focus-visible:ring-blue-500 transition-all"
                    style={{ 
                      backgroundColor: '#111111', 
                      borderColor: 'rgba(255,255,255,0.1)',
                      color: '#ffffff'
                    }}
                  />
                </div>
              </div>
            )}

            <div className="space-y-2">
              <Label htmlFor="email" className="text-xs font-medium" style={{ color: '#cbd5e1' }}>
                Email
              </Label>
              <Input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                placeholder={isRegisterMode ? "e.g. michelle.smith@email.com" : "e.g. michellesmith@mail.com"}
                className="rounded-lg h-11 border-0 focus-visible:ring-2 focus-visible:ring-blue-500 transition-all"
                style={{ 
                  backgroundColor: '#111111', 
                  borderColor: 'rgba(255,255,255,0.1)',
                  color: '#ffffff'
                }}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="password" className="text-xs font-medium" style={{ color: '#cbd5e1' }}>
                Password
              </Label>
              <div className="relative">
                <Input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  placeholder="Enter your password"
                  minLength={isRegisterMode ? 8 : undefined}
                  className="rounded-lg h-11 border-0 focus-visible:ring-2 focus-visible:ring-blue-500 transition-all pr-11"
                  style={{ 
                    backgroundColor: '#111111', 
                    borderColor: 'rgba(255,255,255,0.1)',
                    color: '#ffffff'
                  }}
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 hover:opacity-70 transition-opacity"
                  style={{ color: '#94a3b8' }}
                >
                  {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
              {isRegisterMode && (
                <p className="text-xs" style={{ color: '#64748b' }}>
                  Use at least 8 characters.
                </p>
              )}
            </div>

            {isRegisterMode && (
              <div className="space-y-2">
                <Label htmlFor="confirmPassword" className="text-xs font-medium" style={{ color: '#cbd5e1' }}>
                  Confirm Password
                </Label>
                <div className="relative">
                  <Input
                    id="confirmPassword"
                    type={showConfirmPassword ? "text" : "password"}
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    required
                    placeholder="Enter your confirm password"
                    minLength={8}
                    className="rounded-lg h-11 border-0 focus-visible:ring-2 focus-visible:ring-blue-500 transition-all pr-11"
                    style={{ 
                      backgroundColor: '#111111', 
                      borderColor: 'rgba(255,255,255,0.1)',
                      color: '#ffffff'
                    }}
                  />
                  <button
                    type="button"
                    onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                    className="absolute right-3 top-1/2 transform -translate-y-1/2 hover:opacity-70 transition-opacity"
                    style={{ color: '#94a3b8' }}
                  >
                    {showConfirmPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                  </button>
                </div>
              </div>
            )}

            {!isRegisterMode && (
              <div className="flex items-center gap-3">
                <div className="relative flex items-center">
                  <input
                    type="checkbox"
                    id="remember"
                    className="sr-only peer"
                  />
                  <label
                    htmlFor="remember"
                    className="relative w-5 h-5 rounded border-2 cursor-pointer transition-all
                      peer-checked:bg-[#3b82f6] peer-checked:border-[#3b82f6]
                      hover:border-[#3b82f6] hover:opacity-80
                      bg-[#111111] border-[rgba(255,255,255,0.2)]"
                  >
                    <svg
                      className="absolute inset-0 w-full h-full text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth="3"
                    >
                      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                  </label>
                </div>
                <Label htmlFor="remember" className="text-sm cursor-pointer select-none hover:opacity-80 transition-opacity" style={{ color: '#94a3b8' }}>
                  Remember me
                </Label>
              </div>
            )}

            {error && (
              <Alert 
                variant="destructive" 
                className="py-3 rounded-lg"
                style={{ 
                  backgroundColor: 'rgba(239, 68, 68, 0.1)', 
                  borderColor: 'rgba(239, 68, 68, 0.3)' 
                }}
              >
                <AlertDescription className="text-sm" style={{ color: '#ef4444' }}>
                  {error}
                </AlertDescription>
              </Alert>
            )}

            <Button
              type="submit"
              className="w-full h-12 font-medium text-base rounded-lg transition-all hover:opacity-90 active:scale-[0.98]"
              disabled={isLoading}
              style={{ 
                backgroundColor: '#ffffff',
                color: '#000000',
                border: 'none'
              }}
            >
              {isLoading ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  {isRegisterMode ? 'Creating account...' : 'Signing in...'}
                </>
              ) : (
                isRegisterMode ? 'Sign Up' : 'Sign In'
              )}
            </Button>

            <div className="text-center pt-1">
              <span className="text-sm" style={{ color: '#94a3b8' }}>
                {isRegisterMode ? "Don't have an account? " : "Already have an account? "}
                <button
                  type="button"
                  onClick={() => {
                    setIsRegisterMode(!isRegisterMode);
                    setError('');
                    setPassword('');
                    setConfirmPassword('');
                  }}
                  className="font-medium hover:underline transition-all"
                  style={{ color: '#ffffff' }}
                >
                  {isRegisterMode ? 'Sign In' : 'Sign Up'}
                </button>
              </span>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
