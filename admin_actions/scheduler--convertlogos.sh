#!/bin/bash
# Logo Converter Scheduler Script
# Processes any logos placed in the CONVERT folder

CONVERT_DIR="/mnt/w/BIRTHDAY_SERVER/dev7.birthday.gold/public/assets/img/logos/CONVERT"
LOG_FILE="$CONVERT_DIR/conversion.log"
VENV_DIR="/tmp/logo_converter_env"

# Add timestamp to log
echo "=======================================" >> "$LOG_FILE"
echo "Running logo converter: $(date)" >> "$LOG_FILE"
echo "=======================================" >> "$LOG_FILE"

# Change to convert directory
cd "$CONVERT_DIR"

# Setup virtual environment if it doesn't exist
if [ ! -d "$VENV_DIR" ]; then
    echo "Creating virtual environment..." >> "$LOG_FILE"
    python3 -m venv "$VENV_DIR" >> "$LOG_FILE" 2>&1
    "$VENV_DIR/bin/pip" install Pillow >> "$LOG_FILE" 2>&1
fi

# Run the Python converter with virtual environment
"$VENV_DIR/bin/python" convert_logos.py >> "$LOG_FILE" 2>&1

# Add completion timestamp
echo "Completed: $(date)" >> "$LOG_FILE"
echo "" >> "$LOG_FILE"