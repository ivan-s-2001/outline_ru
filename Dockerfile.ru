ARG APP_PATH=/opt/outline

FROM node:26.3.0 AS build

ARG APP_PATH
ARG RUSSIAN_TRANSLATION_URL
WORKDIR ${APP_PATH}

ENV NODE_OPTIONS=--max-old-space-size=24000

COPY package.json yarn.lock .yarnrc.yml ./
COPY patches ./patches

RUN npm install --global corepack && \
    corepack enable && \
    yarn install --immutable --network-timeout 1000000 && \
    yarn cache clean

COPY . .

RUN RUSSIAN_TRANSLATION_URL="${RUSSIAN_TRANSLATION_URL}" \
    node ./scripts/sync-russian-translation.mjs && \
    yarn build && \
    yarn workspaces focus --production

FROM node:26.3.0-slim AS release

ARG APP_PATH
WORKDIR ${APP_PATH}

LABEL org.opencontainers.image.source="https://github.com/ivan-s-2001/outline_ru"
LABEL org.opencontainers.image.description="Outline 1.9.1 с русской локализацией"

ENV NODE_ENV=production
ENV PORT=3000
ENV MALLOC_ARENA_MAX=2
ENV FILE_STORAGE_LOCAL_ROOT_DIR=/var/lib/outline/data

RUN addgroup --gid 1001 nodejs && \
    adduser --uid 1001 --ingroup nodejs nodejs && \
    mkdir -p "${FILE_STORAGE_LOCAL_ROOT_DIR}" && \
    chown -R nodejs:nodejs /var/lib/outline "${APP_PATH}" && \
    chmod 1777 "${FILE_STORAGE_LOCAL_ROOT_DIR}" && \
    apt-get update && \
    apt-get install -y --no-install-recommends curl && \
    rm -rf /var/lib/apt/lists/*

COPY --chown=nodejs:nodejs --from=build ${APP_PATH}/node_modules ./node_modules
COPY --chown=nodejs:nodejs --from=build ${APP_PATH}/build ./build
COPY --chown=nodejs:nodejs --from=build ${APP_PATH}/server ./server
COPY --chown=nodejs:nodejs --from=build ${APP_PATH}/public ./public
COPY --chown=nodejs:nodejs --from=build ${APP_PATH}/.sequelizerc ./.sequelizerc
COPY --chown=nodejs:nodejs --from=build ${APP_PATH}/package.json ./package.json

VOLUME ["/var/lib/outline/data"]
USER nodejs
EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
  CMD curl --fail --silent "http://localhost:${PORT}/_health" | grep --quiet OK || exit 1

CMD ["node", "build/server/index.js"]
