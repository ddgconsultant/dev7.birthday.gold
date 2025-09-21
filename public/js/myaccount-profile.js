
// Array of instructions for each input field
const instructions = {
    "inputprofile_Username": "<h4>Enter a unique username.</h4>This is the username (some use an email which is a different value) that the Registration Agent will use to create your account for businesses that require a username.  If your username is taken/already registered, the Registration Agent will fail to register this business to your account and you will be notified of the failure. This is does not have to be the same as your birthday.gold username.",
    "inputprofile_gender": "<h4>Select your gender from the dropdown menu.</h4>Some businesses would like to know your gender.",
    "inputprofile_title": "<h4>Select your title.</h4>There are a handful of buinesses that want to properly address you by title.",
    "inputprofile_first_name": "<h4>Enter your first name.</h4>Some buinesses require your name to match the name on your id.",
    "inputprofile_middle_name": "<h4>Enter your middle name</h4>If you have one you might as well provide it for the businesses that request it.",
    "inputprofile_last_name": "<h4>Enter your last name.</h4>",
    "inputprofile_mailing_address": "<h4>Enter your mailing address.</h4>Start typing your address and select from the suggestions to automatically fill in your city, state, and zip code. This will be used for delivery of physical items if any businesses do that. Some business also use your address to help provide localized service.",
    "inputprofile_City": "<h4>Enter the name of your city.</h4>",
    "inputprofile_State": "<h4>Select your state.</h4>",
    "inputprofile_zip_code": "<h4>Enter your Zip code.</h4>",
    "inputprofile_email": "<h4>Enter your email address.</h4>This might end up being how you sign into your account if they don\'t use usernames.",
    "inputprofile_password": "<h4>Enter your password.</h4><p class=\"small\">This is the password you are going to use to log into the account that is created with the businesses you select.</p><p class=\"small\">It\'s important to make your password match the following requirements or the account will probably fail to be created:</p><ul class=\"small\">  <li  class=\"small\">Minimum 10 characters</li> <li  class=\"small\">Contains uppercase and lowercase letters</li><li  class=\"small\">Include numbers</li><li  class=\"small\">Includes a special character [ @ # ? $ . ]).  Other symbols typically are not accepted.</li>   <li  class=\"small\">No username parts</li> <li  class=\"small\">No common words or sequences</li></ul>",
    "inputprofile_phone_number": "<h4>Enter your mobile number.</h4>It is important to provide your mobile number because some businesses will be texting you confirmation messages.  If you don\'t want to receive their marketing text messages then disable the [Receive SMS/Texts] option",
    "inputprofile_phone_type": "<h4>Select your phone type.</h4>Let us know if you're using an iPhone or Android device. This helps businesses send you the right app download links and ensures compatibility with any mobile features.",
   
    "inputprofile_military": "<h4>Veteran or Active Military</h4>Some businesses provide additional benefits to those that are or have served.  Some businesses may request identification.",
    "inputprofile_educator": "<h4>Educator/Teacher</h4>Some businesses provide additional benefits to those educate the next generation.  Some businesses may request identification.",
    "inputprofile_firstresponder": "<h4>First Responder</h4>Some businesses provide additional benefits to those who are there when people need it the most.  Some businesses may request identification.",
   
    "inputprofile_agree_terms": "<h4>Agree to the Terms and Conditions.</h4>If you disable this more than likely all of your registrations will fail.  Legally, we have to provide you this option.",
    "inputprofile_agree_text": "<h4>Agree to receive SMS/Texts.</h4>This option allows you to receive the marketing messages from the business you sign up for.  Sometimes the provide secret/excusive offers via text only.",
    "inputprofile_agree_email": "<h4>Agree to receive emails.</h4>",
    "inputprofile_allergy_gluten": "<h4>Check this box if you have a Gluten allergy.</h4>Enabling this will suppress any businesses that don\'t provide GF offerings.  Please note: This does not mean that the specific birthday benefit is Gluten Free.",
    "inputprofile_allergy_sugar": "<h4>Check this box if you have a Sugar allergy.</h4>",
    "inputprofile_allergy_nuts": "<h4>Check this box if you have a Nut allergy.</h4>If a business has or processes nuts, these businesses will be suppressed.",
    "inputprofile_allergy_dairy": "<h4>Check this box if you have a Dairy allergy.</h4>",
    "inputprofile_diet_vegan": "<h4>Check this box if you follow a Vegan diet.</h4>",
    "inputprofile_diet_kosher": "<h4>Check this box if you follow a Kosher diet.</h4>",
    "inputprofile_diet_pescatarian": "<h4>Check this box if you follow a Pescatarian diet.</h4>",
    "inputprofile_diet_keto": "<h4>Check this box if you follow a Keto diet.</h4>",
    "inputprofile_diet_paleo": "<h4>Check this box if you follow a Paleo diet.</h4>",
    "inputprofile_diet_vegetarian": "<h4>Check this box if you follow a Vegetarian diet.</h4>"
    };
    
// Track if help panel has been shown
let helpPanelShown = false;

 // When an input field is focused, show its corresponding instructions
document.querySelectorAll("input, select").forEach(function(input) {
    input.addEventListener("focus", function(e) {
        let fieldId = e.target.name;
        if(instructions[fieldId]) {
            // Handle both desktop and mobile panels
            const guidanceCards = document.querySelectorAll("#guidancecard, #guidancecard-mobile");
            const helpPanels = document.querySelectorAll(".help-panel");
            const mobileHelpButton = document.querySelector(".floating-help-button");
            
            // Show panel on first field focus (desktop only)
            if (!helpPanelShown && window.innerWidth >= 992) {
                helpPanels.forEach(panel => panel.classList.add("show"));
                helpPanelShown = true;
            }
            
            // Add active class to desktop panels
            helpPanels.forEach(panel => panel.classList.add("active"));
            
            // Update content in all guidance cards
            guidanceCards.forEach(card => {
                // Fade out existing content
                card.style.opacity = "0";
                
                // After fade out, update content and fade in
                setTimeout(() => {
                    card.innerHTML = instructions[fieldId];
                    card.style.opacity = "1";
                }, 150);
            });
            
            // Pulse mobile help button when content updates
            if (mobileHelpButton && window.innerWidth < 992) {
                mobileHelpButton.classList.add("pulse");
                setTimeout(() => {
                    mobileHelpButton.classList.remove("pulse");
                }, 500);
            }
        }
    });
});

window.onload = function() {
    const guidanceCards = document.querySelectorAll("#guidancecard, #guidancecard-mobile");
    
    // Initialize help content opacity for smooth transitions
    guidanceCards.forEach(card => {
        if(card) {
            card.style.transition = "opacity 0.15s ease-in-out";
        }
    });
    
    // Sticky help panel implementation
    if (window.innerWidth >= 992) {
        const helpPanel = document.querySelector('.help-panel');
        const helpContainer = document.querySelector('.help-panel-container');
        
        if (helpPanel && helpContainer) {
            let containerTop = helpContainer.offsetTop;
            const panelWidth = helpPanel.offsetWidth;
            
            function checkSticky() {
                const scrollY = window.scrollY;
                const shouldStick = scrollY > (containerTop - 80);
                
                if (shouldStick && !helpPanel.classList.contains('stuck')) {
                    helpPanel.style.width = panelWidth + 'px';
                    helpPanel.classList.add('stuck');
                } else if (!shouldStick && helpPanel.classList.contains('stuck')) {
                    helpPanel.style.width = '';
                    helpPanel.classList.remove('stuck');
                }
            }
            
            // Check on scroll
            window.addEventListener('scroll', checkSticky);
            
            // Recalculate position on resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    containerTop = helpContainer.offsetTop;
                    checkSticky();
                } else {
                    helpPanel.classList.remove('stuck');
                    helpPanel.style.width = '';
                }
            });
            
            // Initial check
            checkSticky();
        }
    }
    
    // Don't auto-focus on load to prevent help panel from showing immediately
    // User will click/tab into fields when ready
};

let passwordInput = document.getElementById("input_password");
let togglePasswordButton = document.getElementById("togglePassword");
let togglePasswordIcon = togglePasswordButton ? togglePasswordButton.querySelector(".toggle-password") : null;

// Function to toggle password visibility
function togglePasswordVisibility() {
    if (!passwordInput || !togglePasswordIcon) return;
    
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        togglePasswordIcon.classList.remove("bi-eye-fill");
        togglePasswordIcon.classList.add("bi-eye-slash-fill");
    } else {
        passwordInput.type = "password";
        togglePasswordIcon.classList.remove("bi-eye-slash-fill");
        togglePasswordIcon.classList.add("bi-eye-fill");
    }
}

// Add event listener to the toggle password button
if (togglePasswordButton) {
    togglePasswordButton.addEventListener("click", function(e) {
        e.preventDefault();
        togglePasswordVisibility();
    });
}

// Function to generate a random password
function generatePassword(length) {
    const lowerCaseLetters = "abcdefghijklmnopqrstuvwxyz";
    const upperCaseLetters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numbers = "0123456789";
    const specialCharacters = "!#_";
    let password = "";
    
    // We generate one character from each set first to guarantee all conditions are met
    password += lowerCaseLetters.charAt(Math.floor(Math.random() * lowerCaseLetters.length));
    password += upperCaseLetters.charAt(Math.floor(Math.random() * upperCaseLetters.length));
    password += numbers.charAt(Math.floor(Math.random() * numbers.length));
    password += specialCharacters.charAt(Math.floor(Math.random() * specialCharacters.length));
    
    const allCharacters = lowerCaseLetters + upperCaseLetters + numbers + specialCharacters;
    
    let previousChar = password.charAt(password.length - 1);
    
    // Now we fill up to the desired length with characters from all sets
    while (password.length < length) {
        let nextChar = allCharacters.charAt(Math.floor(Math.random() * allCharacters.length));
        
        // Check for repeating characters and sequential numbers
        if (nextChar !== previousChar && 
            !(previousChar.match(/[0-9]/) && nextChar.match(/[0-9]/) && Math.abs(Number(previousChar) - Number(nextChar)) === 1)) {
            
            password += nextChar;
            previousChar = nextChar;
        }
    }
    
    // Finally, we shuffle the password to ensure the first 4 characters aren't predictable
    password = password.split('').sort(() => 0.5 - Math.random()).join('');
    
    return password;
}


document.addEventListener("DOMContentLoaded", function() {
    const passwordField = document.getElementById("input_password");
    const togglePasswordButton = document.getElementById("togglePassword");
    const generatePasswordButton = document.getElementById("generatePassword");

    // Only add the event listener if the generatePasswordButton exists
    if (generatePasswordButton) {
        generatePasswordButton.addEventListener("click", function(e) {
            e.preventDefault(); // Prevent link default behavior
            const generatedPassword = generatePassword(12); // Change 12 to the desired password length
            passwordField.value = generatedPassword;
            // Show the generated password by toggling visibility
            if (passwordField.type === "password") {
                togglePasswordVisibility();
            }
        });
    }
    
    // Mobile help panel controls
    const mobileHelpToggle = document.getElementById("mobileHelpToggle");
    const mobileHelpClose = document.getElementById("mobileHelpClose");
    const mobileHelpOverlay = document.getElementById("mobileHelpOverlay");
    
    if (mobileHelpToggle && mobileHelpOverlay) {
        // Open help panel
        mobileHelpToggle.addEventListener("click", function() {
            mobileHelpOverlay.classList.add("show");
            document.body.style.overflow = "hidden"; // Prevent body scroll
        });
        
        // Close help panel via X button
        mobileHelpClose.addEventListener("click", function() {
            mobileHelpOverlay.classList.remove("show");
            document.body.style.overflow = ""; // Restore body scroll
        });
        
        // Close help panel by clicking overlay
        mobileHelpOverlay.addEventListener("click", function(e) {
            if (e.target === mobileHelpOverlay) {
                mobileHelpOverlay.classList.remove("show");
                document.body.style.overflow = ""; // Restore body scroll
            }
        });
    }
});
