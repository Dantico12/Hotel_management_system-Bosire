// Enhanced JavaScript with cart functionality and FIXED popup

// Initialize EmailJS
(function() {
  emailjs.init("IUB8iEInWUX1D2eEM");
})();

// Navigation
const hamburger = document.querySelector(".hamburger");
const navlist = document.querySelector(".nav-list");
if (hamburger) {
  hamburger.addEventListener("click", () => {
    navlist.classList.toggle("open");
  });
}

// FIXED POPUP FUNCTIONALITY
const popup = document.querySelector(".popup");
const closePopup = document.querySelector(".popup-close");

if (popup && closePopup) {
  // Close popup when clicking the X button
  closePopup.addEventListener("click", () => {
    popup.classList.add("hide-popup");
  });

  // Close popup when clicking outside the content
  popup.addEventListener("click", (e) => {
    if (e.target === popup) {
      popup.classList.add("hide-popup");
    }
  });

  // Auto-show popup after page loads (1 second delay)
  window.addEventListener("load", () => {
    setTimeout(() => {
      popup.classList.remove("hide-popup");
    }, 1000);
  });

  // Close popup with Escape key
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      popup.classList.add("hide-popup");
    }
  });
}

// Manual show popup function (for testing)
function showPopup() {
  if (popup) {
    popup.classList.remove("hide-popup");
  }
}

// Manual hide popup function
function hidePopup() {
  if (popup) {
    popup.classList.add("hide-popup");
  }
}

// Cart functionality
let cartCount = 0;

// Load cart count on page load
document.addEventListener('DOMContentLoaded', function() {
  loadCartCount();
});

function loadCartCount() {
  fetch('get_cart_count.php')
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        updateCartCount(data.count);
      }
    })
    .catch(error => {
      console.log('Error loading cart count:', error);
    });
}

function updateCartCount(count) {
  cartCount = count;
  const cartCountElement = document.getElementById('cart-count');
  if (cartCountElement) {
    if (count > 0) {
      cartCountElement.textContent = count;
      cartCountElement.style.display = 'flex';
    } else {
      cartCountElement.style.display = 'none';
    }
  }
}

function showNotification(message, isSuccess = true) {
  const notification = document.getElementById('cart-notification');
  const notificationText = document.getElementById('notification-text');
  
  if (notification && notificationText) {
    notificationText.textContent = message;
    notification.className = 'cart-notification show';
    
    if (!isSuccess) {
      notification.classList.add('error');
    } else {
      notification.classList.remove('error');
    }
    
    setTimeout(() => {
      notification.classList.remove('show');
    }, 3000);
  }
}

function addToCart(productId, productName, productPrice, buttonElement) {
  // Get quantity from the nearest quantity input
  const productItem = buttonElement.closest('.product-item');
  const quantityInput = productItem ? productItem.querySelector('.quantity-input') : null;
  const quantity = quantityInput ? parseInt(quantityInput.value) || 1 : 1;
  
  // Disable button and show loading
  const originalText = buttonElement.textContent;
  buttonElement.disabled = true;
  buttonElement.innerHTML = '<span class="loading"></span> Adding...';
  
  // Prepare form data
  const formData = new FormData();
  formData.append('product_id', productId);
  formData.append('quantity', quantity);
  formData.append('action', 'add_to_cart');
  
  fetch('cart_handler.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showNotification(`${productName} added to cart!`);
      updateCartCount(data.cart_count);
      if (quantityInput) quantityInput.value = 1; // Reset quantity
    } else {
      if (data.redirect) {
        // User needs to login
        window.location.href = data.redirect;
      } else {
        showNotification(data.message || 'Error adding to cart', false);
      }
    }
  })
  .catch(error => {
    console.error('Error:', error);
    showNotification('Error adding to cart', false);
  })
  .finally(() => {
    // Restore button
    buttonElement.disabled = false;
    buttonElement.textContent = originalText;
  });
}

// Contact Form Functionality
document.addEventListener('DOMContentLoaded', function() {
  const contactForm = document.getElementById('contact-form');
  const alertMessage = document.getElementById('alert-message');
  
  if (contactForm && alertMessage) {
    function showAlert(message, isSuccess) {
      alertMessage.textContent = message;
      alertMessage.style.display = 'block';
      alertMessage.style.backgroundColor = isSuccess ? '#4CAF50' : '#f44336';
      alertMessage.style.color = 'white';
      alertMessage.style.padding = '10px';
      alertMessage.style.marginBottom = '10px';
      alertMessage.style.borderRadius = '4px';
      
      setTimeout(() => {
        alertMessage.style.display = 'none';
      }, 5000);
    }
    
    contactForm.addEventListener('submit', function(event) {
      event.preventDefault();
      
      const email = document.getElementById('email').value;
      const subject = document.getElementById('subject').value;
      const message = document.getElementById('message').value;
      
      if (!email || !subject || !message) {
        showAlert('Please fill in all fields', false);
        return;
      }
      
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(email)) {
        showAlert('Please enter a valid email address', false);
        return;
      }
      
      const submitButton = contactForm.querySelector('button[type="submit"]');
      const originalButtonText = submitButton.textContent;
      submitButton.textContent = 'Sending...';
      submitButton.disabled = true;
      
      const templateParams = {
        email: email,
        subject: subject,
        message: message
      };
      
      emailjs.send('service_84qxocg', 'template_srprsgo', templateParams)
        .then(function(response) {
          console.log('SUCCESS!', response.status, response.text);
          showAlert('Message sent successfully!', true);
          contactForm.reset();
        })
        .catch(function(error) {
          console.log('FAILED...', error);
          showAlert('Failed to send message. Please try again.', false);
        })
        .finally(function() {
          submitButton.textContent = originalButtonText;
          submitButton.disabled = false;
        });
    });
  }
});

// Handle popup subscription form
function handleSubscribe(event) {
  event.preventDefault();
  const emailInput = document.querySelector('.popup-form');
  const email = emailInput ? emailInput.value : '';
  
  if (email) {
    // You can integrate this with your email system
    alert('Thank you for subscribing with email: ' + email);
    hidePopup();
    if (emailInput) emailInput.value = ''; // Clear the input
  }
}