FROM node:21
WORKDIR /var/www/html
COPY backend/package.json  .
RUN npm install
# Add your source files
COPY . .
CMD npm run dev