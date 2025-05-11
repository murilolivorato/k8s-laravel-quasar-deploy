FROM node:18-alpine

WORKDIR /app

# Install Quasar CLI globally
RUN npm install -g @quasar/cli

# First copy package files
COPY frontend/package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the frontend code
COPY frontend/ .

# Run quasar prepare
RUN quasar prepare

# Expose port 3000
EXPOSE 3000

# Start the development server with explicit host and port
CMD ["quasar", "dev", "--host", "0.0.0.0", "--port", "3000"] 