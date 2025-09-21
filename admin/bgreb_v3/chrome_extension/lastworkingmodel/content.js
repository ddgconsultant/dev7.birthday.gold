//================================================================================================
//================================================================================================
// Global Variables
let REGISTRATIONLIST = [];
let dataToStore;
let userName;
let registrationList = [];
let adminDetails = {};
let userDetails = {};
let fieldMapping = {};
let manifestData = chrome.runtime.getManifest();
let version = manifestData.version;
let div = null;
let dropdownOptions = ''; // Define this at the top if it's used globally
// Log the start timestamp for the extension
const startTimestamp = new Date().toISOString();
console.log(`BGREB||Extension content script started at: ${startTimestamp}`);


//================================================================================================
//================================================================================================
//================================================================================================
// Functions
//================================================================================================
// Function to toggle extension bar view
function handleFlashAnimation(element, duration = 500) {
  element.classList.add('flash');
  setTimeout(() => element.classList.remove('flash'), duration);
}


//================================================================================================
// Function to show spinner-overlay
function showSpinner() {
  document.getElementById("bgrab-spinner-overlay").style.display = "flex";
}


//================================================================================================
// Function to hide spinner-overlay
function hideSpinner() {
  document.getElementById("bgrab-spinner-overlay").style.display = "none";
}


//================================================================================================
// Function to simulate typing in an input field
async function simulateTyping(element, valuein) {
  const value = sanitizeSelector(valuein);

  for (let i = 0; i <= value.length; i++) {
    await new Promise(resolve => {
      setTimeout(() => {
        element.value = value.slice(0, i);
        const inputEvent = new Event('input', { bubbles: true });
        element.dispatchEvent(inputEvent);
        resolve();
      }, Math.random() * 100 + 100);
    });
  }
}


//================================================================================================
// Function to simulate selecting an option in a dropdown
function sanitizeSelector(selector) {
  return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}

//================================================================================================
// Function to simulate selecting an option in a dropdown
function handleFormSubmit(event) {
  event.preventDefault();
  updateDatabase(adminDetails.user_id, userDetails.user_id, 'success-sub', currentCompany.company_id, currentCompany.user_company_id);
  event.currentTarget.submit();
}


//================================================================================================
// removeFromStorage function
function removeFromStorage(companyId) {
  chrome.storage.local.get(['bgrabData'], function (result) {
    if (result.bgrabData) {
      const updatedRegistrationList = result.bgrabData.REGISTRATIONLIST.filter(company => company.company_id !== companyId);
      result.bgrabData.REGISTRATIONLIST = updatedRegistrationList;
      chrome.storage.local.set({ bgrabData: result.bgrabData }, function () {
        console.log("BGREB||Company removed from storage");
      });
    } else {
      console.log("BGREB||Removing data... No data found in storage");
    }
  });
  processJSONData();
}


//================================================================================================
// Function to update the database
function updateDatabase(aid, userId, action, companyId, usercompanyId) {
  const url = `https://bgreb.birthday.gold/bgr_actions.php?aid=${aid}&uid=${userId}&act=${action}&cid=${companyId}&ucid=${usercompanyId}&message=done&version=${version}&`;

  fetch(url)
    .then(response => {
      if (response.ok) {
        return response.text().then(text => {
          console.log('BGREB||Database update response:', text);
          hideSpinner();
        });
      } else {
        console.log('BGRAB||Error updating database:', response.status);
        hideSpinner();
        alert('Error updating database. Please try again.');
      }
    })
    .catch(error => {
      console.log('BGRAB||Error:', error);
      hideSpinner();
      alert('An error occurred. Please check your internet connection and try again.');
    });
}


//================================================================================================
// Function to send a message with retries
function sendMessageWithRetry(message, retries = 3, delay = 1000) {
  if (isUnloading || !chrome.runtime || !chrome.runtime.sendMessage) {
    console.warn('BGREB||Message not sent: Page is unloading or extension context invalidated');
    return; // Exit early if context is clearly invalid
  }

  try {
    chrome.runtime.sendMessage(message);
  } catch (error) {
    console.log('BGREB||Failed to send message: Extension context invalidated or data is invalid', error);
    if (retries > 0) {
      console.log(`BGREB||Retrying message in ${delay}ms...`);
      setTimeout(() => sendMessageWithRetry(message, retries - 1, delay), delay);
    } else {
      console.log('BGREB||All retries failed. Giving up.');
      // Optionally, notify the user that the operation failed
      alert('Failed to communicate with the extension. Please reload the page or contact support.');
    }
  }
}


//================================================================================================
// Function to send a message safely
function sendMessageSafely(message) {
  // Check if the context is valid before attempting to send the message
  if (isUnloading || !chrome.runtime || !chrome.runtime.sendMessage) {
    console.log('BGREB||Message not sent: Page is unloading or extension context invalidated');
    return; // Exit early if context is invalid
  }

  try {
    chrome.runtime.sendMessage(message);
  } catch (error) {
    console.log('BGREB||Issue encountered while sending message:', error);
    console.log('BGREB||chrome.runtime:', chrome.runtime);
    console.log('BGREB||chrome.runtime.sendMessage available:', !!chrome.runtime.sendMessage);
  }
}


//================================================================================================
// Function to toggle extension bar view
function toggleExtensionBar2(bgrabExtension, bgrabLogo, barItems) {
  if (bgrabExtension.classList.contains('collapsed')) {
    console.log('BGREB||showing bar');
    bgrabExtension.classList.remove('collapsed');
    bgrabExtension.classList.add('expanded');
    bgrabLogo.style.position = "static";
    bgrabLogo.style.left = "auto";
    bgrabLogo.style.zIndex = "auto";
    barItems.forEach(item => item.style.display = 'flex');
  } else {
    console.log('BGREB||hiding bar');
    bgrabExtension.classList.remove('expanded');
    bgrabExtension.classList.add('collapsed');
    bgrabLogo.style.position = "fixed";
    bgrabLogo.style.left = "7px";
    bgrabLogo.style.zIndex = "9999";
    barItems.forEach(item => item.style.display = 'none');
  }
}


function toggleExtensionBar() {
  const bgrabExtension = document.getElementById('bgrab-extension');
  const barItems = document.querySelectorAll('.bgrab-baritem');

  if (bgrabExtension.classList.contains('collapsed')) {
    console.log('BGREB||showing bar');
    bgrabExtension.classList.remove('collapsed');
    bgrabExtension.classList.add('expanded');
    barItems.forEach(item => item.style.display = 'flex');
  } else {
    console.log('BGREB||hiding bar');
    bgrabExtension.classList.remove('expanded');
    bgrabExtension.classList.add('collapsed');
    barItems.forEach(item => item.style.display = 'none');
  }
}


//================================================================================================
// Function to initialize the extension UI
function initializeExtensionxx() {
  const userButton = createUserButton(userName);
  version = manifestData.version;

  const logoUrl = chrome.runtime.getURL('images/icon48.png');
  const dropdownOptions = REGISTRATIONLIST.map(company => `<option value="${company.signup_url}">${company.company_name}</option>`).join("");

  const div = document.createElement("div");
  div.id = "bgrab-extension";
  div.className = "expanded";
  div.innerHTML = `
<div class="bgrab-logo-title">
<img class="bgrab-logo" src="${logoUrl}" alt="BGREB Logo" width="30" height="30">
<span class="bgrab-title bgrab-baritem">Birthday.Gold Reward Enrollment Bar</span>
<span class="bgrab-version bgrab-baritem">v.${version}</span>
</div>
<div class="bgrab-separator bgrab-baritem"></div>
<div class="bgrab-baritem" id="username-details">${userButton.outerHTML}</div>
<div class="bgrab-separator bgrab-baritem"></div>
<select id="bgrab-company-dropdown" class="form-control bgrab-baritem">${dropdownOptions}</select>
<button class="btn btn-primary btn-sm small-button fill-success bgrab-baritem" id="dropdown-go">Go</button>
`;

  document.body.appendChild(div);

  const bgrabExtension = document.getElementById('bgrab-extension');
  const bgrabLogo = document.querySelector('.bgrab-logo');
  const barItems = document.querySelectorAll('.bgrab-baritem');

  if (!bgrabExtension || !bgrabLogo) {
    console.log("BGREB||Could not find required elements. Exiting...");
    return;
  }

  bgrabLogo.addEventListener('click', () => {
    toggleExtensionBar(bgrabExtension, bgrabLogo, barItems);
  });

  handleFlashAnimation(div);

  setEventListeners(bgrabExtension, userButton);
}



//================================================================================================
function initializeExtension() {
  const userButton = createUserButton(userName);
  version = manifestData.version;

    // Assuming you have a way to determine the current company:
  // For example, you might find the current company based on `currentCompanyID`.
  let currentIndex = registrationList.findIndex(company => company.company_id === currentCompanyID);

  // If you don't have a way to determine the current index, initialize it to 0
  if (currentIndex === -1) {
    currentIndex = 0; // Default to the first company if not found
  }


  // Validate currentIndex to make sure it's within the bounds of the registrationList array
  if (currentIndex < 0 || currentIndex >= registrationList.length) {
    console.error("BGRAB||Invalid currentIndex. It should be between 0 and", registrationList.length - 1);
    return;
  }

  // Calculate the previous and next indices
  const previousIndex = (currentIndex - 1 + registrationList.length) % registrationList.length;
  const nextIndex = (currentIndex + 1) % registrationList.length;

  // Get the company names and signup URLs for the previous, current, and next indices
  const previousCompany = registrationList[previousIndex] || {};
  const nextCompany = registrationList[nextIndex] || {};
  const previousCompanyName = previousCompany.company_name || '';
  const currentCompanyName = currentCompany.company_name || '';
  const currentCompanyID = currentCompany.company_id || '';
  const previousSignupUrl = previousCompany.signup_url || '';
  const currentSignupUrl = currentCompany.signup_url || '';
  const nextSignupUrl = nextCompany.signup_url || '';
  const previousButton = previousSignupUrl ? `<button class="small-button previous-next bgrab-baritem" onclick="window.location.href='${previousSignupUrl}'">Previous</button>` : '';
  const nextButton = nextSignupUrl ? `<button class="small-button previous-next bgrab-baritem" onclick="window.location.href='${nextSignupUrl}'">Next</button>` : '';

  const companyNameDiv = `<div id="company-element" class="font-weight-bold small-text company-name bgrab-baritem">(${currentCompanyID}) - ${currentCompanyName}</div>`;
    
  // Create and append the action bar
  const logoUrl = chrome.runtime.getURL('images/icon48.png');
  const enrollingDiv = document.createElement("div");
  enrollingDiv.id = "bgrab-extension";
  enrollingDiv.className = "expanded"; // Added a class to handle toggling

  enrollingDiv.innerHTML = `
    <div id="bgrab-spinner-overlay" class="bgrab-spinner-overlay">
      <div class="bgrab-spinner"></div>
    </div>

    <div class="bgrab-logo-title">
      <img class="bgrab-logo" src="${logoUrl}" alt="BGRAB Logo" width="30" height="30">
      <span class="bgrab-title bgrab-baritem">Birthday.Gold Registration Action Bar</span>
      <span class="bgrab-version bgrab-baritem">v.${version}</span>
    </div>
    <div class="bgrab-separator bgrab-baritem"></div>
    <div class="bgrab-baritem" id="username-details">${userButton.outerHTML}</div>
    <div class="bgrab-separator bgrab-baritem"></div>
    ${previousButton}
    ${companyNameDiv}
    ${nextButton}
    <div class="bgrab-separator bgrab-baritem"></div>

    <button id="fill-form-button" class="small-button fill-success bgrab-baritem">Fill in Form</button>

    <div class="bgrab-dropdown">
      <button id="bgrab-fill-failed-button" class="small-button fill-failed bgrab-baritem">Failed</button>
      <ul id="bgrab-dropdown-menu" class="bgrab-dropdown-menu" style="display: none;">
        <li><a href="#" data-action="Account Already Exists">Account Exists</a></li>
        <li><a href="#" data-action="Form Failure">Form Failure</a></li>
        <li><a href="#" data-action="Password Failure">Password Failure</a></li>
        <li><a href="#" data-action="Missing Data Element">Missing Data Element</a></li>
      </ul>
    </div>

    <button id="fill-success-button" class="small-button fill-success bgrab-baritem">Success</button>
  `;

  document.body.appendChild(enrollingDiv);

  const bgrabExtension = document.getElementById('bgrab-extension');
  const bgrabLogo = document.querySelector('.bgrab-logo');
  const barItems = document.querySelectorAll('.bgrab-baritem');

  if (!bgrabExtension || !bgrabLogo) {
    console.log("BGREB||Could not find required elements. Exiting...");
    return;
  }

  bgrabLogo.addEventListener('click', toggleExtensionBar);

  handleFlashAnimation(enrollingDiv);

  setEventListeners(bgrabExtension, userButton);
}



//================================================================================================
// Set event listeners for extension UI elements
function setEventListeners(bgrabExtension, userButton) {
  document.getElementById("dropdown-go").addEventListener("click", (event) => {
    event.preventDefault();
    const selectedUrl = document.getElementById("bgrab-company-dropdown").value;
    window.location.href = selectedUrl;
  });

  document.addEventListener("change", (event) => {
    if (event.target.id === "bgrab-company-dropdown") {
      event.preventDefault();
      const selectedUrl = event.target.value;
      window.open(selectedUrl, '_userregistration');
    }
  });

  userButton.addEventListener('click', () => {
    chrome.runtime.openOptionsPage();
  });
}


//================================================================================================
// Additional function to handle any fatal errors gracefully
function gracefulExit(errorMessage) {
  console.log(`BGREB||Graceful exit: ${errorMessage}`);
  // Optionally, you can show an alert or UI message to the user here
  alert('An unexpected error occurred. Please reload the page or contact support.');
}

// Example usage: wrap other critical operations in try-catch blocks
try {
  fetchDataFromStorage();
} catch (error) {
  gracefulExit('Error during data fetch: ' + error.message);
}


//================================================================================================
// Function to fetch data from storage
function fetchDataFromStorage() {
  chrome.storage.local.get(['bgrabData'], ({ bgrabData }) => {
    if (bgrabData && bgrabData.data) {
      processJSONData(bgrabData.data);
    } else {
      console.log('BGREB||fetching data... No data found in storage');
      // Optional: Handle the absence of data, e.g., trigger a data fetch or show a message
    }
  });
}


//================================================================================================
// Function to process the JSON data and update the extension UI
function processJSONData(data) {
  console.log('BGREB||processJSONData', data);

  // Log the USERDETAILS data to diagnose potential issues
  console.log('BGREB||USERDETAILS data:', data.USERDETAILS);

  // Validate USERDETAILS
  if (!data.USERDETAILS || !data.USERDETAILS.full_name) {
    console.log('BGREB||Error: Invalid USERDETAILS data - Applying fallback');
    data.USERDETAILS = {
      full_name: 'Unknown User', // Fallback value if USERDETAILS is invalid
      user_id: null // Consider handling cases where user_id is also needed
    };
  }

  // Create userButton with valid or fallback USERDETAILS
  const userButton = document.createElement("button");
  userButton.className = "user-details-btn";
  userButton.textContent = data.USERDETAILS.full_name;

  // Validate REGISTRATIONLIST and prepare dropdown options
  const dropdownOptions = data.REGISTRATIONLIST && data.REGISTRATIONLIST.length > 0
    ? data.REGISTRATIONLIST.map(company => `<option value="${company.signup_url}">${company.company_name}</option>`).join("")
    : '<option disabled>No companies available</option>'; // Fallback if no companies

  // Update the extension UI with the created elements
  updateExtensionUI(userButton, dropdownOptions);
}


//================================================================================================
// Function to update the extension UI with userButton and dropdownOptions
function updateExtensionUI(userButton, dropdownOptions) {
  const logoUrl = chrome.runtime.getURL('images/icon48.png');

  const div = document.createElement("div");
  div.id = "bgrab-extension";
  div.className = "expanded";
  div.innerHTML = `
<div class="bgrab-logo-title">
<img class="bgrab-logo" src="${logoUrl}" alt="BGREB Logo" width="30" height="30">
<span class="bgrab-title bgrab-baritem">Birthday.Gold Reward Enrollment Bar</span>
<span class="bgrab-version bgrab-baritem">v.${version}</span>
</div>
<div class="bgrab-separator bgrab-baritem"></div>
<div class="bgrab-baritem" id="username-details">${userButton.outerHTML}</div>
<div class="bgrab-separator bgrab-baritem"></div>
<select id="bgrab-company-dropdown" class="form-control bgrab-baritem">${dropdownOptions}</select>
<button class="btn btn-primary btn-sm small-button fill-success bgrab-baritem" id="dropdown-go">Go</button>
`;

  // Attach the newly created div to the document body
  document.body.appendChild(div);

  const bgrabExtension = document.getElementById('bgrab-extension');
  const bgrabLogo = document.querySelector('.bgrab-logo');
  const barItems = document.querySelectorAll('.bgrab-baritem');

  if (!bgrabExtension || !bgrabLogo) {
    console.log("BGREB||Could not find required elements. Exiting...");
    return;
  }

  // Toggle functionality for extension bar
  bgrabLogo.addEventListener('click', () => {
    toggleExtensionBar(bgrabExtension, bgrabLogo, barItems);
  });

  // Initial flash animation
  handleFlashAnimation(div);
}


//================================================================================================
// Function to create a user button with the given name
function createUserButton(name) {
  const btn = document.createElement('button');
  btn.textContent = name;
  btn.addEventListener('click', () => {
    console.log("BGREB||open openUserDetailsModal fired");
    chrome.runtime.openOptionsPage();
  });
  return btn;
}


//================================================================================================
// Fill in the form fields
function fillFormFields(fieldMapping, currentCompany) {
  fieldMapping.forEach(({ key: formField, value: userFormData }) => {
    if (userFormData === '') {
      console.log('BGREB||skipping:', formField, "userFormData is blank");
      return;
    }

    if (formField.startsWith('wait_milliseconds')) {
      const millisecondsToWait = parseInt(userFormData, 10);
      const startTime = Date.now();
      let currentTime = null;
      do {
        currentTime = Date.now();
      } while (currentTime - startTime < millisecondsToWait);
      console.log('BGREB||pause complete:', formField, userFormData);
      return;
    }

    const element = document.querySelector(`input[id="${formField}"], select[id="${formField}"], input[data-testid="${formField}"], select[data-testid="${formField}"], input[name="${formField}"], select[name="${formField}"], input[formcontrolname="${formField}"], input.form-input[placeholder="${formField}"], input[aria-label="${formField}"], input[aria-labelledby="${formField}"], input[aria-describedby="${formField}"], input[title="${formField}"], select[ng-blur="${formField}"], input[ng-blur="${formField}"], select[ng-model="${formField}"], input[ng-model="${formField}"], input[data-sc-field-name="${formField}"], input[placeholder="${formField}"], input[data-quid="${formField}"], input[data-id="${formField}"], input[data-test-id="${formField}"], input[data-di-id="${formField}"], div[id="${formField}"], input[id^="${formField}"], select[id^="${formField}"]`);

    if (!element) {
      console.log('BGREB||Unable to locate:', formField);
      return;
    }

    // Checkboxes handling
    if (element.type === "checkbox") {
      if ((userFormData === 'true' || userFormData === '1') && !element.checked) {
        element.click();
      } else if ((userFormData === 'false' || userFormData === '0') && element.checked) {
        element.click();
      }
      return;
    }

    // Handle other types of form elements
    element.value = userFormData;
    const inputEvent = new Event('input', { bubbles: true });
    element.dispatchEvent(inputEvent);

    const changeEvent = new Event('change', { bubbles: true });
    element.dispatchEvent(changeEvent);

    // Simulate human interaction
    if (element && userFormData) {
      const originalValue = userFormData;
      let tempValue = originalValue.slice(0, -1);
      element.value = tempValue;
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.value = originalValue;
      element.dispatchEvent(new Event('input', { bubbles: true }));
      element.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
}


//================================================================================================
// FUNCTION TO Set event listeners for extension UI elements
function setEventListeners(bgrabExtension, userButton) {
  document.getElementById("dropdown-go").addEventListener("click", (event) => {
    event.preventDefault();
    const selectedUrl = document.getElementById("bgrab-company-dropdown").value;
    window.location.href = selectedUrl;
  });

  document.addEventListener("change", (event) => {
    if (event.target.id === "bgrab-company-dropdown") {
      event.preventDefault();
      const selectedUrl = event.target.value;
      window.open(selectedUrl, '_userregistration');
    }
  });

  userButton.addEventListener('click', () => {
    chrome.runtime.openOptionsPage();
  });
}




//================================================================================================
//================================================================================================
//================================================================================================
// Actual Execution
//================================================================================================
fetchDataFromStorage();

// Check current URL and initialize the extension
const currentUrl = window.location.href;
const parsedUrl = new URL(window.location.href);

if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {
  initializeExtension();
}




//================================================================================================
//================================================================================================
//================================================================================================
// Event Listeners
//================================================================================================
let isUnloading = false;

window.addEventListener('beforeunload', () => {
  isUnloading = true;
});


//---------------------------------------------------------
chrome.storage.onChanged.addListener(function (changes, namespace) {
  for (let [key, { oldValue, newValue }] of Object.entries(changes)) {
    if (key === 'bgrabData') {
      processJSONData(newValue.data);
    }
  }
});

//---------------------------------------------------------
chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {
  if (request.type === "dataUpdated") {
    processJSONData(request.data.data);
  }
});

//---------------------------------------------------------
// Listener to process the JSON data and update the extension UI
document.addEventListener('processUser', ({ detail: { userId, aid } }) => {
  console.log('BGREB||processUser event triggered');
  console.log(`BGREB||User ID: ${userId}, AID: ${aid}`);
  const message = {
    type: 'userSelected',
    userId,
    aid
  };
  sendMessageSafely(message);
});




//---------------------------------------------------------
// Listener to toggle extension bar view
bgrabLogo.addEventListener('click', toggleExtensionBar);
/*
bgrabLogo.addEventListener('click', () => {
  console.log("BGRAB||Logo clicked!");

  if (bgrabExtension.classList.contains('collapsed')) {
    console.log("BGRAB||Expanding...");
    bgrabExtension.classList.remove('collapsed');
    bgrabExtension.classList.add('expanded');
    bgrabLogo.style.position = "static";
    bgrabLogo.style.left = "auto";
    bgrabLogo.style.zIndex = "auto";
    barItems.forEach((item) => {
      if (item.style.display === 'none') {
        item.style.display = 'flex';
      } else {
        item.style.display = 'none';
      }
    });
  } else {
    console.log("BGRAB||Collapsing...");
    bgrabExtension.classList.remove('expanded');
    bgrabExtension.classList.add('collapsed');
    const bgrabLogo = document.querySelector('.bgrab-logo');
    bgrabLogo.style.position = "fixed";
    bgrabLogo.style.left = "7px";
    bgrabLogo.style.zIndex = "9999";
    barItems.forEach((item) => {
      if (item.style.display === 'none') {
        item.style.display = 'flex';
      } else {
        item.style.display = 'none';
      }
    });

  }
});
*/

// End of content.js
