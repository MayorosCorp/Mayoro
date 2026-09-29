pipeline {
    agent any

    stages {
        stage('Checkout') {
            steps {
                echo 'Obteniendo código del repositorio...'
                checkout scm
            }
        }

        stage('Verificación de Sintaxis PHP') {
            steps {
                echo 'Validando que no haya errores de sintaxis en PHP...'
                // Si Jenkins corre en Windows:
                bat 'for /r %%i in (*.php) do php -l "%%i"'
                // Si Jenkins corre en Linux o Docker, comenta la línea de arriba y usa:
                // sh 'find . -name "*.php" -exec php -l {} \\;'
            }
        }

        stage('Pruebas Unitarias') {
            steps {
                echo 'Ejecutando pruebas...'
                // Si usan PHPUnit:
                // bat './vendor/bin/phpunit'
            }
        }

        stage('Despliegue') {
            steps {
                echo 'Desplegando la aplicación...'
                // Aquí colocamos el script para mover los archivos a XAMPP / htdocs o reiniciar Docker
            }
        }
    }

    post {
        success {
            echo '¡Pipeline ejecutado con éxito!'
        }
        failure {
            echo 'Error en la ejecución del pipeline.'
        }
    }
}