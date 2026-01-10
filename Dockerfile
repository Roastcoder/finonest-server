FROM node:18-alpine
RUN apk add --no-cache python3 make g++ vips-dev
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm install -g tsx
EXPOSE 4000
CMD ["tsx", "src/server.ts"]