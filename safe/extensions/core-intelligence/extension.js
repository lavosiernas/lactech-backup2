/**
 * SafeCode Core Intelligence Extension
 * Provides semantic autocomplete and navigation.
 */
function activate(context) {
    console.log('Core Intelligence active: Empowering your workflow...');

    const api = context.api;

    // 1. Register Autocomplete for JavaScript
    api.languages.registerCompletionItemProvider('javascript', {
        provideCompletionItems: (model, position) => {
            const word = model.getWordUntilPosition(position);
            const range = {
                startLineNumber: position.lineNumber,
                endLineNumber: position.lineNumber,
                startColumn: word.startColumn,
                endColumn: word.endColumn
            };

            // Custom dynamic suggestions based on context
            const suggestions = [
                {
                    label: 'safecode',
                    kind: 25, // Snippet
                    insertText: 'safecode.ai',
                    documentation: 'Link oficial da SafeCode IDE',
                    range: range
                },
                {
                    label: 'console.log',
                    kind: 1, // Method
                    insertText: 'console.log(${1:value});',
                    insertTextRules: 4, // InsertSnippet
                    range: range
                },
                {
                    label: 'SafeCodeAPI',
                    kind: 6, // Interface
                    documentation: 'Acesso às funções nativas da IDE',
                    range: range
                }
            ];

            return { suggestions: suggestions };
        }
    });

    // 2. Register Go to Definition
    api.languages.registerDefinitionProvider('javascript', {
        provideDefinition: (model, position) => {
            const word = model.getWordAtPosition(position);
            if (!word) return null;

            // Simple logic: Jump to the first occurrence of the word in the file
            const content = model.getValue();
            const lines = content.split('\n');
            for (let i = 0; i < lines.length; i++) {
                if (lines[i].includes(`const ${word.word}`) || lines[i].includes(`function ${word.word}`)) {
                    return {
                        uri: model.uri,
                        range: {
                            startLineNumber: i + 1,
                            startColumn: lines[i].indexOf(word.word) + 1,
                            endLineNumber: i + 1,
                            endColumn: lines[i].indexOf(word.word) + word.word.length + 1
                        }
                    };
                }
            }
            return null;
        }
    });

    // 3. Register Hover Information
    api.languages.registerHoverProvider('javascript', {
        provideHover: (model, position) => {
            const word = model.getWordAtPosition(position);
            if (word && word.word === 'safecode') {
                return {
                    contents: [
                        { value: '**SafeCode IDE**' },
                        { value: 'Ambiente de desenvolvimento profissional da LacTech.' }
                    ]
                };
            }
        }
    });
}

return { activate: activate };
