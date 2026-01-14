# SAFECODE IDE

A powerful, modern code editor built with React, TypeScript, and Vite.

## Features

- 🎨 **Modern Code Editor** - Monaco Editor integration with syntax highlighting
- 📁 **File Explorer** - Browse and manage your project files
- 💻 **Integrated Terminal** - xterm.js terminal for command-line operations
- 👁️ **Live Preview** - See your changes in real-time
- 🔍 **Search Panel** - Find and replace functionality
- 📝 **Git Integration** - Built-in Git panel for version control
- ⌨️ **Command Palette** - Quick access to commands
- 🎯 **Extensions Panel** - Manage editor extensions
- 📑 **Tab Management** - Multiple file tabs support
- 🔧 **Find & Replace** - Advanced search and replace tools

## Technologies

- **Vite** - Next generation frontend tooling
- **TypeScript** - Type-safe JavaScript
- **React** - UI library
- **shadcn-ui** - Beautiful UI components
- **Tailwind CSS** - Utility-first CSS framework
- **Monaco Editor** - Code editor
- **xterm.js** - Terminal emulator
- **Zustand** - State management
- **React Router** - Routing

## Getting Started

### Prerequisites

- Node.js (v18 or higher)
- npm or yarn

### Installation

```sh
# Install dependencies
npm install

# Start development server
npm run dev
```

The development server will start on `http://localhost:8080`

### Build

```sh
# Build for production
npm run build

# Preview production build
npm run preview
```

## Project Structure

```
safecode/
├── src/
│   ├── components/
│   │   ├── ide/          # IDE-specific components
│   │   └── ui/           # Reusable UI components
│   ├── pages/            # Page components
│   ├── stores/           # State management
│   ├── types/            # TypeScript type definitions
│   └── lib/              # Utility functions
├── public/               # Static assets
└── package.json
```

## Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run build:dev` - Build for development
- `npm run preview` - Preview production build
- `npm run lint` - Run ESLint

## License

MIT
