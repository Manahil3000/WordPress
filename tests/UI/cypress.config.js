const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: 'http://localhost/wordpress', // your local WordPress URL
    specPattern: 'cypress/e2e/**/*.cy.js', // where your tests will live
    supportFile: false,
  },
});
