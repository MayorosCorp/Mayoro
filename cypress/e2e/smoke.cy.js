/**
 * Smoke E2E de Mayoro: recorre las páginas públicas y el flujo de acceso.
 * Los datos demo (catálogo y distribuidores) los siembra el contenedor al arrancar.
 */

const DEMO_EMPRESARIO = {
    email: 'ventas@distribuidora-andina.pe',
    password: 'password',
};

describe('Páginas públicas', () => {
    it('muestra la portada con su propuesta de valor', () => {
        cy.visit('/');

        cy.title().should('contain', 'Mayoro');
        cy.get('h1').should('contain.text', 'Conecta tu negocio');
        cy.contains('nav a', 'Ingresar').should('be.visible');
    });

    it('lista el catálogo mayorista con filtros y productos', () => {
        cy.visit('/products');

        cy.get('h1').should('contain.text', 'Catálogo mayorista');
        cy.get('[data-testid="catalogo-filtros"]').should('be.visible');
        cy.get('[data-testid="producto-card"]').should('have.length.at.least', 1);
    });

    it('lista el directorio de distribuidores', () => {
        cy.visit('/proveedores');

        cy.get('h1').should('contain.text', 'Distribuidores mayoristas');
        cy.get('[data-testid="distribuidor-card"]').should('have.length.at.least', 1);
    });
});

describe('Autenticación', () => {
    it('redirige al login si se intenta entrar al dashboard sin sesión', () => {
        cy.visit('/dashboard');

        cy.location('pathname').should('eq', '/login');
    });

    it('permite iniciar sesión con la cuenta demo del Empresario', () => {
        cy.visit('/login');

        cy.get('[data-testid="login-form"]').within(() => {
            cy.get('#email').type(DEMO_EMPRESARIO.email);
            cy.get('#password').type(DEMO_EMPRESARIO.password);
            cy.get('button[type="submit"]').click();
        });

        cy.location('pathname').should('eq', '/dashboard');
        cy.get('h1').should('contain.text', 'Dashboard');
        cy.contains('button', 'Cerrar sesión').should('be.visible');
    });

    it('muestra un error con credenciales inválidas', () => {
        cy.visit('/login');

        cy.get('[data-testid="login-form"]').within(() => {
            cy.get('#email').type(DEMO_EMPRESARIO.email);
            cy.get('#password').type('clave-incorrecta');
            cy.get('button[type="submit"]').click();
        });

        cy.location('pathname').should('eq', '/login');
        cy.get('[role="alert"]').should('contain.text', 'No pudimos iniciar sesión');
    });
});

describe('Sesión autenticada', () => {
    beforeEach(() => {
        cy.loginAs(DEMO_EMPRESARIO.email, DEMO_EMPRESARIO.password);
    });

    it('mantiene la sesión y muestra el catálogo con acciones de Empresario', () => {
        cy.visit('/dashboard');

        cy.get('h1').should('contain.text', 'Dashboard');
        cy.visit('/products');

        cy.get('[data-testid="catalogo-publicar"]').should('be.visible');
    });
});
