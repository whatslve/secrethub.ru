# Stage сборки
FROM node:20-bullseye AS build
WORKDIR /app

# Копируем package.json и package-lock.json из src
COPY src/package*.json ./

# Копируем весь src
COPY src/ ./

# Устанавливаем зависимости
RUN npm install

# Билдим проект
RUN npm run build

# Stage nginx
FROM nginx:alpine
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
