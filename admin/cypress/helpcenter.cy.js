describe('Birthday.Gold Help Center Tests', () => {
    beforeEach(() => {
      // Visit the main site
      cy.visit('https://birthday.gold')
    })
  
    it('should test live chat, help center navigation and newsletter signup', () => {
      // Find and open live chat widget
      cy.get('[data-testid="live-chat-widget"]')
        .should('be.visible')
        .click()
  
      // Wait for chat to initialize
      cy.get('[data-testid="chat-window"]')
        .should('be.visible')
  
      // Type initial message
      cy.get('[data-testid="chat-input"]')
        .type('Hello, I need help{enter}')
  
      // Wait for response
      cy.get('[data-testid="agent-message"]')
        .should('be.visible')
        .should('not.be.empty')
  
      // Send follow-up message
      cy.get('[data-testid="chat-input"]')
        .type('Thank you for the information{enter}')
  
      // Close chat
      cy.get('[data-testid="close-chat"]')
        .click()
  
      // Go to help center from footer
      cy.get('footer')
        .contains('Help')
        .click()
  
      // Verify we're on the help center page
      cy.url().should('include', '/help')
  
      // Array of help center boxes to click through
      const helpBoxes = ['box1', 'box2', 'box3']
  
      // Click through each help box
      helpBoxes.forEach((boxId) => {
        cy.get(`[data-testid="${boxId}"]`)
          .click()
  
        // Wait on page
        cy.wait(12000) // 12 seconds wait
  
        // Verify page loaded
        cy.get('body').should('be.visible')
  
        // Go back to help center
        cy.go('back')
  
        // Verify back on help center
        cy.url().should('include', '/help')
      })
  
      // Find and click newsletter signup
      cy.get('[data-testid="newsletter-signup"]')
        .click()
  
      // Generate random email
      const randomString = Math.random().toString(36).substring(7)
      const testEmail = `${randomString}@bdtest.xyz`
  
      // Enter email and submit
      cy.get('[data-testid="newsletter-email"]')
        .type(testEmail)
      
      cy.get('[data-testid="newsletter-submit"]')
        .click()
  
      // Verify success message
      cy.get('[data-testid="signup-success"]')
        .should('be.visible')
        .should('contain', 'Thank you for subscribing')
    })
  })