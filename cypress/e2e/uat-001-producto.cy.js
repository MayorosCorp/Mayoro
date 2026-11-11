/**
 * UAT-001 — Registro del producto.
 *
 * Usuario QA: Carlos Eduardo Loaiza Martinez
 * Criterio: un Empresario registra un producto válido y lo visualiza en el
 * catálogo público sin romper las reglas de negocio.
 */

const EMPRESARIO = {
    email: 'ventas@distribuidora-andina.pe',
    password: 'password',
};

describe('UAT-001 — Registro del producto', () => {
    it('registra un producto como Empresario y lo muestra en el catálogo', () => {
        const producto = `UAT-001 Producto ${Date.now()}`;

        // Precondición: el usuario QA inicia sesión correctamente.
        cy.loginAs(EMPRESARIO.email, EMPRESARIO.password);
        cy.visit('/products/create');

        // Paso 1: registro de datos de entrada.
        cy.get('[data-testid="producto-form"]').within(() => {
            cy.get('#nombre').type(producto);
            cy.get('#categoria').select('Abarrotes');
            cy.get('#presentacion').type('Caja');
            cy.get('#unidades_por_bulto').clear().type('12');
            cy.get('#precio_bulto').type('120.00');
            cy.get('#moq_cantidad_minima').clear().type('5');
            cy.get('#stock_disponible').clear().type('240');
            cy.get('#imagen').selectFile('cypress/fixtures/producto-uat-001.png');

            cy.get('#imagen').should('have.value', 'C:\\fakepath\\producto-uat-001.png');
        });

        // Paso 2: guardar datos.
        cy.get('[data-testid="producto-publicar"]').click();

        // El alta válida conserva la regla de negocio y redirige al catálogo.
        cy.location('pathname').should('eq', '/products');
        cy.get('[role="status"]')
            .should('be.visible')
            .and('contain.text', 'Producto publicado exitosamente');

        // Paso 3: verificación en el catálogo.
        cy.get('[data-testid="catalogo-filtros"]').within(() => {
            cy.get('#q').type(producto);
            cy.get('button[type="submit"]').click();
        });

        cy.get('[data-testid="producto-card"]')
            .should('have.length', 1)
            .within(() => {
                cy.get('img')
                    .should('be.visible')
                    .and('have.attr', 'alt', producto);
                cy.contains(producto).should('be.visible');
                cy.contains('Abarrotes').should('be.visible');
                cy.contains('S/ 120.00').should('be.visible');
                cy.contains('MOQ: 5 bultos').should('be.visible');
                cy.contains('Disponible: 240 bultos').should('be.visible');
            });
    });
});
