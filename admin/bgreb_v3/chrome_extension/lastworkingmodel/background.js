// background.js

// Function to fetch data and store it in Chrome's local storage
function fetchData(aid, userId) {
    let url;
    if (!userId) {
      url = 'https://bgreb.birthday.gold/bgr_getprocessdetails.php?uid=20&type=bgrab'; 
    } else {
      url = `https://bgreb.birthday.gold/bgr_getprocessdetails.php?aid=${aid}&uid=${userId}&type=bgrab`;
    }
  
    fetch(url)
      .then(response => response.json())
      .then(data => {
        const dataToStore = {
          data,
          timestamp: new Date().getTime()
        };
  
        // Store the data in local storage
        return new Promise((resolve, reject) => {
          chrome.storage.local.set({ bgrabData: dataToStore }, () => {
            const error = chrome.runtime.lastError;
            if (error) {
              reject(error);
            } else {
              console.log("BGBGJS||Data stored:", dataToStore);
              resolve();
            }
          });
        });
      })
      .catch(error => console.error('Error fetching data:', error));
  }
  
  // Listen for the onCompleted event in web navigation
  chrome.webNavigation.onCompleted.addListener(function(details) {
    const parsedUrl = new URL(details.url);
  
    // Check if the URL matches the conditions for starting registration
    if (parsedUrl.hostname.includes('birthday.gold') && /\/startregistration$/.test(parsedUrl.pathname)) {
      console.log("BGBGJS||Data is refreshing");
      fetchData();
    }
  });
  
  // Listen for updates in tabs to detect URL changes
  chrome.tabs.onUpdated.addListener(function(tabId, changeInfo, tab) {
    if (changeInfo.url) {
      chrome.tabs.sendMessage(tabId, {
        message: 'urlChange',
        url: changeInfo.url
      });
    }
  });
  
  // Listen for messages from content scripts
  chrome.runtime.onMessage.addListener((message, sender) => {
    if (message.type === 'userSelected') {
      const userId = message.userId;    
      const aid = message.aid;    
      // Fetch data for the selected user
      fetchData(aid, userId);
    }
  });
  

  
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
  