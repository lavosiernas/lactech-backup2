/**
 * SafeCode IDE - Manager Classes Part 2
 * Monaco Editor, Enhanced Sidebar, Live Preview, Live Server
 */

// ============================================================================
// Monaco Editor Manager
// ============================================================================
class MonacoEditorManager {
    constructor(ide) {
        this.ide = ide;
        this.editors = new Map();
        this.activeEditor = null;
        this.monacoAvailable = typeof monaco !== 'undefined';

        if (this.monacoAvailable) {
            this.defineThemes();
        }
    }

    defineThemes() {
        if (typeof monaco === 'undefined') return;

        monaco.editor.defineTheme('safecode-dark', {
            base: 'vs-dark',
            inherit: true,
            rules: [],
            colors: {
                'editor.background': '#000000',
                'editor.lineHighlightBackground': '#111111',
                'editorLineNumber.foreground': '#444444',
                'editorLineNumber.activeForeground': '#ffffff',
                'editor.selectionBackground': '#333333',
                'editorCursor.foreground': '#ffffff'
            }
        });
    }

    async createEditor(filePath, content = '') {
        const container = document.getElementById('editorContainer');
        const welcome = document.getElementById('editorWelcome');
        if (welcome) welcome.style.display = 'none';

        // Create editor wrapper
        const editorWrapper = document.createElement('div');
        editorWrapper.className = 'editor-instance monaco-editor-wrapper';
        editorWrapper.dataset.file = filePath;

        if (this.monacoAvailable && typeof monaco !== 'undefined') {
            // Use Monaco Editor
            const editor = monaco.editor.create(editorWrapper, {
                value: content,
                language: this.getLanguage(filePath),
                theme: this.ide.settingsManager.get('theme'),
                automaticLayout: true,
                fontSize: this.ide.settingsManager.get('fontSize'),
                fontFamily: this.ide.settingsManager.get('fontFamily'),
                minimap: { enabled: this.ide.settingsManager.get('minimap') },
                scrollBeyondLastLine: false,
                wordWrap: this.ide.settingsManager.get('wordWrap'),
                lineNumbers: 'on',
                renderWhitespace: 'selection',
                tabSize: this.ide.settingsManager.get('tabSize'),
                insertSpaces: true,
                formatOnPaste: true,
                formatOnType: true,

                // Advanced IntelliSense
                suggestOnTriggerCharacters: true,
                quickSuggestions: {
                    other: true,
                    comments: false,
                    strings: true
                },
                parameterHints: {
                    enabled: true
                },
                suggest: {
                    showIcons: true,
                    showStatusBar: true,
                    preview: true,
                    detailsVisible: true
                },
                acceptSuggestionOnEnter: 'smart',

                folding: true,
                foldingStrategy: 'indentation',
                showFoldingControls: 'always',
                matchBrackets: 'always',
                autoClosingBrackets: 'always',
                autoClosingQuotes: 'always',
                autoIndent: 'full'
            });

            // Listen for changes
            editor.onDidChangeModelContent(() => {
                this.onEditorChange(filePath);
            });

            // Listen for cursor position
            editor.onDidChangeCursorPosition((e) => {
                this.updateStatusBar(e.position);
            });

            this.editors.set(filePath, { editor, wrapper: editorWrapper });
            this.activeEditor = editor;
        } else {
            // Fallback to textarea
            const textarea = document.createElement('textarea');
            textarea.value = content;
            textarea.style.cssText = `
                width: 100%;
                height: 100%;
                background: #1e1e1e;
                color: #d4d4d4;
                border: none;
                outline: none;
                padding: 1rem;
                font-family: 'JetBrains Mono', monospace;
                font-size: 14px;
                line-height: 1.6;
                resize: none;
            `;

            textarea.addEventListener('input', () => this.onEditorChange(filePath));
            editorWrapper.appendChild(textarea);

            this.editors.set(filePath, { editor: textarea, wrapper: editorWrapper });
            this.activeEditor = textarea;
        }

        container.appendChild(editorWrapper);
        this.showEditor(filePath);
        this.updateLanguageStatus(filePath);
    }

    getLanguage(filePath) {
        const ext = filePath.split('.').pop()?.toLowerCase();
        const languageMap = {
            'js': 'javascript',
            'jsx': 'javascript',
            'ts': 'typescript',
            'tsx': 'typescript',
            'html': 'html',
            'htm': 'html',
            'css': 'css',
            'scss': 'scss',
            'sass': 'sass',
            'less': 'less',
            'json': 'json',
            'md': 'markdown',
            'py': 'python',
            'php': 'php',
            'java': 'java',
            'cpp': 'cpp',
            'c': 'c',
            'cs': 'csharp',
            'go': 'go',
            'rs': 'rust',
            'rb': 'ruby',
            'xml': 'xml',
            'yaml': 'yaml',
            'yml': 'yaml',
            'sql': 'sql',
            'sh': 'shell',
            'bat': 'bat',
            'ps1': 'powershell'
        };
        return languageMap[ext] || 'plaintext';
    }

    showEditor(filePath) {
        document.querySelectorAll('.editor-instance').forEach(el => {
            el.classList.remove('active');
            el.style.display = 'none';
        });

        const editorData = this.editors.get(filePath);
        if (editorData) {
            editorData.wrapper.classList.add('active');
            editorData.wrapper.style.display = 'block';
            this.activeEditor = editorData.editor;

            if (this.monacoAvailable && editorData.editor.layout) {
                editorData.editor.layout();
                editorData.editor.focus();
            } else {
                editorData.editor.focus();
            }
        }
    }

    closeEditor(filePath) {
        const editorData = this.editors.get(filePath);
        if (editorData) {
            if (editorData.editor.dispose) {
                editorData.editor.dispose();
            }
            editorData.wrapper.remove();
            this.editors.delete(filePath);
        }

        if (this.editors.size === 0) {
            const welcome = document.getElementById('editorWelcome');
            if (welcome) welcome.style.display = 'flex';
            this.activeEditor = null;
        }
    }

    getCurrentContent() {
        if (!this.activeEditor) return '';

        if (this.activeEditor.getValue) {
            return this.activeEditor.getValue();
        } else {
            return this.activeEditor.value;
        }
    }

    setContent(filePath, content) {
        const editorData = this.editors.get(filePath);
        if (editorData) {
            if (editorData.editor.setValue) {
                editorData.editor.setValue(content);
            } else {
                editorData.editor.value = content;
            }
        }
    }

    onEditorChange(filePath) {
        this.ide.tabManager.markDirty(filePath);

        // Auto-refresh preview if live server is running
        if (this.ide.liveServer.isRunning && this.ide.liveServer.autoRefresh) {
            clearTimeout(this.refreshTimeout);
            this.refreshTimeout = setTimeout(() => {
                this.ide.previewManager.refresh();
            }, 500);
        }
    }

    async reloadFile(filePath) {
        if (this.editors.has(filePath)) {
            try {
                const content = await this.ide.fileSystem.readFile(filePath);
                this.setContent(filePath, content);
                this.ide.tabManager.markSaved(filePath);
            } catch (error) {
                console.error('Error reloading file:', error);
            }
        }
    }

    updateStatusBar(position) {
        const statusPos = document.getElementById('statusPosition');
        if (statusPos && position) {
            statusPos.innerHTML = `<span>Ln ${position.lineNumber}, Col ${position.column}</span>`;
        }
    }

    updateLanguageStatus(filePath) {
        const statusLang = document.getElementById('statusLanguage');
        if (statusLang) {
            const lang = this.getLanguage(filePath);
            const langNames = {
                'javascript': 'JavaScript',
                'typescript': 'TypeScript',
                'html': 'HTML',
                'css': 'CSS',
                'json': 'JSON',
                'markdown': 'Markdown',
                'python': 'Python',
                'php': 'PHP',
                'plaintext': 'Plain Text'
            };
            statusLang.innerHTML = `<span>${langNames[lang] || lang}</span>`;
        }
    }
}

// ============================================================================
// Enhanced Sidebar Manager
// ============================================================================
class EnhancedSidebarManager {
    constructor(ide) {
        this.ide = ide;
        this.currentView = 'explorer';
        this.workspacePath = null;
        this.expandedFolders = new Set();
        this.setupEventListeners();
        this.setupResizer();
        this.showExplorer();
    }

    setupResizer() {
        const resizer = document.getElementById('sidebarResizer');
        const sidebar = document.getElementById('sidebar');
        const panel = document.getElementById('sidebarPanel');
        if (!resizer || !sidebar || !panel) return;

        let isResizing = false;

        function handleMouseMove(e) {
            if (!isResizing) return;
            const width = e.clientX - sidebar.getBoundingClientRect().left;
            // The panel width is total width minus activity bar (48px)
            const panelWidth = width - 48;
            if (panelWidth > 150 && width < window.innerWidth * 0.5) {
                panel.style.width = panelWidth + 'px';
            }
        }

        function handleMouseUp() {
            if (!isResizing) return;
            isResizing = false;
            document.body.style.cursor = 'default';
            const overlay = document.getElementById('resize-overlay-sidebar');
            if (overlay) overlay.remove();

            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        }

        resizer.addEventListener('mousedown', (e) => {
            isResizing = true;
            document.body.style.cursor = 'col-resize';

            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100vw';
            overlay.style.height = '100vh';
            overlay.style.zIndex = '9999';
            overlay.style.cursor = 'col-resize';
            overlay.id = 'resize-overlay-sidebar';
            document.body.appendChild(overlay);

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
    }

    setupEventListeners() {
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const view = tab.dataset.view;
                this.switchView(view);
            });
        });
    }

    switchView(view) {
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.view === view);
        });
        this.currentView = view;

        switch (view) {
            case 'explorer': this.showExplorer(); break;
            case 'search': this.showSearch(); break;
            case 'git': this.showGit(); break;
            case 'extensions': this.showExtensions(); break;
        }
    }

    showExplorer() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        if (this.workspacePath) {
            content.innerHTML = `
                <div class="sidebar-section">
                    <div class="section-header">
                        <span>EXPLORER</span>
                        <div class="section-actions">
                            <button class="btn-icon-xs" id="btnNewFileExplorer" title="New File">
                                <i data-lucide="file-plus" class="w-3 h-3"></i>
                            </button>
                            <button class="btn-icon-xs" id="btnNewFolderExplorer" title="New Folder">
                                <i data-lucide="folder-plus" class="w-3 h-3"></i>
                            </button>
                            <button class="btn-icon-xs" id="btnRefreshExplorer" title="Refresh">
                                <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                            </button>
                            <button class="btn-icon-xs" id="btnCollapseAll" title="Collapse All">
                                <i data-lucide="chevrons-up" class="w-3 h-3"></i>
                            </button>
                        </div>
                    </div>
                    <div class="workspace-name">
                        <i data-lucide="folder" class="w-4 h-4"></i>
                        <span>${this.getWorkspaceName()}</span>
                    </div>
                    <div class="file-tree" id="fileTree">
                        <div class="loading">Loading...</div>
                    </div>
                </div>
            `;

            // Setup action buttons
            document.getElementById('btnRefreshExplorer')?.addEventListener('click', () => this.refreshExplorer());
            document.getElementById('btnCollapseAll')?.addEventListener('click', () => this.collapseAll());

            this.loadFileTree();
        } else {
            content.innerHTML = `
                <div class="sidebar-section">
                    <div class="section-header">
                        <span>EXPLORER</span>
                    </div>
                    <div class="empty-state">
                        <p>You have not yet opened a folder.</p>
                        <button id="btnOpenFolderSidebar" class="btn-primary-sm">
                            <i data-lucide="folder-open" class="w-3 h-3"></i>
                            Open Folder
                        </button>
                    </div>
                </div>
            `;

            document.getElementById('btnOpenFolderSidebar')?.addEventListener('click', () => this.ide.openFolder());
        }

        this.ide.initializeLucideIcons();
    }

    async loadWorkspace(path) {
        this.workspacePath = path;
        this.expandedFolders.clear();
        this.expandedFolders.add(path);
        this.showExplorer();
    }

    async loadFileTree() {
        const fileTree = document.getElementById('fileTree');
        if (!fileTree || !this.workspacePath) return;

        try {
            const items = await this.ide.fileSystem.readDirectory(this.workspacePath);
            fileTree.innerHTML = this.renderFileTree(items, this.workspacePath, 0);
            this.setupFileTreeHandlers();
            this.ide.initializeLucideIcons();
        } catch (error) {
            console.error('Error loading file tree:', error);
            fileTree.innerHTML = `<div class="error">Error loading files: ${error.message}</div>`;
        }
    }

    renderFileTree(items, basePath, level) {
        const sorted = items.sort((a, b) => {
            if (a.isDirectory && !b.isDirectory) return -1;
            if (!a.isDirectory && b.isDirectory) return 1;
            return a.name.localeCompare(b.name);
        });

        return sorted.map(item => {
            const indent = 12 + (level * 10); // Base 12px + 10px per level
            const icon = item.isDirectory ? 'folder' : this.getFileIcon(item.name);
            const className = item.isDirectory ? 'tree-folder' : 'tree-file';
            const isExpanded = this.expandedFolders.has(item.path);
            const chevron = item.isDirectory ? (isExpanded ? 'chevron-down' : 'chevron-right') : '';

            let html = `
                <div class="tree-item ${className}" data-path="${item.path}" data-is-folder="${item.isDirectory}" style="padding-left: ${indent}px;">
                    ${chevron ? `<i data-lucide="${chevron}" class="w-3.5 h-3.5 tree-chevron"></i>` : '<span class="tree-spacer"></span>'}
                    <i data-lucide="${icon}" class="w-4 h-4"></i>
                    <span class="tree-label">${item.name}</span>
                </div>
            `;

            // If folder is expanded, load its children
            if (item.isDirectory && isExpanded) {
                html += `<div class="tree-children" data-parent="${item.path}"></div>`;
            }

            return html;
        }).join('');
    }

    setupFileTreeHandlers() {
        document.querySelectorAll('.tree-item').forEach(item => {
            item.addEventListener('click', async (e) => {
                e.stopPropagation();
                const path = item.dataset.path;
                const isFolder = item.dataset.isFolder === 'true';

                if (isFolder) {
                    await this.toggleFolder(path, item);
                } else {
                    await this.ide.openFileByPath(path);
                }
            });
        });
    }

    async toggleFolder(path, itemElement) {
        if (this.expandedFolders.has(path)) {
            this.expandedFolders.delete(path);
            const childrenContainer = itemElement.nextElementSibling;
            if (childrenContainer) childrenContainer.remove();

            const chevron = itemElement.querySelector('.tree-chevron');
            if (chevron) {
                chevron.setAttribute('data-lucide', 'chevron-right');
                lucide.createIcons();
            }
        } else {
            this.expandedFolders.add(path);

            try {
                const items = await this.ide.fileSystem.readDirectory(path);
                const level = (itemElement.style.paddingLeft.replace('px', '') / 12) + 1;
                const html = this.renderFileTree(items, path, level);

                const childrenContainer = document.createElement('div');
                childrenContainer.className = 'tree-children';
                childrenContainer.dataset.parent = path;
                childrenContainer.innerHTML = html;

                itemElement.after(childrenContainer);
                this.setupFileTreeHandlers();

                const chevron = itemElement.querySelector('.tree-chevron');
                if (chevron) {
                    chevron.setAttribute('data-lucide', 'chevron-down');
                    lucide.createIcons();
                }
            } catch (error) {
                console.error('Error loading folder:', error);
            }
        }
    }

    collapseAll() {
        this.expandedFolders.clear();
        if (this.workspacePath) {
            this.expandedFolders.add(this.workspacePath);
        }
        this.loadFileTree();
    }

    async refreshExplorer() {
        await this.loadFileTree();
    }

    getWorkspaceName() {
        if (!this.workspacePath) return '';
        return this.workspacePath.split(/[/\\]/).pop();
    }

    getFileIcon(fileName) {
        const ext = fileName.split('.').pop()?.toLowerCase();
        const iconMap = {
            'js': 'file-code',
            'jsx': 'file-code',
            'ts': 'file-code',
            'tsx': 'file-code',
            'html': 'file-code',
            'css': 'file-code',
            'json': 'braces',
            'md': 'file-text',
            'py': 'file-code',
            'php': 'file-code',
            'java': 'file-code',
            'txt': 'file-text',
            'png': 'image',
            'jpg': 'image',
            'jpeg': 'image',
            'gif': 'image',
            'svg': 'image',
            'pdf': 'file-text'
        };
        return iconMap[ext] || 'file';
    }

    showSearch() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;
        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header"><span>SEARCH</span></div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search in files..." />
                    <div class="empty-state"><p>Search functionality coming soon</p></div>
                </div>
            </div>
        `;
    }

    showGit() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;
        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header"><span>SOURCE CONTROL</span></div>
                <div class="empty-state"><p>Git integration coming soon</p></div>
            </div>
        `;
    }

    showExtensions() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        const extensions = [
            { id: 'monochrome-theme', name: 'Obsidian Night', version: '1.2.0', desc: 'Ultra-dark theme for OLED screens.', author: 'SafeCode Team', active: true },
            { id: 'git-lens-mini', name: 'GitLens Lite', version: '0.9.5', desc: 'Visual git history and blame info.', author: 'Safenode', active: false },
            { id: 'prettier-bw', name: 'Prettier BW', version: '3.1.0', desc: 'Monochrome code formatter.', author: 'Community', active: true },
            { id: 'lucide-helper', name: 'Lucide Icons Helper', version: '2.0.1', desc: 'Autocomplete for Lucide icons.', author: 'Lucide', active: false }
        ];

        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header"><span>EXTENSIONS</span></div>
                <div class="extension-search">
                    <input type="text" placeholder="Search extensions..." id="extSearch" />
                </div>
                <div class="extension-list">
                    ${extensions.map(ext => `
                        <div class="extension-card ${ext.active ? 'active' : ''}">
                            <div class="ext-header">
                                <span class="ext-name">${ext.name}</span>
                                <span class="ext-version">v${ext.version}</span>
                            </div>
                            <p class="ext-desc">${ext.desc}</p>
                            <div class="ext-footer">
                                <span class="ext-author">${ext.author}</span>
                                <button class="btn-ext-action" data-id="${ext.id}">
                                    ${ext.active ? 'Disable' : 'Enable'}
                                </button>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        this.ide.initializeLucideIcons();
    }
}

// Continue in next part...
