#!/bin/bash
# SafeCode IDE Professional Installer for macOS/Linux
# Usage: curl -fsSL https://safenode.cloud/safenode/safecode/install.sh | bash

set -e

# Configuration
INSTALL_DIR="$HOME/.local/bin"
EXE_NAME="safecode"
TARGET_PATH="$INSTALL_DIR/$EXE_NAME"
DOWNLOAD_URL="https://github.com/safenode/safecode/releases/latest/download/safecode"

# Colors
CYAN='\033[0;36m'
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_step() {
    echo -e "\n${CYAN}[🚀] $1${NC}"
}

print_success() {
    echo -e "${GREEN}[✅] $1${NC}"
}

print_error() {
    echo -e "${RED}[❌] $1${NC}"
}

echo -e "\n--- SafeCode IDE Installer ---"
echo -e "Elevating your workspace...\n"

# 1. Create Directory
if [ ! -d "$INSTALL_DIR" ]; then
    print_step "Creating installation directory: $INSTALL_DIR"
    mkdir -p "$INSTALL_DIR"
fi

# 2. Download Binary
print_step "Downloading SafeCode IDE..."
if command -v curl &> /dev/null; then
    curl -fsSL "$DOWNLOAD_URL" -o "$TARGET_PATH"
elif command -v wget &> /dev/null; then
    wget -q "$DOWNLOAD_URL" -O "$TARGET_PATH"
else
    print_error "Neither curl nor wget found. Please install one of them."
    exit 1
fi

# 3. Make executable
chmod +x "$TARGET_PATH"

# 4. Add to PATH
print_step "Configuring Environment Variables..."
SHELL_RC=""
if [ -n "$BASH_VERSION" ]; then
    SHELL_RC="$HOME/.bashrc"
elif [ -n "$ZSH_VERSION" ]; then
    SHELL_RC="$HOME/.zshrc"
fi

if [ -n "$SHELL_RC" ] && [ -f "$SHELL_RC" ]; then
    if ! grep -q "$INSTALL_DIR" "$SHELL_RC"; then
        echo "export PATH=\"\$PATH:$INSTALL_DIR\"" >> "$SHELL_RC"
        print_success "SafeCode added to PATH in $SHELL_RC"
    else
        print_success "SafeCode already in PATH"
    fi
fi

# 5. Finalizing
print_success "Installation Complete!"
echo -e "\n------------------------------------------------"
echo -e "You can now run SafeCode by typing 'safecode' in any terminal."
echo -e "${YELLOW}Restart your terminal or run: source $SHELL_RC${NC}"
echo -e "------------------------------------------------\n"
