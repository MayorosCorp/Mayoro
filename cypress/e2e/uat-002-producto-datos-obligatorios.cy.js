/**
 * UAT-002 — Rechazo de producto con datos obligatorios incompletos.
 *
 * Usuario QA: Carlos Eduardo Loaiza Martinez
 * Criterio: el backend bloquea el alta cuando faltan los datos obligatorios.
 */

const EMPRESARIO = {
    email: 'ventas@distribuidora-andina.pe',
    password: 'password',
};

describe('UAT-002 — Datos obligatorios del producto', () => {
    it('muestra errores visibles y no registra un producto con el formulario vacío', () => {
        // Precondición: Empresario autenticado.
        cy.loginAs(EMPRESARIO.email, EMPRESARIO.password);
        cy.visit('/products/create');

        // Paso 1 y 2: abrir el alta y dejar vacíos los campos obligatorios.
        // Se desactiva solo la validación HTML nativa para alcanzar la validación
        // Laravel del servidor, que es el criterio que UAT-002 debe comprobar.
        cy.get('[data-testid="producto-form"]').invoke('attr', 'novalidate', 'novalidate');

        // Paso 3: pulsar publicar.
        cy.get('[data-testid="producto-publicar"]').click();

        // Resultado esperado: no hay redirección exitosa al catálogo.
        cy.location('pathname').should('eq', '/products/create');

        // El formulario vuelve con errores visibles del servidor.
        cy.get('.error-list')
            .should('have.length.at.least', 1)
            .first()
            .should('be.visible')
            .and('contain.text', 'The nombre field is required');

        // Se comprueban los campos exigidos por UAT-002.
        cy.get('.error-list').first().within(() => {
            cy.contains('The nombre field is required').should('be.visible');
            cy.contains('The categoria field is required').should('be.visible');
            cy.contains('The presentacion field is required').should('be.visible');
            cy.contains('The precio bulto field is required').should('be.visible');
        });

        // MOQ y stock tienen valores iniciales válidos (1 y 0), por lo que no
        // están vacíos en este escenario; sus valores inválidos se cubren en
        // las pruebas UAT-003/UAT-004.
        cy.get('#moq_cantidad_minima').should('have.value', '1');
        cy.get('#stock_disponible').should('have.value', '0');
        cy.get('[data-testid="producto-form"]').should('be.visible');
    });
});
