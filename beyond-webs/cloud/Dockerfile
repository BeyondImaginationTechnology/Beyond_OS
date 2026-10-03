FROM node:22-slim
ENV NODE_ENV=production
WORKDIR /app/cloud
COPY cloud/package.json ./package.json
RUN npm install --omit=dev --no-audit --no-fund
COPY cloud/server.mjs ./server.mjs
COPY machine-profiles.json ./machine-profiles.json
USER node
EXPOSE 8080
CMD ["npm", "start"]
