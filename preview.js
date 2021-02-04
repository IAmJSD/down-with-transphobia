"use strict";

// Load all the elements required for the preview.
var nameObj = document.getElementById("name");
var gender = document.getElementById("gender");
var additional_info = document.getElementById("additional_info");
var preview = document.getElementById("preview");
var preview_content = document.getElementById("preview_content");

// Generates the preview.
function generatePreview() {
    // Generate the preview string.
    var preview = window.letter.replace(/\$name/g, nameObj.value).replace(/\$gender/g, gender.value).replace(/\$personal_experiences/g, additional_info.value ? "\n\n" + additional_info.value : "");

    // Make the line breaks proper for what we need.
    preview = preview.replace(/(?:\r\n|\r|\n)/g, "\r\n");

    // Set the inner text.
    preview_content.innerText = preview;
}

// Do this on page load.
generatePreview();

// Handle inputs.
[nameObj, gender, additional_info, preview, preview_content].forEach(function(x) {
    // Handle setting oninput for all of them.
    x.oninput = function () {
        generatePreview();
    };
});

// Handle preview opening.
function showPreview() {
    preview.classList.add("is-active");
}

// Handle preview closing.
document.getElementById("preview_close").onclick = function() {
    preview.classList.remove("is-active");
};
