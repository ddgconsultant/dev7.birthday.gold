describe('Birthday.Gold Login Flow', () => {
    beforeEach(() => {
      // Visit the login page before each test
      cy.visit('https://www.birthday.gold/login')
      
      // Clear any existing cookies/local storage to ensure clean state
      cy.clearCookies()
      cy.clearLocalStorage()
    })
  
    it('should complete login flow and navigation', () => {
      // Test credentials - these should be environment variables in practice
      const username = 'test@example.com'
      const password = 'testpassword123'
  
      // Fill in login form
      cy.get('input[type="text"]').type(username)
      cy.get('input[type="password"]').type(password)
  
      // Show password
      cy.get('.password-toggle-icon').click()
      cy.get('input[type="text"]').should('have.value', password)
  
      // Pause after entering credentials
      cy.pause()
  
      // Click login button
      cy.get('button').contains('Log in').click()
  
      // Wait for login to complete and dashboard to load
      cy.url().should('include', '/dashboard')
  
      // Click avatar menu
      cy.get('.avatar-menu').click()
  
      // Navigate to Settings
      cy.contains('Settings').click()
      cy.url().should('include', '/settings')
  
      // Open mega menu and navigate to Select Businesses
      cy.contains('Pick Enrollments').click()
      cy.contains('Select Businesses').click()
      cy.url().should('include', '/businesses')
  
      // Open avatar menu again and logout
      cy.get('.avatar-menu').click()
      cy.contains('Logout').click()
  
      // Verify redirect to login page
      cy.url().should('include', '/login')
    })
  
    afterEach(() => {
      // Clean up after each test
      cy.clearCookies()
      cy.clearLocalStorage()
    })
  })