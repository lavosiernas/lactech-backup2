/**
 * EditorManager - Manages CodeMirror editor instances
 */

import { EditorView, basicSetup } from 'codemirror';
import { EditorState } from '@codemirror/state';
import { javascript } from '@codemirror/lang-javascript';
import { html } from '@codemirror/lang-html';
import { css } from '@codemirror/lang-css';
import { json } from '@codemirror/lang-json';
import { python } from '@codemirror/lang-python';
import { php } from '@codemirror/lang-php';
import { markdown } from '@codemirror/lang-markdown';
import { oneDark } from '@codemirror/theme-one-dark';
import { keymap } from '@codemirror/view';
import { indentWithTab, selectNextOccurrence } from '@codemirror/commands';
import { search, searchKeymap } from '@codemirror/search';

export class EditorManager {
    constructor(ide) {
        this.ide = ide;
        this.editors = new Map(); // filePath -> EditorView
        this.activeEditor = null;
    }

    createEditor(filePath, content = '') {
        const container = document.getElementById('editorContainer');

        // Hide welcome screen
        const welcome = document.getElementById('editorWelcome');
        if (welcome) {
            welcome.style.display = 'none';
        }

        // Create editor wrapper
        const editorWrapper = document.createElement('div');
        editorWrapper.className = 'editor-instance';
        editorWrapper.dataset.file = filePath;

        // Determine language mode
        const language = this.getLanguageMode(filePath);

        // Create CodeMirror editor
        const state = EditorState.create({
            doc: content,
            extensions: [
                basicSetup,
                language,
                oneDark,
                search({ top: true }),
                keymap.of([
                    indentWithTab,
                    ...searchKeymap,
                    { key: "Mod-d", run: selectNextOccurrence }
                ]),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        this.onEditorChange(filePath);
                    }
                })
            ]
        });

        const view = new EditorView({
            state,
            parent: editorWrapper
        });

        // Add to container
        container.appendChild(editorWrapper);

        // Store editor
        this.editors.set(filePath, view);
        this.activeEditor = view;

        // Show only this editor
        this.showEditor(filePath);

        return view;
    }

    getLanguageMode(filePath) {
        const ext = filePath.split('.').pop()?.toLowerCase();

        const languageMap = {
            'js': javascript(),
            'jsx': javascript({ jsx: true }),
            'ts': javascript({ typescript: true }),
            'tsx': javascript({ typescript: true, jsx: true }),
            'html': html(),
            'htm': html(),
            'css': css(),
            'scss': css(),
            'sass': css(),
            'json': json(),
            'py': python(),
            'php': php(),
            'md': markdown(),
            'markdown': markdown()
        };

        return languageMap[ext] || javascript();
    }

    showEditor(filePath) {
        // Hide all editors
        const allEditors = document.querySelectorAll('.editor-instance');
        allEditors.forEach(editor => {
            editor.style.display = 'none';
        });

        // Show the requested editor
        const editorWrapper = document.querySelector(`[data-file="${filePath}"]`);
        if (editorWrapper) {
            editorWrapper.style.display = 'block';
            const view = this.editors.get(filePath);
            if (view) {
                this.activeEditor = view;
                view.focus();
            }
        }
    }

    closeEditor(filePath) {
        const view = this.editors.get(filePath);
        if (view) {
            view.destroy();
            this.editors.delete(filePath);
        }

        const editorWrapper = document.querySelector(`[data-file="${filePath}"]`);
        if (editorWrapper) {
            editorWrapper.remove();
        }

        // If no editors left, show welcome screen
        if (this.editors.size === 0) {
            const welcome = document.getElementById('editorWelcome');
            if (welcome) {
                welcome.style.display = 'flex';
            }
            this.activeEditor = null;
        }
    }

    getCurrentContent() {
        if (this.activeEditor) {
            return this.activeEditor.state.doc.toString();
        }
        return '';
    }

    setContent(filePath, content) {
        const view = this.editors.get(filePath);
        if (view) {
            view.dispatch({
                changes: {
                    from: 0,
                    to: view.state.doc.length,
                    insert: content
                }
            });
        }
    }

    onEditorChange(filePath) {
        // Mark tab as dirty
        this.ide.tabManager.markDirty(filePath);
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

    getActiveFilePath() {
        const activeWrapper = document.querySelector('.editor-instance:not([style*="display: none"])');
        return activeWrapper?.dataset.file || null;
    }
}
