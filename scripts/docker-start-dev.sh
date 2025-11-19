#!/bin/bash

# Start Docker services in the background
echo "Starting Docker services..."
docker-compose up -d

# Wait for services to be ready
echo "Waiting for services to start..."
sleep 5

# Start Vite dev server with hot reload
echo "Starting Vite development server with hot module replacement..."
echo "Access your app at: http://10.47.0.26:8080"
echo "Vite dev server running at: http://10.47.0.26:5173"
echo ""
echo "Press Ctrl+C to stop both Docker and Vite dev server"
echo ""

# Run Vite dev server in the foreground
npm run dev

# When Vite is stopped (Ctrl+C), stop Docker services
echo ""
echo "Stopping Docker services..."
docker-compose down
