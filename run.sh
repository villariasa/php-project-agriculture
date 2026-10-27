#!/bin/bash
# run.sh - Starts the local PHP development server
cd "$(dirname "$0")"

PORT=8000
echo "=========================================================="
echo " 🌾 Agriculture Information System (AIS)"
echo " Server starting at: http://localhost:$PORT"
echo " Press Ctrl + C to stop the server."
echo "=========================================================="
php -S localhost:$PORT
