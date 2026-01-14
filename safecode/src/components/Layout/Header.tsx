import React from 'react';
import { File, Edit, Eye, Terminal as TerminalIcon, Puzzle, Help } from 'lucide-react';
import { useIDE } from '../../contexts/IDEContext';
import './Header.css';

const Header: React.FC = () => {
  const { toggleSidebar, toggleTerminal, togglePreview } = useIDE();

  return (
    <header className="ide-header">
      <div className="ide-header-left">
        <div className="ide-header-title">
          <img src="../assets/img/logos%20(6).png" alt="SafeCode" style={{ height: '20px' }} />
        </div>
        <nav className="ide-menu">
          <div className="menu-item-dropdown">
            <span><File size={16} /> File</span>
            <div className="dropdown-content">
              <div className="dropdown-item">New File <span className="shortcut">Ctrl+N</span></div>
              <div className="dropdown-item">Open File <span className="shortcut">Ctrl+O</span></div>
              <div className="dropdown-item">Open Folder <span className="shortcut">Ctrl+Shift+O</span></div>
              <div className="divider"></div>
              <div className="dropdown-item">Save <span className="shortcut">Ctrl+S</span></div>
              <div className="dropdown-item">Save As... <span className="shortcut">Ctrl+Shift+S</span></div>
            </div>
          </div>
          <div className="menu-item-dropdown">
            <span><Edit size={16} /> Edit</span>
            <div className="dropdown-content">
              <div className="dropdown-item">Undo</div>
              <div className="dropdown-item">Redo</div>
              <div className="divider"></div>
              <div className="dropdown-item">Cut</div>
              <div className="dropdown-item">Copy</div>
              <div className="dropdown-item">Paste</div>
            </div>
          </div>
          <div className="menu-item-dropdown">
            <span><Eye size={16} /> View</span>
            <div className="dropdown-content">
              <div className="dropdown-item" onClick={toggleSidebar}>
                Toggle Sidebar <span className="shortcut">Ctrl+B</span>
              </div>
              <div className="dropdown-item" onClick={toggleTerminal}>
                Toggle Terminal <span className="shortcut">Ctrl+`</span>
              </div>
              <div className="dropdown-item" onClick={togglePreview}>
                Toggle Preview <span className="shortcut">Ctrl+Shift+V</span>
              </div>
            </div>
          </div>
          <div className="menu-item-dropdown">
            <span><TerminalIcon size={16} /> Terminal</span>
            <div className="dropdown-content">
              <div className="dropdown-item">New Terminal</div>
              <div className="dropdown-item">Clear Terminal</div>
            </div>
          </div>
          <div className="menu-item-dropdown">
            <span><Puzzle size={16} /> Extensions</span>
            <div className="dropdown-content">
              <div className="dropdown-item">Manage Extensions</div>
            </div>
          </div>
          <div className="menu-item-dropdown">
            <span><Help size={16} /> Help</span>
            <div className="dropdown-content">
              <div className="dropdown-item">Welcome</div>
              <div className="dropdown-item">About SafeCode</div>
            </div>
          </div>
        </nav>
      </div>
    </header>
  );
};

export default Header;


