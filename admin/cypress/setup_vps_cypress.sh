#!/bin/bash

# Script to install Node.js, Cypress, Google Chrome, Microsoft Edge, Chromium, and Firefox
# on Ubuntu 24.04 under a dedicated "cypress" user.

echo "Starting full setup for Cypress and browsers on Ubuntu 24.04..."

# Update and upgrade the system
echo "Updating and upgrading system packages..."
sudo apt update && sudo apt upgrade -y

# Step 1: Create a dedicated "cypress" user if it doesn't exist
if id "cypress" &>/dev/null; then
    echo "User 'cypress' already exists. Skipping user creation."
else
    echo "Creating user 'cypress'..."
    sudo adduser --disabled-password --gecos "" cypress
    echo "User 'cypress' created successfully."
fi

# Install required dependencies
echo "Installing required dependencies..."
sudo apt install -y wget curl apt-transport-https software-properties-common \
  build-essential libnss3 libxss1 libappindicator3-1 libindicator7 fonts-liberation \
  xvfb libgbm-dev git x11-xkb-utils xkb-data

# Install Node.js (LTS)
echo "Installing Node.js LTS..."
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt install -y nodejs
echo "Node.js version: $(node -v)"
echo "NPM version: $(npm -v)"

# Step 2: Switch to "cypress" user and set up Cypress
echo "Setting up Cypress under the 'cypress' user..."
sudo -u cypress bash <<EOF
mkdir -p ~/cypress-project
sudo chown -R $USER:$USER ~/cypress-project
cd ~/cypress-project
npm init -y
npm install cypress --save-dev
echo "Cypress installed successfully for user 'cypress'."
EOF
sleep 15


echo "Node.js version: $(node -v)"
echo "NPM version: $(npm -v)"


# Install Google Chrome
echo "Installing Google Chrome..."
wget https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
sudo apt install -y ./google-chrome-stable_current_amd64.deb
rm google-chrome-stable_current_amd64.deb
echo "Google Chrome installed successfully."
sleep 15

# Install Microsoft Edge
echo "Installing Microsoft Edge..."
# Download and validate GPG key
if ! gpg --dry-run --quiet --import-options import-show /usr/share/keyrings/microsoft.gpg 2>/dev/null | grep -q "Microsoft"; then
    curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > microsoft.gpg
    sudo install -o root -g root -m 644 microsoft.gpg /usr/share/keyrings/microsoft.gpg
    rm microsoft.gpg
else
    echo "Microsoft GPG key already exists. Skipping key addition."
fi

# Add repository if not already present
if ! grep -q "https://packages.microsoft.com/repos/edge" /etc/apt/sources.list.d/microsoft-edge-dev.list 2>/dev/null; then
    echo "Adding Microsoft Edge repository..."
    echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/repos/edge stable main" | sudo tee /etc/apt/sources.list.d/microsoft-edge-dev.list > /dev/null
else
    echo "Microsoft Edge repository already exists. Skipping."
fi

# Update and install Microsoft Edge
sudo apt update
sudo apt install -y microsoft-edge-stable
rm microsoft.gpg
echo "Microsoft Edge installed successfully."


# Install Chromium
echo "Installing Chromium..."
sudo snap install chromium
chromium --version
echo "Chromium installed successfully."
sleep 15

# Install Firefox
echo "Installing Firefox..."
sudo snap install firefox
firefox --version
echo "Firefox installed successfully."

# Troubleshooting Elements for Smooth Installation
echo "Adding troubleshooting fixes..."
sudo apt install -y mesa-vulkan-drivers
echo "Ensuring Xvfb setup for Cypress..."
sudo apt install -y xvfb
Xvfb :99 -screen 0 1280x1024x24 &
export DISPLAY=:99
export XDG_RUNTIME_DIR=/tmp/xdg
mkdir -p $XDG_RUNTIME_DIR
chmod 700 $XDG_RUNTIME_DIR


# Cursor size fix
echo "Setting cursor size to avoid truncation..."
echo "Xcursor.size: 24" >> ~/.Xresources
echo "Xcursor.theme: default" >> ~/.Xresources
xrdb ~/.Xresources
export XCURSOR_SIZE=24


# Final Setup for Cypress
sudo -u cypress bash <<EOF
mkdir -p ~/cypress-project/cypress/e2e
touch ~/cypress-project/cypress/e2e/register.cy.js
echo "Download the test spec files from the DEV /admin/cypress repository and place them in ~/cypress-project/cypress/e2e."
EOF



# Create necessary directories for Cypress
echo "Creating Cypress project structure..."
mkdir -p ~/cypress-project/cypress/e2e

# Create the Cypress configuration file
echo "Creating cypress.config.js file..."
cat <<EOF > ~/cypress-project/cypress.config.js
const { defineConfig } = require("cypress");

module.exports = defineConfig({
    e2e: {
        baseUrl: "http://localhost:3000", // Replace with your app URL
        supportFile: false,
        specPattern: "cypress/e2e/**/*.cy.js",
    },
});
EOF

echo "Cypress project structure and configuration file created successfully!"


# Display installed versions for validation
echo "Installed software versions:"
echo "Node.js: $(node -v)"
echo "NPM: $(npm -v)"
echo "Google Chrome: $(google-chrome --version)"
echo "Microsoft Edge: $(microsoft-edge --version)"
echo "Chromium: $(chromium --version)"
echo "Firefox: $(firefox --version)"
sudo -u cypress bash <<EOF
echo "Cypress: $(npx cypress --version)"
EOF


# Instructions for running browsers in headless mode
echo "To run browsers in headless mode:"
echo "Google Chrome: google-chrome --headless --disable-gpu --remote-debugging-port=9222 https://example.com"
echo "Microsoft Edge: microsoft-edge --headless --disable-gpu --remote-debugging-port=9222 https://example.com"
echo "Chromium: chromium-browser --headless --disable-gpu --remote-debugging-port=9222 https://example.com"
echo "Firefox: firefox -headless https://example.com"


# Instructions for running Cypress
echo "To run Cypress, switch to the 'cypress' user using:"
echo "  sudo su - cypress"
echo "Then navigate to the project directory:"
echo "  cd ~/cypress-project"
echo "Run Cypress interactively:"
echo "  npx cypress open"
echo "Or in headless mode:"

echo "  npx cypress run"


echo "Commands to run to start server"
echo "cd ~/cypress-project"
echo "npx cypress open"
echo ""
echo "This will initialize the cypress project and open the cypress dashboard."
echo "you will need to close the dashboard and run the following command to run the tests"
echo "cd ~/cypress-project"
echo "npx cypress open"







echo "Full setup completed successfully!"

