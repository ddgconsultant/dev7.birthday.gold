document.getElementById("inputconfirmPassword").onkeyup = function() {
    // Get the values from the password and confirmation password fields
    var password = document.getElementById("newPassword").value;
    var confirmPassword = document.getElementById("inputconfirmPassword").value;

    // Check if the passwords match
    if (password === confirmPassword) {
        // If the passwords match, set the background color of the confirmation password field to green
        document.getElementById("inputconfirmPassword").style.backgroundColor = "lightgreen";
    } else {
        // If the passwords don not match, set the background color of the confirmation password field to red
        document.getElementById("inputconfirmPassword").style.backgroundColor = "salmon";
    }
}
