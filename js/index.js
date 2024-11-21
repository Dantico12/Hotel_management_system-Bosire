// Initialize EmailJS
(function() {
    emailjs.init("IUB8iEInWUX1D2eEM"); // Replace with your actual public key
})();

// Navigation
const hamburger = document.querySelector(".hamburger");
const navlist = document.querySelector(".nav-list");

if (hamburger) {
    hamburger.addEventListener("click", () => {
        navlist.classList.toggle("open");
    });
}

// Popup
const popup = document.querySelector(".popup");
const closePopup = document.querySelector(".popup-close");

if (popup) {
    closePopup.addEventListener("click", () => {
        popup.classList.add("hide-popup");
    });

    window.addEventListener("load", () => {
        setTimeout(() => {
            popup.classList.remove("hide-popup");
        }, 1000);
    });
}

// Contact Form Functionality
document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    const alertMessage = document.getElementById('alert-message');

    // Only proceed if contact form exists on the page
    if (contactForm) {
        // Show alert message function
        function showAlert(message, isSuccess) {
            alertMessage.textContent = message;
            alertMessage.style.display = 'block';
            alertMessage.style.backgroundColor = isSuccess ? '#4CAF50' : '#f44336';
            alertMessage.style.color = 'white';
            alertMessage.style.padding = '10px';
            alertMessage.style.marginBottom = '10px';
            alertMessage.style.borderRadius = '4px';

            // Hide the alert after 5 seconds
            setTimeout(() => {
                alertMessage.style.display = 'none';
            }, 5000);
        }

        // Add submit event listener to form
        contactForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Get form data
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;

            // Basic validation
            if (!email || !subject || !message) {
                showAlert('Please fill in all fields', false);
                return;
            }

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('Please enter a valid email address', false);
                return;
            }

            // Show loading state
            const submitButton = contactForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.textContent = 'Sending...';
            submitButton.disabled = true;

            // Prepare template parameters
            const templateParams = {
                email: email,
                subject: subject,
                message: message
            };

            // Send email using EmailJS
            emailjs.send('service_84qxocg', 'template_srprsgo', templateParams)
                .then(function(response) {
                    console.log('SUCCESS!', response.status, response.text);
                    showAlert('Message sent successfully!', true);
                    contactForm.reset(); // Reset form after successful submission
                })
                .catch(function(error) {
                    console.log('FAILED...', error);
                    showAlert('Failed to send message. Please try again.', false);
                })
                .finally(function() {
                    // Restore button state
                    submitButton.textContent = originalButtonText;
                    submitButton.disabled = false;
                });
        });
    }
});