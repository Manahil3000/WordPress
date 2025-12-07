/// <reference types="cypress" />

describe('WordPress Add New User Tests', () => {
  const loginUrl = 'http://localhost/WordPress/wp-login.php';
  const username = 'sana123';
  const password = 'ip^x6OHCFrcnEtLD1K';

  beforeEach(() => {
    // Visit login page
    cy.visit(loginUrl);

    // Fill login credentials
    cy.get('#user_login').clear().type(username);
    cy.get('#user_pass').clear().type(password);
    cy.get('#wp-submit').click();

    // Wait for successful login
    cy.url({ timeout: 15000 }).should('include', 'wp-admin');

    // Open Add New User page
    cy.visit('http://localhost/WordPress/wp-admin/user-new.php');
    cy.url().should('include', 'user-new.php');
  });

 const newUserData = [
  // Valid user
  {
    testCase: 'Valid User - All fields normal',
    user: 'user01',
    email: 'user01@example.com',
    firstName: 'John',
    lastName: 'Doe',
    website: 'https://example.com',
    password: 'StrongP@ss1',
    confirmWeak: true,
    sendEmail: true,
    role: 'Subscriber'
  },
  // Missing username
  {
    testCase: 'Missing Username',
    user: '',
    email: 'user02@example.com',
    firstName: 'Jane',
    lastName: 'Doe',
    website: '',
    password: 'StrongP@ss2',
    confirmWeak: false,
    sendEmail: true,
    role: 'Subscriber'
  },
  // Weak password, checkbox ticked
  {
    testCase: 'Weak Password with Confirmation',
    user: 'user03',
    email: 'user03@example.com',
    firstName: '',
    lastName: '',
    website: '',
    password: '123',
    confirmWeak: true,
    sendEmail: false,
    role: 'Subscriber'
  },
  // Email without @
  {
    testCase: 'Invalid Email - missing @',
    user: 'user04',
    email: 'user04example.com',
    firstName: 'Tom',
    lastName: 'Smith',
    website: '',
    password: 'StrongP@ss4',
    confirmWeak: false,
    sendEmail: false,
    role: 'Subscriber'
  },
  // Maximum username length (assuming 60 chars)
  {
    testCase: 'Maximum Username Length',
    user: 'u'.repeat(60),
    email: 'user05@example.com',
    firstName: '',
    lastName: '',
    website: '',
    password: 'StrongP@ss5',
    confirmWeak: false,
    sendEmail: false,
    role: 'Subscriber'
  },
  // Minimum password length
  {
    testCase: 'Minimum Password Length',
    user: 'user06',
    email: 'user06@example.com',
    firstName: '',
    lastName: '',
    website: '',
    password: '1',
    confirmWeak: true,
    sendEmail: false,
    role: 'Subscriber'
  }
];


  newUserData.forEach((data, index) => {
    it(data.testCase, () => {
      // Username
      if (data.user) cy.get('#user_login').clear().type(data.user);
      else cy.get('#user_login').clear();

      // Email
      cy.get('#email').clear().type(data.email);

      // First Name
      if (data.firstName) cy.get('#first_name').clear().type(data.firstName);
      else cy.get('#first_name').clear();

      // Last Name
      if (data.lastName) cy.get('#last_name').clear().type(data.lastName);
      else cy.get('#last_name').clear();

      // Website
      if (data.website) cy.get('#url').clear().type(data.website);
      else cy.get('#url').clear();

      // Password
      cy.get('#pass1').clear().type(data.password, { force: true });

      // Confirm weak password
      if (data.confirmWeak) {
        cy.get('input[name="pw_weak"]').then($checkbox => {
          if (!$checkbox.is(':checked')) {
            cy.wrap($checkbox).check({ force: true });
          }
        });
      } else {
        cy.get('input[name="pw_weak"]').then($checkbox => {
          if ($checkbox.is(':checked')) {
            cy.wrap($checkbox).uncheck({ force: true });
          }
        });
      }

      // Send User Notification
      if (data.sendEmail) cy.get('#send_user_notification').check({ force: true });
      else cy.get('#send_user_notification').uncheck({ force: true });

      // Select Role
      cy.get('#role').select(data.role);

      // Outcome validation
      if (!data.user) {
        // Attempt to click Add User button
        cy.get('#createusersub').click({ force: true });

        // Assert page did NOT redirect
        cy.url().should('include', 'user-new.php');

        // Screenshot
        cy.screenshot(`AddUser_Failed_${index + 1}`);
      } else {
        // Successful case
        cy.get('#createusersub', { timeout: 10000 })
          .should('not.be.disabled')
          .click();

        // Assert redirect
        cy.url().should('include', 'wp-admin/user-new.php');

        // Match the user ID in URL if present
        cy.url().then((url) => {
          const match = url.match(/id=\d+$/);
          if (match) cy.log(`User created with ID: ${match[0]}`);
        });

     
        // Screenshot
        cy.screenshot(`AddUser_Success_${index + 1}`);
      }
    });
  });

  afterEach(() => {
    cy.log('Test completed');
  });

}); // closes describe
