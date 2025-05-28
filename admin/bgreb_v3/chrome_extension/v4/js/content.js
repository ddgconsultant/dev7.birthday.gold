// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// start: content.js


// /-------------------------------------------------------------------------------------
// SET UP Global SCOPE VARIABLES
// /-------------------------------------------------------------------------------------
let REGISTRATIONLIST = [];
let dataToStore;
let userName;
let registrationList = [];  // Will be populated elsewhere
let adminDetails = {};      // Will be populated elsewhere
let userDetails = {};       // Will be populated elsewhere
let fieldMapping = {};      // Will be populated elsewhere
let manifestData = chrome.runtime.getManifest();
let version = manifestData.version;
let div = null;  // Initialize or populate elsewhere
// END - SET UP VARIABLES
// /-------------------------------------------------------------------------------------




// /-------------------------------------------------------------------------------------
// FUNCTIONS
// /-------------------------------------------------------------------------------------
/**
 * Adds a flash animation to the provided element for a specified duration.
 * 
 * @param {HTMLElement} element - The DOM element to which the flash class is applied.
 * @param {number} [duration=500] - The duration in milliseconds for which the flash class remains applied.
 */
function handleFlashAnimation(element, duration = 500) {
  if (element && element.classList) {
    element.classList.add('flash');
    setTimeout(() => {
      element.classList.remove('flash');
    }, duration);
  } else {
    console.log('BGREB: Invalid element passed to handleFlashAnimation');
  }
}


// /-------------------------------------------------------------------------------------
// Displays the spinner overlay by setting its display style to "flex".

function showSpinner() {
  const spinnerOverlay = document.getElementById("bgrab-spinner-overlay");
  if (spinnerOverlay) {
    spinnerOverlay.style.display = "flex";
  } else {
    console.log('BGREB: Spinner overlay element not found in showSpinner');
  }
}


// /-------------------------------------------------------------------------------------
// Hides the spinner overlay by setting its display style to "none".
function hideSpinner() {
  const spinnerOverlay = document.getElementById("bgrab-spinner-overlay");
  if (spinnerOverlay) {
    spinnerOverlay.style.display = "none";
  } else {
    console.log('BGREB: Spinner overlay element not found in hideSpinner');
  }
}


// -------------------------------------------------------------------------------------
// Simulate human-like typing
/**
* Simulates human-like typing by gradually inputting text into a given element.
* 
* @param {HTMLElement} element - The DOM element where the text will be typed.
* @param {string} valuein - The string to be typed into the element.
* @returns {Promise<void>} - A promise that resolves after the typing simulation is complete.
*/
async function simulateTyping(element, valuein) {
  if (!element || typeof valuein !== 'string') {
    console.log('BGREB: Invalid arguments passed to simulateTyping');
    return;
  }

  const value = sanitizeSelector(valuein);

  for (let i = 0; i <= value.length; i++) {
    await new Promise(resolve => {
      setTimeout(() => {
        element.value = value.slice(0, i);
        const inputEvent = new Event('input', { bubbles: true });
        element.dispatchEvent(inputEvent);
        resolve();
      }, Math.random() * 100 + 100); // Random delay between 100-200ms
    });
  }
}


// -------------------------------------------------------------------------------------
/**
* Escapes special characters in a selector string to make it safe for use in query selectors.
* 
* @param {string} selector - The string that needs to be sanitized.
* @returns {string} - The sanitized string with special characters escaped.
*/
function sanitizeSelector(selector) {
  if (typeof selector !== 'string') {
    console.log('BGREB: Invalid selector passed to sanitizeSelector');
    return '';
  }

  return selector.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, "\\$&");
}


// -------------------------------------------------------------------------------------
// Form submit handler function
/**
* Handles the form submission event, prevents default submission, updates the database,
* and then allows the form to submit.
* 
* @param {Event} event - The form submission event.
*/
function handleFormSubmit(event) {
  event.preventDefault();

  try {
    // Ensure necessary data is present before proceeding
    if (!adminDetails || !userDetails || !currentCompany) {
      console.log('BGREB: Missing required data in handleFormSubmit');
      return;
    }

    // Call the updateDatabase function to update the database
    updateDatabase(adminDetails.user_id, userDetails.user_id, 'success-sub', currentCompany.company_id, currentCompany.user_company_id);

    // Allow the form to submit
    event.currentTarget.submit();
  } catch (error) {
    console.log('BGREB: Error in handleFormSubmit', error);
  }
}


// -------------------------------------------------------------------------------------
// This function removes the company from the storage

/**
* Removes the company with the specified ID from the local storage's registration list.
* 
* @param {string} companyId - The ID of the company to be removed from storage.
*/
function removeFromStorage(companyId) {
  if (!companyId) {
    console.log('BGREB: No companyId provided to removeFromStorage');
    return;
  }

  chrome.storage.local.get(['bgrabData'], function (result) {
    if (chrome.runtime.lastError) {
      console.log('BGREB: Error accessing storage in removeFromStorage', chrome.runtime.lastError);
      return;
    }

    if (result.bgrabData && Array.isArray(result.bgrabData.REGISTRATIONLIST)) {
      const updatedRegistrationList = result.bgrabData.REGISTRATIONLIST.filter(company => company.company_id !== companyId);

      result.bgrabData.REGISTRATIONLIST = updatedRegistrationList;

      chrome.storage.local.set({ bgrabData: result.bgrabData }, function () {
        if (chrome.runtime.lastError) {
          console.log('BGREB: Error saving updated data in removeFromStorage', chrome.runtime.lastError);
        } else {
          console.log('BGREB: Company removed from storage');
        }
      });
    } else {
      console.log('BGREB: No registration list found in storage');
    }
  });

  try {
    processJSONData();
  } catch (error) {
    console.log('BGREB: Error processing JSON data in removeFromStorage', error);
  }
}



// -------------------------------------------------------------------------------------
// This function updates the database

/**
* Sends a request to update the database with the provided parameters.
* 
* @param {string} aid - The admin ID.
* @param {string} userId - The user ID.
* @param {string} action - The action to be recorded.
* @param {string} companyId - The company ID.
* @param {string} usercompanyId - The user company ID.
*/
function updateDatabase(aid, userId, action, companyId, usercompanyId) {
  if (!aid || !userId || !action || !companyId || !usercompanyId) {
    console.log('BGREB: Missing required parameters in updateDatabase');
    return;
  }

  const url = `https://dev.birthday.gold/admin/bgreb_v3/bgr_actions.php?aid=${encodeURIComponent(aid)}&uid=${encodeURIComponent(userId)}&act=${encodeURIComponent(action)}&cid=${encodeURIComponent(companyId)}&ucid=${encodeURIComponent(usercompanyId)}&message=done&version=${encodeURIComponent(version)}&`;

  fetch(url)
    .then(response => {
      document.getElementById("bgrab-spinner-overlay").style.display = "none";

      if (response.ok) {
        return response.text();
      } else {
        console.log('BGREB: Error updating database:', response.status);
     //   alert('Error updating database. Please try again.');
        throw new Error(`HTTP error! status: ${response.status}`);
      }
    })
    .then(text => {
      console.log('BGREB: Database update response:', text);
    })
    .catch(error => {
      console.log('BGREB: Error in updateDatabase:', error);
     // alert('An error occurred. Please check your internet connection and try again.');
    });
}


// END - OF FUNCTIONS
// /-------------------------------------------------------------------------------------


// -------------------------------------------------------------------------------------
// LISTENERS
// -------------------------------------------------------------------------------------

/**
* Listens for the custom 'processUser' event and sends a message to the background script.
*/
document.addEventListener('processUser', ({ detail: { userId, aid, bid } }) => {  // Added bid to destructuring
  if (userId && aid) {
    chrome.runtime.sendMessage({
      type: 'userSelected',
      userId,
      aid,
      bid  // Added bid to the message
    });
  } else {
    console.log('BGREB: Missing userId or aid in processUser event');
  }
});
/**
* Listens for changes in Chrome storage and processes new data if 'bgrabData' is changed.
*/
chrome.storage.onChanged.addListener((changes, namespace) => {
  if (namespace === 'local' && changes.bgrabData) {
    const { oldValue, newValue } = changes.bgrabData;
    if (newValue && newValue.data) {
      processJSONData(newValue.data);
    } else {
      console.log('BGREB: No valid data found in storage change');
    }
  }
});

/**
* Listens for the "dataUpdated" message from the background script and processes the updated data.
*/
chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
  if (request.type === "dataUpdated" && request.data && request.data.data) {
    processJSONData(request.data.data);
  } else {
    console.log('BGREB: Invalid data received in dataUpdated message');
  }
});

/**
* Fetches data from local storage and processes it if available.
*/
const fetchDataFromStorage = () => {
  chrome.storage.local.get(['bgrabData'], ({ bgrabData }) => {
    if (bgrabData && bgrabData.data) {
      processJSONData(bgrabData.data);
    } else {
      console.log('BGREB: No data found in storage');
    }
  });
};


// END - LISTENERS
// /-------------------------------------------------------------------------------------


// /-------------------------------------------------------------------------------------
// /-------------------------------------------------------------------------------------
// /-------------------------------------------------------------------------------------
// /-------------------------------------------------------------------------------------


// Initial data fetch
fetchDataFromStorage();




// Check current URL and proceed if it matches target URL
const currentUrl = window.location.href;
/// if (currentUrl.includes('birthday.gold/startregistration')) {
//  if (currentUrl.includes('birthday.gold') && /\/startregistration$/.test(currentUrl)) {


const parsedUrl = new URL(window.location.href);

// Check if the hostname includes 'birthday.gold' and the pathname ends with '/startregistration'
if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {


  // /-------------------------------------------------------------------------------------
  // Create and set up the user button
  const createUserButton = (name) => {
    const btn = document.createElement('button');
    btn.textContent = name;
    btn.addEventListener('click', () => {
      console.log("BGREB||open openUserDetailsModal fired");
      chrome.runtime.openOptionsPage();
    });
    return btn;
  };

  const userButton = createUserButton(userName);
  version = manifestData.version;


  // Create main extension div and populate with HTML content
  const logoUrl = chrome.runtime.getURL('images/icon48.png');
  const dropdownOptions = REGISTRATIONLIST.map(company => `<option value="${company.signup_url}">${company.company_name}</option>`).join("");

  const div = document.createElement("div");
  div.id = "bgrab-extension";
  div.className = "expanded"; // Added a class to handle toggling
  div.innerHTML = `
<!-- =========================================================================================================== -->
<!-- BIRTHDAY.GOLD REWARD ENROLLMENT BAR ${version} ------ INITIALIZED -->
<!-- =========================================================================================================== -->
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
    console.log("BGREB||Logo clicked!");

    if (bgrabExtension.classList.contains('collapsed')) {
      console.log("BGREB||Expanding...");
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
      console.log("BGREB||Collapsing...");
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

  handleFlashAnimation(div);
}
















// ==========================================================
// BEGIN TO PROCESS THINGS IN THE CORRECT ORDER
// ==========================================================
// Listen for "Load User" button click -- from startregistration page


///-------------------------------------------------------------------------------------
///-------------------------------------------------------------------------------------
///-------------------------------------------------------------------------------------
///-------------------------------------------------------------------------------------
// Process the JSON data
function processJSONData(data) {
  const adminDetails = data.ADMINDETAILS || {};
  const userDetails = data.USERDETAILS || {};
  const registrationList = data.REGISTRATIONLIST || [];
  console.log('BGRAB||processJSONData', data);
  // Get the user name from the JSON response
  const userName = userDetails.username || '';
  const userButton = document.createElement('button');
  userButton.textContent = userName;  // Add userName text 
  userButton.addEventListener('click', () => {    // Make clickable
    // Open popup 
    console.log('BGRAB||open openUserDetailsModal fired');
    // openUserDetailsModal();
    chrome.runtime.openOptionsPage();
  });




  // Request user details 
  chrome.runtime.sendMessage({ type: 'populatePopup' });
  // Listen for response
  chrome.runtime.onMessage.addListener((msg) => {
    if (msg.type === 'userDetails') {
      // Populate popup
      document.getElementById('userDetails').innerText = JSON.stringify(msg.data);
    }
  });

  ///-------------------------------------------------------------------------------------
  // Get the current URL
  const currentUrl = window.location.href;

  // Parse the current window URL
  const parsedUrl = new URL(window.location.href);

  // Check if the hostname includes 'birthday.gold' and the pathname ends with '/startregistration'
  if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {

    // Create and append the dropdown list
    const logoUrl = chrome.runtime.getURL('images/icon48.png');
    const runningDiv = document.createElement("div");
    runningDiv.id = "bgrab-extension";
    runningDiv.className = "expanded"; // Added a class to handle toggling
    const dropdownOptions = registrationList
      .map(company => {
        return `<option value="${company.signup_url}">${company.company_name}</option>`;
      })
      .join("");

    runningDiv.innerHTML = `
<!-- =========================================================================================================== -->
<!-- BIRTHDAY.GOLD REWARD ENROLLMENT BAR ${version} ------ RUNNING -->
<!-- =========================================================================================================== -->
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


    const oldDiv = document.getElementById("bgrab-extension");
    if (oldDiv) {
      oldDiv.parentNode.replaceChild(runningDiv, oldDiv);
      console.log("BGREB||runningDiv REPLACING DIV");
    } else {
      // If the old div doesn't exist, append the new one
      document.body.appendChild(runningDiv);
      console.log("BGREB||runningDiv APPENDING DIV");
    }
    runningDiv.style.display = "flex";
    console.log("BGREB||runningDiv:", runningDiv);

    handleFlashAnimation(runningDiv);

    const bgrabExtension = document.getElementById('bgrab-extension');
    const bgrabLogo = document.querySelector('.bgrab-logo');
    const barItems = document.querySelectorAll('.bgrab-baritem');


    if (!bgrabExtension || !bgrabLogo) {
      console.log("BGREB||Could not find required elements. Exiting...");
    }

    bgrabLogo.addEventListener('click', () => {
      console.log("BGREB||Logo clicked!");

      if (bgrabExtension.classList.contains('collapsed')) {
        console.log("BGREB||Expanding...");
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
        console.log("BGREB||Collapsing...");
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




    // Set the event listener for the "Go" button
    const goButton = document.getElementById("dropdown-go");
    goButton.addEventListener("click", (event) => {
      event.preventDefault();
      const selectedUrl = document.getElementById("bgrab-company-dropdown").value;
      window.location.href = selectedUrl;
    });


    document.addEventListener("change", (event) => {
      if (event.target.id === "bgrab-company-dropdown") {
        event.preventDefault();
        const selectedUrl = event.target.value;
        //  window.location.href = selectedUrl;
        window.open(selectedUrl, '_userregistration'); // Open in new tab

      }
    });

  } else {

    // Find the current company based on the current URL
    const currentCompany = registrationList.find(item => {
      console.log("BGREB||Current URL=", currentUrl, " :: Surl=", item.signup_url, "Sdomain=", item.signup_domain, "Bdomain=", item.bgrab_domain);

      // If bgrab_domain has multiple values separated by a comma
      let bgrab_domains = item.bgrab_domain ? item.bgrab_domain.split(',') : [];
      console.log("BGREB||Split domains: ", bgrab_domains);

      return currentUrl.includes(item.signup_url) ||
        currentUrl.includes(item.signup_domain) ||
        (bgrab_domains.length > 0 && bgrab_domains.some(domain => currentUrl.includes(domain)));
    }) || {};

    console.log("BGREB||Found matching company:", currentCompany.company_id, '-', currentCompany.company_name);


    if (currentCompany && (Object.keys(currentCompany).length !== 0)) {
      console.log("BGREB||WE ARE IN");

      // Convert it into an array of objects
      const fieldMapping = Object.entries(currentCompany.FIELDMAPPING || {}).map(([key, value]) => {
        const [order, actualKey] = key.split("||");  // Since all keys have ##||, we can directly split
        return { order, key: actualKey, value };
      });

      // Sort the array based on 'order'
      fieldMapping.sort((a, b) => a.order.localeCompare(b.order));



      // Find the index of the current URL in the registration list
      const currentIndex = registrationList.findIndex(item => {

        // If bgrab_domain has multiple values separated by a comma
        let bgrab_domains = item.bgrab_domain ? item.bgrab_domain.split(',') : [];

        return currentUrl.includes(item.signup_url) ||
          currentUrl.includes(item.signup_domain) ||
          (bgrab_domains.length > 0 && bgrab_domains.some(domain => currentUrl.includes(domain)));
      });


      // Validate currentIndex to make sure it's within the bounds of the registrationList array
      if (currentIndex < 0 || currentIndex >= registrationList.length) {
        console.log("BGRAB||Invalid currentIndex. It should be between 0 and", registrationList.length - 1);
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
      const previousButton = previousSignupUrl ? `<button class="small-button previous-next bgrab-baritem" onclick="window.location.href='${previousSignupUrl}'"><i class="bi bi-caret-left-square-fill"></i> Previous</button>` : '';
      const nextButton = nextSignupUrl ? `<button class="small-button previous-next bgrab-baritem" onclick="window.location.href='${nextSignupUrl}'"><i class="bi bi-caret-right-square-fill"></i> Next</button>` : '';

      const companyNameDiv = `<div id="company-element" class="font-weight-bold small-text company-name bgrab-baritem">(${currentCompanyID}) - ${currentCompanyName}</div>`;

      // Create and append the action bar


      const logoUrl = chrome.runtime.getURL('images/icon48.png');
      const enrollingDiv = document.createElement("div");
      enrollingDiv.id = "bgrab-extension";
      enrollingDiv.className = "expanded"; // Added a class to handle toggling

      enrollingDiv.innerHTML = `
<!-- =========================================================================================================== -->
<!-- BIRTHDAY.GOLD REWARD ENROLLMENT BAR ${version} ------ ENROLLING -->
<!-- =========================================================================================================== -->
<div id="bgrab-spinner-overlay" class="bgrab-spinner-overlay">
<div class="bgrab-spinner"></div>
</div>

<div class="bgrab-logo-title">
<img class="bgrab-logo" src="${logoUrl}" alt="BGREB Logo" width="30" height="30">
<span class="bgrab-title bgrab-baritem">Birthday.Gold Reward Enrollment Bar</span>
<span class="bgrab-version bgrab-baritem">v.${version}</span>
</div>
<div class="bgrab-separator bgrab-baritem"></div>
<div class="bgrab-baritem" id="username-details">${userButton.outerHTML}</div>
<div class="bgrab-separator bgrab-baritem"></div>
<!-- ${previousButton} -->
${companyNameDiv}
<!-- ${nextButton} -->
<div class="bgrab-separator bgrab-baritem"></div>

<button id="fill-form-button" class="small-button fill-success bgrab-baritem"><i class="bi bi-pencil"></i> Fill in Form</button>

<!--
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
-->
`;

      const oldDiv = document.getElementById("bgrab-extension");
      if (oldDiv) {
        oldDiv.parentNode.replaceChild(enrollingDiv, oldDiv);
        console.log("BGREB||enrollingDiv REPLACING DIV");
      } else {
        // If the old div doesn't exist, append the new one
        document.body.appendChild(enrollingDiv);
        console.log("BGREB||enrollingDiv APPENDING DIV");
      }

      console.log("BGREB||enrollingDiv:", enrollingDiv);
      enrollingDiv.style.display = "none";


      handleFlashAnimation(enrollingDiv);

      const bgrabExtension = document.getElementById('bgrab-extension');
      const bgrabLogo = document.querySelector('.bgrab-logo');
      const barItems = document.querySelectorAll('.bgrab-baritem');


      if (!bgrabExtension || !bgrabLogo) {
        console.log("BGREB||Could not find required elements. Exiting...");
      }

      bgrabLogo.addEventListener('click', () => {
        console.log("BGREB||Logo clicked!");

        if (bgrabExtension.classList.contains('collapsed')) {
          console.log("BGREB||Expanding...");
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
          console.log("BGREB||Collapsing...");
          bgrabExtension.classList.remove('expanded');
          bgrabExtension.classList.add('collapsed');
          const bgrabLogo = document.querySelector('.bgrab-logo');
          bgrabLogo.style.position = "fixed";
          bgrabLogo.style.left = "5px";
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


      ///-------------------------------------------------------------------------------------

      const fillbutton = document.getElementById('fill-form-button');
      fillbutton.style.display = 'none';
      ///-------------------------------------------------------------------------------------
      ///  THIS IS WHERE WE ACTUALLY START PROCESSING STUFF 
      ///-------------------------------------------------------------------------------------
      console.log("BGREB||This page is " + (window.getAllAngularRootElements ? "" : "NOT ") + "using Angular [getAllAngularRootElements].");
      console.log("BGREB||This page is " + (!!window.ngProbeToken ? "" : "NOT ") + "using Angular [ngProbeToken].");
      console.log("BGREB||This page is " + (window.angular ? "using AngularJS." : "NOT using AngularJS [angular]."));
      console.log("BGREB||This page is " + (Array.from(document.querySelectorAll('*')).some(el => el.hasAttribute('ng-version')) ? "" : "NOT ") + "using Angular [ng-version].");



      if (document.readyState === "interactive" || document.readyState === "complete") {
        console.log("BGREB||page completed");

        registrationList.forEach((item, index) => {
          let bgrab_domains = item.bgrab_domain ? item.bgrab_domain.split(',') : [];

          if (currentUrl.includes(item.signup_url) ||
            currentUrl.includes(item.signup_domain) ||
            (bgrab_domains.length > 0 && bgrab_domains.some(domain => currentUrl.includes(domain)))) {
            enrollingDiv.style.display = "flex";
            console.log("BGREB||enrollingDiv: on");
          }
        });




        const bgrabcompanyelement = document.getElementById('company-element');
        if (bgrabcompanyelement) { // Check that the element exists
          bgrabcompanyelement.addEventListener('click', () => {
            console.log("BGREB||bgrabcompanyelement", registrationList);
          });
        } else {
          console.log("BGREB||bgrabcompanyelement does not exist");
        }

        fillbutton.style.display = 'flex';

        fillbutton.addEventListener('click', (event) => {
          event.preventDefault();
          console.log('BGRAB||fillformbutton clicked');
          fillFormFields(fieldMapping, currentCompany.company_name,);
        });



        const forms = document.querySelectorAll('form');

        forms.forEach((form, index) => {
          console.log(`FORM ${index + 1}:`);
          console.log(`ID: ${form.id || 'No ID'}`, `Name: ${form.name || 'No Name'}`);
          console.log(`Action: ${form.action || 'No Action'}`);
          console.log('-------------------------');

          const inputFields = document.querySelectorAll('input');

          inputFields.forEach((input, index) => {
            console.log(`INPUT Field ${index + 1}:`, `Type: ${input.type}`);
            console.log(`ID: ${input.id}`, `Name: ${input.name}`, `Value: ${input.value}`);
            console.log('-------------------------');
          });
          console.log('===========================================');
        });

        fillFormFields(fieldMapping, currentCompany.company_name);


      }



      ///-------------------------------------------------------------------------------------
      ///-------------------------------------------------------------------------------------
      ///-------------------------------------------------------------------------------------
      // Get the button element - Function to handle button clicks
      function handleButtonClick(action, adminDetailsuser_id, userDetailsuser_id, currentCompany, nextSignupUrl) {
        // Show the spinner
        document.getElementById("bgrab-spinner-overlay").style.display = "flex";


        console.log(`BGREB||Button pressed, updating database and removing company from storage with the following details:`);
        console.log(`BGREB||User ID:`, userDetailsuser_id);
        console.log(`BGREB||Action: ${action}`);
        console.log(`BGREB||Company ID:`, currentCompany.company_id);
        console.log(`BGREB||User Company ID:`, currentCompany.user_company_id);

        // Call the updateDatabase function
        updateDatabase(adminDetailsuser_id, userDetailsuser_id, `${action}`, currentCompany.company_id, currentCompany.user_company_id);

        // Call the removeFromStorage function to remove the company from the storage
        // removeFromStorage(currentCompany.company_id);

        // Wait for 3 seconds and navigate to the next company URL
        setTimeout(() => {
          window.location.href = nextSignupUrl;
        }, 2500);
        hideSpinner();
        document.getElementById("bgrab-spinner-overlay").style.display = "none";
      }

      // Get the button elements
      const successButton = document.getElementById("fill-success-button");

      // Event listener for 'Success' button
      successButton.addEventListener('click', (event) => {
        event.preventDefault();
        console.log("BGREB||Success button pressed");
        showSpinner();
        document.getElementById("bgrab-spinner-overlay").style.display = "flex";
        handleButtonClick('success-btn', adminDetails.user_id, userDetails.user_id, currentCompany, nextSignupUrl);
        document.getElementById("bgrab-spinner-overlay").style.display = "none";

      });


      // Event listener for 'Failed' button
      const failedButton = document.getElementById("bgrab-fill-failed-button");

      failedButton.addEventListener('click', (event) => {
        event.preventDefault();
        console.log("BGREB||Failed button pressed");

        // document.getElementById('bgrab-dropdown-menu').style.display === 'block' ? 'none' : 'block';
        const dropdownMenu = document.getElementById('bgrab-dropdown-menu');
        dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';

        console.log("BGREB||menu state", dropdownMenu.style.display);

        // Event listeners for dropdown items
        document.querySelectorAll('#bgrab-dropdown-menu a').forEach(function (item) {
          item.addEventListener('click', function (event) {
            event.preventDefault();
            var action = this.getAttribute('data-action');
            document.getElementById('bgrab-fill-failed-button').textContent = action;
            document.getElementById('bgrab-dropdown-menu').style.display = 'none';
            console.log("BGREB||Failed button pressed with action: " + action);
            showSpinner();
            document.getElementById("bgrab-spinner-overlay").style.display = "flex";
            handleButtonClick('failed||' + action, adminDetails.user_id, userDetails.user_id, currentCompany, nextSignupUrl);
            document.getElementById("bgrab-spinner-overlay").style.display = "none";
          });
        });

        // Close the dropdown if the user clicks outside of it
        window.addEventListener('click', function (event) {
          if (!event.target.matches('#bgrab-fill-failed-button')) {
            var dropdowns = document.getElementsByClassName('bgrab-dropdown-menu');
            for (var i = 0; i < dropdowns.length; i++) {
              var openDropdown = dropdowns[i];
              if (openDropdown.style.display === 'block') {
                openDropdown.style.display = 'none';
              }
            }
          }
        });

      });




    } else {
      // The current URL is not in the registration list
      console.log("BGREB||The current URL is not in the registration list");
    }

  }




  ///===============================================================================================================
  // FILL IN THE FORM
  ///===============================================================================================================
  // This function fills in the forms
  function fillFormFields(fieldMapping, currentCompany) {

    if (window.top === window.self) {
      console.log("Not in an iframe");
    } else {
      console.log("Inside an iframe");
    }

    if (typeof document === 'undefined') {
      console.log("Likely running in a Web Worker or another non-DOM environment");
    }



    console.log("BGREB||FILLING IN FORM");


    console.log(`BGREB||Field Mapping for ${currentCompany}:`, fieldMapping);


    // loop through all the fields in fieldMapping and fill them in
    fieldMapping.forEach(({ key: formField, value: userFormData }) => {

      if (userFormData === '') {
        console.log('BGRAB||skipping:', formField, "userFormData is blank");
        return;
      }


      if (formField.startsWith('wait_milliseconds')) {
        console.log('BGRAB||pausing enabled:', formField, userFormData);
        const millisecondsToWait = parseInt(userFormData, 10);
        const startTime = Date.now();
        let currentTime = null;
        do {
          currentTime = Date.now();
        } while (currentTime - startTime < millisecondsToWait);
        console.log('BGRAB||pause complete:', formField, userFormData);
        return;
      }

      console.log('BGRAB||Disabling copy/paste preventers');
      document.addEventListener('copy', function (e) {
        e.stopPropagation();
      }, true);

      document.addEventListener('cut', function (e) {
        e.stopPropagation();
      }, true);

      document.addEventListener('paste', function (e) {
        e.stopPropagation();
      }, true);

      document.addEventListener('contextmenu', function (e) {
        e.stopPropagation();
      }, true);

      document.querySelectorAll('*').forEach(el => {
        el.style.userSelect = 'auto';
      });


      console.log('BGRAB||searching for: [', formField, "] to fill with:", userFormData);

      const elementById = document.querySelector(`input[id="${formField}"], select[id="${formField}"]`);
      const elementByDataId = document.querySelector(`input[data-testid="${formField}"], select[data-testid="${formField}"]`);
      const elementByName = document.querySelector(`input[name="${formField}"], select[name="${formField}"]`);
      const elementByAttribute = document.querySelector(`input[formcontrolname="${formField}"], input.form-input[placeholder="${formField}"], input[aria-label="${formField}"], input[aria-labelledby="${formField}"], input[aria-describedby="${formField}"], input[title="${formField}"]`);
      const elementByAttribute1 = document.querySelector(`select[ng-blur="${formField}"], input[ng-blur="${formField}"]`);
      const elementByAttribute2 = document.querySelector(`select[ng-model="${formField}"], input[ng-model="${formField}"]`);
      const elementByDataSc = document.querySelector(`input[data-sc-field-name="${formField}"]`);
      const elementByPlaceholder = document.querySelector(`input[placeholder="${formField}"]`);
      const elementByDataTag = document.querySelector(`input[data-quid="${formField}"], input[data-testid="${formField}"],  input[data-id="${formField}"],  input[data-test-id="${formField}"],  input[data-di-id="${formField}"]`);
      const divById = document.querySelector(`div[id="${formField}"]`);
      const elementByWildCardId = document.querySelector(`input[id^="${formField}"], select[id^="${formField}"]`);


      // New code for finding checkbox by parent ID or Class
      const elementByParentId = document.querySelector(`#${formField} input[type="checkbox"]`);
      const elementByParentClass = document.querySelector(`.${formField} input[type="checkbox"]`);


      if (userFormData === 'buttonclick') {
        const button = document.querySelector(formField);
        if (button) {    // You can add a check to see if the button was found before attempting to click it
          button.click();
        } else {
          console.log(`BGREB||Button ${userFormData} not found`);
        }
        return;
      }

      // --------------------------------------------------------------------------------------------------------------
      // --------------------------------------------------------------------------------------------------------------
      // Store all possible elements in an array with corresponding names for logging
      const elements = [
        { name: 'divById', element: divById },
        { name: 'elementById', element: elementById },
        { name: 'elementByName', element: elementByName },
        { name: 'elementByDataId', element: elementByDataId },
        { name: 'elementByAttribute', element: elementByAttribute },
        { name: 'elementByAttribute1', element: elementByAttribute1 },
        { name: 'elementByAttribute2', element: elementByAttribute2 },
        { name: 'elementByPlaceholder', element: elementByPlaceholder },
        { name: 'elementByDataTag', element: elementByDataTag },
        { name: 'elementByDataSc', element: elementByDataSc },
        { name: 'elementByParentId', element: elementByParentId },
        { name: 'elementByParentClass', element: elementByParentClass },
        { name: 'elementByWildCardId', element: elementByWildCardId }
      ];

      // Find the first non-null element
      const element = elements.find(item => item.element)?.element;

      // Log the values of each element
      elements.forEach(({ name, element }) => {
        console.log(`BGREB||Value of ${name}:`, element ? element.value : "Element not found");
      });

      if (!element) {
        console.log('BGRAB||Unable to locate:', formField);
        return;
      }
      // --------------------------------------------------------------------------------------------------------------
      // --------------------------------------------------------------------------------------------------------------

      // Set focus to the element
      element.focus();

      // We should have a valid element to fill in
      console.log(`BGREB||Setting value for ${formField}`, element, `with value: ${userFormData}`);


      /// DEAL WITH CHECKBOX
      if (element.type === "checkbox") {
        // element.checked = userFormData.toLowerCase() === 'true' ? true : false;
        console.log(`BGREB||dealing with checkbox`);
        if ((userFormData === 'true' || userFormData === 'True' || userFormData === 'T' || userFormData === '1') && !element.checked) {
          element.click();
        }
        if ((userFormData === 'false' || userFormData === 'False' || userFormData === 'F' || userFormData === '0') && element.checked) {
          element.click();
        }
        return;
      }

      if (element.tagName.toLowerCase() === "div") {
        console.log(`BGREB||dealing with DIV element`);
        element.textContent = userFormData;
        element.click();
        const mouseEvent = new MouseEvent('mouseover', {
          'view': window,
          'bubbles': true,
          'cancelable': true
        });
        element.dispatchEvent(mouseEvent);
        return;
      }

      /// DEAL WITH ANY OTHER FORM ELEMENT TYPE
      console.log(`BGREB||dealing with ANY OTHER TYPE`);
      element.value = userFormData;

      // act like a human
      const inputEvent = new Event('input', { bubbles: true });


      // Set focus to the element
      element.focus();

      // Dispatch the input event
      element.dispatchEvent(inputEvent);

      // Additional step: Dispatch a 'change' event
      const changeEvent = new Event('change', { bubbles: true });
      element.dispatchEvent(changeEvent);

      // Simulate a user's keystroke
      if (element && userFormData) {
        console.log(`BGREB||Performing Human interaction - setting value for ${formField}: ${userFormData}`);

        // 1. Capture the current value
        const originalValue = userFormData;

        if (element.hasAttribute('value')) {
          const currentValue = element.getAttribute('value');
          if (currentValue === null || currentValue === '') {

            element.setAttribute('value', originalValue);  // Set to desired default value
            console.log(`BGREB||missing value`, element);
          }
        }



        // 2. Remove the last character
        let tempValue = originalValue.slice(0, -1); // all characters except the last one
        element.value = tempValue;

        // 3. Dispatch input event
        let inputEvent1 = new Event('input', { bubbles: true });
        element.dispatchEvent(inputEvent1);

        // 4. Restore the last character
        element.value = originalValue;

        // Dispatch input and change events
        let inputEvent2 = new Event('input', { bubbles: true });
        element.dispatchEvent(inputEvent2);
        const mouseEvent = new MouseEvent('mouseover', {
          'view': window,
          'bubbles': true,
          'cancelable': true
        });

        // Dispatch input and change events after simulating typing
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));


        element.dispatchEvent(mouseEvent);
        console.log(`BGREB||Completed setting ${formField}`, element, `with value: ${userFormData}`);
      }
      // end acting like a human



    });


  }
}





// end: content.js
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////