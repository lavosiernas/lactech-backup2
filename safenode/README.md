# SafeCode IDE

Full-featured development environment for mobile and web development, built with Electron.

![SafeCode IDE](assets/img/logos%20(6).png)

## Features

✨ **Core Features**
- 📁 File system management with workspace support
- 📝 Multi-language code editor (JavaScript, TypeScript, HTML, CSS, Python, PHP, Markdown)
- 🎨 Syntax highlighting with CodeMirror 6
- 📑 Multiple file tabs with dirty state tracking
- 🔍 File explorer sidebar
- 💻 Integrated terminal (xterm.js)
- 📱 Live preview with mobile/desktop modes
- ⌨️ Keyboard shortcuts (VSCode-style)
- 🎯 Command palette (Ctrl+Shift+P)

🚀 **Coming Soon**
- 🔌 Extension system
- 🔥 Hot reload / Go Live
- 🤖 IntelliSense / Autocomplete
- 🔎 Search in files
- 📊 Git integration
- 🎨 Theme customization

## Installation

### Prerequisites
- Node.js 18+ 
- npm or yarn

### Install Dependencies

```bash
npm install
```

## Usage

### Development Mode

Run the IDE in development mode with hot reload:

```bash
npm run dev
```

Or start Electron directly:

```bash
npm start
```

### Build for Production

Build the web version:

```bash
npm run build
```

### Package as Desktop App

Package for your current platform:

```bash
npm run package
```

Package for specific platforms:

```bash
# Windows
npm run package:win

# macOS
npm run package:mac

# Linux
npm run package:linux
```

The packaged application will be in the `dist` folder.

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Ctrl+N` | New File |
| `Ctrl+O` | Open File |
| `Ctrl+Shift+O` | Open Folder |
| `Ctrl+S` | Save |
| `Ctrl+Shift+S` | Save As |
| `Ctrl+W` | Close Tab |
| `Ctrl+B` | Toggle Sidebar |
| `Ctrl+` ` | Toggle Terminal |
| `Ctrl+Shift+P` | Command Palette |
| `Ctrl+Shift+V` | Toggle Preview |
| `Ctrl+F` | Find |
| `Ctrl+H` | Replace |

## Project Structure

```
safenode/
├── electron-main.js          # Electron main process
├── electron-preload.js       # Electron preload script
├── package.json              # Dependencies and scripts
├── vite.config.js           # Vite configuration
├── src/
│   ├── index.html           # Main HTML
│   ├── main.js              # Application entry point
│   ├── core/
│   │   └── FileSystem.js    # File system manager
│   ├── components/
│   │   ├── EditorManager.js # Code editor manager
│   │   ├── TabManager.js    # Tab management
│   │   ├── SidebarManager.js # Sidebar views
│   │   └── TerminalManager.js # Terminal integration
│   └── styles/
│       └── main.css         # Main stylesheet
├── assets/                  # Images and icons
└── build/                   # Production build output
```

## Technologies Used

- **Electron** - Desktop application framework
- **CodeMirror 6** - Code editor
- **xterm.js** - Terminal emulator
- **Vite** - Build tool and dev server
- **Chokidar** - File watcher
- **Lucide Icons** - Icon library

## Development

### Adding New Language Support

Edit `src/components/EditorManager.js` and add the language to the `getLanguageMode()` method:

```javascript
import { yourLanguage } from '@codemirror/lang-yourlanguage';

// In getLanguageMode()
const languageMap = {
  'ext': yourLanguage(),
  // ...
};
```

### Creating Extensions

Extension system is coming soon! The architecture is designed to support VSCode-style extensions.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

MIT License

## Author

SafeNode Team

---

**Note**: This IDE is currently in active development. Some features may be incomplete or subject to change.
