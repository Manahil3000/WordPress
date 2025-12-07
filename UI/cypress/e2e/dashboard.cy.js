/// <reference types="cypress" />

describe('WordPress Dashboard Tests', () => {
  const loginUrl = 'http://localhost/WordPress/wp-login.php';
  const username = 'sana123';
  const password = 'ip^x6OHCFrcnEtLD1K';

  beforeEach(() => {
    // Visit login page
    cy.visit(loginUrl);

    // Fill username and password
    cy.get('#user_login').clear().type(username);
    cy.get('#user_pass').clear().type(password);

    // Click login
    cy.get('#wp-submit').click();

    // Confirm we are on dashboard
    cy.url().should('include', 'wp-admin');
  });

  // ---------------- Quick Draft Box Test Data ----------------
  const quickDraftData = [
    { testCase: 'TC-QD-01: Valid Draft', title: 'Hello', content: 'My post content', expectedOutcome: 'success' },
    { testCase: 'TC-QD-02: Title Empty', title: '', content: 'Valid content', expectedOutcome: 'success' },
    { testCase: 'TC-QD-03: Long Title', title: 'AB'.repeat(201), content: 'Valid content', expectedOutcome: 'success' },
    { testCase: 'TC-QD-04: Long Content', title: 'Title', content: 'AB'.repeat(201), expectedOutcome: 'success' },
    { testCase: 'TC-QD-05: Content Empty', title: 'Valid title', content: '', expectedOutcome: 'success' }
  ];

quickDraftData.forEach((data, index) => {
  it(data.testCase, () => {
    // Fill Quick Draft fields
    if (data.title === '') cy.get('#title').clear();
    else cy.get('#title').clear().type(data.title);

    if (data.content === '') cy.get('#content').clear();
    else cy.get('#content').clear().type(data.content);

    cy.get('#save-post').click();

    // Assertions
    if (data.expectedOutcome === 'success') {
      const titleText = data.title === '' ? '(no title)' : data.title;

      // Assert latest draft title and time
      cy.get('.draft-title').first().within(() => {
        cy.contains(titleText).should('exist');
        cy.get('time').should('exist');
      });

      // Assert content only if not empty
      if (data.content !== '') {
        cy.get('.draft-title').first().next('p')
          .should('contain.text', data.content);
      } else {
        cy.get('.draft-title').first().next('p').should('not.exist');
      }
    }

    // Take screenshot
    cy.screenshot(`QuickDraft_${index + 1}`);
  }); // closes it()
}); // closes forEach()



  // ---------------- WordPress Events and News Box Test Data ----------------
  const eventsNewsData = [
    { testCase: 'TC-WEN-01: Valid City Input', city: 'Karachi', action: 'submit' },
    { testCase: 'TC-WEN-02: City Empty (Mutation)', city: '', action: 'submit' },
    { testCase: 'TC-WEN-03: City does not exist', city: 'Kar4chi' , action: 'submit' },
    { testCase: 'TC-WEN-04: Cancel Button Clears Input', city: 'Test City', action: 'cancel' }
  ];
eventsNewsData.forEach((data, index) => {
  it(data.testCase, () => {
    // Fill city input (force because hidden)
    if (data.city === '') {
      cy.get('#community-events-location').clear({ force: true });
    } else {
      cy.get('#community-events-location').clear({ force: true }).type(data.city, { force: true });
    }

    if (data.action === 'submit') {
      cy.get('#community-events-submit').click({ force: true });

      if (data.city === '') {
        // Empty city: no results should appear
        cy.get('li.event-none').should('not.exist');
      } else {
        // Any non-empty city (valid or invalid) shows the generic message
        cy.get('li.event-none')
          .should('be.visible')
          .and('contain.text', `There are no events scheduled near ${data.city}`);
      }

    } else if (data.action === 'cancel') {
      // Cancel button resets input
      cy.get('button.community-events-cancel').click({ force: true });
      cy.get('span.community-events-location-edit')
        .should('be.visible')
        .and('have.text', 'Select location');
    }

    // Auto screenshot
    cy.screenshot(`EventsNews_${index + 1}`);
  });
});



  afterEach(() => {
    cy.log('Test completed');
  });
});
