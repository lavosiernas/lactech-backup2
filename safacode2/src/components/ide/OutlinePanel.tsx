import React, { useState } from 'react';
import { ChevronDown, ChevronRight, Code, Zap, Variable, Type, FileText, Hash } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';

interface OutlineItem {
  name: string;
  type: 'class' | 'function' | 'variable' | 'interface' | 'type' | 'constant' | 'method';
  line: number;
  children?: OutlineItem[];
}

export const OutlinePanel: React.FC = () => {
  const { tabs, activeTabId } = useIDEStore();
  const [expandedItems, setExpandedItems] = useState<Set<string>>(new Set());
  const activeTab = tabs.find(t => t.id === activeTabId);

  // Simular estrutura do outline baseado no arquivo ativo
  const getOutlineItems = (): OutlineItem[] => {
    if (!activeTab) return [];

    const content = activeTab.content;
    const items: OutlineItem[] = [];

    // Detectar classes, funções, etc. baseado na linguagem
    if (activeTab.language === 'typescript' || activeTab.language === 'javascript' || activeTab.language === 'tsx' || activeTab.language === 'jsx') {
      const lines = content.split('\n');
      lines.forEach((line, index) => {
        const trimmed = line.trim();
        if (!trimmed || trimmed.startsWith('//') || trimmed.startsWith('*') || trimmed.startsWith('/*')) return;
        
        // Classes
        const classMatch = trimmed.match(/(?:export\s+)?(?:abstract\s+)?class\s+(\w+)/);
        if (classMatch) {
          items.push({
            name: classMatch[1],
            type: 'class',
            line: index + 1
          });
          return;
        }
        
        // React Components (export const/function ComponentName)
        const componentMatch = trimmed.match(/(?:export\s+)?(?:const|function)\s+([A-Z]\w*)\s*[=:]/);
        if (componentMatch && componentMatch[1].match(/^[A-Z]/)) {
          items.push({
            name: componentMatch[1],
            type: 'class',
            line: index + 1
          });
          return;
        }
        
        // Functions (function name, const name = function, const name = () =>)
        const functionMatch = trimmed.match(/(?:export\s+)?(?:async\s+)?function\s+(\w+)/) || 
                             trimmed.match(/(?:export\s+)?const\s+(\w+)\s*=\s*(?:async\s+)?(?:\(|function)/);
        if (functionMatch) {
          items.push({
            name: functionMatch[1],
            type: 'function',
            line: index + 1
          });
          return;
        }
        
        // Interfaces/Types
        const interfaceMatch = trimmed.match(/(?:export\s+)?(?:type|interface)\s+(\w+)/);
        if (interfaceMatch) {
          items.push({
            name: interfaceMatch[1],
            type: 'interface',
            line: index + 1
          });
          return;
        }
        
        // Variables (const/let/var name =)
        const varMatch = trimmed.match(/(?:export\s+)?(?:const|let|var)\s+(\w+)\s*=/);
        if (varMatch && !varMatch[1].match(/^[A-Z]/)) {
          items.push({
            name: varMatch[1],
            type: 'variable',
            line: index + 1
          });
          return;
        }
        
        // Constants (UPPER_CASE)
        const constMatch = trimmed.match(/(?:export\s+)?const\s+([A-Z_][A-Z0-9_]*)\s*=/);
        if (constMatch) {
          items.push({
            name: constMatch[1],
            type: 'constant',
            line: index + 1
          });
        }
      });
    }

    return items.slice(0, 20); // Limitar a 20 itens
  };

  const outlineItems = getOutlineItems();

  const toggleExpand = (itemName: string) => {
    const newExpanded = new Set(expandedItems);
    if (newExpanded.has(itemName)) {
      newExpanded.delete(itemName);
    } else {
      newExpanded.add(itemName);
    }
    setExpandedItems(newExpanded);
  };

  const handleItemClick = (line: number) => {
    if (activeTabId && (window as any).monacoEditor) {
      const editor = (window as any).monacoEditor;
      editor.setPosition({ lineNumber: line, column: 1 });
      editor.revealLineInCenter(line);
    }
  };

  const renderItem = (item: OutlineItem, depth: number = 0) => {
    const hasChildren = item.children && item.children.length > 0;
    const isExpanded = expandedItems.has(item.name);

    return (
      <div key={`${item.name}-${item.line}`}>
        <div
          className="flex items-center gap-1 px-2 py-1 hover:bg-sidebar-hover cursor-pointer group"
          style={{ paddingLeft: `${8 + depth * 12}px` }}
          onClick={() => {
            if (hasChildren) {
              toggleExpand(item.name);
            } else {
              handleItemClick(item.line);
            }
          }}
        >
          {hasChildren ? (
            isExpanded ? (
              <ChevronDown className="w-3 h-3 text-muted-foreground flex-shrink-0" />
            ) : (
              <ChevronRight className="w-3 h-3 text-muted-foreground flex-shrink-0" />
            )
          ) : (
            <div className="w-3 h-3 flex-shrink-0" />
          )}
          <span className="text-muted-foreground flex-shrink-0">
            {item.type === 'class' ? <Type className="w-3 h-3" /> :
             item.type === 'function' ? <Zap className="w-3 h-3" /> :
             item.type === 'interface' ? <Type className="w-3 h-3" /> :
             item.type === 'variable' ? <Variable className="w-3 h-3" /> :
             item.type === 'constant' ? <Hash className="w-3 h-3" /> :
             <Code className="w-3 h-3" />}
          </span>
          <span className="text-xs text-foreground truncate flex-1">{item.name}</span>
          <span className="text-[10px] text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity">
            {item.line}
          </span>
        </div>
        {hasChildren && isExpanded && item.children?.map(child => renderItem(child, depth + 1))}
      </div>
    );
  };

  return (
    <div className="h-full flex flex-col" style={{ backgroundColor: '#000000' }}>
      <div className="flex-1 overflow-auto hide-scrollbar py-1">
        {outlineItems.length > 0 ? (
          <div>
            {outlineItems.map(item => renderItem(item))}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center py-8 text-center px-4">
            <FileText className="w-8 h-8 text-muted-foreground opacity-50 mb-2" />
            <p className="text-xs text-muted-foreground">No symbols found</p>
            <p className="text-[10px] text-muted-foreground mt-1 opacity-75">
              Open a file to see its structure
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

