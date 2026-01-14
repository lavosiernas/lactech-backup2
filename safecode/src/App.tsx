import React from 'react';
import { IDEProvider } from './contexts/IDEContext';
import Layout from './components/Layout/Layout';

const App: React.FC = () => {
  return (
    <IDEProvider>
      <Layout />
    </IDEProvider>
  );
};

export default App;



