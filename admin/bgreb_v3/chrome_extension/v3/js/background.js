// At the very top of background.js
console.log('BGBGJS||Background script initialized');


  
  // ===========================================================================================================
// Function to fetch data and store it in Chrome's local storage
function fetchData( userId, aid) {
  let url;
  console.log('BGBGJS||fetchData called with:', {  userId, aid });

  if (!userId) {
    console.warn('BGBGJS||Warning: No userId provided to fetchData');
    url = 'https://dev.birthday.gold/admin/bgreb_v3/bgr_getprocessdetails.php?uid=20&type=bgrab';
  } else {
    url = `https://dev.birthday.gold/admin/bgreb_v3/bgr_getprocessdetails.php?aid=${aid}&uid=${userId}&type=bgrab`;
  }

  console.log('BGBGJS||Fetching from URL:', url);

  fetch(url)
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data) {
        console.log('BGBGJS||Data fetched successfully:', data);
        const dataToStore = {
          data,
          timestamp: new Date().getTime()
        };

        chrome.storage.local.set({ bgrabData: dataToStore }, () => {
          console.log('BGBGJS||Data stored in local storage:', dataToStore);
        });
      } else {
        console.log('BGBGJS||No data received');
      }
    })
    .catch(error => {
      console.log('BGBGJS||Error fetching data:', error);
    });
}


// ===========================================================================================================
// Listen for the onCompleted event in web navigation
chrome.webNavigation.onCompleted.addListener(function(details) {
  const parsedUrl = new URL(details.url);

  // Check if the URL matches the conditions for starting registration
  if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {
    console.log(`BGBGJS||Data is refreshing from ${details.url}`);
    fetchData();
  }
}, { url: [{ hostSuffix: 'birthday.gold' }] });


// ===========================================================================================================
// Listen for updates in tabs to detect URL changes
chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab) {
  if (changeInfo.url && changeInfo.url.includes('birthday.gold')) {
    // Execute the content script to ensure it's injected
    chrome.scripting.executeScript({
      target: { tabId: tabId },
      files: ['js/content.js']
    }, () => {
      // Now send the message
      chrome.tabs.sendMessage(tabId, {
        message: 'urlChange',
        url: changeInfo.url
      }, function(response) {
        if (chrome.runtime.lastError) {
          // Log the error and fail gracefully
          console.log('BGBGJS||Error: Content script not available in tab:', tabId, chrome.runtime.lastError.message);
        } else {
          console.log('BGBGJS||Message sent to content script in tab:', tabId);
        }
      });
    });
  }
});


// ===========================================================================================================
// Listen for messages from content scripts
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
  console.log('BGBGJS||Message listener is active.');
  console.log('BGBGJS||Received message:', message);

  if (message.type === 'userSelected') {
    console.log('BGBGJS||userSelected message received with:', {
      aid: message.aid,
      userId: message.userId
    });
    
    // Modify fetchData call to be explicit about parameters
    const userIdToUse = message.userId;
    const aidToUse = message.aid;
    console.log('BGBGJS||Calling fetchData with:', { userIdToUse, aidToUse });
    
    fetchData( userIdToUse, aidToUse);
    sendResponse({ status: 'Processing request' });
  }
  return true;  // Keep messaging channel open
});


// ===========================================================================================================
// Listen for the popup connection to pass user details
chrome.runtime.onConnect.addListener((port) => {
  port.onMessage.addListener((msg) => {
    if (msg.type === 'populatePopup') {
      chrome.storage.local.get(['userDetails'], (result) => {
        port.postMessage({
          type: 'userDetails',
          data: result.userDetails 
        });
      });
    }
  });
});
