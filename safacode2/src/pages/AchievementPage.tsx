import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Copy, Check, ArrowLeft, ExternalLink } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { getLogoPath } from '@/lib/assets';

const badgeCode = `<a href="https://safenode.cloud/safecode">
  <img src="https://i.postimg.cc/9fMqbs8k/logos-(6).png" alt="Dev SAFECODE" width="180" height="32" />
</a>`;

const badgeCodeMarkdown = `[![Dev SAFECODE](https://img.shields.io/badge/Dev-SAFECODE-000000?style=for-the-badge&logo=visual-studio-code&logoColor=white)](https://safenode.cloud/safecode)`;

const badgeCodeSVG = `<a href="https://safenode.cloud/safecode">
  <img src="./public/badge-dev-safecode.svg" alt="Dev SAFECODE" />
</a>`;

export const AchievementPage: React.FC = () => {
  const [copiedIndex, setCopiedIndex] = useState<number | null>(null);
  const navigate = useNavigate();
  const { toast } = useToast();

  const copyToClipboard = (text: string, index: number) => {
    navigator.clipboard.writeText(text).then(() => {
      setCopiedIndex(index);
      toast({
        title: "Copiado!",
        description: "Código copiado para a área de transferência",
      });
      setTimeout(() => setCopiedIndex(null), 2000);
    });
  };

  return (
    <div className="min-h-screen bg-background flex items-center justify-center p-4">
      <div className="max-w-4xl w-full">
        {/* Header */}
        <div className="mb-8 text-center">
          <Button
            variant="ghost"
            onClick={() => navigate('/')}
            className="mb-6"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Voltar para o IDE
          </Button>
          
          <div className="flex items-center justify-center mb-6">
            <div className="relative">
              <div className="absolute inset-0 bg-yellow-500/20 blur-2xl rounded-full"></div>
              <img 
                src={getLogoPath()} 
                alt="SAFECODE Logo" 
                className="h-16 w-16 object-contain relative z-10"
              />
            </div>
          </div>
          
          <h1 className="text-4xl font-bold mb-2">Emblema Dev SAFECODE</h1>
          <p className="text-muted-foreground text-lg">
            Você conquistou este emblema! Adicione-o ao seu README do GitHub.
          </p>
        </div>

        {/* Badge Preview */}
        <div className="bg-card border rounded-lg p-8 mb-8 text-center">
          <h2 className="text-2xl font-semibold mb-6">Preview do Emblema</h2>
          <div className="flex items-center justify-center gap-4 flex-wrap">
            {/* Badge com Logo */}
            <div className="bg-black rounded-lg p-4 flex items-center gap-3">
              <img 
                src={getLogoPath()} 
                alt="SAFECODE Logo" 
                className="h-6 w-6 object-contain"
              />
              <span className="text-white font-semibold text-sm">Dev SAFECODE</span>
            </div>
            
            {/* Badge Shields.io */}
            <a 
              href="https://safenode.cloud/safecode" 
              target="_blank" 
              rel="noopener noreferrer"
            >
              <img 
                src="https://img.shields.io/badge/Dev-SAFECODE-000000?style=for-the-badge&logo=visual-studio-code&logoColor=white" 
                alt="Dev SAFECODE" 
              />
            </a>
          </div>
        </div>

        {/* Code Options */}
        <div className="space-y-6">
          {/* Opção 1: HTML com Logo */}
          <div className="bg-card border rounded-lg p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold">Opção 1: HTML com Logo SAFECODE</h3>
              <Button
                variant="outline"
                size="sm"
                onClick={() => copyToClipboard(badgeCode, 0)}
              >
                {copiedIndex === 0 ? (
                  <Check className="h-4 w-4 mr-2" />
                ) : (
                  <Copy className="h-4 w-4 mr-2" />
                )}
                Copiar
              </Button>
            </div>
            <pre className="bg-muted p-4 rounded-md overflow-x-auto text-sm">
              <code>{badgeCode}</code>
            </pre>
          </div>

          {/* Opção 2: Markdown Shields.io */}
          <div className="bg-card border rounded-lg p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold">Opção 2: Markdown (Shields.io)</h3>
              <Button
                variant="outline"
                size="sm"
                onClick={() => copyToClipboard(badgeCodeMarkdown, 1)}
              >
                {copiedIndex === 1 ? (
                  <Check className="h-4 w-4 mr-2" />
                ) : (
                  <Copy className="h-4 w-4 mr-2" />
                )}
                Copiar
              </Button>
            </div>
            <pre className="bg-muted p-4 rounded-md overflow-x-auto text-sm">
              <code>{badgeCodeMarkdown}</code>
            </pre>
          </div>

          {/* Opção 3: SVG Local */}
          <div className="bg-card border rounded-lg p-6">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-semibold">Opção 3: SVG Local (se tiver o arquivo)</h3>
              <Button
                variant="outline"
                size="sm"
                onClick={() => copyToClipboard(badgeCodeSVG, 2)}
              >
                {copiedIndex === 2 ? (
                  <Check className="h-4 w-4 mr-2" />
                ) : (
                  <Copy className="h-4 w-4 mr-2" />
                )}
                Copiar
              </Button>
            </div>
            <pre className="bg-muted p-4 rounded-md overflow-x-auto text-sm">
              <code>{badgeCodeSVG}</code>
            </pre>
            <p className="text-sm text-muted-foreground mt-2">
              Nota: Para usar esta opção, você precisa ter o arquivo <code className="bg-muted px-1 rounded">badge-dev-safecode.svg</code> no seu repositório.
            </p>
          </div>
        </div>

        {/* Instructions */}
        <div className="mt-8 bg-muted/50 border rounded-lg p-6">
          <h3 className="text-lg font-semibold mb-4">Como usar:</h3>
          <ol className="list-decimal list-inside space-y-2 text-muted-foreground">
            <li>Escolha uma das opções acima</li>
            <li>Clique em "Copiar" para copiar o código</li>
            <li>Cole o código no seu arquivo <code className="bg-background px-1 rounded">README.md</code> do GitHub</li>
            <li>Faça commit e push das alterações</li>
            <li>O emblema aparecerá no seu perfil!</li>
          </ol>
        </div>

        {/* Footer */}
        <div className="mt-8 text-center text-muted-foreground">
          <p className="mb-4">
            Desenvolvido com <span className="text-red-500">❤</span> usando SafeCode IDE
          </p>
          <Button
            variant="outline"
            onClick={() => window.open('https://safenode.cloud/safecode', '_blank')}
          >
            <ExternalLink className="mr-2 h-4 w-4" />
            Acessar SafeCode IDE
          </Button>
        </div>
      </div>
    </div>
  );
};

export default AchievementPage;

