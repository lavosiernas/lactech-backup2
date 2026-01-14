import React from 'react';
import Header from './Header';
import Sidebar from './Sidebar';
import EditorArea from './EditorArea';
import Terminal from './Terminal';
import { useIDE } from '../../contexts/IDEContext';
import './Layout.css';

const Layout: React.FC = () => {
  const { state } = useIDE();

  return (
    <div className="ide-container">
      <Header />
      <div className="ide-main">
        {state.sidebarVisible && <Sidebar />}
        <EditorArea />
      </div>
      {state.terminalVisible && <Terminal />}
    </div>
  );
};

export default Layout;



