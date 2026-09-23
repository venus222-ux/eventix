FROM node:20-alpine

WORKDIR /app

# Instalăm dependențele o dată — layer-ul se cache-uiește dacă package.json nu se schimbă
COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
