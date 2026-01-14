import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Trophy, ExternalLink } from 'lucide-react';
import { getLogoPath } from '@/lib/assets';

const ACHIEVEMENT_STORAGE_KEY = 'safecode_achievement_shown';

export const AchievementModal: React.FC = () => {
  const [open, setOpen] = useState(false);
  const navigate = useNavigate();

  useEffect(() => {
    // Verificar se já mostrou o modal antes
    const hasShown = localStorage.getItem(ACHIEVEMENT_STORAGE_KEY);
    
    if (!hasShown) {
      // Mostrar o modal após um pequeno delay para melhor UX
      setTimeout(() => {
        setOpen(true);
      }, 1000);
    }
  }, []);

  const handleGetBadge = () => {
    // Marcar como mostrado
    localStorage.setItem(ACHIEVEMENT_STORAGE_KEY, 'true');
    setOpen(false);
    // Navegar para a página de achievement
    navigate('/achievement');
  };

  const handleClose = () => {
    localStorage.setItem(ACHIEVEMENT_STORAGE_KEY, 'true');
    setOpen(false);
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <div className="flex items-center justify-center mb-4">
            <div className="relative">
              <Trophy className="h-16 w-16 text-yellow-500" />
              <div className="absolute inset-0 flex items-center justify-center">
                <img 
                  src={getLogoPath()} 
                  alt="SAFECODE" 
                  className="h-8 w-8 object-contain"
                />
              </div>
            </div>
          </div>
          <DialogTitle className="text-2xl text-center">
            🎉 Emblema Conquistado!
          </DialogTitle>
          <DialogDescription className="text-center text-base pt-2">
            Parabéns! Você acabou de conquistar o emblema <strong>Dev SAFECODE</strong>!
            <br />
            <br />
            Adicione este emblema ao seu perfil do GitHub para mostrar que você desenvolve com SafeCode IDE.
          </DialogDescription>
        </DialogHeader>
        
        <div className="flex flex-col gap-3 pt-4">
          <Button 
            onClick={handleGetBadge}
            className="w-full"
            size="lg"
          >
            <ExternalLink className="mr-2 h-4 w-4" />
            Ver Emblema e Copiar Código
          </Button>
          
          <Button 
            onClick={handleClose}
            variant="outline"
            className="w-full"
          >
            Fechar
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

