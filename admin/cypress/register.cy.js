Cypress.on('uncaught:exception', (err, runnable) => {
    return false; // Ignore uncaught exceptions
  });
  
  
  // Utility function to generate random number
  const getRandomNum = () => Math.floor(Math.random() * 10000);
  
  // Test data generator
  const generateTestData = () => {
    const randomNum = getRandomNum();
    return {
      firstName: `John${randomNum}`,
      lastName: `Doe${randomNum}`,
      email: `john.doe${randomNum}@bdtest.xyz`
    };
  };
  
  
  describe('Signup and Register Flow', () => {
  
    beforeEach(() => {
      // Set viewport to 1920x1080 (1080p)
      cy.viewport(1320, 1500)
      
      // Handle uncaught exceptions...
      Cypress.on('uncaught:exception', (err, runnable) => {
        return false;
      });
    });
  
  
    it('Should successfully sign up for a Gold Plan and register a new user', () => {
      // Step 1: Visit the signup-route page
      cy.visit('https://dev.birthday.gold/signup-route');
  
      // Step 2: Click "Yes! Sign Me Up" button
      // Wait for the "Yes! Sign Me Up!" button to exist
      cy.get('button').contains('Yes! Sign Me Up!').should('be.visible').click();
  
      // Step 3: Verify navigation to /register
      cy.url().should('include', '/register');
  
  
      // Generate random test data
      const testData = generateTestData();
  
      // Step 4: Fill out the registration form
      cy.get('input[name="first_name"]').type(testData.firstName);
      cy.get('input[name="last_name"]').type(testData.lastName);
      cy.get('input[name="accountemail"]').type(testData.email);
      cy.get('input[name="password"]').type('securepassword123!'); // Password
  
      // Step 5: Enter the Date of Birth
      cy.get('[data-cy="birthday-input"]').type('1990-01-21');
  
      // Step 6: Check the Terms and Conditions box
      cy.get('input[type="checkbox"]').check();
  
      // Step 7: Click the "Next" button
      cy.get('button').contains('Next').click();
  
      // Step 8: Verify successful registration
      // Replace this with actual verification logic (e.g., URL, page content)
      cy.url().should('include', '/validate');
      cy.contains('Validate Your Email').should('be.visible');
    });
  });
  
  