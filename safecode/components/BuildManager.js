/**
 * BuildManager - Manages NPM and Composer scripts/packages
 */

export class BuildManager {
    constructor(ide) {
        this.ide = ide;
        this.scripts = {
            npm: [],
            composer: []
        };
    }

    async refresh() {
        if (!this.ide.sidebar.workspacePath) return;

        await this.loadNpmScripts();
        await this.loadComposerScripts();
        this.render();
    }

    async loadNpmScripts() {
        try {
            const pkgPath = `${this.ide.sidebar.workspacePath}/package.json`;
            const content = await this.ide.fileSystem.readFile(pkgPath);
            const pkg = JSON.parse(content);
            this.scripts.npm = Object.keys(pkg.scripts || {}).map(name => ({
                name,
                command: pkg.scripts[name]
            }));
        } catch (e) {
            this.scripts.npm = [];
        }
    }

    async loadComposerScripts() {
        try {
            const compPath = `${this.ide.sidebar.workspacePath}/composer.json`;
            const content = await this.ide.fileSystem.readFile(compPath);
            const comp = JSON.parse(content);
            this.scripts.composer = Object.keys(comp.scripts || {}).map(name => ({
                name,
                command: comp.scripts[name]
            }));
        } catch (e) {
            this.scripts.composer = [];
        }
    }

    render() {
        this.renderPanel('npm');
        this.renderPanel('composer');
        this.setupHandlers();
    }

    renderPanel(type) {
        const container = document.getElementById(`panel-${type}`);
        if (!container) return;

        const scripts = this.scripts[type];
        if (scripts.length === 0) {
            container.innerHTML = `<div class="empty-state"><p>No ${type.toUpperCase()} scripts found.</p></div>`;
            return;
        }

        container.innerHTML = `
            <div class="build-scripts-grid">
                ${scripts.map(s => `
                    <div class="build-script-item" data-type="${type}" data-name="${s.name}">
                        <div class="script-info">
                            <span class="script-name">${s.name}</span>
                            <span class="script-cmd">${s.command}</span>
                        </div>
                        <button class="btn-run-script">
                            <i data-lucide="play" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
            <div class="build-actions">
                <button class="btn-build-action" data-action="${type}-install">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    Install Dependencies
                </button>
            </div>
        `;

        if (typeof lucide !== 'undefined') {
            lucide.createIcons({
                attrs: {
                    class: 'lucide'
                },
                parentElement: container
            });
        }
    }

    setupHandlers() {
        document.querySelectorAll('.btn-run-script').forEach(btn => {
            btn.onclick = (e) => {
                const item = e.target.closest('.build-script-item');
                const type = item.dataset.type;
                const name = item.dataset.name;
                this.runScript(type, name);
            };
        });

        document.querySelectorAll('.btn-build-action').forEach(btn => {
            btn.onclick = (e) => {
                const action = e.target.closest('.btn-build-action').dataset.action;
                this.handleAction(action);
            };
        });
    }

    runScript(type, name) {
        const command = type === 'npm' ? `npm run ${name}` : `composer ${name}`;
        this.executeInTerminal(command);
    }

    handleAction(action) {
        let command = '';
        if (action === 'npm-install') command = 'npm install';
        if (action === 'composer-install') command = 'composer install';

        if (command) {
            this.executeInTerminal(command);
        }
    }

    executeInTerminal(command) {
        const terminalId = this.ide.terminalManager.createTerminal();
        this.ide.bottomPanel.switchPanel('terminal');

        // Give it a tiny bit of time for PTY to initialize
        setTimeout(() => {
            if (this.ide.isElectron) {
                window.electronAPI.terminal.write(terminalId, `${command}\r`);
            } else {
                // In web mode, we don't really have a shell, but we can simulate
                const terminal = this.ide.terminalManager.terminals.get(terminalId).terminal;
                terminal.writeln(`\r\nRunning: ${command}`);
                terminal.writeln('Web mode shell simulation - command not actually executed.');
            }
        }, 500);
    }
}
