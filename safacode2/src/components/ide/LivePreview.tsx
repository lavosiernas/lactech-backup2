import { useState } from 'react';
import { Monitor, Tablet, Smartphone, RefreshCw, ExternalLink, Moon, Sun, RotateCcw } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import type { PreviewMode } from '@/types/ide';

export const LivePreview: React.FC = () => {
  const { previewMode, setPreviewMode, tabs, activeTabId } = useIDEStore();
  const [darkMode, setDarkMode] = useState(false);
  const [rotated, setRotated] = useState(false);
  const [key, setKey] = useState(0);
  
  const activeTab = tabs.find(t => t.id === activeTabId);

  const previewModes: { mode: PreviewMode; icon: React.ReactNode; label: string }[] = [
    { mode: 'desktop', icon: <Monitor className="w-4 h-4" />, label: 'Desktop' },
    { mode: 'tablet', icon: <Tablet className="w-4 h-4" />, label: 'Tablet' },
    { mode: 'mobile', icon: <Smartphone className="w-4 h-4" />, label: 'Mobile' },
  ];

  const getPreviewSize = () => {
    const sizes = {
      desktop: { width: '100%', height: '100%' },
      tablet: rotated ? { width: '1024px', height: '768px' } : { width: '768px', height: '1024px' },
      mobile: rotated ? { width: '844px', height: '390px' } : { width: '390px', height: '844px' },
    };
    return sizes[previewMode];
  };

  const size = getPreviewSize();

  // Get preview content from active tab or default
  const getPreviewContent = () => {
    if (activeTab && activeTab.content) {
      // If it's HTML, use it directly
      if (activeTab.language === 'html') {
        return activeTab.content;
      }
      // If it's a React/TSX file, wrap it
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
    // This is a preview - full React rendering would require build step
    console.log('Preview mode - code:', \`${codePreview}\`);
  </script>
</body>
</html>`;
      }
      // For CSS files, create a demo page
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
    
    // Default preview content
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

  return (
    <div className="h-full flex flex-col" style={{ backgroundColor: '#000000' }}>
      {/* Preview toolbar */}
      <div className="flex items-center justify-between px-3 py-2 border-b border-panel-border h-9" style={{ backgroundColor: '#000000' }}>
        <div className="flex items-center gap-1">
          {previewModes.map(({ mode, icon, label }) => (
            <button
              key={mode}
              onClick={() => setPreviewMode(mode)}
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
            {darkMode ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
          </button>
          <button
            onClick={() => setKey(k => k + 1)}
            className="p-1.5 rounded text-muted-foreground hover:bg-muted transition-colors"
            title="Refresh"
          >
            <RefreshCw className="w-4 h-4" />
          </button>
          <button
            className="p-1.5 rounded text-muted-foreground hover:bg-muted transition-colors"
            title="Open in New Tab"
          >
            <ExternalLink className="w-4 h-4" />
          </button>
        </div>
      </div>

      {/* Preview frame */}
      <div className="flex-1 flex items-center justify-center p-4 overflow-auto" style={{ backgroundColor: '#000000' }}>
        <div
          className={`bg-background rounded-lg shadow-2xl overflow-hidden transition-all duration-300 ${
            previewMode !== 'desktop' ? 'border-8 border-gray-800 rounded-3xl' : ''
          }`}
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
      </div>

      {/* Preview status */}
      {previewMode !== 'desktop' && (
        <div className="px-3 py-1 text-xs text-muted-foreground text-center border-t border-panel-border">
          {previewMode === 'tablet' ? '768 × 1024' : '390 × 844'}
          {rotated && ' (rotated)'}
        </div>
      )}
    </div>
  );
};
