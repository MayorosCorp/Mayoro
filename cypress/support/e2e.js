// Comandos compartidos por los tests E2E de Mayoro.
// Los selectores usan `data-testid` para no acoplarse a las clases de Tailwind.

/**
 * Inicia sesión con una cuenta existente y valida que llegó al dashboard.
 * Usa `cy.session` para reutilizar la cookie entre tests del mismo spec.
 */
Cypress.Commands.add('loginAs', (email, password) => {
    cy.session([email, password], () => {
        cy.visit('/login');
        cy.get('[data-testid="login-form"]').within(() => {
            cy.get('#email').type(email);
            cy.get('#password').type(password, { log: false });
            cy.get('button[type="submit"]').click();
        });

        cy.location('pathname').should('eq', '/dashboard');
    });
});
