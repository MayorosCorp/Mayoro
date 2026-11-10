# Jenkins LTS con el toolchain de Mayoro:
#   - PHP 8.4 + Composer  → backend Laravel y tests
#   - Node 22             → build de Vite y Cypress
#   - Cypress + Xvfb      → tests E2E en modo headless
#   - Docker CLI          → construir la imagen y desplegar la app
#     (habla con el daemon Docker del host vía /var/run/docker.sock,
#      patrón "docker brotherhood": lanza contenedores hermanos)
FROM jenkins/jenkins:lts-jdk21

USER root

# PHP 8.4 con las extensiones que exige Laravel 12 + Docker CLI
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        unzip \
        xz-utils \
        php8.4-cli \
        php8.4-curl \
        php8.4-gd \
        php8.4-mbstring \
        php8.4-sqlite3 \
        php8.4-xml \
        php8.4-zip \
    && rm -rf /var/lib/apt/lists/*

# Dependencias de sistema de Cypress/Electron + Xvfb para correr E2E sin pantalla.
# Debian 13 (trixie) renombra varias librerías con el sufijo t64.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        fonts-liberation \
        libasound2t64 \
        libatk-bridge2.0-0t64 \
        libatspi2.0-0t64 \
        libcups2t64 \
        libdrm2 \
        libgbm1 \
        libgtk-3-0t64 \
        libnotify4 \
        libnss3 \
        libx11-xcb1 \
        libxcb-dri3-0 \
        libxcomposite1 \
        libxdamage1 \
        libxkbcommon0 \
        libxrandr2 \
        libxshmfence1 \
        libxss1 \
        libxtst6 \
        xauth \
        xvfb \
    && rm -rf /var/lib/apt/lists/*

# Docker CLI (solo cliente: habla con el daemon del host por el socket)
COPY --from=docker:cli /usr/local/bin/docker /usr/local/bin/docker

# Node.js 22 LTS (Vite 7 requiere Node >= 20.19)
ARG NODE_VERSION=22.14.0
RUN curl -fsSL "https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-x64.tar.xz" \
        | tar -xJ --strip-components=1 -C /usr/local

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Plugins de Jenkins: Git, Pipeline (workflow) y JUnit
RUN jenkins-plugin-cli --plugins git workflow-aggregator junit

# Saltar el asistente de primera ejecución (setup headless):
# con este marcador Jenkins arranca listo para usar la API REST.
RUN echo "$JENKINS_VERSION" > /usr/share/jenkins/ref/jenkins.install.InstallUtil.lastExecVersion

USER jenkins
