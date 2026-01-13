/**
 * SidebarManagerExtended - Extensions for Git, Search, and Extensions UI  
 */

// Extend SidebarManager with new methods
Object.assign(SidebarManager.prototype, {
    async showGit() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        // Check if repo exists
        if (!this.ide.gitManager || !this.ide.workspace) {
            content.innerHTML = `
                <div class="sidebar-section">
                    <div class="section-header">
                        <span>SOURCE CONTROL</span>
                    </div>
                    <div class="empty-state" style="padding: 1rem;">
                        <p style="margin-bottom: 1rem; color: var(--text-secondary);">Open a folder first</p>
                    </div>
                </div>
            `;
            return;
        }

        const repoCheck = await this.ide.gitManager.checkRepository();

        if (!repoCheck.isRepo) {
            content.innerHTML = `
                <div class="sidebar-section">
                    <div class="section-header">
                        <span>SOURCE CONTROL</span>
                    </div>
                    <div class="empty-state" style="padding: 1rem;">
                        <p style="margin-bottom: 1rem; color: var(--text-secondary);">No repository found</p>
                        <button id="btnGitInit" class="btn-primary" style="width: 100%; margin-bottom: 0.5rem; font-size: 0.875rem; padding: 0.5rem;">
                            Initialize Repository
                        </button>
                        <button id="btnGitClone" class="btn-primary" style="width: 100%; background: transparent; border: 1px solid #444; color: #fff; font-size: 0.875rem; padding: 0.5rem;">
                            Clone Repository
                        </button>
                    </div>
                </div>
            `;

            document.getElementById('btnGitInit')?.addEventListener('click', () => this.ide.gitManager.initRepository());
            document.getElementById('btnGitClone')?.addEventListener('click', () => this.ide.gitManager.cloneRepository());
        } else {
            // Show git status
            await this.ide.gitManager.refreshStatus();

            content.innerHTML = `
                <div class="sidebar-section">
                    <div class="section-header">
                        <span>SOURCE CONTROL</span>
                        <button id="btnRefreshGit" class="btn-icon-xs" style="margin-left: auto; padding: 0.25rem;">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                    <div class="git-container" style="padding: 0.5rem;">
                        <div class="git-commit-section" style="margin-bottom: 1rem;">
                            <input type="text" id="gitCommitMessage" placeholder="Commit message..." style="width: 100%; padding: 0.5rem; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; margin-bottom: 0.5rem; font-size: 0.875rem;" />
                            <button id="btnGitCommit" class="btn-primary" style="width: 100%; font-size: 0.875rem; padding: 0.5rem;">
                                Commit
                            </button>
                        </div>
                        <div class="git-changes" id="gitChanges">
                            <!-- Changes will be populated here -->
                        </div>
                    </div>
                </div>
            `;

            this.renderGitChanges();
            document.getElementById('btnGitCommit')?.addEventListener('click', () => {
                const msg = document.getElementById('gitCommitMessage').value;
                if (!msg) return alert('Enter a commit message');
                this.ide.gitManager.commit();
            });
            document.getElementById('btnRefreshGit')?.addEventListener('click', async () => {
                await this.ide.gitManager.refreshStatus();
                this.renderGitChanges();
            });
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    renderGitChanges() {
        const changesEl = document.getElementById('gitChanges');
        if (!changesEl || !this.ide.gitManager) return;

        const { modifiedFiles, stagedFiles } = this.ide.gitManager;

        if (modifiedFiles.size === 0) {
            changesEl.innerHTML = '<div class="empty-state"><p>No changes</p></div>';
            return;
        }

        let html = '<div class="git-file-list" style="font-size: 0.875rem;">';

        modifiedFiles.forEach((status, filePath) => {
            const fileName = filePath.split(/[/\\]/).pop();
            const isStaged = stagedFiles.has(filePath);
            const statusChar = String(status).substring(0, 1);
            const statusColor = statusChar === 'M' ? '#3b82f6' : statusChar === 'A' ? '#10b981' : statusChar === 'D' ? '#ef4444' : '#f59e0b';

            html += `
                <div class="git-file-item" style="padding: 0.5rem; border-bottom: 1px solid #222; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;" data-path="${filePath}">
                    <span style="color: ${statusColor}; font-weight: bold; width: 20px;">${statusChar}</span>
                    <i data-lucide="file" class="w-3.5 h-3.5" style="flex-shrink: 0;"></i>
                    <span style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${fileName}</span>
                    <button class="btn-git-stage" data-path="${filePath}" style="padding: 0.25rem 0.5rem; background: ${isStaged ? '#10b981' : 'transparent'}; border: 1px solid ${isStaged ? '#10b981' : '#444'}; color: #fff; border-radius: 4px; font-size: 0.75rem;">
                        ${isStaged ? '-' : '+'}
                    </button>
                </div>
            `;
        });

        html += '</div>';
        changesEl.innerHTML = html;

        // Add event listeners
        changesEl.querySelectorAll('.btn-git-stage').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const path = btn.dataset.path;
                const isStaged = stagedFiles.has(path);
                if (isStaged) {
                    await this.ide.gitManager.unstageFile(path);
                } else {
                    await this.ide.gitManager.stageFile(path);
                }
            });
        });

        changesEl.querySelectorAll('.git-file-item').forEach(item => {
            item.addEventListener('click', () => {
                const path = item.dataset.path;
                this.ide.gitManager.viewDiff(path);
            });
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    async showExtensions() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header">
                    <span>EXTENSIONS</span>
                </div>
                <div class="extensions-container" style="padding: 0.5rem;">
                    <div class="extension-tabs" style="display: flex; gap: 0.5rem; margin-bottom: 1rem; border-bottom: 1px solid #222; padding-bottom: 0.5rem;">
                        <button class="ext-tab active" data-tab="installed" style="flex: 1; padding: 0.5rem; background: rgba(255,255,255,0.1); border: none; color: #fff; border-radius: 4px; cursor: pointer; font-size: 0.8125rem;">Installed</button>
                        <button class="ext-tab" data-tab="marketplace" style="flex: 1; padding: 0.5rem; background: transparent; border: none; color: var(--text-secondary); border-radius: 4px; cursor: pointer; font-size: 0.8125rem;">Marketplace</button>
                    </div>
                    <div id="installedExtensions" class="ext-view">
                        <div id="extensionList"></div>
                    </div>
                    <div id="marketplaceExtensions" class="ext-view" style="display: none;">
                        <div class="marketplace-search" style="margin-bottom: 1rem;">
                            <input type="text" id="marketplaceSearch" placeholder="Search extensions..." style="width: 100%; padding: 0.5rem; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; font-size: 0.8125rem; margin-bottom: 0.5rem;" />
                            <select id="marketplaceCategory" style="width: 100%; padding: 0.5rem; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; font-size: 0.8125rem;">
                                <option value="all">All Categories</option>
                            </select>
                        </div>
                        <div id="marketplaceContent"></div>
                    </div>
                </div>
            </div>
        `;

        // Setup tab switching
        document.querySelectorAll('.ext-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.dataset.tab;

                // Update tab styles
                document.querySelectorAll('.ext-tab').forEach(t => {
                    t.classList.remove('active');
                    t.style.background = 'transparent';
                    t.style.color = 'var(--text-secondary)';
                });
                tab.classList.add('active');
                tab.style.background = 'rgba(255,255,255,0.1)';
                tab.style.color = '#fff';

                // Show/hide views
                document.getElementById('installedExtensions').style.display = tabName === 'installed' ? 'block' : 'none';
                document.getElementById('marketplaceExtensions').style.display = tabName === 'marketplace' ? 'block' : 'none';

                if (tabName === 'marketplace') {
                    this.loadMarketplaceView();
                }
            });
        });

        // Load installed extensions
        const extensions = this.ide.extensionManager.extensions;
        const listEl = document.getElementById('extensionList');

        if (extensions.size === 0) {
            listEl.innerHTML = '<div class="empty-state"><p style="font-size: 0.875rem;">No extensions installed</p></div>';
        } else {
            let html = '';
            extensions.forEach((ext, id) => {
                html += `
                    <div class="extension-item" style="padding: 0.75rem; border-bottom: 1px solid #222;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i data-lucide="puzzle" class="w-4 h-4" style="color: var(--text-tertiary); flex-shrink: 0;"></i>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 500; font-size: 0.875rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${ext.manifest.displayName || id}</div>
                                <div style="font-size: 0.75rem; color: var(--text-tertiary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${ext.manifest.description || ''}</div>
                            </div>
                            <span style="font-size: 0.75rem; color: var(--text-tertiary); flex-shrink: 0;">${ext.manifest.version || '1.0.0'}</span>
                        </div>
                    </div>
                `;
            });
            listEl.innerHTML = html;
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    async loadMarketplaceView() {
        if (!this.ide.marketplace) {
            // Initialize marketplace if not exists
            this.ide.marketplace = new ExtensionMarketplace(this.ide);
            await this.ide.marketplace.loadMarketplace();

            // Populate categories
            const categorySelect = document.getElementById('marketplaceCategory');
            if (categorySelect && this.ide.marketplace.categories) {
                this.ide.marketplace.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat;
                    option.textContent = cat;
                    categorySelect.appendChild(option);
                });
            }

            // Add search listener
            document.getElementById('marketplaceSearch')?.addEventListener('input', (e) => {
                this.ide.marketplace.setSearch(e.target.value);
            });

            // Add category listener
            document.getElementById('marketplaceCategory')?.addEventListener('change', (e) => {
                this.ide.marketplace.setFilter(e.target.value);
            });
        }

        // Render marketplace
        this.ide.marketplace.renderMarketplace('marketplaceContent');
    },

    showSearch() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header">
                    <span>SEARCH</span>
                </div>
                <div class="search-container" style="padding: 0.5rem;">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search in files..." style="width: 100%; padding: 0.5rem; background: #000; border: 1px solid #333; color: #fff; border-radius: 4px; margin-bottom: 0.5rem; font-size: 0.875rem;" />
                    <div class="search-options" style="margin-bottom: 0.5rem; font-size: 0.75rem;">
                        <label style="display: flex; align-items: center; gap: 0.25rem; color: var(--text-secondary); margin-bottom: 0.25rem; cursor: pointer;">
                            <input type="checkbox" id="searchCaseSensitive" /> Match Case
                        </label>
                        <label style="display: flex; align-items: center; gap: 0.25rem; color: var(--text-secondary); cursor: pointer;">
                            <input type="checkbox" id="searchRegex" /> Use Regex
                        </label>
                    </div>
                    <button id="btnSearch" class="btn-primary" style="width: 100%; margin-bottom: 1rem; font-size: 0.875rem; padding: 0.5rem;">
                        Search
                    </button>
                    <div class="search-results" id="searchResults">
                        <div class="empty-state">
                            <p style="font-size: 0.875rem;">Enter a search term</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add search handler
        document.getElementById('btnSearch')?.addEventListener('click', () => this.performSearch());
        document.getElementById('searchInput')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.performSearch();
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    async performSearch() {
        const input = document.getElementById('searchInput');
        const resultsEl = document.getElementById('searchResults');
        if (!input || !resultsEl) return;

        const query = input.value.trim();
        if (!query) {
            resultsEl.innerHTML = '<div class="empty-state"><p style="font-size: 0.875rem;">Enter a search term</p></div>';
            return;
        }

        const options = {
            caseSensitive: document.getElementById('searchCaseSensitive')?.checked || false,
            useRegex: document.getElementById('searchRegex')?.checked || false
        };

        resultsEl.innerHTML = '<div class="loading" style="padding: 1rem; text-align: center; font-size: 0.875rem;">Searching...</div>';

        try {
            if (!this.ide.isElectron || !window.electronAPI.search) {
                resultsEl.innerHTML = '<div class="error" style="padding: 1rem; font-size: 0.875rem;">Search only available in Desktop mode</div>';
                return;
            }

            const result = await window.electronAPI.search.query(this.ide.workspace, query, options);

            if (result.success) {
                if (result.results.length === 0) {
                    resultsEl.innerHTML = '<div class="empty-state"><p style="font-size: 0.875rem;">No results found</p></div>';
                } else {
                    let html = `<div style="font-size: 0.75rem; color: var(--text-tertiary); margin-bottom: 0.5rem; padding: 0 0.5rem;">Found ${result.results.length} results</div>`;

                    // Group by file
                    const files = {};
                    result.results.forEach(res => {
                        if (!files[res.path]) files[res.path] = [];
                        files[res.path].push(res);
                    });

                    for (const filePath in files) {
                        const fileName = filePath.split(/[/\\]/).pop();
                        const relativePath = filePath.replace(this.ide.workspace, '').replace(/^[/\\]/, '');

                        html += `
                            <div class="search-file-group" style="margin-bottom: 0.5rem;">
                                <div class="search-file-header" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.5rem; cursor: pointer; background: rgba(255,255,255,0.03);">
                                    <i data-lucide="chevron-down" class="w-3 h-3"></i>
                                    <i data-lucide="file" class="w-3.5 h-3.5"></i>
                                    <span style="font-size: 0.8125rem; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${fileName}</span>
                                    <span style="font-size: 0.7rem; color: var(--text-tertiary); margin-left: auto;">${files[filePath].length}</span>
                                </div>
                                <div class="search-file-matches" style="padding-left: 1.5rem;">
                                    ${files[filePath].map(match => `
                                        <div class="search-match-item" data-path="${match.path}" data-line="${match.line}" style="padding: 0.25rem 0.5rem; cursor: pointer; font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border-left: 1px solid #333; margin-top: 1px;">
                                            <span style="color: var(--text-tertiary); margin-right: 0.5rem;">${match.line}</span>
                                            <span>${this.highlightMatch(match.content, query, options)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }
                    resultsEl.innerHTML = html;

                    // Add click handlers for results
                    resultsEl.querySelectorAll('.search-match-item').forEach(item => {
                        item.addEventListener('click', () => {
                            const path = item.dataset.path;
                            const line = parseInt(item.dataset.line);
                            this.ide.openFileByPath(path).then(() => {
                                // Jump to line if editor manager supports it
                                if (this.ide.editorManager && this.ide.editorManager.getActiveEditor) {
                                    const editor = this.ide.editorManager.getActiveEditor();
                                    if (editor) {
                                        editor.revealLineInCenter(line);
                                        editor.setPosition({ lineNumber: line, column: 1 });
                                    }
                                }
                            });
                        });
                    });
                }
            } else {
                resultsEl.innerHTML = `<div class="error" style="padding: 1rem; font-size: 0.875rem; color: #ef4444;">${result.error}</div>`;
            }
        } catch (error) {
            console.error('Search error:', error);
            resultsEl.innerHTML = '<div class="error" style="padding: 1rem; font-size: 0.875rem; color: #ef4444;">Search failed</div>';
        }

        if (typeof lucide !== 'undefined') lucide.createIcons();
    },

    highlightMatch(content, query, options) {
        if (!query) return content;
        try {
            const regex = options.useRegex
                ? new RegExp(`(${query})`, options.caseSensitive ? 'g' : 'gi')
                : new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, options.caseSensitive ? 'g' : 'gi');

            return content.replace(regex, '<span style="background: rgba(255, 255, 0, 0.3); color: #fff;">$1</span>');
        } catch (e) {
            return content;
        }
    }
});

console.log('✅ SidebarManager extended with Git, Extensions, and Search views');
