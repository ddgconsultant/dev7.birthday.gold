#!/bin/bash
# Simple wrapper to run the logo converter

# Try to find Python
PYTHON_CMD=""
for cmd in python3 python py; do
    if command -v $cmd >/dev/null 2>&1; then
        PYTHON_CMD=$cmd
        echo "Found Python: $cmd"
        break
    fi
done

if [ -z "$PYTHON_CMD" ]; then
    echo "Error: Python not found. Please install Python."
    exit 1
fi

# Setup temporary virtual environment if needed
VENV_DIR="/tmp/logo_converter_env"

if [ ! -d "$VENV_DIR" ]; then
    echo "Setting up Python environment..."
    $PYTHON_CMD -m venv "$VENV_DIR"
    
    # Handle different OS paths
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
        "$VENV_DIR/Scripts/pip" install Pillow cairosvg
        PYTHON_VENV="$VENV_DIR/Scripts/python"
    else
        "$VENV_DIR/bin/pip" install Pillow cairosvg
        PYTHON_VENV="$VENV_DIR/bin/python"
    fi
else
    # Use existing venv
    if [[ "$OSTYPE" == "msys" || "$OSTYPE" == "win32" ]]; then
        PYTHON_VENV="$VENV_DIR/Scripts/python"
    else
        PYTHON_VENV="$VENV_DIR/bin/python"
    fi
fi

# Run the converter
$PYTHON_VENV convert_logos.py