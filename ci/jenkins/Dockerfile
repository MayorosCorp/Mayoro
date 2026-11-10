# Jenkins LTS con el toolchain de Mayoro:
#   - PHP 8.3 + Composer  → backend Laravel y tests
#   - Node 22             → build de Vite
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
        php8.4-mbstring \
        php8.4-sqlite3 \
        php8.4-xml \
        php8.4-zip \
        docker.io \
    && rm -rf /var/lib/apt/lists/*

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
