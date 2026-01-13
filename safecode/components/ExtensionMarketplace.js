/**
 * ExtensionMarketplace - Handles extension discovery and installation
 */
class ExtensionMarketplace {
    constructor(ide) {
        this.ide = ide;
        this.marketplaceExtensions = [];
        this.categories = [];
        this.currentFilter = 'all';
        this.searchQuery = '';
    }

    async loadMarketplace() {
        try {
            // Load marketplace data
            const response = await fetch('extensions/marketplace.json');
            const data = await response.json();
            this.marketplaceExtensions = data.extensions;
            this.categories = data.categories;
            return { success: true };
        } catch (error) {
            console.error('[ExtensionMarketplace] Failed to load marketplace:', error);
            return { success: false, error: error.message };
        }
    }

    renderMarketplace(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        // Get installed extension IDs
        const installedIds = Array.from(this.ide.extensionManager.extensions.keys());

        // Filter extensions
        let filtered = this.marketplaceExtensions;

        if (this.searchQuery) {
            const query = this.searchQuery.toLowerCase();
            filtered = filtered.filter(ext =>
                ext.name.toLowerCase().includes(query) ||
                ext.displayName.toLowerCase().includes(query) ||
                ext.description.toLowerCase().includes(query) ||
                ext.tags.some(tag => tag.toLowerCase().includes(query))
            );
        }

        if (this.currentFilter !== 'all') {
            filtered = filtered.filter(ext => ext.categories.includes(this.currentFilter));
        }

        // Sort by downloads
        filtered.sort((a, b) => b.downloads - a.downloads);

        let html = '<div class="marketplace-grid" style="padding: 0.5rem;">';

        if (filtered.length === 0) {
            html += '<div class="empty-state"><p style="font-size: 0.875rem;">No extensions found</p></div>';
        } else {
            filtered.forEach(ext => {
                const isInstalled = installedIds.includes(ext.id);
                const downloadText = this.formatDownloads(ext.downloads);

                html += `
                    <div class="marketplace-item" style="padding: 1rem; border: 1px solid #222; border-radius: 4px; margin-bottom: 0.75rem; background: #000;">
                        <div style="display: flex; gap: 0.75rem;">
                            <div class="ext-icon" style="width: 48px; height: 48px; background: #111; border-radius: 4px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="puzzle" class="w-6 h-6" style="color: var(--text-tertiary);"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                    <h4 style="font-size: 0.9375rem; font-weight: 600; margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${ext.displayName}</h4>
                                    ${isInstalled ? '<span style="font-size: 0.75rem; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 0.125rem 0.5rem; border-radius: 3px;">Installed</span>' : ''}
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-tertiary); margin-bottom: 0.5rem;">${ext.publisher} • ${downloadText} downloads</div>
                                <p style="font-size: 0.8125rem; color: var(--text-secondary); margin: 0 0 0.75rem 0; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">${ext.description}</p>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; color: var(--text-tertiary);">
                                        <i data-lucide="star" class="w-3 h-3"></i>
                                        ${ext.rating.toFixed(1)}
                                    </div>
                                    ${ext.tags.slice(0, 3).map(tag => `<span style="font-size: 0.7rem; color: var(--text-tertiary); background: rgba(255,255,255,0.05); padding: 0.125rem 0.4rem; border-radius: 3px;">${tag}</span>`).join('')}
                                    <button class="btn-install-ext" data-ext-id="${ext.id}" style="margin-left: auto; padding: 0.375rem 0.75rem; background: ${isInstalled ? '#222' : '#10b981'}; border: 1px solid ${isInstalled ? '#333' : '#10b981'}; color: #fff; border-radius: 4px; font-size: 0.8125rem; cursor: ${isInstalled ? 'not-allowed' : 'pointer'}; opacity: ${isInstalled ? '0.5' : '1'};" ${isInstalled ? 'disabled' : ''}>
                                        ${isInstalled ? 'Installed' : 'Install'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        html += '</div>';
        container.innerHTML = html;

        // Add install handlers
        container.querySelectorAll('.btn-install-ext:not([disabled])').forEach(btn => {
            btn.addEventListener('click', async () => {
                const extId = btn.dataset.extId;
                await this.installExtension(extId);
            });
        });

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async installExtension(extensionId) {
        const ext = this.marketplaceExtensions.find(e => e.id === extensionId);
        if (!ext) {
            alert('Extension not found');
            return;
        }

        if (!this.ide.isElectron || !window.electronAPI.extensions) {
            alert('Extension installation is only available in Electron mode');
            return;
        }

        // Show installing state
        const btn = document.querySelector(`[data-ext-id="${extensionId}"]`);
        if (btn) {
            btn.textContent = 'Installing...';
            btn.disabled = true;
        }

        try {
            const result = await window.electronAPI.extensions.install(ext.repositoryUrl, extensionId);

            if (result.success) {
                alert(`${ext.displayName} installed successfully! Restart the IDE to activate.`);
                // Refresh marketplace view
                this.renderMarketplace('marketplaceContent');
            } else {
                alert(`Failed to install ${ext.displayName}: ${result.error}`);
                if (btn) {
                    btn.textContent = 'Install';
                    btn.disabled = false;
                }
            }
        } catch (error) {
            console.error('Installation error:', error);
            alert(`Installation failed: ${error.message}`);
            if (btn) {
                btn.textContent = 'Install';
                btn.disabled = false;
            }
        }
    }

    formatDownloads(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    setFilter(category) {
        this.currentFilter = category;
        this.renderMarketplace('marketplaceContent');
    }

    setSearch(query) {
        this.searchQuery = query;
        this.renderMarketplace('marketplaceContent');
    }
}

window.ExtensionMarketplace = ExtensionMarketplace;
