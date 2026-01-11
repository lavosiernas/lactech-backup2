/**
 * SafeCode IDE - Settings View
 * UI Component for managing settings.
 */

class SettingsView {
    constructor(ide) {
        this.ide = ide;
        this.modal = null;
        this.createModal();
    }

    createModal() {
        const modal = document.createElement('div');
        modal.className = 'settings-modal';
        modal.id = 'settingsModal';
        modal.style.display = 'none';

        modal.innerHTML = `
            <div class="settings-container">
                <header class="settings-header">
                    <h2>Settings</h2>
                    <button class="btn-icon" id="btnCloseSettings"><i data-lucide="x"></i></button>
                </header>
                <div class="settings-body">
                    <div class="settings-section">
                        <h3>Editor</h3>
                        <div class="setting-item">
                            <label>Font Size</label>
                            <input type="number" id="settingFontSize" min="10" max="30">
                        </div>
                        <div class="setting-item">
                            <label>Tab Size</label>
                            <select id="settingTabSize">
                                <option value="2">2</option>
                                <option value="4">4</option>
                                <option value="8">8</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label>Word Wrap</label>
                            <select id="settingWordWrap">
                                <option value="on">On</option>
                                <option value="off">Off</option>
                            </select>
                        </div>
                        <div class="setting-item">
                            <label>Minimap</label>
                            <input type="checkbox" id="settingMinimap">
                        </div>
                    </div>
                    <div class="settings-section">
                        <h3>Files</h3>
                        <div class="setting-item">
                            <label>Auto Save</label>
                            <input type="checkbox" id="settingAutoSave">
                        </div>
                    </div>
                </div>
                <footer class="settings-footer">
                    <button class="btn-primary" id="btnSaveSettings">Save Changes</button>
                    <button class="btn-secondary" id="btnResetSettings">Reset Defaults</button>
                </footer>
            </div>
        `;

        document.body.appendChild(modal);
        this.modal = modal;
        this.setupListeners();
        this.ide.initializeLucideIcons();
    }

    setupListeners() {
        document.getElementById('btnCloseSettings')?.addEventListener('click', () => this.hide());
        document.getElementById('btnSaveSettings')?.addEventListener('click', () => this.save());
        document.getElementById('btnResetSettings')?.addEventListener('click', () => this.reset());

        // Close on ESC
        window.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.style.display === 'flex') {
                this.hide();
            }
        });

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) this.hide();
        });
    }

    show() {
        const s = this.ide.settingsManager.settings;
        document.getElementById('settingFontSize').value = s.fontSize;
        document.getElementById('settingTabSize').value = s.tabSize;
        document.getElementById('settingWordWrap').value = s.wordWrap;
        document.getElementById('settingMinimap').checked = s.minimap;
        document.getElementById('settingAutoSave').checked = s.autoSave;

        this.modal.style.display = 'flex';
    }

    hide() {
        this.modal.style.display = 'none';
    }

    save() {
        const sm = this.ide.settingsManager;
        sm.set('fontSize', parseInt(document.getElementById('settingFontSize').value));
        sm.set('tabSize', parseInt(document.getElementById('settingTabSize').value));
        sm.set('wordWrap', document.getElementById('settingWordWrap').value);
        sm.set('minimap', document.getElementById('settingMinimap').checked);
        sm.set('autoSave', document.getElementById('settingAutoSave').checked);

        this.hide();
    }

    reset() {
        if (confirm('Reset all settings to default?')) {
            this.ide.settingsManager.reset();
            this.show();
        }
    }
}
