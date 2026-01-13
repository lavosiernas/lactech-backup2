function activate(context) {
    console.log('Hello World extension is now active!');

    safecode.commands.registerCommand('helloWorld.sayHello', () => {
        safecode.window.showInformationMessage('Hello from SafeCode Extension!');
    }, {
        title: 'Say Hello',
        icon: 'smile'
    });
}

return {
    activate: activate
};
