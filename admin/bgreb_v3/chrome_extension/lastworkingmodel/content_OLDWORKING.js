// content.js

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

// Functions
function handleFlashAnimation(element, duration = 500) {
  element.classList.add('flash');
  setTimeout(() => element.classList.remove('flash'), duration);
}

function showSpinner() {
  document.getElementById("bgrab-spinner-overlay").style.display = "flex";
}

function hideSpinner() {
  document.getElementById("bgrab-spinner-overlay").style.display = "none";
}

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

function sanitizeSelector(selector) {
  return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}

function handleFormSubmit(event) {
  event.preventDefault();
  updateDatabase(adminDetails.user_id, userDetails.user_id, 'success-sub', currentCompany.company_id, currentCompany.user_company_id);
  event.currentTarget.submit();
}

function removeFromStorage(companyId) {
  chrome.storage.local.get(['bgrabData'], function (result) {
    if (result.bgrabData) {
      const updatedRegistrationList = result.bgrabData.REGISTRATIONLIST.filter(company => company.company_id !== companyId);
      result.bgrabData.REGISTRATIONLIST = updatedRegistrationList;
      chrome.storage.local.set({ bgrabData: result.bgrabData }, function () {
        console.log("BGREB||Company removed from storage");
      });
    } else {
      console.error("BGREB||No data found in storage");
    }
  });
  processJSONData();
}

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
        console.error('BGRAB||Error updating database:', response.status);
        hideSpinner();
        alert('Error updating database. Please try again.');
      }
    })
    .catch(error => {
      console.error('BGRAB||Error:', error);
      hideSpinner();
      alert('An error occurred. Please check your internet connection and try again.');
    });
}

// Event Listeners
document.addEventListener('processUser', ({ detail: { userId, aid } }) => {
  chrome.runtime.sendMessage({
    type: 'userSelected',
    userId,
    aid
  });
});

chrome.storage.onChanged.addListener(function (changes, namespace) {
  for (let [key, { oldValue, newValue }] of Object.entries(changes)) {
    if (key === 'bgrabData') {
      processJSONData(newValue.data);
    }
  }
});

chrome.runtime.onMessage.addListener(function (request, sender, sendResponse) {
  if (request.type === "dataUpdated") {
    processJSONData(request.data.data);
  }
});

function fetchDataFromStorage() {
  chrome.storage.local.get(['bgrabData'], ({ bgrabData }) => {
    bgrabData ? processJSONData(bgrabData.data) : console.error('BGRAB||No data found in storage');
  });
}

fetchDataFromStorage();

// Check current URL and initialize the extension
const currentUrl = window.location.href;
const parsedUrl = new URL(window.location.href);

if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {
  initializeExtension();
}

function initializeExtension() {
  const userButton = createUserButton(userName);
  version = manifestData.version;

  const logoUrl = chrome.runtime.getURL('images/icon48.png');
  const dropdownOptions = REGISTRATIONLIST.map(company => `<option value="${company.signup_url}">${company.company_name}</option>`).join("");

  const div = document.createElement("div");
  div.id = "bgrab-extension";
  div.className = "expanded";
  div.innerHTML = `
    <div class="bgrab-logo-title">
      <img class="bgrab-logo" src="${logoUrl}" alt="BGRAB Logo" width="30" height="30">
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
  }

  bgrabLogo.addEventListener('click', () => {
    toggleExtensionBar(bgrabExtension, bgrabLogo, barItems);
  });

  handleFlashAnimation(div);

  setEventListeners(bgrabExtension, userButton);
}

function toggleExtensionBar(bgrabExtension, bgrabLogo, barItems) {
  if (bgrabExtension.classList.contains('collapsed')) {
    bgrabExtension.classList.remove('collapsed');
    bgrabExtension.classList.add('expanded');
    bgrabLogo.style.position = "static";
    bgrabLogo.style.left = "auto";
    bgrabLogo.style.zIndex = "auto";
    barItems.forEach((item) => {
      item.style.display = 'flex';
    });
  } else {
    bgrabExtension.classList.remove('expanded');
    bgrabExtension.classList.add('collapsed');
    bgrabLogo.style.position = "fixed";
    bgrabLogo.style.left = "7px";
    bgrabLogo.style.zIndex = "9999";
    barItems.forEach((item) => {
      item.style.display = 'none';
    });
  }
}

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

function processJSONData(data) {
  adminDetails = data.ADMINDETAILS || {};
  userDetails = data.USERDETAILS || {};
  registrationList = data.REGISTRATIONLIST || [];
  console.log('BGREB||processJSONData', data);

  const currentUrl = window.location.href;
  const parsedUrl = new URL(window.location.href);

  if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {
    updateExtensionUI();
  }
}

function updateExtensionUI() {
  const logoUrl = chrome.runtime.getURL('images/icon48.png');
  const userButton = createUserButton(userName);

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
  }

  bgrabLogo.addEventListener('click', () => {
    toggleExtensionBar(bgrabExtension, bgrabLogo, barItems);
  });

  handleFlashAnimation(div);

  // Call the function to fill in the form fields
  fillFormFields(fieldMapping, currentCompany);
}

function createUserButton(name) {
  const btn = document.createElement('button');
  btn.textContent = name;
  btn.addEventListener('click', () => {
    console.log("BGREB||open openUserDetailsModal fired");
    chrome.runtime.openOptionsPage();
  });
  return btn;
}

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
      console.error('BGREB||Unable to locate:', formField);
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

// End of content.js
