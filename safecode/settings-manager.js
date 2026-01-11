/**
 * SafeCode IDE - Settings Manager
 * Handles local storage of user preferences and IDE configuration.
 */

class SettingsManager {
    constructor(ide) {
        this.ide = ide;
        this.settingsKey = 'safecode_settings';
        this.defaults = {
            theme: 'safecode-dark',
            fontSize: 14,
            fontFamily: 'JetBrains Mono, Consolas, monospace',
            tabSize: 4,
            wordWrap: 'on',
            minimap: true,
            autoSave: false,
            liveServerAutoRefresh: true,
            sidebarPosition: 'left'
        };
        this.settings = this.loadSettings();
    }

    loadSettings() {
        const stored = localStorage.getItem(this.settingsKey);
        if (stored) {
            try {
                return { ...this.defaults, ...JSON.parse(stored) };
            } catch (e) {
                console.error('Error parsing settings:', e);
                return { ...this.defaults };
            }
        }
        return { ...this.defaults };
    }

    saveSettings() {
        localStorage.setItem(this.settingsKey, JSON.stringify(this.settings));
        this.applySettings();
    }

    get(key) {
        return this.settings[key];
    }

    set(key, value) {
        this.settings[key] = value;
        this.saveSettings();
    }

    applySettings() {
        // Apply to Monaco Editors
        if (this.ide.editorManager) {
            this.ide.editorManager.editors.forEach(data => {
                if (data.editor.updateOptions) {
                    data.editor.updateOptions({
                        theme: this.settings.theme,
                        fontSize: this.settings.fontSize,
                        fontFamily: this.settings.fontFamily,
                        tabSize: this.settings.tabSize,
                        wordWrap: this.settings.wordWrap,
                        minimap: { enabled: this.settings.minimap }
                    });
                }
            });
        }

        // Apply to UI
        document.body.style.setProperty('--ide-font-size', `${this.settings.fontSize}px`);

        // Notify other managers if needed
        if (this.ide.liveServer) {
            this.ide.liveServer.autoRefresh = this.settings.liveServerAutoRefresh;
        }

        console.log('Settings applied:', this.settings);
    }

    reset() {
        this.settings = { ...this.defaults };
        this.saveSettings();
    }
}
