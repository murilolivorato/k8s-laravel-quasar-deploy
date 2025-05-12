FROM node:18-alpine

WORKDIR /app

# Install Quasar CLI globally
RUN npm install -g @quasar/cli

# First copy package files
COPY frontend/package*.json ./

# Install dependencies with specific chart.js version
RUN npm install chart.js

# Copy the rest of the frontend code
COPY frontend/ .

# Now run quasar prepare manually
RUN quasar prepare

# Expose port 8080
EXPOSE 8080

# Start the development server with explicit host and port
CMD ["quasar", "dev", "--host", "0.0.0.0", "--port", "8080"] 