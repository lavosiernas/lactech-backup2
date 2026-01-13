/**
 * SafeCode 'Hello World' Extension
 */
function activate(context) {
    console.log('Hello World extension is now active!');

    // Register a command
    context.api.commands.registerCommand('hello.world', () => {
        context.api.window.showInformationMessage('Hello from SafeCode Extension API! 🚀');
    });

    // Listen for file saves
    context.api.workspace.onDidSaveFile((file) => {
        console.log(`Extension detected file save: ${file}`);
    });
}

// Return the activate function to the manager
return {
    activate: activate
};
