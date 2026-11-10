/*
 * Pipeline de integración continua de Mayoro (Laravel 12 + Vite).
 *
 * Corre sobre el Jenkins local (contenedor Docker, ver ci/jenkins/).
 * Etapas:
 *   1. Dependencias de PHP (Composer)
 *   2. Frontend (Vite build)
 *   3. Calidad de código (Laravel Pint)
 *   4. Tests (PHPUnit, SQLite en memoria, reporte JUnit)
 *   5. Imagen Docker de la aplicación
 *   6. Despliegue local (contenedor mayoro-app en http://localhost:8000)
 *   7. Verificación de salud
 *
 * Ramas (Gitflow): este job sigue la rama develop (integración continua).
 */
pipeline {
    agent any

    options {
        disableConcurrentBuilds()
        buildDiscarder(logRotator(numToKeepStr: '20'))
    }

    // Sondeo de GitHub cada ~5 min (el webhook requeriría exponer Jenkins a Internet).
    triggers {
        pollSCM('H/5 * * * *')
    }

    environment {
        COMPOSER_ALLOW_SUPERUSER = '1'
        APP_IMAGE                = 'mayoro/app'
        NETWORK                  = 'mayoro_default'
        DB_VOLUME                = 'mayoro_storage'
        DEPLOY_ENV               = "${JENKINS_HOME}/mayoro-deploy.env"
    }

    stages {

        stage('Dependencias (Composer)') {
            steps {
                sh 'composer install --no-interaction --prefer-dist --optimize-autoloader'
            }
        }

        stage('Frontend (Vite)') {
            steps {
                sh 'npm ci'
                sh 'npm run build'
            }
        }

        stage('Calidad de código (Pint)') {
            steps {
                sh 'vendor/bin/pint --test'
            }
        }

        stage('Tests (PHPUnit)') {
            steps {
                sh 'mkdir -p reports'
                sh 'vendor/bin/phpunit --log-junit reports/junit.xml'
            }
        }

        stage('Imagen Docker') {
            steps {
                sh 'docker build -t "$APP_IMAGE:$BUILD_NUMBER" -t "$APP_IMAGE:latest" -f ci/app/Dockerfile .'
            }
        }

        stage('Despliegue local') {
            steps {
                sh '''
                    # La APP_KEY se genera una sola vez y se conserva en jenkins_home:
                    # así las sesiones no se invalidan entre despliegues.
                    if [ ! -f "$DEPLOY_ENV" ]; then
                        echo "APP_KEY=$(php artisan key:generate --show)" > "$DEPLOY_ENV"
                    fi

                    # Red compartida con Jenkins (resolucion de nombres entre contenedores)
                    docker network create "$NETWORK" 2>/dev/null || true

                    # Despliegue simple: fuera el contenedor viejo, dentro el nuevo
                    docker rm -f mayoro-app 2>/dev/null || true

                    docker run -d \
                        --name mayoro-app \
                        --restart unless-stopped \
                        --network "$NETWORK" \
                        -p 8000:8080 \
                        -v "$DB_VOLUME":/var/www/html/storage \
                        --env-file "$DEPLOY_ENV" \
                        -e APP_NAME=Mayoro \
                        -e APP_ENV=production \
                        -e APP_DEBUG=false \
                        -e APP_URL=http://localhost:8000 \
                        -e DB_CONNECTION=sqlite \
                        -e DB_DATABASE=/var/www/html/storage/database.sqlite \
                        -e SESSION_DRIVER=file \
                        -e QUEUE_CONNECTION=database \
                        -e CACHE_STORE=file \
                        "$APP_IMAGE:$BUILD_NUMBER"

                    echo "Contenedor mayoro-app desplegado desde $APP_IMAGE:$BUILD_NUMBER"
                '''
            }
        }

        stage('Verificación de salud') {
            steps {
                sh '''
                    curl -fsS --retry 5 --retry-delay 3 --retry-connrefused \
                        http://mayoro-app:8080/ > /dev/null \
                        && echo "Mayoro responde HTTP 200 en http://localhost:8000"
                '''
            }
        }
    }

    post {
        always {
            junit allowEmptyResults: true, testResults: 'reports/junit.xml'
        }
        success {
            echo "OK: build #$BUILD_NUMBER desplegado en http://localhost:8000"
        }
        failure {
            echo "FALLO: revisa el log de la etapa marcada en rojo (Console Output)."
        }
    }
}
