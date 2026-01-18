# Guia de Integração com Banco de Dados

## ✅ O QUE FOI IMPLEMENTADO

### APIs PHP Criadas

1. **`api/projects.php`** - CRUD completo de projetos
   - `GET ?action=list` - Listar projetos
   - `GET ?action=get&id=X` - Buscar projeto
   - `POST ?action=create` - Criar projeto
   - `PUT ?action=update` - Atualizar projeto
   - `DELETE ?action=delete&id=X` - Deletar projeto

2. **`api/files.php`** - CRUD completo de arquivos
   - `GET ?action=tree&project_id=X` - Árvore de arquivos
   - `GET ?action=list&project_id=X` - Lista plana
   - `GET ?action=get&id=X` - Buscar arquivo
   - `POST ?action=create` - Criar arquivo/pasta
   - `PUT ?action=update` - Atualizar arquivo
   - `DELETE ?action=delete&id=X` - Deletar arquivo

3. **`api/settings.php`** - Configurações do usuário
   - `GET ?action=get` - Buscar settings
   - `PUT ?action=update` - Atualizar settings

### Stores do Frontend Criados

1. **`stores/projectStore.ts`** - Gerenciar projetos
2. **`stores/fileStore.ts`** - Gerenciar arquivos
3. **`services/api/projectsApi.ts`** - API client para projetos
4. **`services/api/filesApi.ts`** - API client para arquivos
5. **`services/api/settingsApi.ts`** - API client para settings

---

## 📝 COMO USAR

### 1. Carregar Projetos

```typescript
import { useProjectStore } from '@/stores/projectStore';

function MyComponent() {
  const { projects, loadProjects, isLoading } = useProjectStore();
  
  useEffect(() => {
    loadProjects();
  }, []);
  
  return (
    <div>
      {isLoading ? 'Carregando...' : (
        projects.map(project => (
          <div key={project.id}>{project.name}</div>
        ))
      )}
    </div>
  );
}
```

### 2. Criar Novo Projeto

```typescript
import { useProjectStore } from '@/stores/projectStore';

function CreateProjectForm() {
  const { createProject } = useProjectStore();
  
  const handleCreate = async () => {
    try {
      const project = await createProject({
        name: 'Meu Projeto',
        description: 'Descrição do projeto',
        color: '#FF5733'
      });
      console.log('Projeto criado:', project);
    } catch (error) {
      console.error('Erro:', error);
    }
  };
  
  return <button onClick={handleCreate}>Criar Projeto</button>;
}
```

### 3. Carregar Arquivos de um Projeto

```typescript
import { useFileStore } from '@/stores/fileStore';
import { useProjectStore } from '@/stores/projectStore';

function FileExplorer() {
  const { currentProject } = useProjectStore();
  const { files, loadFiles, isLoading } = useFileStore();
  
  useEffect(() => {
    if (currentProject) {
      loadFiles(currentProject.id);
    }
  }, [currentProject]);
  
  return (
    <div>
      {isLoading ? 'Carregando arquivos...' : (
        files.map(file => <div key={file.id}>{file.name}</div>)
      )}
    </div>
  );
}
```

### 4. Criar/Atualizar Arquivo

```typescript
import { useFileStore } from '@/stores/fileStore';
import { useProjectStore } from '@/stores/projectStore';

function Editor() {
  const { currentProject } = useProjectStore();
  const { createFile, updateFile } = useFileStore();
  
  const handleSave = async (fileId: string, content: string) => {
    if (!currentProject) return;
    
    try {
      if (fileId === 'new') {
        await createFile(currentProject.id, '/src', 'App.tsx', content);
      } else {
        await updateFile(fileId, content);
      }
    } catch (error) {
      console.error('Erro ao salvar:', error);
    }
  };
  
  return <textarea onBlur={(e) => handleSave('new', e.target.value)} />;
}
```

### 5. Carregar/Salvar Configurações

```typescript
import * as settingsApi from '@/services/api/settingsApi';

async function loadSettings() {
  const settings = await settingsApi.getSettings();
  console.log('Editor settings:', settings.editor_settings);
}

async function saveSettings() {
  await settingsApi.updateSettings({
    editor_settings: {
      fontSize: 16,
      theme: 'dark'
    }
  });
}
```

---

## 🔄 PRÓXIMOS PASSOS (Integração com ideStore)

Para integrar completamente com o `ideStore` existente, você precisa:

### 1. Atualizar `ideStore.ts`

Remover a persistência local de arquivos e usar `fileStore`:

```typescript
import { useFileStore } from './fileStore';
import { useProjectStore } from './projectStore';

// Ao criar arquivo
createFile: (parentPath, name) => {
  const { currentProject } = useProjectStore.getState();
  const { createFile } = useFileStore.getState();
  if (currentProject) {
    createFile(currentProject.id, parentPath, name);
  }
}

// Ao salvar tab
saveTab: (tabId) => {
  const { tabs } = get();
  const tab = tabs.find(t => t.id === tabId);
  if (tab) {
    const { updateFile } = useFileStore.getState();
    updateFile(tab.path.split('/').pop() || '', tab.content);
  }
}
```

### 2. Carregar Projeto ao Abrir IDE

```typescript
useEffect(() => {
  const { loadProjects, setCurrentProject } = useProjectStore.getState();
  const { loadFiles } = useFileStore.getState();
  
  loadProjects().then(() => {
    const projects = useProjectStore.getState().projects;
    if (projects.length > 0) {
      const project = projects[0]; // ou usar seleção do usuário
      setCurrentProject(project);
      loadFiles(project.id);
    }
  });
}, []);
```

---

## ⚠️ NOTAS IMPORTANTES

1. **Autenticação**: Todas as APIs requerem token JWT (gerenciado por `authStore`)

2. **Erros**: Todas as funções lançam exceções, use try/catch

3. **Loading States**: Use `isLoading` dos stores para mostrar loading

4. **Sync Local/Server**: Arquivos são salvos no banco, mas você pode manter cache local para performance

5. **Permissões**: APIs verificam automaticamente permissões do usuário

---

## 🐛 TROUBLESHOOTING

### Erro: "Token não fornecido"
- Verifique se o usuário está autenticado
- Token deve estar em `localStorage` como `safecode-auth-storage`

### Erro: "Sem permissão"
- Usuário não é owner/admin do projeto
- Verifique `project_collaborators` no banco

### Arquivos não aparecem
- Verifique se `loadFiles(projectId)` foi chamado
- Verifique se o projeto tem `currentProject` definido

---

## 📚 Estrutura de Dados

### Project
```typescript
{
  id: number;
  user_id: number;
  name: string;
  slug: string;
  description?: string;
  is_public: boolean;
  color?: string;
  default_language?: string;
  created_at: string;
  updated_at: string;
}
```

### FileNode (retornado pelo API)
```typescript
{
  id: string; // convertido de number
  name: string;
  type: 'file' | 'folder';
  path: string;
  language?: string;
  content?: string;
  children?: FileNode[];
}
```

