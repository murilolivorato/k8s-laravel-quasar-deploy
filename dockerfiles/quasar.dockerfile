# Build stage
FROM node:18-alpine as build-stage

WORKDIR /app

# Install Quasar CLI globally
RUN npm install -g @quasar/cli

# First, copy only package files
COPY frontend/package*.json ./

# Temporarily disable postinstall script
RUN npm pkg delete scripts.postinstall

# Install dependencies without running postinstall
RUN npm install --ignore-scripts

# Now copy the entire frontend directory
COPY frontend/ .

# Re-enable postinstall script by copying it back
COPY frontend/package.json ./package.json

# Run quasar prepare manually
RUN quasar prepare

# Set environment to production
ENV NODE_ENV=production

# Build the app
RUN quasar build

# Production stage
FROM nginx:stable-alpine as production-stage

# Copy built assets from build-stage
COPY --from=build-stage /app/dist/spa /var/www/frontend/dist/spa

# Copy nginx configuration
COPY nginx/frontend.conf /etc/nginx/conf.d/default.conf

EXPOSE 3000

CMD ["nginx", "-g", "daemon off;"] 